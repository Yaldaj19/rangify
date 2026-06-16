<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Smart selection: given an image and one or more click points,
 * return a pixel-perfect binary mask of the wall/object under that point.
 *
 * Provider chain (auto-fallback in order):
 *   1. opencv     — Local Python FastAPI (GrabCut/Watershed/SLIC), FREE, fastest
 *   2. replicate  — Meta SAM 2 (paid, best quality on complex scenes, ~1-2s)
 *   3. huggingface — facebook/sam-vit-base (free tier, slower)
 *   4. none → returns 503 with a clear hint
 *
 * Response shape:
 *   { mask: "data:image/png;base64,...",  // alpha=mask
 *     width, height,
 *     provider: "opencv"|"replicate"|"huggingface",
 *     method?: "grabcut"|"watershed"|...,
 *     elapsed_ms }
 */
class SmartSelectController extends Controller
{
    /**
     * Precompute a label-map for the entire image so the client can do
     * instant hover preview without per-mouse-move API calls.
     * OpenCV-only (Felzenszwalb + LAB merge).
     */
    public function precompute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'string', 'max:20000000'],
        ]);

        if (empty(config('services.opencv.url')) || !$this->opencvAlive()) {
            return response()->json([
                'error' => 'سرویس OpenCV در دسترس نیست',
                'detail' => 'برای پیش‌نمایش هاور باید python-vision\\start.bat بالا باشه.',
            ], 503);
        }

        $imageDataUrl = $this->normalizeImageDataUrl($validated['image']);
        $base = rtrim((string) config('services.opencv.url'), '/');
        $timeout = (int) (config('services.opencv.timeout') ?: 30);

        try {
            $resp = Http::timeout($timeout)->connectTimeout(5)->acceptJson()
                ->post("{$base}/precompute", ['image' => $imageDataUrl]);
        } catch (Throwable $e) {
            Log::warning('precompute call failed', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'تماس با سرویس پایتون ناموفق', 'detail' => $e->getMessage()], 502);
        }

        if (!$resp->successful()) {
            return response()->json([
                'error' => 'پاسخ نادرست از سرویس',
                'detail' => substr($resp->body(), 0, 300),
            ], 502);
        }

        return response()->json($resp->json());
    }

    public function point(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'string', 'max:20000000'],
            'points' => ['required', 'array', 'min:1', 'max:16'],
            'points.*' => ['array', 'size:2'],
            'labels' => ['nullable', 'array'],
            'provider' => ['nullable', 'in:auto,opencv,replicate,huggingface'],
            'method' => ['nullable', 'in:grabcut,watershed,flood-smart,slic-superpixels'],
        ]);

        $imageDataUrl = $this->normalizeImageDataUrl($validated['image']);
        $points = $validated['points'];
        $labels = $validated['labels'] ?? array_fill(0, count($points), 1);
        $requested = $validated['provider'] ?? 'auto';
        $method = $validated['method'] ?? config('services.opencv.default_method', 'grabcut');

        $providers = $this->resolveProviderChain($requested);
        if (empty($providers)) {
            return response()->json([
                'error' => 'هیچ provider هوشمندی در دسترس نیست',
                'detail' => 'سرویس OpenCV (python-vision\\start.bat) رو بالا بیار، یا یکی از REPLICATE_API_TOKEN / HUGGINGFACE_API_KEY رو در .env ست کن.',
            ], 503);
        }

        $errors = [];
        $started = microtime(true);

        foreach ($providers as $provider) {
            try {
                $result = match ($provider) {
                    'opencv' => $this->callOpenCV($imageDataUrl, $points, $labels, $method),
                    'replicate' => $this->callReplicate($imageDataUrl, $points, $labels),
                    'huggingface' => $this->callHuggingFace($imageDataUrl, $points, $labels),
                };

                if ($result !== null) {
                    $result['provider'] = $provider;
                    $result['elapsed_ms'] = (int) ((microtime(true) - $started) * 1000);
                    return response()->json($result);
                }
            } catch (Throwable $e) {
                Log::warning("smart-select {$provider} failed", ['msg' => $e->getMessage()]);
                $errors[$provider] = $e->getMessage();
            }
        }

        return response()->json([
            'error' => 'همه provider ها fail شدن',
            'detail' => $errors,
        ], 502);
    }

    private function resolveProviderChain(string $requested): array
    {
        $available = [];
        if (!empty(config('services.opencv.url')) && $this->opencvAlive()) {
            $available[] = 'opencv';
        }
        if (!empty(config('services.replicate.key'))) $available[] = 'replicate';
        if (!empty(config('services.huggingface.key'))) $available[] = 'huggingface';

        if ($requested === 'auto') return $available;

        if (in_array($requested, $available, true)) {
            // Put requested first, others as fallback
            return array_values(array_unique(array_merge([$requested], $available)));
        }
        return $available;
    }

    private function normalizeImageDataUrl(string $image): string
    {
        if (str_starts_with($image, 'data:image/')) return $image;
        return 'data:image/jpeg;base64,' . $image;
    }

    private function httpClient(?string $proxyKey): PendingRequest
    {
        $http = Http::timeout(120)->connectTimeout(20);
        $proxy = $proxyKey ? config($proxyKey) : null;
        if (!empty($proxy)) {
            $http = $http->withOptions(['proxy' => $proxy]);
        }
        return $http;
    }

    /** Cheap liveness check so we skip OpenCV instantly when the service is down. */
    private function opencvAlive(): bool
    {
        try {
            $base = rtrim((string) config('services.opencv.url'), '/');
            $r = Http::timeout(2)->connectTimeout(1)->get("{$base}/health");
            return $r->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Local OpenCV/scikit-image Python microservice (python-vision\main.py).
     * Free, fastest, runs offline. Best for plain walls and simple scenes.
     */
    private function callOpenCV(string $imageDataUrl, array $points, array $labels, string $method = 'grabcut'): ?array
    {
        $base = rtrim((string) config('services.opencv.url'), '/');
        $timeout = (int) (config('services.opencv.timeout') ?: 30);

        $allowed = ['grabcut', 'watershed', 'flood-smart', 'slic-superpixels'];
        if (!in_array($method, $allowed, true)) {
            $method = 'grabcut';
        }

        $http = Http::timeout($timeout)->connectTimeout(5)->acceptJson();

        $body = [
            'image' => $imageDataUrl,
            'points' => $points,
            'labels' => $labels,
        ];

        $resp = $http->post("{$base}/{$method}", $body);

        if (!$resp->successful()) {
            throw new \RuntimeException('OpenCV HTTP ' . $resp->status() . ' — ' . substr($resp->body(), 0, 300));
        }

        $data = $resp->json();
        if (empty($data['mask'])) {
            throw new \RuntimeException('OpenCV returned no mask');
        }

        return [
            'mask' => $data['mask'],
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'method' => $data['method'] ?? $method,
        ];
    }

    /**
     * Replicate SAM 2: prediction → poll until done → fetch mask URL → return as base64.
     * Uses `wait=true` Prefer header so we get sync response (faster).
     */
    private function callReplicate(string $imageDataUrl, array $points, array $labels): ?array
    {
        $token = config('services.replicate.key');
        $model = config('services.replicate.sam_model', 'meta/sam-2');

        // Replicate expects normalized points as JSON-encoded strings for many SAM 2 forks.
        // Convert normalized [0..1] to JSON-string format expected by the model.
        $pointsStr = json_encode($points);
        $labelsStr = json_encode($labels);

        $http = $this->httpClient('services.replicate.proxy')
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Prefer' => 'wait=60',
            ]);

        // Use Replicate's official model endpoint format
        $url = "https://api.replicate.com/v1/models/{$model}/predictions";

        $body = [
            'input' => [
                'image' => $imageDataUrl,
                'input_points' => $pointsStr,
                'input_labels' => $labelsStr,
                'use_m2m' => true,
                'multimask_output' => false,
            ],
        ];

        $resp = $http->post($url, $body);

        if (!$resp->successful()) {
            throw new \RuntimeException('Replicate HTTP ' . $resp->status() . ' — ' . substr($resp->body(), 0, 300));
        }

        $data = $resp->json();
        $status = $data['status'] ?? '';

        // If still processing (Prefer:wait timed out), poll
        if ($status !== 'succeeded' && !empty($data['urls']['get'])) {
            $data = $this->pollReplicate($data['urls']['get'], $token);
        }

        if (($data['status'] ?? '') !== 'succeeded') {
            throw new \RuntimeException('Replicate prediction status: ' . ($data['status'] ?? 'unknown'));
        }

        // Output is typically a URL (or array of URLs) to the mask PNG
        $output = $data['output'] ?? null;
        if (is_array($output)) $output = $output[0] ?? null;
        if (!is_string($output)) {
            throw new \RuntimeException('Replicate output format unexpected');
        }

        $maskBin = $this->fetchUrl($output, 'services.replicate.proxy');
        $info = @getimagesizefromstring($maskBin);
        if (!$info) throw new \RuntimeException('mask not decodable');

        return [
            'mask' => 'data:image/png;base64,' . base64_encode($maskBin),
            'width' => $info[0],
            'height' => $info[1],
        ];
    }

    private function pollReplicate(string $getUrl, string $token): array
    {
        $http = $this->httpClient('services.replicate.proxy')
            ->withHeaders(['Authorization' => 'Bearer ' . $token]);

        for ($i = 0; $i < 30; $i++) {
            usleep(800_000); // 0.8s
            $r = $http->get($getUrl);
            if (!$r->successful()) continue;
            $d = $r->json();
            $s = $d['status'] ?? '';
            if (in_array($s, ['succeeded', 'failed', 'canceled'], true)) return $d;
        }
        throw new \RuntimeException('Replicate poll timeout (24s)');
    }

    private function fetchUrl(string $url, ?string $proxyKey): string
    {
        $r = $this->httpClient($proxyKey)->get($url);
        if (!$r->successful()) {
            throw new \RuntimeException("fetch mask URL HTTP {$r->status()}");
        }
        return $r->body();
    }

    /**
     * HuggingFace Inference API for SAM.
     * Endpoint: https://api-inference.huggingface.co/models/{model}
     * Input: raw image bytes or base64 with parameters.
     */
    private function callHuggingFace(string $imageDataUrl, array $points, array $labels): ?array
    {
        $token = config('services.huggingface.key');
        $model = config('services.huggingface.sam_model', 'facebook/sam-vit-base');

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $imageDataUrl);
        $imgBytes = base64_decode($base64);
        $info = @getimagesizefromstring($imgBytes);
        if (!$info) throw new \RuntimeException('invalid image for HF');
        [$w, $h] = $info;

        // HF SAM expects pixel coordinates, not normalized [0..1]
        $pixelPoints = array_map(fn($p) => [(int) round($p[0] * $w), (int) round($p[1] * $h)], $points);

        $payload = [
            'inputs' => $imageDataUrl,
            'parameters' => [
                'input_points' => [$pixelPoints],
                'input_labels' => [$labels],
            ],
        ];

        $http = $this->httpClient('services.huggingface.proxy')
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'x-wait-for-model' => 'true',
            ]);

        $resp = $http->post("https://api-inference.huggingface.co/models/{$model}", $payload);

        if (!$resp->successful()) {
            throw new \RuntimeException('HF HTTP ' . $resp->status() . ' — ' . substr($resp->body(), 0, 300));
        }

        // HF returns array of {mask: base64 PNG, score}
        $data = $resp->json();
        $bestMask = null;
        $bestScore = -1;
        foreach ((array) $data as $item) {
            $score = (float) ($item['score'] ?? 0);
            $mask = $item['mask'] ?? null;
            if ($mask && $score > $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
            }
        }
        if (!$bestMask) throw new \RuntimeException('HF returned no mask');

        $maskBin = base64_decode($bestMask);
        $mInfo = @getimagesizefromstring($maskBin);

        return [
            'mask' => 'data:image/png;base64,' . $bestMask,
            'width' => $mInfo ? $mInfo[0] : $w,
            'height' => $mInfo ? $mInfo[1] : $h,
            'score' => $bestScore,
        ];
    }
}

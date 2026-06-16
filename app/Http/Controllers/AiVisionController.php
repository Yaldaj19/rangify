<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiVisionController extends Controller
{
    public function segment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'string', 'max:20000000'],
        ]);

        $image = $validated['image'];
        if (str_starts_with($image, 'data:image/')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#', '', $image);
        } else {
            $base64 = $image;
        }

        $apiKey = config('services.openrouter.key');
        $baseUrl = rtrim((string) config('services.openrouter.base_url'), '/');
        $model = config('services.openrouter.default_model');
        $proxy = config('services.openrouter.proxy');

        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenRouter API key not configured'], 500);
        }

        $prompt = <<<'PROMPT'
You are an expert image analyst working for a wall paint simulator.
Output will be used to drive PIXEL-LEVEL flood fill on the image, so precision matters.

TASK: Identify ONLY the painted WALL surfaces and the CEILING.
HARD EXCLUSIONS — never include any of these as a region:
  - Floor, carpet, rug, tiles
  - Furniture (sofa, table, chair, bed, shelf)
  - Doors, windows, curtains, blinds
  - Art, mirrors, TVs, decorations, plants
  - Skirting boards / baseboards
  - Light switches, outlets, vents

Return ONLY valid JSON (no markdown, no commentary):

{
  "regions": [
    {
      "label": "back wall" | "left wall" | "right wall" | "front wall" | "ceiling",
      "polygon": [[x, y], [x, y], ...],
      "seeds": [[sx, sy], ...],
      "color_hex": "#RRGGBB"
    }
  ]
}

CRITICAL: ALL coordinates are NORMALIZED to range [0.0, 1.0].
- x: 0.0 = leftmost edge of image, 1.0 = rightmost edge
- y: 0.0 = topmost edge of image, 1.0 = bottommost edge

FIELD SPECS — follow EXACTLY:

- polygon: 8 to 16 points outlining the wall surface, in clockwise order. Trace the ACTUAL boundary, going around objects in front of it (furniture, doors, windows). The polygon should hug the wall tightly — bottom should follow exact wall/floor line, top should follow wall/ceiling line, sides should follow corners. Avoid covering floor, ceiling (if not the ceiling region), furniture, or windows. More points = more accurate.

- seeds: 6 to 10 distinct interior points (normalized [0..1]). Spread them widely across the wall — upper-left, upper-right, lower-left, lower-right, center, and a few mid-points. Each seed MUST be on a CLEAN portion of wall — far from picture frames, switches, outlets, furniture, baseboards, edges. Avoid shadowed areas. Flood fill from each seed must stay confined to the wall.

- color_hex: The DOMINANT painted color of this wall, sampled from a clean unshaded interior area. Be precise — this color will be used to validate seed points.

If a wall has very different colors in different areas (e.g. two-tone), return them as separate regions with their own labels (you may add a suffix like "back wall (upper)" if needed).

If you only see one wall, still return that one region.
PROMPT;

        try {
            $http = Http::timeout(120)
                ->connectTimeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'Rangify',
                ]);

            if (!empty($proxy)) {
                $http = $http->withOptions(['proxy' => $proxy]);
            }

            $response = $http->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $prompt],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/jpeg;base64,' . $base64,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::error('OpenRouter segment error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'error' => 'AI service error',
                    'status' => $response->status(),
                    'detail' => substr($response->body(), 0, 500),
                ], 502);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return response()->json(['error' => 'Empty AI response', 'raw' => $data], 502);
            }

            $content = trim($content);
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid AI JSON',
                    'raw' => substr($content, 0, 500),
                ], 502);
            }

            return response()->json($parsed);
        } catch (Throwable $e) {
            Log::error('AI segment failed', ['exception' => $e->getMessage()]);
            $msg = $e->getMessage();
            $hint = '';
            if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'timeout')) {
                $hint = 'اتصال به OpenRouter timeout شد. اگه از VPN استفاده می‌کنی چک کن فعال باشه، یا OPENROUTER_PROXY رو در .env خالی کن.';
            } elseif (str_contains($msg, 'cURL error 7')) {
                $hint = 'پروکسی local در دسترس نیست. پورت SOCKS5 رو چک کن یا OPENROUTER_PROXY رو خالی کن.';
            }
            return response()->json([
                'error' => 'خطای اتصال به سرور AI',
                'detail' => $hint ?: $msg,
            ], 500);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'label',
        'mask_path',
        'polygon',
        'color_hex',
        'blend_mode',
        'opacity',
        'source',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'opacity' => 'float',
            'confidence' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

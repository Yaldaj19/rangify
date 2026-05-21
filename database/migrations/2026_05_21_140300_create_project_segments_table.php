<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * هر segment یک ناحیه تشخیص‌داده‌شده در عکس است (دیوار، سقف، کابینت و ...)
     * به همراه رنگ اعمال‌شده روی همان ناحیه.
     */
    public function up(): void
    {
        Schema::create('project_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('wall'); // wall, ceiling, cabinet, floor, window, door, other
            $table->string('label')->nullable();
            $table->string('mask_path')->nullable();
            $table->json('polygon')->nullable();
            $table->string('color_hex')->nullable();
            $table->string('blend_mode')->default('multiply');
            $table->float('opacity')->default(1);
            $table->string('source')->default('manual'); // ade20k, sam2, opencv, manual
            $table->float('confidence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_segments');
    }
};

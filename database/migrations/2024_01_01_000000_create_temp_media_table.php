<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_media', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 255)->nullable()->index();
            $table->string('original_name', 500);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size');
            $table->timestamp('expires_at')->index();
            $table->boolean('is_processed')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_media');
    }
};

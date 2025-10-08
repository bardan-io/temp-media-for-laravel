<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Contracts;

use BardanIO\TempMedia\DTOs\TempMediaUploadDTO;
use BardanIO\TempMedia\Models\TempMedia;
use Illuminate\Http\UploadedFile;

interface TempMediaServiceInterface
{
    /**
     * Upload a temporary media file
     */
    public function uploadTempMedia(
        UploadedFile $file,
        ?string $sessionId = null,
        ?int $ttlHours = null
    ): TempMediaUploadDTO;

    /**
     * Get active temporary media by ID
     */
    public function getTempMedia(string $id): ?TempMedia;

    /**
     * Validate array of temporary media IDs
     */
    public function validateTempMediaIds(array $ids): array;

    /**
     * Delete temporary media
     */
    public function deleteTempMedia(string $id): bool;

    /**
     * Mark temporary media as processed
     */
    public function markAsProcessed(array $ids): void;
}

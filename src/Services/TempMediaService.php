<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Services;

use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use BardanIO\TempMedia\DTOs\TempMediaUploadDTO;
use BardanIO\TempMedia\Events\TempMediaExpired;
use BardanIO\TempMedia\Events\TempMediaUploaded;
use BardanIO\TempMedia\Exceptions\InvalidFileException;
use BardanIO\TempMedia\Models\TempMedia;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class TempMediaService implements TempMediaServiceInterface
{
    /**
     * @throws InvalidFileException
     */
    public function uploadTempMedia(
        UploadedFile $file,
        ?string $sessionId = null,
        ?int $ttlHours = null
    ): TempMediaUploadDTO {
        $this->validateFile($file);

        return DB::transaction(function () use ($file, $sessionId, $ttlHours) {
            $tempMedia = $this->createTempMedia($file, $sessionId, $ttlHours);
            $mediaItem = $tempMedia
                ->addMediaFromRequest('file')
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection($tempMedia->getCollectionName());

            $dto = new TempMediaUploadDTO(
                id: $tempMedia->id,
                url: $mediaItem->getUrl(),
                originalName: $tempMedia->original_name,
                mimeType: $tempMedia->mime_type,
                size: $tempMedia->size,
                expiresAt: $tempMedia->expires_at,
                sessionId: $tempMedia->session_id,
            );

            if (config('temp-media.dispatch_events', true)) {
                TempMediaUploaded::dispatch($tempMedia, $dto);
            }

            return $dto;
        });
    }

    public function getTempMedia(string $id): ?TempMedia
    {
        return TempMedia::active()->find($id);
    }

    public function validateTempMediaIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $tempMediaItems = TempMedia::active()
            ->whereIn('id', $ids)
            ->get();

        $foundIds = $tempMediaItems->pluck('id')->toArray();
        $missingIds = array_diff($ids, $foundIds);

        if (! empty($missingIds)) {
            throw new InvalidFileException(
                'Invalid or expired temp media IDs: '.implode(', ', $missingIds)
            );
        }

        return $tempMediaItems->toArray();
    }

    public function deleteTempMedia(string $id): bool
    {
        $tempMedia = TempMedia::find($id);

        if (! $tempMedia) {
            return false;
        }

        return (bool) $tempMedia->delete();
    }

    public function markAsProcessed(array $ids): void
    {
        TempMedia::whereIn('id', $ids)
            ->update(['is_processed' => true]);
    }

    public function cleanupExpired(): int
    {
        $expiredMedia = TempMedia::expired()->get();
        $count = $expiredMedia->count();

        foreach ($expiredMedia as $media) {
            if (config('temp-media.dispatch_events', true)) {
                TempMediaExpired::dispatch($media);
            }

            $media->clearMediaCollection($media->getCollectionName());
            $media->forceDelete();
        }

        return $count;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new InvalidFileException('Invalid file upload');
        }

        $maxSize = config('temp-media.max_file_size', 10 * 1024 * 1024);
        if ($file->getSize() > $maxSize) {
            throw new InvalidFileException('File size exceeds maximum allowed size');
        }

        $allowedMimeTypes = config('temp-media.allowed_mime_types', []);
        if (! empty($allowedMimeTypes) && ! in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            throw new InvalidFileException('File type not allowed');
        }
    }

    private function createTempMedia(
        UploadedFile $file,
        ?string $sessionId,
        ?int $ttlHours
    ): TempMedia {
        $ttl = $ttlHours ?? config('temp-media.default_ttl_hours', 24);

        return TempMedia::create([
            'session_id' => $sessionId,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'expires_at' => Carbon::now()->addHours($ttl),
            'is_processed' => false,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Services;

use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use BardanIO\TempMedia\DTOs\MediaTransferDTO;
use BardanIO\TempMedia\DTOs\TempMediaTransferDTO;
use BardanIO\TempMedia\Events\MediaTransferred;
use BardanIO\TempMedia\Models\TempMedia;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;

final readonly class MediaTransferService implements MediaTransferServiceInterface
{
    public function __construct(
        private TempMediaServiceInterface $tempMediaService
    ) {}

    public function transferTempMediaToModel(
        HasMedia $model,
        TempMediaTransferDTO $tempMediaTransferDTO,
        string $collectionName = 'default',
        array $customProperty = []
    ): MediaTransferDTO {
        if ($tempMediaTransferDTO->isEmpty()) {
            return $this->emptyTransferResult($model, $collectionName);
        }

        $tempMediaIds = $tempMediaTransferDTO->getTempMediaIds();
        $this->tempMediaService->validateTempMediaIds($tempMediaIds);

        return DB::transaction(function () use ($model, $tempMediaTransferDTO, $collectionName,$customProperty) {
            [$transferredMedia, $failedTransfers] = $this->processTransfers($model, $tempMediaTransferDTO, $collectionName, $customProperty);

            if (! empty($transferredMedia)) {
                $this->markMediaAsProcessed($transferredMedia);
            }

            $dto = $this->buildTransferDTO($model, $collectionName, $transferredMedia, $failedTransfers);

            if (config('temp-media.dispatch_events', true)) {
                MediaTransferred::dispatch($model, $dto);
            }

            return $dto;
        });
    }

    public function cleanupProcessedTempMedia(): int
    {
        $processedMedia = TempMedia::processed()->get();
        $count = $processedMedia->count();

        foreach ($processedMedia as $media) {
            $media->clearMediaCollection($media->getCollectionName());
            $media->forceDelete();
        }

        return $count;
    }

    public function validateOwnership(
        array $tempMediaIds,
        ?string $sessionId = null,
        ?string $userId = null
    ): bool {
        if (empty($tempMediaIds)) {
            return true;
        }

        $query = TempMedia::whereIn('id', $tempMediaIds);

        if ($sessionId !== null) {
            $query->where('session_id', $sessionId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $foundCount = $query->count();

        return $foundCount === count($tempMediaIds);
    }

    public function getTransferStats(): array
    {
        return [
            'total_temp_media' => TempMedia::count(),
            'active_temp_media' => TempMedia::active()->count(),
            'processed_temp_media' => TempMedia::processed()->count(),
            'expired_temp_media' => TempMedia::expired()->count(),
        ];
    }

    protected function emptyTransferResult(HasMedia $model, string $collectionName): MediaTransferDTO
    {
        return MediaTransferDTO::successful(
            [],
            get_class($model),
            (string) $model->getKey(),
            $collectionName
        );
    }

    protected function processTransfers(
        HasMedia $model,
        TempMediaTransferDTO $tempMediaTransferDTO,
        string $collectionName,
        array $customProperty = []
    ): array {
        $transferredMedia = [];
        $failedTransfers = [];

        foreach ($tempMediaTransferDTO->getItems() as $item) {
            $result = $this->attemptMediaTransfer($model, $item, $collectionName, $customProperty);

            if (isset($result['media'])) {
                $transferredMedia[] = $result['media'];
            } else {
                $failedTransfers[] = $result['error'];
            }
        }

        return [$transferredMedia, $failedTransfers];
    }

    protected function attemptMediaTransfer(
        HasMedia $model,
        $tempMediaItem,
        string $collectionName,
        array $customProperty = []
    ): array {
        try {
            $result = $this->transferSingleMedia(
                $model,
                $tempMediaItem->tempMediaId,
                $collectionName,
                $tempMediaItem->orderColumn,
                $customProperty
            );

            if ($result) {
                return ['media' => $result];
            }

            return ['error' => [
                'temp_media_id' => $tempMediaItem->tempMediaId,
                'error' => 'Media item not found or already processed',
            ]];
        } catch (\Throwable $e) {
            return ['error' => [
                'temp_media_id' => $tempMediaItem->tempMediaId,
                'error' => $e->getMessage(),
            ]];
        }
    }

    protected function markMediaAsProcessed(array $transferredMedia): void
    {
        $successfulIds = array_column($transferredMedia, 'temp_media_id');
        $this->tempMediaService->markAsProcessed($successfulIds);
    }

    protected function buildTransferDTO(
        HasMedia $model,
        string $collectionName,
        array $transferredMedia,
        array $failedTransfers
    ): MediaTransferDTO {
        $modelClass = get_class($model);
        $modelId = (string) $model->getKey();

        return empty($failedTransfers)
            ? MediaTransferDTO::successful($transferredMedia, $modelClass, $modelId, $collectionName)
            : MediaTransferDTO::withFailures($transferredMedia, $failedTransfers, $modelClass, $modelId, $collectionName);
    }

    private function transferSingleMedia(
        HasMedia $model,
        string $tempMediaId,
        string $collectionName,
        ?int $order = null,
        array $customProperty = []
    ): ?array {

        $tempMedia = TempMedia::active()->find($tempMediaId);

        if (! $tempMedia) {
            return null;
        }

        $mediaItem = $tempMedia->getFirstMedia($tempMedia->getCollectionName());

        if (! $mediaItem) {
            return null;
        }

        // Copy media to target model
        $mediaAdder = $model
            ->addMediaFromDisk($mediaItem->getPathRelativeToRoot(), $mediaItem->disk)
            ->usingName($tempMedia->original_name)
            ->usingFileName($mediaItem->file_name);

        if (!empty($customProperty)) {
            $mediaAdder->withCustomProperties($customProperty);
        }

        $newMediaItem = $mediaAdder->toMediaCollection($collectionName);

        if ($order !== null) {
            $newMediaItem->order_column = $order;
            $newMediaItem->save();
        }

        return [
            'id' => $newMediaItem->id,
            'temp_media_id' => $tempMediaId,
            'url' => $newMediaItem->getUrl(),
            'collection' => $collectionName,
            'original_name' => $tempMedia->original_name,
            'size' => $tempMedia->size,
            'mime_type' => $tempMedia->mime_type,
            'order' => $order,
        ];
    }
}

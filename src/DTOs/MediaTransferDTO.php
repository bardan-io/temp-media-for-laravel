<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\DTOs;

final readonly class MediaTransferDTO
{
    public function __construct(
        public array $transferredMedia,
        public int $transferredCount,
        public array $failedTransfers = [],
        public int $failedCount = 0,
        public string $targetModelType = '',
        public string $targetModelId = '',
        public string $collectionName = 'default'
    ) {}

    public static function successful(
        array $transferredMedia,
        string $targetModelType,
        string $targetModelId,
        string $collectionName = 'default'
    ): self {
        return new self(
            transferredMedia: $transferredMedia,
            transferredCount: count($transferredMedia),
            failedTransfers: [],
            failedCount: 0,
            targetModelType: $targetModelType,
            targetModelId: $targetModelId,
            collectionName: $collectionName
        );
    }

    public static function withFailures(
        array $transferredMedia,
        array $failedTransfers,
        string $targetModelType,
        string $targetModelId,
        string $collectionName = 'default'
    ): self {
        return new self(
            transferredMedia: $transferredMedia,
            transferredCount: count($transferredMedia),
            failedTransfers: $failedTransfers,
            failedCount: count($failedTransfers),
            targetModelType: $targetModelType,
            targetModelId: $targetModelId,
            collectionName: $collectionName
        );
    }

    public function toArray(): array
    {
        return [
            'transferred_media' => $this->transferredMedia,
            'transferred_count' => $this->transferredCount,
            'failed_transfers' => $this->failedTransfers,
            'failed_count' => $this->failedCount,
            'target_model_type' => $this->targetModelType,
            'target_model_id' => $this->targetModelId,
            'collection_name' => $this->collectionName,
        ];
    }

    public function isFullySuccessful(): bool
    {
        return $this->failedCount === 0;
    }

    public function hasFailures(): bool
    {
        return $this->failedCount > 0;
    }

    public function getTransferredIds(): array
    {
        return array_column($this->transferredMedia, 'id');
    }

    public function getFailedIds(): array
    {
        return array_column($this->failedTransfers, 'temp_media_id');
    }
}

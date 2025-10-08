<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Traits;

use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;
use BardanIO\TempMedia\DTOs\MediaTransferDTO;
use BardanIO\TempMedia\DTOs\TempMediaTransferDTO;
use Spatie\MediaLibrary\HasMedia;

trait HandlesTempMedia
{
    /**
     * Transfer temporary media to this model
     */
    public function transferTempMedia(
        TempMediaTransferDTO $tempMediaTransferDTO,
        string $collectionName = 'default',
        array $customProperty = []
    ): MediaTransferDTO {
        $transferService = app(MediaTransferServiceInterface::class);

        if (! ($this instanceof HasMedia)) {
            throw new \InvalidArgumentException('Model must implement HasMedia interface');
        }

        return $transferService->transferTempMediaToModel(
            $this,
            $tempMediaTransferDTO,
            $collectionName,
            $customProperty
        );
    }

    /**
     * Transfer temporary media to the default product images collection
     */
    public function transferTempMediaAsProductImages(TempMediaTransferDTO $tempMediaTransferDTO): MediaTransferDTO
    {
        return $this->transferTempMedia($tempMediaTransferDTO, 'product_images');
    }

    /**
     * Get media URLs from a transfer result
     */
    public function getMediaUrlsFromTransfer(MediaTransferDTO $transferDto): array
    {
        return array_map(
            fn (array $media) => [
                'id' => $media['id'],
                'url' => $media['url'],
                'original_name' => $media['original_name'],
                'order' => $media['order'] ?? null,
            ],
            $transferDto->transferredMedia
        );
    }
}

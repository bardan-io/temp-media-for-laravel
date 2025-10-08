<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Events;

use BardanIO\TempMedia\DTOs\MediaTransferDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\HasMedia;

final class MediaTransferred
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly HasMedia $targetModel,
        public readonly MediaTransferDTO $transferDto
    ) {}
}

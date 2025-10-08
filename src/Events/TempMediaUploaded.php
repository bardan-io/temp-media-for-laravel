<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Events;

use BardanIO\TempMedia\DTOs\TempMediaUploadDTO;
use BardanIO\TempMedia\Models\TempMedia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TempMediaUploaded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly TempMedia $tempMedia,
        public readonly TempMediaUploadDTO $uploadDto
    ) {}
}

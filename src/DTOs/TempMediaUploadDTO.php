<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\DTOs;

use Carbon\Carbon;
use Illuminate\Support\Arr;

final readonly class TempMediaUploadDTO
{
    public function __construct(
        public int $id,
        public string $url,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public Carbon $expiresAt,
        public ?string $sessionId = null,
        public bool $isTemporary = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            url: $data['url'],
            originalName: $data['original_name'],
            mimeType: $data['mime_type'],
            size: $data['size'],
            expiresAt: Carbon::parse($data['expires_at']),
            sessionId: $data['session_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'expires_at' => $this->expiresAt->toISOString(),
            'session_id' => $this->sessionId,
            'is_temporary' => $this->isTemporary,
        ];
    }

    public function toJsonResponse(): array
    {
        return [
            'success' => true,
            'data' => Arr::except($this->toArray(), ['session_id']),
            'message' => 'File uploaded successfully',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }
}

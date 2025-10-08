<?php

namespace BardanIO\TempMedia\DTOs;

final readonly class TempMediaItemDTO
{
    public function __construct(
        public string $tempMediaId,
        public ?int $orderColumn = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tempMediaId: $data['id'],
            orderColumn: $data['order_column'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->tempMediaId,
            'order_column' => $this->orderColumn,
        ];
    }
}

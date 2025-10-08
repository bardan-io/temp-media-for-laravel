<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\DTOs;

final readonly class TempMediaTransferDTO
{
    /**
     * @param  TempMediaItemDTO[]  $items
     */
    public function __construct(
        private array $items = []
    ) {}

    public static function fromArray(array $items): self
    {
        $dtoItems = array_map(
            fn (array $item) => TempMediaItemDTO::fromArray($item),
            $items
        );

        return new self($dtoItems);
    }

    /**
     * @return TempMediaItemDTO[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return string[]
     */
    public function getTempMediaIds(): array
    {
        return array_map(
            fn (TempMediaItemDTO $item) => $item->tempMediaId,
            $this->items
        );
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return array_map(
            fn (TempMediaItemDTO $item) => $item->toArray(),
            $this->items
        );
    }
}

<?php

namespace App\FileCollections;

class Collection
{
    protected \Illuminate\Support\Collection $items;
    public int $itemsPerPage = 3;
    public ?string $sorting = null;
    public string $path = '{filename}';

    public function setItems(array|\Illuminate\Support\Collection $items): void
    {
        if (is_array($items)) {
            $this->items = collect($items);
        } else {
            $this->items = $items;
        }
    }

    public function getItems(): \Illuminate\Support\Collection
    {
        return $this->items;
    }

    public function addItem($item): void
    {
        if (!isset($this->items)) {
            $this->items = collect();
        }
        $this->items->push($item);
    }

    public function addItems(array|\Illuminate\Support\Collection $items): void
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function sortItems(string $field, bool $descending = false): void
    {
        $this->items = $this->items->sortBy($field, SORT_REGULAR, $descending);
    }
}

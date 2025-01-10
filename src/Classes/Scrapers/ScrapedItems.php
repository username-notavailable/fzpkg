<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Scrapers;

class ScrapedItems implements \Iterator
{
    private array $scrapedItems;
    private int $index = 0;
    
    public function __construct()
    {
        $this->scrapedItems = [];
        $this->index = 0;
    }

    final public function addItem(array $item) : void
    {
        foreach (array_keys($item) as $key) {
            $item[$key] = is_string($item[$key]) ? trim($item[$key], " \t\n") : $item[$key];
        }

        $item['__md5__'] = hash('md5', implode('', array_keys($item)) . implode('', array_values($item)));

        $this->scrapedItems[] = $item;

        return;
    }

    final public function count() : int
    {
        return count($this->scrapedItems);
    }

    final public function toArray() : array
    {
        return $this->scrapedItems;
    }

    final public function toJson() : string
    {
        return json_encode($this->scrapedItems);
    }

    final public function reset() : void
    {
        $this->scrapedItems = [];
        $this->index = 0;
    }

    // Iterator

    public function current() : mixed
    {
        return empty($this->scrapedItems) ? null : $this->scrapedItems[$this->index];
    }

    public function next() : void
    {
        $this->index++;
    }

    public function key() : mixed
    {
        return $this->index;
    }

    public function valid() : bool
    {
        return isset($this->scrapedItems[$this->key()]);
    }

    public function rewind() : void
    {
        $this->index = 0;
    }

    // ---

    public function reverse()
    {
        $this->scrapedItems = array_reverse($this->scrapedItems, true);
        $this->rewind();
    }
}
<?php

namespace Fuzzy\Fzpkg\Classes;

class ScrapedItems implements \Iterator
{
    private array $scrapedItems;
    private int $index = 0;
    
    public function __construct()
    {
        $this->scrapedItems = [];
        $this->index = 0;
    }

    final public function addItem(string $image, string $time, string $title, string $link, string $tags) : void
    {
        $i = [ $image, $time, $title, $link, $tags ];
    
        $items = array_map(function($item) { return trim($item, " \t\n"); }, $i);

        $md5 = hash('md5', implode('', $items));

        $this->scrapedItems[] = [
            'md5' => $md5,
            'image' => $items[0],
            'time' =>  $items[1],
            'title' => $items[2],
            'link' => $items[3],
            'tags' => $items[4]
        ];

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
<?php

namespace Fuzzy\Fzpkg\Classes;

class ScrapedItems 
{
    private array $scrapedItems;
    
    public function __construct()
    {
        $this->scrapedItems = [];
    }

    final public function addItem(string $image, string $time, string $title, string $link, string $tags, string $tagsLink) : void
    {
        $i = [ $image, $time, $title, $link, $tags, $tagsLink ];
    
        $items = array_map(function($item) { return trim($item, " \t\n"); }, $i);

        $md5 = hash('md5', implode('', $items));

        $this->scrapedItems[] = [
            'md5' => $md5,
            'image' => $items[0],
            'time' =>  $items[1],
            'title' => $items[2],
            'link' => $items[3],
            'tags' => $items[4],
            'tagsLink' => $items[5]
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
    }
}
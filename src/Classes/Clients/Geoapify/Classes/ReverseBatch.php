<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\Geoapify\Classes;

class ReverseBatch 
{
    protected $coordinates;

    /**
     * [Description for __construct]
     * 
     * @param array<int, array{lat: float, lon: float}>|null $coordinates
     * 
     */
    public function __construct(?array $coordinates = null)
    {
        $this->coordinates = $coordinates ?? [];    
    }
    
    /**
     * [Description for addCoordinates]
     *
     * @param float $longitude
     * @param float $latitude
     * 
     * @return self
     * 
     */
    public function addCoordinates(float $latitude, float $longitude) : self
    {
        $this->coordinates[] = ['lat' => $latitude, 'lon' => $longitude];
        return $this;
    }
    
    /**
     * [Description for addCoordinatesArray]
     *
     * @param array<int, array{lat: float, lon: float}> $coordinates
     * 
     * @return self
     * 
     */
    public function addCoordinatesArray(array $coordinates) : self
    {
        $this->coordinates = array_merge($this->coordinates, $coordinates);
        return $this;
    }
    
    /**
     * [Description for toArray]
     * 
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api-reverse
     *
     * @return array
     * 
     */
    public function toArray() : array
    {
        return $this->coordinates;
    }
}
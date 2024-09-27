<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\Geoapify\Classes;

class Waypoints 
{
    protected $coordinates;

    /**
     * [Description for __construct]
     */
    public function __construct()
    {
        $this->coordinates = [];    
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
    public function addCoordinates(float $latitude, float $longitude, string $timestamp = '', ?int $bearing = null) : self
    {
        $this->coordinates[] = ['lat' => $latitude, 'lon' => $longitude, 'timestamp' => $timestamp, 'bearing' => $bearing];
        return $this;
    }
    
    /**
     * [Description for toLatLonString]
     * 
     * https://apidocs.geoapify.com/docs/routing
     *
     * @return string
     * 
     */
    public function toLatLonString() : string
    {
        $elaboratedCoordinates = [];

        foreach ($this->coordinates as $coordinates) {
            $elaboratedCoordinates[] = $coordinates['lat'] . ',' . $coordinates['lon'];
        }

        return implode('|', $elaboratedCoordinates);
    }

    /**
     * [Description for toLonLatString]
     * 
     * https://apidocs.geoapify.com/docs/routing
     *
     * @return string
     * 
     */
    public function toLonLatString() : string
    {
        $elaboratedCoordinates = [];

        foreach ($this->coordinates as $coordinates) {
            $elaboratedCoordinates[] = $coordinates['lon'] . ',' . $coordinates['lat'];
        }

        return implode('|', $elaboratedCoordinates);
    }

    /**
     * [Description for toLocationsArray]
     * 
     * https://apidocs.geoapify.com/docs/route-matrix
     *
     * @return array
     * 
     */
    public function toRouteMatrixArray() : array
    {
        $elaboratedCoordinates = [];

        foreach ($this->coordinates as $coordinates) {
            $elaboratedCoordinates[] = ['location' => [$coordinates['lon'], $coordinates['lat']]];
        }

        return $elaboratedCoordinates;
    }

    /**
     * [Description for toLocationsArray]
     * 
     * https://apidocs.geoapify.com/docs/map-matching
     *
     * @return array
     */
    public function toMapMatchingArray() : array
    {
        $elaboratedCoordinates = [];

        foreach ($this->coordinates as $coordinates) {
            $item = [];
            $item['timestamp'] = $coordinates['timestamp'];
            $item['location'] = [$coordinates['lon'], $coordinates['lat']];

            if (!is_null($coordinates['bearing'])) {
                $item['bearing'] = $coordinates['bearing'];
            }

            $elaboratedCoordinates[] = $item;
        }

        return $elaboratedCoordinates;
    }
}
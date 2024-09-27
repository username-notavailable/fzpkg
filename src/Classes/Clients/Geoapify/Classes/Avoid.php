<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\Geoapify\Classes;

use InvalidArgumentException;

class Avoid 
{
    protected $avoidsTypes;
    protected $avoidsLocations;

    public function __construct()
    {
        $this->avoidsTypes = [];
        $this->avoidsLocations = [];
    }

    public function tolls(float $importance = null) : self
    {
        $this->avoidsTypes['tolls'] = $importance;
        return $this;
    }

    public function ferries(float $importance = null) : self
    {
        $this->avoidsTypes['ferries'] = $importance;
        return $this;
    }

    public function highways(float $importance = null) : self
    {
        $this->avoidsTypes['highways'] = $importance;
        return $this;
    }

    public function location(float $latitude, float $longitude) : self
    {
        $this->avoidsLocations[] = ['lat' => $latitude, 'lon' => $longitude];
        return $this;
    }

    /**
     * [Description for toString]
     * 
     * https://apidocs.geoapify.com/docs/routing
     *
     * @return string
     * 
     */
    public function toString() : string
    {
        if (count($this->avoidsTypes) > 0 || count($this->avoidsLocations) > 0) {
            $elaboratedAvoids = [];

            foreach (['tolls', 'ferries', 'highways'] as $key) {
                if (array_key_exists($key, $this->avoidsTypes)) {
                    if (!is_null($this->avoidsTypes[$key])) {
                        $elaboratedAvoids[] = $key . ':' . $this->avoidsTypes[$key];
                    }
                    else {
                        $elaboratedAvoids[] = $key;
                    }
                }
            }

            foreach ($this->avoidsLocations as $location) {
                $elaboratedAvoids[] = 'location:' . $location['lat'] . ',' . $location['lon'];
            }

            return implode('|', $elaboratedAvoids);
        }
        else {
            return '';
        }
    }

    /**
     * [Description for toArray]
     * 
     * https://apidocs.geoapify.com/docs/route-matrix
     *
     * @return array
     * 
     */
    public function toArray() : array
    {
        if (count($this->avoidsTypes) > 0 || count($this->avoidsLocations) > 0) {
            $elaboratedAvoids = [];

            foreach (['tolls', 'ferries', 'highways'] as $key) {
                if (array_key_exists($key, $this->avoidsTypes)) {
                    if (!is_null($this->avoidsTypes[$key])) {
                        //$elaboratedAvoids[] = $key . ':' . $this->avoidsTypes[$key];
                        $elaboratedAvoids[] = ['type' => $key, 'importance' => $this->avoidsTypes[$key]];
                    }
                    else {
                        //$elaboratedAvoids[] = $key;
                        $elaboratedAvoids[] = ['type' => $key];
                    }
                }
            }

            if (count($this->avoidsLocations) > 0) {
                $locations = [];

                foreach ($this->avoidsLocations as $location) {
                    $locations[] = ['lat' => $location['lat'], 'lon' => $location['lon']];
                }

                $elaboratedAvoids[] = ['type' => 'locations', 'values' => $locations];
            }

            return $elaboratedAvoids;
        }
        else {
            return [];
        }
    }
}
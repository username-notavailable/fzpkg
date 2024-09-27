<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx;

use Fuzzy\Fzpkg\Classes\Gpx\Complex\MetadataType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\WptType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\RteType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\TrkType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\ExtensionsType;
use Fuzzy\Fzpkg\Classes\Utils\Utils;

// https://www.topografix.com/gpx/1/1/
class GpxFile
{
    private array $xmlErrors;
    protected ?\DOMXPath $xPath;

    public array $namespaces;
    public array $schemaLocations;
    public bool $xsiSchemaLocationMustExistsOnLoad;

    public string $version;
    public string $creator;
    public MetadataType $metadata;
    public array $waypoints;
    public array $routes;
    public array $tracks;
    public ExtensionsType $extensions;

    public function __construct()
    {
        $this->xmlErrors = [];
        $this->xPath = null;

        $this->namespaces['xmlns'] = 'http://www.topografix.com/GPX/1/1';
        $this->namespaces['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
        $this->schemaLocations['http://www.topografix.com/GPX/1/1'] = 'http://www.topografix.com/GPX/1/1/gpx.xsd';
        $this->xsiSchemaLocationMustExistsOnLoad = true;

        $this->version = '1.1';
        $this->creator = 'Fzpkg';

        // Information about the GPX file, author, and copyright restrictions goes in the metadata section. Providing rich, meaningful information about your GPX files allows others to search for and use your GPS data. 
        $this->metadata = new MetadataType();
        // wpt represents a waypoint, point of interest, or named feature on a map. 
        $this->waypoints = [];
        // rte represents route - an ordered list of waypoints representing a series of turn points leading to a destination. 
        $this->routes = [];
        // trk represents a track - an ordered list of points describing a path. 
        $this->tracks = [];
        $this->extensions = new ExtensionsType();
    }

    /**
     * https://www.php.net/manual/en/domdocument.load.php
     * https://php.net/manual/en/domdocument.construct.php
     * https://php.net/manual/en/domxpath.construct.php
     * 
     * @param string    $filename   
     * @param int       $options    (https://php.net/manual/en/domdocument.loadxml.php)
     * @param string    $version    (https://php.net/manual/en/domdocument.construct.php)
     * @param string    $encoding   (https://php.net/manual/en/domdocument.construct.php)
     * @param bool      $validateSchema (https://www.php.net/manual/en/domdocument.schemavalidate.php)
     * 
     * @throws Exception
     */
    public function loadGpxFile(string $filename, int $options = LIBXML_BIGLINES | LIBXML_NOBLANKS, string $version = '1.0', string $encoding = '', $validateSchema = true) : void
    {
        $xml = file_get_contents($filename);

        if (!$xml) {
            throw new \Exception('Read GPX file contents failed');
        }

        libxml_use_internal_errors(true);

        $xmlDoc = new \DOMDocument($version, $encoding);

        if (!$xmlDoc->loadXML($xml, $options)) {
            $this->xmlErrors = libxml_get_errors();
            libxml_clear_errors();
            
            throw new \Exception('Invalid GPX file, check getXmlErrors()');
        }
        else {
            if ($validateSchema && !$xmlDoc->schemaValidate(Utils::makeFilePath(__DIR__, '..', '..', '..', 'data', 'gpx.xsd'))) {
                $this->xmlErrors = libxml_get_errors();
                libxml_clear_errors();

                throw new \Exception('Invalid GPX file schema, check getXmlErrors()');
            }

            $this->xmlErrors = [];

            $this->xPath = new \DOMXPath($xmlDoc, false);

            $this->namespaces = [];

            foreach ((new \SimpleXMLElement($xml))->getDocNamespaces() as $prefix => $namespace) {
                $this->namespaces[(empty($prefix) ? 'xmlns' : ('xmlns:' . $prefix))] = $namespace;

                $this->xPath->registerNamespace((empty($prefix) ? 'ns' : $prefix), $namespace);
            }

            if ($this->xPath->query('/ns:gpx')->count() === 0) {
                throw new \Exception('Invalid GPX file, namespace "http://www.topografix.com/GPX/1/1" not found');
            }

            $this->schemaLocations = [];

            $nodes = $this->xPath->query('/ns:gpx/@xsi:schemaLocation');

            if ($nodes->count() > 0) {
                $schemaLocations = explode(' ', $nodes[0]->value);

                if (count($schemaLocations) % 2 !== 0) {
                    throw new \Exception('Invalid GPX file, malformed xsi:schemaLocation');
                }
                else {
                    for ($i = 0; $i < count($schemaLocations); $i += 2) {
                        $this->schemaLocations[$schemaLocations[$i]] = $schemaLocations[$i + 1];
                    }
                }
            }
            elseif ($this->xsiSchemaLocationMustExistsOnLoad) {
                throw new \Exception('Invalid GPX file, "xsi:schemaLocation" not found');
            }

            $this->version = (string)$this->evaluate('string(/ns:gpx/@version)');
            $this->creator = (string)$this->evaluate('string(/ns:gpx/@creator)');

            $nodes = $this->query('/ns:gpx/ns:metadata');

            $this->metadata = $nodes->count() > 0 ? (new MetadataType())->loadFromXpath($this->xPath, $nodes[0]) : new MetadataType();

            $this->waypoints = [];

            foreach ($this->query('/ns:gpx/ns:wpt') as $node) {
                $this->waypoints[] = (new WptType())->loadFromXpath($this->xPath, $node);
            }

            $this->routes = [];

            foreach ($this->query('/ns:gpx/ns:rte') as $node) {
                $this->routes[] = (new RteType())->loadFromXpath($this->xPath, $node);
            }

            $this->tracks = [];

            foreach ($this->query('/ns:gpx/ns:trk') as $node) {
                $this->tracks[] = (new TrkType())->loadFromXpath($this->xPath, $node);
            }

            $nodes = $this->query('/ns:gpx/ns:extensions');

            $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($this->xPath, $nodes[0]) : new ExtensionsType();
        }
    }

    public function saveGpxFile(string $filename) : void
    {
        file_put_contents($filename, '<?xml version="1.0"?>');

        $openTag = '<gpx version="' . $this->version . '" creator="' . $this->creator . '" ';
        
        foreach ($this->namespaces as $name => $value) {
            $openTag .= $name . '="' . $value . '" ';
        }

        if (count($this->schemaLocations) > 0) {
            $schemaLocations = '';

            foreach ($this->schemaLocations as $name => $value) {
                $schemaLocations .= $name . ' ' . $value . ' ';
            }

            $openTag .= 'xsi:schemaLocation="' . trim($schemaLocations) . '"';
        }

        $openTag = trim($openTag) . '>';

        file_put_contents($filename, $openTag, FILE_APPEND);

        file_put_contents($filename, $this->metadata, FILE_APPEND);

        foreach ($this->waypoints as $waypoint) {
            file_put_contents($filename, $waypoint, FILE_APPEND);
        }

        foreach ($this->routes as $route) {
            file_put_contents($filename, $route, FILE_APPEND);
        }

        foreach ($this->tracks as $track) {
            file_put_contents($filename, $track, FILE_APPEND);
        }

        file_put_contents($filename, '</gpx>', FILE_APPEND);
    }

    public function searchWaypointsByName(string $name) : array
    {
        return $this->__searchItemsByName($this->waypoints, $name);
    }

    public function searchWaypointsBySymbol(string $symbol) : array
    {
        $symbol = strtolower($symbol);
        $waypoints = [];

        foreach ($this->waypoints as $waypoint) {
            if (strtolower($waypoint->sym) === $symbol) {
                $waypoints[] = $waypoint;
            }
        }

        return $waypoints;
    }

    public function searchRoutesByName(string $name) : array
    {
        return $this->__searchItemsByName($this->routes, $name);
    }

    public function searchRoutesWaypointsByName(string $routeName, string $waypointName) : array
    {
        $waypoints = [];

        $routes = $this->searchRoutesByName($routeName);

        if (!empty($routes)) {
            $waypointName = strtolower($waypointName);
        
            foreach ($routes as $idx => $route) {
                $waypoints[$idx] = [];
                foreach ($route->rtept as $waypoint) {
                    if (strtolower($waypoint->name) === $waypointName) {
                        $waypoints[$idx][] = $waypoint;
                    }
                }
            }
        }

        return $waypoints;
    }

    public function searchRoutesWaypointsBySymbol(string $routeName, string $symbol) : array
    {
        $waypoints = [];

        $routes = $this->searchRoutesByName($routeName);

        if (!empty($routes)) {
            $symbol = strtolower($symbol);
        
            foreach ($routes as $idx => $route) {
                $waypoints[$idx] = [];
                foreach ($route->rtept as $waypoint) {
                    if (strtolower($waypoint->sym) === $symbol) {
                        $waypoints[$idx][] = $waypoint;
                    }
                }
            }
        }

        return $waypoints;
    }

    public function searchTracksByName(string $name) : array
    {
        return $this->__searchItemsByName($this->tracks, $name);
    }

    public function searchTracksSegmentsWaypoints(string $trackName) : array
    {
        $waypoints = [];

        $tracks = $this->searchTracksByName($trackName);

        if (!empty($tracks)) {
            foreach ($tracks as $idx => $track) {
                $waypoints[$idx] = [];
                foreach ($track->trkseg as $trackSegment) {
                    $waypoints[$idx][] = $trackSegment->trkpt;
                }
            }
        }

        return $waypoints;
    }

    public function searchTracksSegmentsWaypointsByName(string $trackName, string $waypointName) : array
    {
        $waypoints = [];

        $tracks = $this->searchTracksByName($trackName);

        if (!empty($tracks)) {
            $waypointName = strtolower($waypointName);
        
            foreach ($tracks as $idx => $track) {
                $waypoints[$idx] = [];
                foreach ($track->trkseg as $idx2 => $trackSegment) {
                    $waypoints[$idx][$idx2] = [];
                    foreach ($trackSegment->trkpt as $waypoint) {
                        if (strtolower($waypoint->name) === $waypointName) {
                            $waypoints[$idx][$idx2][] = $waypoint;
                        }
                    }
                }
            }
        }

        return $waypoints;
    }

    public function searchTracksSegmentsWaypointsBySymbol(string $trackName, string $symbol) : array
    {
        $waypoints = [];

        $tracks = $this->searchTracksByName($trackName);

        if (!empty($tracks)) {
            $symbol = strtolower($symbol);
        
            foreach ($tracks as $idx => $track) {
                $waypoints[$idx] = [];
                foreach ($track->trkseg as $idx2 => $trackSegment) {
                    $waypoints[$idx][$idx2] = [];
                    foreach ($trackSegment->trkpt as $waypoint) {
                        if (strtolower($waypoint->sym) === $symbol) {
                            $waypoints[$idx][$idx2][] = $waypoint;
                        }
                    }
                }
            }
        }

        return $waypoints;
    }

    /**
     * https://php.net/manual/en/function.libxml-get-errors.php
     */
    public function getXmlErrors() : array
    {
        return $this->xmlErrors;
    }

    protected function query(string $expression) : \DOMNodeList
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->query($expression);
    }

    protected function evaluate(string $expression) : mixed
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->evaluate($expression);
    }

    private function __searchItemsByName(array &$array, string $name) : array
    {
        $items = [];
        $name = strtolower($name);

        foreach ($array as $item) {
            if (strtolower($item->name) === $name) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx;

use Fuzzy\Fzpkg\Classes\Gpx\Complex\MetadataType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\WptType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\RteType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\TrkType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\ExtensionsType;
use Fuzzy\Fzpkg\Classes\Utils\Utils;

class GpxFile
{
    private array $xmlErrors;
    protected ?\DOMXPath $xPath;

    public array $namespaces;
    public array $schemaLocations;
    public bool $xsiSchemaLocationMustBeInluded;

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
        $this->xsiSchemaLocationMustBeInluded = true;

        $this->version = '1.1';
        $this->creator = 'Fzpkg';
        $this->metadata = new MetadataType();
        $this->waypoints = [];
        $this->routes = [];
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
            elseif ($this->xsiSchemaLocationMustBeInluded) {
                throw new \Exception('Invalid GPX file, "xsi:schemaLocation" not found');
            }

            $this->version = (string)$this->__evaluate('string(/ns:gpx/@version)');
            $this->creator = (string)$this->__evaluate('string(/ns:gpx/@creator)');

            $nodes = $this->__query('/ns:gpx/ns:metadata');

            $this->metadata = $nodes->count() > 0 ? (new MetadataType())->loadFromXpath($this->xPath, $nodes[0]) : new MetadataType();

            $this->waypoints = [];

            foreach ($this->__query('/ns:gpx/ns:wpt') as $node) {
                $this->waypoints[] = (new WptType())->loadFromXpath($this->xPath, $node);
            }

            $this->routes = [];

            foreach ($this->__query('/ns:gpx/ns:rte') as $node) {
                $this->routes[] = (new RteType())->loadFromXpath($this->xPath, $node);
            }

            $this->tracks = [];

            foreach ($this->__query('/ns:gpx/ns:trk') as $node) {
                $this->tracks[] = (new TrkType())->loadFromXpath($this->xPath, $node);
            }

            $nodes = $this->__query('/ns:gpx/ns:extensions');

            $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($this->xPath, $nodes[0]) : new ExtensionsType();
        }
    }

    /**
     * @param string $filename
     * 
     * 
     */
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

    /**
     * https://php.net/manual/en/function.libxml-get-errors.php
     */
    public function getXmlErrors() : array
    {
        return $this->xmlErrors;
    }

    protected function __query(string $expression) : \DOMNodeList
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->query($expression);
    }

    protected function __evaluate(string $expression) : mixed
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->evaluate($expression);
    }
}
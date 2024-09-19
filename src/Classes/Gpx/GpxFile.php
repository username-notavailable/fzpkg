<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx;

use Fuzzy\Fzpkg\Classes\Gpx\Complex\MetadataType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\WtpType;
use Fuzzy\Fzpkg\Classes\Gpx\Complex\ExtensionsType;

class GpxFile
{
    private array $xmlErrors;
    protected ?\DOMXPath $xPath;

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

        $this->version = '';
        $this->creator = '';
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
     * @param string    $filename   (https://www.php.net/manual/en/domdocument.load.php)
     * @param int       $options    (https://www.php.net/manual/en/domdocument.load.php)
     * @param string    $version    (https://php.net/manual/en/domdocument.construct.php)
     * @param string    $encoding   (https://php.net/manual/en/domdocument.construct.php)
     * @param bool      $validateSchema
     * 
     * @throws Exception
     */
    public function loadGpxFile(string $filename, int $options = 0, string $version = '1.0', string $encoding = '', $validateSchema = true) : void
    {
        libxml_use_internal_errors(true);

        $xmlDoc = new \DOMDocument($version, $encoding);

        if (!$xmlDoc->load($filename, $options)) {
            $this->xmlErrors = libxml_get_errors();
            libxml_clear_errors();
            
            throw new \Exception('Invalid GPX file, check getXmlErrors()');
        }
        else {
            if ($validateSchema && !$xmlDoc->schemaValidate(__DIR__ . '/../../../data/gpx.xsd')) {
                $this->xmlErrors = libxml_get_errors();
                libxml_clear_errors();

                throw new \Exception('Invalid GPX file schema, check getXmlErrors()');
            }

            $this->xmlErrors = [];

            $this->xPath = new \DOMXPath($xmlDoc, false);
            $this->xPath->registerNamespace('ns', 'http://www.topografix.com/GPX/1/1');

            if ($this->xPath->query('/ns:gpx')->count() === 0) {
                throw new \Exception('Invalid GPX file, namespace "http://www.topografix.com/GPX/1/1" not found');
            }

            $this->version = (string)$this->__evaluate('string(/ns:gpx/@version)');
            $this->creator = (string)$this->__evaluate('string(/ns:gpx/@creator)');

            $nodes = $this->__query('/ns:gpx/ns:metadata');

            $this->metadata = $nodes->count() > 0 ? (new MetadataType())->loadFromXpath($this->xPath, $nodes[0]) : new MetadataType();

            $this->waypoints = [];

            foreach ($this->__query('/ns:gpx/ns:wpt') as $node) {
                $this->waypoints[] = (new WtpType())->loadFromXpath($this->xPath, $node);
            }

            /*$this->routes = [];

            foreach ($this->__query('/ns:gpx/ns:rte') as $node) {
                $this->routes[] = (new WtpType())->loadFromXpath($this->xPath, $node);
            }

            $this->tracks = [];

            foreach ($this->__query('/ns:gpx/ns:trk') as $node) {
                $this->tracks[] = (new WtpType())->loadFromXpath($this->xPath, $node);
            }*/

            $nodes = $this->__query('/ns:gpx/ns:extensions');

            $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($this->xPath, $nodes[0]) : new ExtensionsType();
        }
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
<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx;

use Fuzzy\Fzpkg\Classes\Gpx\Complex\MetadataType;

class GpxReader
{
    private array $xmlErrors;
    protected ?\DOMXPath $xPath;

    public function __construct()
    {
        $this->xmlErrors = [];
        $this->xPath = null;
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
        }
    }

    /**
     * https://php.net/manual/en/function.libxml-get-errors.php
     */
    public function getXmlErrors() : array
    {
        return $this->xmlErrors;
    }

    protected function __query($query) : \DOMNodeList
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->query($query);
    }

    protected function __evaluate($query) : mixed
    {
        if (empty($this->xPath)) {
            throw new \Exception('Gpx file not loaded');
        }

        return $this->xPath->evaluate($query);
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getVersion() : string
    {
        return (string)$this->__evaluate('string(/ns:gpx/@version)');
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getCreator() : string
    {
        return (string)$this->__evaluate('string(/ns:gpx/@creator)');
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getRawMetadata() : ?\DOMNode
    {
        $nodes = $this->__query('/ns:gpx/ns:metadata');

        return $nodes->count() > 0 ? $nodes[0] : null;
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getMetadata() : ?MetadataType
    {
        $node = $this->getRawMetadata();

        return !is_null($node) ? new MetadataType($this->xPath, $node) : null;
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getRawWaypoints() : \DOMNodeList
    {
        return $this->__query('/ns:gpx/ns:wpt');
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getRawRoutes() : \DOMNodeList
    {
        return $this->__query('/ns:gpx/ns:rte');
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getRawTracks() : \DOMNodeList
    {
        return $this->__query('/ns:gpx/ns:trk');
    }

    /**
     * https://www.topografix.com/gpx/1/1/
     * 
     * @throws Exception
     */
    public function getRawExtensions() : ?\DOMNode
    {
        $nodes = $this->__query('/ns:gpx/ns:extensions');

        return $nodes->count() > 0 ? $nodes[0] : null;
    }
}
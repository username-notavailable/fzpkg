<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\Geoapify;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Fuzzy\Fzpkg\Classes\Clients\Geoapify\Body\ForwardBatch;
use Fuzzy\Fzpkg\Classes\Clients\Geoapify\Body\ReverseBatch;
use Fuzzy\Fzpkg\Classes\Clients\Geoapify\Body\Avoid;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected $apiKey;
    protected $httpClient;

    private $apiHost;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = new GuzzleClient();

        $this->apiHost = 'https://api.geoapify.com';
    }

    /**
     * [Description for doRequest]
     *
     * @param string $method
     * @param array $headers
     * @param string $body
     * 
     * @return ResponseInterface
     * 
     * @throws GuzzleException
     * 
     */
    protected function doRequest(string $method, string $url, array $headers = [], string $body = '') : ResponseInterface
    {
        $requestOptions = [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
            ]
        ];

        if (!empty($headers)) {
            $requestOptions['headers'] = $headers;
        }

        if (!empty($body)) {
            $requestOptions['body'] = $body;
        }

        return $this->httpClient->request($method, $url, $requestOptions);
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/address-autocomplete/
     * 
     * @param string    $text
     * @param string    $type
     * @param string    $lang (default: en)
     * @param string    $filter
     * @param string    $bias
     * @param string    $format (default: geojson)
     * @param int       $limit (default: 15)
     * 
     * @throws GuzzleException
     */
    public function addressAutocomplete(string $text = null, string $type = null, string $lang = null, string $filter = null, string $bias = null, string $format = null, int $limit = null) : string
    {
        $url = $this->apiHost . '/v1/geocode/autocomplete?apiKey=' . $this->apiKey;

        $d['text'] = $text;
        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';
        $d['filter'] = $filter;
        $d['bias'] = $bias;
        $d['format'] = $format ?? 'geojson';
        $d['limit'] = $limit ?? 15;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if ($name === 'text' && !is_null($value)) {
                $value = rawurlencode($value);
            }

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/forward-geocoding/
     * 
     * @param string    $text (FreeForm)
     * @param string    $type
     * @param string    $lang (default: en)
     * @param string    $filter
     * @param string    $bias (default: countrycode:auto)
     * @param string    $format (default: geojson)
     * @param int       $limit (default: 15)
     * 
     * @throws GuzzleException
     */
    public function forwardGeocodingFreeForm(string $text = null, string $type = null, string $lang = null, string $filter = null, string $bias = null, string $format = null, int $limit = null) : string
    {
        $url = $this->apiHost . '/v1/geocode/search?apiKey=' . $this->apiKey;

        $d['text'] = $text;
        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';
        $d['filter'] = $filter;
        $d['bias'] = $bias ?? 'countrycode:auto';
        $d['format'] = $format ?? 'geojson';
        $d['limit'] = $limit ?? 15;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if ($name === 'text' && !is_null($value)) {
                $value = rawurlencode($value);
            }

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/forward-geocoding/
     * 
     * @param string    $name (Structured)
     * @param string    $housenumber (Structured)
     * @param string    $street (Structured)
     * @param string    $postcode (Structured)
     * @param string    $city (Structured)
     * @param string    $state (Structured)
     * @param string    $country (Structured)
     * @param string    $type
     * @param string    $lang (default: en)
     * @param string    $filter
     * @param string    $bias (default: countrycode:auto)
     * @param string    $format (default: geojson)
     * @param int       $limit (default: 15)
     * 
     * @throws GuzzleException
     */
    public function forwardGeocodingStructured(string $name = null, string $housenumber = null, string $street = null, string $postcode = null, string $city = null, string $state = null, string $country = null, string $type = null, string $lang = null, string $filter = null, string $bias = null, string $format = null, int $limit = null) : string
    {
        $url = $this->apiHost . '/v1/geocode/search?apiKey=' . $this->apiKey;

        $d['name'] = $name;
        $d['housenumber'] = $housenumber;
        $d['street'] = $street;
        $d['postcode'] = $postcode;
        $d['city'] = $city;
        $d['state'] = $state;
        $d['country'] = $country;
        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';
        $d['filter'] = $filter;
        $d['bias'] = $bias ?? 'countrycode:auto';
        $d['format'] = $format ?? 'geojson';
        $d['limit'] = $limit ?? 15;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (in_array($name, ['name', 'street', 'city', 'state', 'country']) && !is_null($value)) {
                $value = rawurlencode($value);
            }

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/reverse-geocoding/
     * 
     * @param float     $lat
     * @param float     $lon
     * @param string    $type
     * @param string    $lang (default: en)
     * @param string    $format (default: geojson)
     * @param int       $limit (default: 1)
     * 
     * @throws GuzzleException
     */
    public function reverseGeocoding(float $lat = null, float $lon = null, string $type = null, string $lang = null, string $format = null, int $limit = null) : string
    {
        $url = $this->apiHost . '/v1/geocode/reverse?apiKey=' . $this->apiKey;

        $d['lat'] = $lat;
        $d['lon'] = $lon;
        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';
        $d['format'] = $format ?? 'geojson';
        $d['limit'] = $limit ?? 1;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/ip-geolocation/
     * 
     * @param string $ip
     * 
     * @throws GuzzleException
     */
    public function ipGeolocation(string $ip = null) : string
    {
        $url = $this->apiHost . '/v1/ipinfo?apiKey=' . $this->apiKey;

        $d['ip'] = $ip;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api
     * 
     * @param array     $body
     * @param string    $type
     * @param string    $lang (default: en)
     * @param string    $filter
     * @param string    $bias
     * 
     * @throws GuzzleException
     */
    public function geocondingBatchCreateJob(array $body = [], string $type = null, string $lang = null, string $filter = null, string $bias = null) : string
    {
        $url = $this->apiHost . '/v1/batch/geocode/search?apiKey=' . $this->apiKey;

        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';
        $d['filter'] = $filter;
        $d['bias'] = $bias;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);

        $response = $this->doRequest('POST', $url, ['CONTENT-TYPE' => 'application/json'], json_encode($body));
        
        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api
     * 
     * @param string    $id
     * @param string    $format
     * 
     * @throws GuzzleException
     */
    public function geocondingBatchGetResult(string $id = null, string $format = null) : string
    {
        $url = $this->apiHost . '/v1/batch/geocode/search?apiKey=' . $this->apiKey;

        $d['id'] = $id;
        $d['format'] = $format;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api-reverse
     * 
     * @param array     $body
     * @param string    $type
     * @param string    $lang (default: en)
     * 
     * @throws GuzzleException
     */
    public function geocondingBatchReverseCreateJob(array $body = [], string $type = null, string $lang = null) : string
    {
        $url = $this->apiHost . '/v1/batch/geocode/reverse?apiKey=' . $this->apiKey;

        $d['type'] = $type;
        $d['lang'] = $lang ?? 'en';

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);

        $response = $this->doRequest('POST', $url, ['CONTENT-TYPE' => 'application/json'], json_encode($body));

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api-reverse
     * 
     * @param string    $id
     * @param string    $format
     * 
     * @throws GuzzleException
     */
    public function geocondingBatchReverseGetResult(string $id = null, string $format = null) : string
    {
        $url = $this->apiHost . '/v1/batch/geocode/reverse?apiKey=' . $this->apiKey;

        $d['id'] = $id;
        $d['format'] = $format;

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/routing
     * 
     * @param string    $waypoints
     * @param string    $mode (default: drive)
     * @param string    $type (default: balanced)
     * @param string    $units (default: metric)
     * @param string    $lang (default: en)
     * @param string    $avoid
     * @param string    $details
     * @param string    $traffic
     * @param string    $max_speed
     * @param string    $format (default: geojson)
     * 
     * @throws GuzzleException
     */
    public function routing(string $waypoints = null, string $mode = null, string $type = null, string $units = null, string $lang = null, string $avoid = null, string $details = null, string $traffic = null, float $maxSpeed = null, string $format = null) : string
    {
        $url = $this->apiHost . '/v1/routing?apiKey=' . $this->apiKey;

        $d['waypoints'] = $waypoints;
        $d['mode'] = $mode ?? 'drive';
        $d['type'] = $type ?? 'balanced';
        $d['units'] = $units ?? 'metric';
        $d['lang'] = $lang ?? 'en';
        $d['avoid'] = $avoid;
        $d['details'] = $details;
        $d['traffic'] = $traffic;
        $d['max_speed'] = $maxSpeed;
        $d['format'] = $format ?? 'geojson';

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $url .= '&' . $name . '=' . $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);
        
        $response = $this->doRequest('GET', $url);

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/route-matrix
     * 
     * @param string    $mode (default: drive)
     * @param array[]   $sources
     * @param array[]   $targets
     * @param array[]   $avoid
     * @param string    $traffic
     * @param string    $type (default: balanced)
     * @param string    $max_speed
     * @param string    $units (default: metric)
     * 
     * @throws GuzzleException
     */
    public function routeMatrix(string $mode = null, array $sources = null, array $targets = null, array $avoid = null, string $traffic = null, string $type = null, float $maxSpeed = null, string $units = null) : string
    {
        $url = $this->apiHost . '/v1/routematrix?apiKey=' . $this->apiKey;

        $d['mode'] = $mode ?? 'drive';
        $d['sources'] = $sources ?? [];
        $d['targets'] = $targets ?? [];
        $d['avoid'] = $avoid;
        $d['traffic'] = $traffic;
        $d['type'] = $type ?? 'balanced';
        $d['max_speed'] = $maxSpeed;
        $d['units'] = $units ?? 'metric';

        $body = [];

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $body[$name] = $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);

        $response = $this->doRequest('POST', $url, ['CONTENT-TYPE' => 'application/json'], json_encode($body));

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/map-matching
     * 
     * @param array[]   $waypoints
     * @param string    $mode (default: drive)
     * 
     * @throws GuzzleException
     */
    public function mapMatching(array $waypoints = null, string $mode = null) : string
    {
        $url = $this->apiHost . '/v1/mapmatching?apiKey=' . $this->apiKey;

        $d['waypoints'] = $waypoints ?? [];
        $d['mode'] = $mode ?? 'drive';

        $body = [];

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $body[$name] = $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);

        $response = $this->doRequest('POST', $url, ['CONTENT-TYPE' => 'application/json'], json_encode($body));

        return (string) $response->getBody();
    }

    /**
     * https://apidocs.geoapify.com/docs/map-matching
     * 
     * @param array[]   $waypoints
     * @param string    $mode (default: drive)
     * 
     * @throws GuzzleException
     */
    /*public function mapMatching(array $waypoints = null, string $mode = null) : string
    {
        $url = $this->apiHost . '/v1/mapmatching?apiKey=' . $this->apiKey;

        $d['waypoints'] = $waypoints ?? [];
        $d['mode'] = $mode ?? 'drive';

        $body = [];

        foreach (array_keys($d) as $name) {
            $value = $d[$name];

            if (!is_null($value) && !empty($value)) {
                $body[$name] = $value;
            }
        }

        Log::debug('URL: ' . $url, [__METHOD__]);

        $response = $this->doRequest('POST', $url, ['CONTENT-TYPE' => 'application/json'], json_encode($body));

        return (string) $response->getBody();
    }*/
}
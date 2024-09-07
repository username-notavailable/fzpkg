<?php

namespace App\Scrapers\Classes;

use Fuzzy\Fzpkg\Classes\BaseScrape;
use Fuzzy\Fzpkg\Enums\ScrapeResult;
//use Fuzzy\Fzpkg\Enums\RunScraperResult;
//use Illuminate\Support\Facades\Log;
//use App\Scrapers\Search\SharedSearchWords;

class WwwItaliaItClass extends BaseScrape
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getSearchWords() : array
    {
        //return SharedSearchWords::words();

        return [
            'toscana',
            '',
            //...
        ];
    }

    public function doScrape(string $search) : ScrapeResult
    {
        $page = 0;
        $nbrPages = '';
        $count = 1;
        $done = false;
        $applicationId = '';
        $apiKey = '';
        $totArticles = '';
        $origin = 'https://www.italia.it';
        $postHost = 'g75k8jzfiv-2.algolianet.com';

        $referer = 'https://www.italia.it/it/ricerca?q=' . $search;

        do {
            if ($page == 0) {
                $response = $this->httpClient->request('GET', $referer, [
                'headers' => [
                    'ACCEPT' => '*/*',
                    'ACCEPT-ENCODING' => 'gzip, deflate, br',
                    'CONNECTION' => 'keep-alive',
                    'USER-AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
                    'HOST' => 'www.italia.it',
                    'ORIGIN' => $origin
                ]]);

                $htmlString = (string) $response->getBody();

                $matches = [];

                if (preg_match('@algoliaConfigs\.applicationId = "(.+)"@m', $htmlString, $matches) !== 1) {
                    return ScrapeResult::PAGE_MODIFIED;
                }
                else {
                    $applicationId = $matches[1];
                }

                if (preg_match('@algoliaConfigs\.searchOnlyApiKey = "(.+)"@m', $htmlString, $matches) !== 1) {
                    return ScrapeResult::PAGE_MODIFIED;
                }
                else {
                    $apiKey = $matches[1];
                }
            }

            $requestData = '{
	            "requests": [
                    {
                        "query": "' . $search . '",
                        "indexName": "generic_object_index_prod_it",
                        "params": "page=' . $page . '",
                        "filters": "type:Itinerary",
                        "enablePersonalization": false
                    }
                ]
            }';

            $response = $this->httpClient->request('POST', 'https://' . $postHost . '/1/indexes/*/queries?x-algolia-agent=Algolia+for+JavaScript+(4.24.0)+Browser', [
                'body' => $requestData,
                'headers' => [
                    'ACCEPT' => '*/*',
                    'ACCEPT-ENCODING' => 'gzip, deflate, br',
                    'CONNECTION' => 'keep-alive',
                    'USER-AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
                    'HOST' => $postHost,
                    'ORIGIN' => $origin,
                    'REFERER' => $referer,
                    'CONTENT-TYPE' => 'application/json',
                    'X-ALGOLIA-API-KEY' => $apiKey,
                    'X-ALGOLIA-APPLICATION-ID' => $applicationId
                ]
            ]);

            $htmlString = (string) $response->getBody();

            $data = json_decode($htmlString, true);

            if ($page === 0) {
                $nbrPages = (int) $data['results'][0]['nbPages'];
                $totArticles = (int) $data['results'][0]['nbHits'];
            }

            foreach ($data['results'][0]['hits'] as $article) {
                $image = $article['image'];
                $time = '';
                $title = $article['title'];
                $link = $article['url'];;
                $tags = implode(', ', $article['_tags']);

                $this->scrapedItems->addItem(
                    $image,
                    $time,
                    $title,
                    $link,
                    $tags
                );

                $this->showProgress($count++, $totArticles);
            }

            $page++;

            if ($page >= $nbrPages) {
                $done = true;
            }
            else {
                sleep(1);
            }

        } while(!$done);

        return ScrapeResult::OK;
    }

    public static function getSite() : string
    {
        return 'https://www.italia.it';
    }
}
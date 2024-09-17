<?php

namespace App\Scrapers\Classes;

use Fuzzy\Fzpkg\Classes\Scrapers\BaseScraper;
use Fuzzy\Fzpkg\Enums\Scrapers\ScrapeResult;
//use Fuzzy\Fzpkg\Enums\RunScraperResult;
//use Illuminate\Support\Facades\Log;
//use App\Scrapers\Search\SharedSearchWords;

class WwwTrekkingItClass extends BaseScraper
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
        $page = 1;
        $count = 1;
        $item = [];
        $done = false;
        $nonce = '';
        $totArticles = '';
        $origin = 'https://www.trekking.it';

        $referer = 'https://www.trekking.it/?s=' . urlencode($search);

        do {
            if ($page == 1) {
                $response = $this->httpClient->request('GET', $referer, [
                'headers' => [
                    'ACCEPT' => '*/*',
                    'ACCEPT-ENCODING' => 'gzip, deflate, br',
                    'CONNECTION' => 'keep-alive',
                    'USER-AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
                    'HOST' => 'www.trekking.it',
                    'ORIGIN' => $origin
                ]]);
            }
            else {
                $response = $this->httpClient->request('POST', 'https://www.trekking.it/wp-admin/admin-ajax.php', [
                'headers' => [
                    'ACCEPT' => '*/*',
                    'ACCEPT-ENCODING' => 'gzip, deflate, br',
                    'CONNECTION' => 'keep-alive',
                    'USER-AGENT' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
                    'HOST' => 'www.trekking.it',
                    'ORIGIN' => $origin,
                    'REFERER' => $referer,
                    'CONTENT-TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8'
                ],
                'form_params' => [
                    'action' => 'anotherSearchPosts',
                    'paged' => $page,
                    'nonce' => $nonce,
                    'type' => 'SearchPosts',
                    'key' => $search
                ]]);
            }

            $htmlString = (string) $response->getBody();

            if ($page === 1) {
                $matches = [];

                if (preg_match('@var frontEndAjax = {"ajaxurl":.*,"nonce":"(.+)"}.$@m', $htmlString, $matches) !== 1) {
                    return ScrapeResult::PAGE_MODIFIED;
                }
                else {
                    $nonce = $matches[1];
                }
            }
            else {
                $htmlString = '<!doctype html><html lang="it-IT"><head></head><body><div id="container-posts">' . $htmlString . '</div></body>';
            }

            // add this line to suppress any warnings
            libxml_use_internal_errors(true);

            $doc = new \DOMDocument();
            $doc->loadHTML($htmlString);

            // https://www.w3schools.com/xml/xpath_syntax.asp
            $xpath = new \DOMXPath($doc);

            $articles = $xpath->evaluate('//div[@id="container-posts"]/article');

            if ($page === 1) {
                $tot = $xpath->evaluate('//div[@class="box-results-header"]/div/span/strong');

                if ($tot->count() === 0) {
                    return ScrapeResult::PAGE_MODIFIED;
                }
                else {
                    $totArticles = (int) $tot[0]->textContent;
                }
            }

            if ($articles->count() === 0) {
                if ($page === 1) {
                    return ScrapeResult::NO_DATA;
                }
                else {
                    $done = true;
                }
            }
            else {
                foreach ($articles as $article) {
                    $image = $xpath->evaluate('./figure/a/img', $article);
                    $time = $xpath->evaluate('./div/header/span', $article);
                    $title = $xpath->evaluate('./div/header/a', $article);
                    $tags = $xpath->evaluate('./div/footer/a', $article);

                    $item['image'] = ($image->count() === 0 ? '' : $image[0]->getAttribute('data-src'));
                    $item['time'] = ($time->count() === 0 ? '' : $time[0]->textContent);
                    $item['title'] = ($title->count() === 0 ? '' : $title[0]->textContent);
                    $item['link'] = ($title->count() === 0 ? '' : $title[0]->getAttribute('href')); // link
                    $item['tags'] = ($tags->count() === 0 ? '' : $tags[0]->textContent);

                    $this->scrapedItems->addItem($item);

                    $this->showProgress($count++, $totArticles);
                }

                $page++;
                sleep(1);
            }

        } while(!$done);

        return ScrapeResult::OK;
    }

    public static function getSite() : string
    {
        return 'https://www.trekking.it';
    }
}
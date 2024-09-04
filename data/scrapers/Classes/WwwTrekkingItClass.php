<?php

namespace App\Scrapers\Classes;

use Fuzzy\Fzpkg\Classes\BaseScrape;
use Fuzzy\Fzpkg\Enums\ScrapeResult;

class WwwTrekkingItClass extends BaseScrape
{
    public function doScrape(string $search) : ScrapeResult
    {
        // from BaseScrape
        $this->items = [];
                
        $httpClient = new \GuzzleHttp\Client();

        $page = 1;
        $count = 1;
        $done = false;
        $nonce = '';
        $totArticles = '';
        $origin = 'https://www.trekking.it';

        $referer = 'https://www.trekking.it/?s=' . $search;

        do {
            if ($page == 1) {
                $response = $httpClient->request('GET', $referer, [
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
                $response = $httpClient->request('POST', 'https://www.trekking.it/wp-admin/admin-ajax.php', [
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
                    'key' => (!empty($search) ? $search : '')
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
                    $_title = $xpath->evaluate('./div/header/a', $article);
                    $_tags = $xpath->evaluate('./div/footer/a', $article);

                    if ($_title->count() === 0) {
                        $title = '';
                        $link = '';
                    }
                    else {
                        $title = trim($_title[0]->textContent, " \n");
                        $link = trim($_title[0]->getAttribute('href'), " \n");
                    }

                    if ($_tags->count() === 0) {
                        $tags = '';
                        $tagsLink = '';
                    }
                    else {
                        $tags = trim($_tags[0]->textContent, " \n");
                        $tagsLink = $origin . trim($_tags[0]->getAttribute('href'), " \n");
                    }

                    $this->items[] = [
                        'image' => ($image->count() === 0 ? '' : trim($image[0]->getAttribute('data-src'), " \n")),
                        'time' =>  ($time->count() === 0 ? '' : trim($time[0]->textContent, " \n")),
                        'title' => $title,
                        'link' => $link,
                        'tags' => $tags,
                        'tagsLink' => $tagsLink
                    ];

                    $this->showProgress($count++, $totArticles);

                    //sleep(1);
                }

                $page++;
                sleep(1);
            }

        } while(!$done);

        return ScrapeResult::OK;
    }

    public function getSearchItems() : array
    {
        return [
            'toscana',
            ''
        ];
    }

    public function useProgressBar() : bool
    {
        return true;
    }
}
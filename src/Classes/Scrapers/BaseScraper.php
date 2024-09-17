<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Scrapers;

use GuzzleHttp\Client;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Fuzzy\Fzpkg\Enums\Scrapers\ScrapeResult;
use Fuzzy\Fzpkg\Enums\Scrapers\RunScraperResult;
use Fuzzy\Fzpkg\Classes\Scrapers\ScrapedItems;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BaseScraper 
{
    protected Client $httpClient;
    protected ScrapedItems $scrapedItems;
    private OutputStyle $outputStyle;
    private Factory $outputComponents;
    private ?ProgressBar $progressBar;
    private bool $useProgressBar;
    private int $sleepBetweenSearch;

    public function __construct(bool $useProgressBar = true, $sleepBetweenSearch = 0)
    {
        $this->httpClient = new Client();
        $this->scrapedItems = new ScrapedItems();
        $this->progressBar = null;
        $this->useProgressBar = $useProgressBar;
        $this->sleepBetweenSearch = $sleepBetweenSearch;
    }

    public function getSearchWords() : array
    {
        return [];
    }

    public function doScrape(string $search) : ScrapeResult
    {
        return ScrapeResult::NO_DATA;
    }

    public function finalize(RunScraperResult $result, string $outputDir, string $fileName, string $className, string $search) : void
    {
        /*if (!Schema::hasTable($className)) {
            Schema::create($className, function (Blueprint $table) {
                $table->id();
                $table->string('md5')->index();
                $table->text('search')->index();
                $table->text('image')->nullable();
                $table->string('time');
                $table->text('title')->index();
                $table->text('link')->index();
                $table->text('tags')->index();
                $table->timestamps();
            });
        }

        if (in_array($result, [ RunScraperResult::FILE_CREATED, RunScraperResult::FILE_UPDATED ])) {
            
        }*/

        return;
    }

    public static function getSite() : string
    {
        return '';
    }

    final public function sleepBetweenSearch() : int
    {
        return $this->sleepBetweenSearch;
    }

    final public function setOutput(OutputStyle $outputStyle, Factory $outputComponents) : void
    {
        $this->outputStyle = $outputStyle;
        $this->outputComponents = $outputComponents;
    }

    final public function showProgress(int $count, int $total) : void
    {
        // https://github.com/symfony/symfony/blob/7.2/src/Symfony/Component/Console/Helper/ProgressBar.php
        // https://symfony.com/doc/7.2/components/console/helpers/progressbar.html
        // https://www.php.net/manual/en/reserved.constants.php

        if ($this->useProgressBar) {
            if (empty($this->progressBar)) {
                $this->outputStyle->newLine();
                $this->progressBar = $this->outputStyle->createProgressBar($total);
                $this->progressBar->minSecondsBetweenRedraws(PHP_FLOAT_MIN);
                $this->progressBar->maxSecondsBetweenRedraws(PHP_FLOAT_MIN);
                $this->progressBar->setFormat('- %current%/%max% %bar% %percent:3s%% %elapsed:10s%/%estimated:-16s% %memory:6s%');
                $this->progressBar->start($total);
            }
            
            $this->progressBar->advance();
            
            if ($count === $total) {
                $this->progressBar->finish();
            }
        }
        else {
            $this->outputComponents->info('--- ' . $count . '/' . $total . ' ---');

            if (!$this->outputStyle->isQuiet()) {
                echo "\033[2A";
            }
        }
    }

    final public function resetProgress() : void
    {
        if (!empty($this->progressBar)) {
            $this->progressBar = null;
        }
    }

    final public function getScrapedItems() : ScrapedItems
    {
        return $this->scrapedItems;
    }

    final public function resetScrapedItems() : void
    {
        $this->scrapedItems->reset();
    }
}
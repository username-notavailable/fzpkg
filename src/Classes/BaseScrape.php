<?php

namespace Fuzzy\Fzpkg\Classes;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Fuzzy\Fzpkg\Enums\ScrapeResult;

class BaseScrape 
{
    protected array $items;
    private OutputStyle $outputStyle;
    private Factory $outputComponents;
    private $progressBar;

    public function getSearchItems() : array
    {
        return [];
    }

    public function doScrape(string $search) : ScrapeResult
    {
        return ScrapeResult::NO_DATA;
    }

    public function useProgressBar() : bool
    {
        return true;
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

        if ($this->useProgressBar() || !empty($this->progressBar)) {
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

    final public function getItemsArray() : array
    {
        return $this->items;
    }
}
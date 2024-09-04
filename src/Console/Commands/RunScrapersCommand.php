<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Illuminate\Filesystem\Filesystem;
use Fuzzy\Fzpkg\Enums\ScrapeResult;
use Illuminate\Support\Facades\Log;

final class RunScrapersCommand extends BaseCommand
{
    protected $signature = 'fz:run:scrapers';

    protected $description = 'Run scraper classes';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $outputDir = app_path('Scrapers/output');

        $fileSystem->ensureDirectoryExists($outputDir);

        $classes = glob(app_path('Scrapers/Classes') . DIRECTORY_SEPARATOR . '*Class.php');

        if (count($classes) > 0) {
            $this->newLine();
            $this->outText('info', '>>> Start scraping... <<<', true);    

            foreach ($classes as $class) {
                $className = basename($class, '.php');
                $classNamespace = '\App\Scrapers\Classes\\' . $className;
                $instance = new $classNamespace();
    
                if (!($instance instanceof \Fuzzy\Fzpkg\Classes\BaseScrape)) {
                    $this->outLabelledText('error', 'Class "' . $className . '" must extend "\Fuzzy\Fzpkg\Classes\BaseScrape"');
                }
                else {
                    $instance->setOutput($this->output, $this->outputComponents());

                    foreach ($instance->getSearchItems() as $search) {
                        $this->outText('line', '- Current class: ' . $className);
                        $this->outText('line', '- Search: "' . ($search === '' ? 'ALL' : $search) . '"');

                        $instance->resetProgress();
                        $scrapeResult = $instance->doScrape($search);

                        if ($scrapeResult === ScrapeResult::OK) {
                            $this->newLine()->newLine();

                            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $className . '__' . ($search === '' ? 'ALL' : $search) . '.json';

                            $itemsStr = json_encode($instance->getItemsArray());

                            if (file_exists($outputFile)) {
                                $writeFile = false;

                                $fileItemsStr = file_get_contents($outputFile);

                                if (!$fileItemsStr) {
                                    $this->outLabelledText('error', 'File "'. basename($outputFile) . '" not updated (read error)');
                                }
                                else {
                                    if (hash('md5', $itemsStr) === hash('md5', $fileItemsStr)) {
                                        $this->outLabelledText('info', 'File "'. basename($outputFile) . '" not updated (' . count($instance->getItemsArray()) . ' items unchanged)');
                                    }
                                    else {
                                        $writeFile = true;
                                    }
                                }
                            }
                            else {
                                $writeFile = true;
                            }

                            if ($writeFile) {
                                if (file_put_contents($outputFile, $itemsStr) === false) {
                                    $this->outLabelledText('error', 'File "'. basename($outputFile) . '" not updated (write error)');
                                }
                                else {
                                    $this->outLabelledText('success', 'File "'. basename($outputFile) . '" updated (' . count($instance->getItemsArray()) . ' items)');
                                }
                            }
                        }
                        else if ($scrapeResult === ScrapeResult::PAGE_MODIFIED) {
                            $this->outLabelledText('warning', '>>> The page structure was modified <<<');
                        }
                        else {
                            $this->outLabelledText('warning', '>>> No data found <<<');
                        }
                    }
                }
            }

            $this->outText('info', '>>> Scraping terminated <<<', true);
        }
        else {
            $this->outLabelledText('warning', 'No scraping classes found');
        }
    }

    protected function outText(string $type, string $message, bool $newLine = false) : void
    {
        Log::info($message, [$this->signature]);

        if ($type === 'info') {
            $this->line($message, 'info');
        }
        else {
            $this->line($message);
        }

        if ($newLine) {
            $this->newLine();
        }
    }

    protected function outLabelledText(string $level, string $message) : void
    {
        switch ($level) {
            case 'error':
                Log::error($message, [$this->signature]);
                $this->outputComponents()->error($message);
                break;

            case 'warning':
                Log::warning($message, [$this->signature]);
                $this->outputComponents()->warn($message);
                break;

            case 'info':
                Log::info($message, [$this->signature]);
                $this->outputComponents()->info($message);
                break;
            
            case 'success':
                Log::info($message, [$this->signature]);
                $this->outputComponents()->success($message);
                break;
        }
    }
}

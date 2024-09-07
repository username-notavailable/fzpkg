<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Classes\BaseScrape;
use Illuminate\Filesystem\Filesystem;
use Fuzzy\Fzpkg\Enums\ScrapeResult;
use Fuzzy\Fzpkg\Enums\RunScraperResult;
use Illuminate\Support\Facades\Log;

final class RunScrapersCommand extends BaseCommand
{
    protected $signature = 'fz:run:scrapers { --list : List the scraper classes } { --skip=* : Use --skip=files to skip the search (of every classes) if the output file already exists and/or use --skip=<class name case sensitive> to skip all the searches for the class }';

    protected $description = 'Run scraper classes';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $classes = glob(app_path('Scrapers/Classes') . DIRECTORY_SEPARATOR . '*Class.php');

        if ($this->option('list')) {
            if (count($classes) > 0) {
                $rows = array_map(function($class) { 
                    $className = basename($class, '.php');
                    $classNamespace = '\App\Scrapers\Classes\\' . $className;

                    return [ $className, $classNamespace::getSite() ]; 
                }, $classes);
                
                $this->newLine();
                $this->line('Scrapers:');
                $this->table(['Class', 'Site'], $rows);
                $this->newLine();
            }
            else {
                $this->outputComponents()->info('No scraping classes found');
            }
        }
        else {
            if (count($classes) > 0) {
                $this->newLine();
                $this->outText('info', '>>> Start scraping... <<<', true);    

                foreach ($classes as $class) {
                    $className = basename($class, '.php');
    
                    if (in_array($className, $this->option('skip'))) {
                        $this->outLabelledText('info', '--skip=' . $className  . ' (class "' . $className . '" skipped)');
                        continue;
                    }

                    $classNamespace = '\App\Scrapers\Classes\\' . $className;
                    $instance = new $classNamespace();

                    if (!($instance instanceof BaseScrape)) {
                        $this->outLabelledText('error', 'Class "' . $className . '" must extend "\Fuzzy\Fzpkg\Classes\BaseScrape"');
                    }
                    else {
                        $outputDir = app_path('Scrapers/output/' . $className);
    
                        $fileSystem->ensureDirectoryExists($outputDir);
    
                        $instance->setOutput($this->output, $this->outputComponents());
                        $searchWords = $instance->getSearchWords();
                        $searchCount = 1;
                        $searchMax = count($searchWords);
    
                        foreach ($searchWords as $search) {
                            $search = trim($search);
                            
                            $this->outText('line', '- Current class: ' . $className);
                            $this->outText('line', '- Search: "' . $search . '" - ' . $searchCount . '/' . $searchMax);
    
                            $instance->resetProgress();
                            $instance->resetScrapedItems();
    
                            $fileName = $className . '__#__' . preg_replace('@( |\')@', '_', $search) . '.json';
    
                            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $fileName;
                            $outputFileExists = file_exists($outputFile);
    
                            if ($outputFileExists && in_array('files', $this->option('skip'))) {
                                $this->outLabelledText('info', '--skip=files (File "'. basename($outputFile) . '" already exists; search "' . $search . '" skipped)');
                            }
                            else {
                                try {
                                    $scrapeResult = $instance->doScrape($search);
                                }catch (\Throwable $e) {
                                    $this->outLabelledText('error', 'Exception: ' . $e->getMessage());
                                    $instance->finalize(RunScraperResult::SCRAPER_EXCEPTION, $outputDir, $fileName, $className, $search);
                                    $scrapeResult = null;
                                }
                                
                                if ($scrapeResult === ScrapeResult::OK) {
                                    $this->newLine();
                            
                                    $itemsStr = $instance->getScrapedItems()->toJson();
                                    $itemsCount = $instance->getScrapedItems()->count();
    
                                    if ($itemsCount > 0) {
                                        $this->newLine();
                                    }
    
                                    if ($outputFileExists) {
                                        $writeFile = false;
    
                                        $fileItemsStr = file_get_contents($outputFile);
    
                                        if (!$fileItemsStr) {
                                            $this->outLabelledText('error', 'File "'. basename($outputFile) . '" not updated (read error)');
                                            $instance->finalize(RunScraperResult::READ_FILE_ERROR, $outputDir, $fileName, $className, $search);
                                        }
                                        else {
                                            if (hash('md5', $itemsStr) === hash('md5', $fileItemsStr)) {
                                                $this->outLabelledText('info', 'File "'. basename($outputFile) . '" not updated (' . $itemsCount  . ' items unchanged)');
                                                $instance->finalize(RunScraperResult::FILE_NO_NEED_UPDATE, $outputDir, $fileName, $className, $search);
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
                                            if ($outputFileExists) {
                                                $message = 'File "'. basename($outputFile) . '" not updated (write error)';
                                            }
                                            else {
                                                $message = 'File "'. basename($outputFile) . '" not created (write error)';
                                            }
    
                                            $this->outLabelledText('error', $message);
                                            $instance->finalize(RunScraperResult::WRITE_FILE_ERROR, $outputDir, $fileName, $className, $search);
                                        }
                                        else {
                                            if ($outputFileExists) {
                                                $message = 'File "'. basename($outputFile) . '" updated (' . $itemsCount  . ' items)';
                                                $result = RunScraperResult::FILE_UPDATED;
                                            }
                                            else {
                                                $message = 'File "'. basename($outputFile) . '" created (' . $itemsCount  . ' items)';
                                                $result = RunScraperResult::FILE_CREATED;
                                            }
    
                                            $this->outLabelledText('success', $message);
                                            $instance->finalize($result, $outputDir, $fileName, $className, $search);
                                        }
                                    }
                                }
                                else if ($scrapeResult === ScrapeResult::PAGE_MODIFIED) {
                                    $this->outLabelledText('warning', '>>> The page structure was modified <<<');
                                    $instance->finalize(RunScraperResult::PAGE_MODIFIED, $outputDir, $fileName, $className, $search);
                                }
                                else {
                                    $this->outLabelledText('warning', '>>> No data found <<<');
                                    $instance->finalize(RunScraperResult::NO_DATA, $outputDir, $fileName, $className, $search);
                                }
                            }
    
                            $searchCount++;
    
                            sleep($instance->sleepBetweenSearch());
                        }
                    }
                }
    
                $this->outText('info', '>>> Scraping terminated <<<', true);
            }
            else {
                $this->outLabelledText('warning', 'No scraping classes found');
            }
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

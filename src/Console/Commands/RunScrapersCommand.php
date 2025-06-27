<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Classes\Scrapers\BaseScraper;
use Illuminate\Filesystem\Filesystem;
use Fuzzy\Fzpkg\Enums\Scrapers\ScrapeResult;
use Fuzzy\Fzpkg\Enums\Scrapers\RunScraperResult;

final class RunScrapersCommand extends BaseCommand
{
    protected $signature = 'fz:run:scrapers { --list : List the scraper classes } { --skip=* : Use --skip=files to skip the search (of every classes) if the output file already exists and/or use --skip=<class name case sensitive> to skip all the searches for the class }';

    protected $description = 'Run scrapers classes';

    public function handle(): void
    {
        $this->enableOutLogs();
        
        $filesystem = new Filesystem();

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

                umask(0);
                ini_set('memory_limit', '-1');

                $this->newLine();
                $this->outInfo('>>> Start scraping... <<<');  
                $this->newLine();  

                foreach ($classes as $class) {
                    $className = basename($class, '.php');
    
                    if (in_array($className, $this->option('skip'))) {
                        $this->outLabelledInfo('--skip=' . $className  . ' (class "' . $className . '" skipped)');
                        continue;
                    }

                    $classNamespace = '\App\Scrapers\Classes\\' . $className;
                    $instance = new $classNamespace();

                    if (!($instance instanceof BaseScraper)) {
                        $this->outLabelledError('Class "' . $className . '" must extend "\Fuzzy\Fzpkg\Classes\Scrapers\BaseScraper"');
                    }
                    else {
                        $outputDir = app_path('Scrapers/output/' . $className);
    
                        $filesystem->ensureDirectoryExists($outputDir);
    
                        $instance->setOutput($this->output, $this->outputComponents());
                        $searchWords = $instance->getSearchWords();
                        $searchCount = 1;
                        $searchMax = count($searchWords);
    
                        foreach ($searchWords as $search) {
                            $search = trim($search);
                            
                            $this->outText('- Current class: ' . $className);
                            $this->outText('- Search: "' . $search . '" - ' . $searchCount . '/' . $searchMax);
    
                            $instance->resetProgress();
                            $instance->resetScrapedItems();
    
                            $fileName = $className . '__#__' . preg_replace('@( |\')@', '_', $search) . '.json';
    
                            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $fileName;
                            $outputFileExists = file_exists($outputFile);
    
                            if ($outputFileExists && in_array('files', $this->option('skip'))) {
                                $this->outLabelledInfo('--skip=files (File "'. basename($outputFile) . '" already exists; search "' . $search . '" skipped)');
                            }
                            /*else if ($outputFileExists && $this->option('force-finalize')) {
                                $this->outLabelledInfo('File "'. basename($outputFile) . '" already exists; --force-finalize is set... call finalize())');

                                $finalizeJson = file_get_contents($outputFile);
    
                                if (!$finalizeJson) {
                                    $this->outLabelledError('Read file "'. basename($outputFile) . '" error');
                                }
                                else {
                                    $finalizeResult = $instance->finalize(RunScraperResult::FILE_NO_NEED_UPDATE, $outputDir, $fileName, $className, $search, $finalizeJson);

                                    if ($finalizeResult->message !== '') {
                                        if ($finalizeResult->hasError) {
                                            $this->outLabelledWarning('>>> Finalize: ' . $finalizeResult->message . ' <<<');
                                        }
                                        else {
                                            $this->outLabelledSuccess($finalizeResult->message);
                                        }
                                    }
                                }
                            }*/
                            else {
                                try {
                                    $scrapeResult = $instance->doScrape($search);
                                }catch (\Throwable $e) {
                                    $this->outLabelledError('Exception: ' . $e->getMessage());
                                    $instance->finalize(RunScraperResult::SCRAPER_EXCEPTION, $outputDir, $fileName, $className, $search, '[]');
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
                                            $this->outLabelledError('File "'. basename($outputFile) . '" not updated (read error)');
                                            
                                            $finalizeResult = RunScraperResult::READ_FILE_ERROR;
                                            $finalizeJson = '[]';
                                        }
                                        else {
                                            if (hash('md5', $itemsStr) === hash('md5', $fileItemsStr)) {
                                                $this->outLabelledInfo('File "'. basename($outputFile) . '" not updated (' . $itemsCount  . ' items unchanged)');
                                                
                                                $finalizeResult = RunScraperResult::FILE_NO_NEED_UPDATE;
                                                $finalizeJson = $itemsStr;
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
    
                                            $this->outLabelledError($message);
                                            
                                            $finalizeResult = RunScraperResult::WRITE_FILE_ERROR;
                                            $finalizeJson = '[]';
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
    
                                            $this->outLabelledSuccess($message);

                                            $finalizeResult = $result;
                                            $finalizeJson = $itemsStr;
                                        }
                                    }
                                }
                                else if ($scrapeResult === ScrapeResult::PAGE_MODIFIED) {
                                    $this->outLabelledWarning('>>> The page structure was modified <<<');
                                    $finalizeResult = RunScraperResult::PAGE_MODIFIED;
                                    $finalizeJson = '[]';
                                }
                                else if ($scrapeResult === ScrapeResult::HTTP_REQUEST_ERROR) {
                                    $this->outLabelledWarning('>>> HTTP request error <<<');
                                    $finalizeResult = RunScraperResult::HTTP_REQUEST_ERROR;
                                    $finalizeJson = '[]';
                                }
                                else {
                                    $this->outLabelledWarning('>>> No data found <<<');
                                    $finalizeResult = RunScraperResult::NO_DATA;
                                    $finalizeJson = '[]';
                                }

                                $this->outLabelledInfo('Run finalize...');

                                $finalizeResult = $instance->finalize($finalizeResult, $outputDir, $fileName, $className, $search, $finalizeJson);

                                if ($finalizeResult->message !== '') {
                                    if ($finalizeResult->hasError) {
                                        $this->outLabelledWarning('>>> Finalize: ' . $finalizeResult->message . ' <<<');
                                    }
                                    else {
                                        $this->outLabelledSuccess($finalizeResult->message);
                                    }
                                }
                                else {
                                    $this->outLabelledSuccess('Finish');
                                }
                            }
    
                            $searchCount++;
    
                            sleep($instance->sleepBetweenSearch());
                        }
                    }
                }
    
                $this->outInfo('>>> Scraping terminated <<<');
                $this->newLine();
            }
            else {
                $this->outLabelledWarning('No scraping classes found');
            }
        }
    }
}

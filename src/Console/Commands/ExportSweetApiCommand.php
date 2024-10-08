<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Illuminate\Filesystem\Filesystem;

final class ExportSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:sweetapi:export { apiName : SweetApi Folder (case sensitive) }';

    protected $description = 'Export SweetAPI directory';

    public function handle(): void
    {
        if (defined('SWEET_LARAVEL_FOR')) {
            $this->fail('Command unavailable from inside a SweetAPI directory');
        }

        $filesystem = new Filesystem();

        $apiName = $this->argument('apiName');
        $targetSweetApiPath = base_path('sweets/' . $apiName);

        if (!$filesystem->exists($targetSweetApiPath)) {
            $this->fail('SweetAPI "' . $apiName . '" not exists');
        }

        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone(config('app.timezone', 'UTC')));
        
		$zip = new \ZipArchive();

        $zipFilePath = Utils::makeFilePath(base_path('sweets'), $apiName . '_' . $date->format('Ymd__His') . '.zip');

        $this->outLabelledInfo('Creation of ZIP file for SweetAPI "' . $apiName . '" in progres...');
		
		if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== true) {
			$this->fail('Creation of ZIP file for SweetAPI "' . $apiName . '" failed (open)');
		}
        else {
            $rootPath = realpath($targetSweetApiPath);
            $zipPathOffset = mb_strlen($rootPath) - mb_strlen(basename($rootPath));

            $files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($rootPath),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ($files as $file)
			{
				if (!$file->isDir())
				{
					$filePath = $file->getRealPath();
					$relativePath = mb_substr($filePath, $zipPathOffset);

                    // Skip vendor directory contents and composer.lock file
                    if (str_starts_with($relativePath, $apiName . '/vendor') || str_starts_with($relativePath, $apiName . '/composer.lock')) {
                        continue;
                    }

					if (!$zip->addFile($filePath, $relativePath)) {
						$this->fail('Creation of ZIP file for SweetAPI "' . $apiName . '" failed (add)');
					}
				}
			}

            if (!$zip->close()) {
                $this->fail('Creation of ZIP file for SweetAPI "' . $apiName . '" failed (close)');
            }
        }

        $this->outLabelledSuccess('ZIP file created for SweetAPI "' . $apiName . '" (' . basename($zipFilePath) . ')');
    }
}

<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Illuminate\Filesystem\Filesystem;

final class InstallSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:install:sweetapi { apiName : SweetApi Folder (case sensitive) }';

    protected $description = 'Install new SweetAPI';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $apiName = $this->argument('apiName');
        $sweetApiPath = app_path('Http/SweetApi');
        $newSweetApiPath = app_path('Http/SweetApi/' . $apiName);

        $filesystem->ensureDirectoryExists($sweetApiPath);

        if ($filesystem->exists($newSweetApiPath)) {
            $this->fail('SweetAPI "' . $apiName . '" already exists');
        }

        $filesystem->copyDirectory(__DIR__.'/../../../data/sweetapi', $newSweetApiPath);

        $apiEnvFilePath = app_path('Http/SweetApi/' . $apiName . '/bootstrap/.env');

        $filesystem->copy(base_path('.env'), $apiEnvFilePath);

        $targets = [
            'SWEETAPI_TITLE=' => [
                'from' => 'SWEETAPI_TITLE=.*$',
                'to' => 'SWEETAPI_TITLE=\'SweetAPI "' . $apiName . '"\'',
            ],
            'SWEETAPI_SUMMARY=' => [
                'from' => 'SWEETAPI_SUMMARY=.*$',
                'to' => 'SWEETAPI_SUMMARY=',
            ],
            'SWEETAPI_DESCRIPTION=' => [
                'from' => 'SWEETAPI_DESCRIPTION=.*$',
                'to' => 'SWEETAPI_DESCRIPTION=',
            ],
            'SWEETAPI_TERMS_OF_SERVICE=' => [
                'from' => 'SWEETAPI_TERMS_OF_SERVICE=.*$',
                'to' => 'SWEETAPI_TERMS_OF_SERVICE=',
            ],
            'SWEETAPI_CONTACT_NAME=' => [
                'from' => 'SWEETAPI_CONTACT_NAME=.*$',
                'to' => 'SWEETAPI_CONTACT_NAME=',
            ],
            'SWEETAPI_CONTACT_URL=' => [
                'from' => 'SWEETAPI_CONTACT_URL=.*$',
                'to' => 'SWEETAPI_CONTACT_URL=',
            ],
            'SWEETAPI_CONTACT_EMAIL=' => [
                'from' => 'SWEETAPI_CONTACT_EMAIL=.*$',
                'to' => 'SWEETAPI_CONTACT_EMAIL=',
            ],
            'SWEETAPI_LICENSE_NAME=' => [
                'from' => 'SWEETAPI_LICENSE_NAME=.*$',
                'to' => 'SWEETAPI_LICENSE_NAME=',
            ],
            'SWEETAPI_LICENSE_SPDX=' => [
                'from' => 'SWEETAPI_LICENSE_SPDX=.*$',
                'to' => 'SWEETAPI_LICENSE_SPDX=',
            ],
            'SWEETAPI_LICENSE_URL=' => [
                'from' => 'SWEETAPI_LICENSE_URL=.*$',
                'to' => 'SWEETAPI_LICENSE_URL=',
            ],
            'SWEETAPI_OPEN_API_DOC_VERSION=' => [
                'from' => 'SWEETAPI_OPEN_API_DOC_VERSION=.*$',
                'to' => 'SWEETAPI_OPEN_API_DOC_VERSION=',
            ],
            'SWEETAPI_EXT_DOC_URL=' => [
                'from' => 'SWEETAPI_EXT_DOC_URL=.*$',
                'to' => 'SWEETAPI_EXT_DOC_URL=',
            ],
            'SWEETAPI_EXT_DESCRIPTION=' => [
                'from' => 'SWEETAPI_EXT_DESCRIPTION=.*$',
                'to' => 'SWEETAPI_EXT_DESCRIPTION=',
            ],
        ];

        $this->updateEnvFileOrAppend($targets, $apiEnvFilePath);

        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/Endpoints.php'));
        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/SwaggerEndpoints.php'));

        $this->outLabelledSuccess('Fuzzy SweetAPI "' . $apiName . '" installed');
    }
}

<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Swagger;

class OpenApiObject
{
    public string $openapi = '';
    public InfoObject $info;
    public string $jsonSchemaDialect = '';
    protected array $servers = [];
    protected array $paths = [];
    protected array $webhooks = [];
    protected array $security = [];
    protected array $tags = [];
    //public $externalDocs;

    public function __construct()
    {
        $this->openapi = '3.1.0';
        $this->info = new InfoObject();
    }
}
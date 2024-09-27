<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Swagger;

class InfoObject
{
    public string $title = '';
    public string $summary = '';
    public string $description = '';
    public string $termsOfService;
    //public ContactObject $contact;
    //public LicenseObject $license;
    public string $version = '';

    public function __construct()
    {}
}
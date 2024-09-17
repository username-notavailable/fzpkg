<?php

namespace Fuzzy\Fzpkg\Enums\Scrapers;

enum ScrapeResult
{
    case OK;
    case PAGE_MODIFIED;
    case NO_DATA;
}
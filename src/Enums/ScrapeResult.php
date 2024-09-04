<?php

namespace Fuzzy\Fzpkg\Enums;

enum ScrapeResult
{
    case OK;
    case PAGE_MODIFIED;
    case NO_DATA;
}
<?php

namespace Fuzzy\Fzpkg\Enums;

enum RunScraperResult
{
    case PAGE_MODIFIED;
    case NO_DATA;
    case FILE_NO_NEED_UPDATE;
    case FILE_CREATED;
    case FILE_UPDATED;
    case WRITE_FILE_ERROR;
    case READ_FILE_ERROR;
    case SCRAPER_EXCEPTION;
}
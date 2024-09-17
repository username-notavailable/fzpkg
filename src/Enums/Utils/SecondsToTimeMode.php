<?php

namespace Fuzzy\Fzpkg\Enums\Utils;

enum SecondsToTimeMode: int
{
    case ROUND_NONE = 0;
    case ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    case ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    case ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    case ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
}
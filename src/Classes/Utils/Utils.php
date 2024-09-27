<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils;

use Fuzzy\Fzpkg\Enums\Utils\SecondsToTimeType;
use Fuzzy\Fzpkg\Enums\Utils\SecondsToTimeMode;

class Utils 
{
    public static function secondsToTime(float $seconds, SecondsToTimeType $type = SecondsToTimeType::HMS, SecondsToTimeMode $mode = SecondsToTimeMode::ROUND_HALF_DOWN) : array
    {
        if ($mode !== SecondsToTimeMode::ROUND_NONE) {
            $seconds = round($seconds, 0, $mode->value);
        }

        $steps['d'] = 60 * 60 * 24;
        $steps['h'] = 60 * 60;
        $steps['m'] = 60;

        $time = [];

        foreach (($type === SecondsToTimeType::DHMS ? ['d', 'h', 'm'] : ['h', 'm']) as $stepIdx) {
            $time[$stepIdx] = (int)($seconds / $steps[$stepIdx]);
            $seconds -= $time[$stepIdx] * $steps[$stepIdx];
        }
        
        $time['s'] = $seconds;

        return $time;
    }

    public static function secondsToTimeString(float $seconds, bool $skipZero = true, SecondsToTimeType $type = SecondsToTimeType::HMS, SecondsToTimeMode $mode = SecondsToTimeMode::ROUND_HALF_DOWN) : string
    {
        $time = self::secondsToTime($seconds, $type, $mode);
        $timeStrArray = [];

        foreach (($type === SecondsToTimeType::DHMS ? ['d', 'h', 'm'] : ['h', 'm']) as $stepIdx) {
            if ($time[$stepIdx] === 0 && $skipZero) {
                continue;
            }

            $timeStrArray[] = $time[$stepIdx] . $stepIdx;
        }

        if ($time['s'] === 0 && $skipZero) {
            if (empty($timeStrArray)) {
                $timeStrArray[] = $time['s'] . 's';
            }
        }
        else {
            $timeStrArray[] = $time['s'] . 's';
        }

        return implode(':', $timeStrArray);
    }

    public static function metersToDistance(int $meters) : array
    {
        $steps['Km'] = 1000;

        $distance = [];

        foreach (['Km'] as $stepIdx) {
            $distance[$stepIdx] = (int)($meters / $steps[$stepIdx]);
            $meters -= $distance[$stepIdx] * $steps[$stepIdx];
        }
        
        $distance['m'] = $meters;

        return $distance;
    }

    public static function metersToDistanceString(int $meters, bool $skipZero = true) : string
    {
        $distance = self::metersToDistance($meters);
        $distanceStrArray = [];

        foreach (['Km'] as $stepIdx) {
            if ($distance[$stepIdx] === 0 && $skipZero) {
                continue;
            }

            $distanceStrArray[] = $distance[$stepIdx] . $stepIdx;
        }

        if ($distance['m'] === 0 && $skipZero) {
            if (empty($distanceStrArray)) {
                $distanceStrArray[] = $distance['m'] . 'm';
            }
        }
        else {
            $distanceStrArray[] = $distance['m'] . 'm';
        }

        return implode(':', $distanceStrArray);
    }

    public static function makeFilePath(...$pathParts) : string
    {
        return implode(DIRECTORY_SEPARATOR, array_filter(array_filter((array)$pathParts), function($value) { return !empty($value) && is_string($value); }));
    }

    public static function makeDirectoryPath(...$pathParts) : string
    {
        return implode(DIRECTORY_SEPARATOR, array_filter(array_filter((array)$pathParts), function($value) { return !empty($value) && is_string($value); })) . DIRECTORY_SEPARATOR;
    }

    public static function makeNamespacePath(...$pathParts) : string
    {
        return implode('\\', array_filter(array_filter((array)$pathParts), function($value) { return !empty($value) && is_string($value); }));
    }

    public static function contentTypeFromExtension($filename) : string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($extension)
        {
            case 'html':
                $contentType = 'text/html';
                break;

            case 'json':
                $contentType = 'application/json';
                break;

            case 'js':
                $contentType = 'application/javascript';
                break;

            case 'css':
                $contentType = 'text/css';
                break;

            case 'png':
                $contentType = 'image/' . $extension;
                break;

            case 'map':
                $contentType = 'plain/text';
                break;

            default:
                $contentType = 'application/octet-stream';
                break;
        }

        return $contentType;
    }
}
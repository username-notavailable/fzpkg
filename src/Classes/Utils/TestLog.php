<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class TestLog
{
    public static function log(string $message, array $context = [], string $level = 'debug')
    {
        if (config('fz.log.testLogEnabled')) {
            Log::log($level, $message, $context);
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (config('fz.log.testLogEnabled') && $request->has('testlog-id')) {
            Log::withContext([
                'testlog-id' => 'TestLogId___' . $request->input('testlog-id')
            ]);
        }

        return $next($request);
    }
}
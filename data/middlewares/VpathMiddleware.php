<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
 
class VpathMiddleware
{
    public function handle(Request $request, Closure $next, string $viewSubPath = 'vpath') : Response
    {
        if (strtolower($request->method()) === 'get') {
            if ($request->route()->hasParameter('vpath')) {
                $vpath = strtolower(rtrim($request->route('vpath'), '/'));

                $urlPath = resource_path('views' . DIRECTORY_SEPARATOR . $viewSubPath . DIRECTORY_SEPARATOR . preg_replace('@/@', DIRECTORY_SEPARATOR, $vpath));

                if (is_dir($urlPath) || is_file($urlPath . '.blade.php')) {
                    if (is_dir($urlPath)) {
                        $urlPath = realpath($urlPath);
                    }
                    else {
                        $urlPath = realpath($urlPath . '.blade.php');
                    }

                    if (!str_starts_with($urlPath, realpath(resource_path('views' . DIRECTORY_SEPARATOR . $viewSubPath . DIRECTORY_SEPARATOR)))) {
                        Log::warning('"' . $urlPath . '" is invalid as views filesystem path');
                    }
                    else {
                        if (is_dir($urlPath)) {
                            $__dirFilePath = $urlPath . DIRECTORY_SEPARATOR . '__dir.blade.php';

                            if (is_file($__dirFilePath)) {
                                $itemViewPath = $viewSubPath . '.' . preg_replace('@/@', '.', $vpath) . '.__dir';

                                return response()->view($itemViewPath);
                            }
                            else {
                                Log::debug('Directory "' . $urlPath . '" exists but "__dir.blade.php" file not found');
                            }
                        }
                        else {
                            if (is_file($urlPath)) {
                                $itemViewPath = $viewSubPath . '.' . preg_replace('@/@', '.', $vpath);

                                return response()->view($itemViewPath);
                            }
                            else {
                                Log::debug('File "' . $urlPath . '.blade.php" not found');
                            }
                        }
                    }
                }
            }
            else {
                Log::warning('vpath route parameter is mandatory for "VpathMiddleware" middleware');
            }

            abort(404);
        }
        else {
            return $next($request);
        }
    }
}
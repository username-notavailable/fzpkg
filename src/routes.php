<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Fuzzy\Fzpkg\Classes\SweetApi\Classes\HtmxRequest;

Route::middleware(['web'])->group(function () {
    Route::get('/.well-known/js/translations.js', function (Request $request) {
        if (is_string($request->cookie(config('fz.default.localeCookieName')))) {
            $useLocale = $request->cookie(config('fz.default.localeCookieName'));

            if (!in_array($useLocale, array_keys(config('fz.i18n.locales')))) {
                $useLocale = config('fz.default.locale');
            }
        }
        else {
            $useLocale = config('fz.default.locale');
        }

        $languageFilePath = base_path('lang/' . $useLocale . '.json');

        if (file_exists($languageFilePath)) {
            if (config('app.env') === 'production') {
                $strings = Cache::rememberForever('lang_'.$useLocale.'.js', function () use ($languageFilePath) {
                    return file_get_contents($languageFilePath);
                });
            }
            else {
                $strings = file_get_contents($languageFilePath);
            }

            return response('window.i18n = ' . trim($strings) . ';', 200)->header('content-type', 'text/javascript');
        }
        else {
            Log::debug(__METHOD__ . ': Language file "' . $languageFilePath . '" not found');
        }

        return response('', 404);
    })->name('fz_locale_js_translations');

    Route::any('/.well-known/htmx/link', function (Request $request) {
        $validator = Validator::make($request->all(), [
            '__fz_c__' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z_]+[a-zA-Z0-9_]*$/'],
            '__fz_cm__' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z_]+[a-zA-Z0-9_]*$/'],
        ]);

        if ($validator->fails()) {
            Log::debug(__METHOD__ . ': validazione richiesta fallita', $validator->errors()->toArray());
        }
        else {
            $files = new Filesystem();

            if ($files->exists(app_path('View/Components/' . $request->__fz_c__ . 'Htmx.php'))) {
                $object = new \ReflectionClass(Utils::makeNamespacePath('App', 'View', 'Components', $request->__fz_c__ . 'Htmx'));

                $requestedMethodName = $request->__fz_cm__;
                
                foreach ($object->getMethods(\ReflectionProperty::IS_PUBLIC) as $method) {
                    if ($method->getName() === $requestedMethodName) {
                        return $method->invoke($object->newInstance(), HtmxRequest::createFrom($request));
                    }
                }

                Log::debug(__METHOD__ . ': Class method "' . $request->__fz_c__ . 'Htmx::' . $requestedMethodName . '()" not found');
            }
            else if ($files->exists(app_path('View/Components/' . $request->__fz_c__ . '.php'))) {
                $object = new \ReflectionClass(Utils::makeNamespacePath('App', 'View', 'Components', $request->__fz_c__));

                $requestedMethodName = $request->__fz_cm__ . 'Htmx';
                
                foreach ($object->getMethods(\ReflectionProperty::IS_PUBLIC) as $method) {
                    if ($method->getName() === $requestedMethodName) {
                        return $method->invoke($object->newInstance(), HtmxRequest::createFrom($request));
                    }
                }

                Log::debug(__METHOD__ . ': Class method "' . $request->__fz_c__ . '::' . $requestedMethodName . '()" not found');
            }
            else {
                Log::debug(__METHOD__ . ': Class "' . $request->__fz_c__ . '" not found');
            }
        }

        return response('', 422);
    })->name('fz_htmx_link');

    Route::get('/.well-known/auth/authorizazion-code/v1/callback', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'session_state' => 'required|string|max:2000',
            'iss' => 'required|string|max:200',
            'code' => 'required|string|max:2000',
            'state' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            Log::debug(__METHOD__ . ': validazione richiesta fallita', $validator->errors());
            return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
        }
        else {
            $parts = explode('|', $request->state);

            if (count($parts) !== 2) {
                Log::debug(__METHOD__ . ': Check "state" 1/2 failed', $parts);
                return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
            }
            else if (hash('md5', config('app.key') . $parts[0]) !== $parts[1]) {
                Log::debug(__METHOD__ . ': Check "state" 2/2 failed', ['clientIdx' => $parts[0], 'expectedMd5' => $parts[1], 'md5' => hash('md5', config('app.key') . $parts[0])]);
                return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
            }
            else {
                $keycloakClientIdx = $parts[0];

                Auth::guard(config('fz.default.keycloak.authGuardName'))->kcClient->setKeycloakClientIdxData($keycloakClientIdx);

                if (Auth::guard(config('fz.default.keycloak.authGuardName'))->doUserFrontendLogin($request->code, route('fz_authorization_code_callback'), $request->session_state)) {
                    $jsonToken = Auth::guard(config('fz.default.keycloak.authGuardName'))->getClientIdxUserToken($keycloakClientIdx);
                    $decoded = Client::decodeAccessTokenPayload($jsonToken['access_token']);
                    
                    event(LoginRequest::successResult($request, $decoded['email'], 'authorization_code'));

                    session()->regenerate();

                    Log::debug('redirect alla rotta "fz.keycloak.login.postRouteName" (' . config('fz.keycloak.login.postRouteName') . ' = ' . route(config('fz.keycloak.login.postRouteName')) . ')');

                    return redirect()->route(config('fz.keycloak.login.postRouteName'));
                }
                else {
                    return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Login fallita.')]);
                }
            }
        }
    })->name('fz_authorization_code_callback');

    Route::get('/.well-known/auth/end-user-session/v1/callback', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'state' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            Log::debug(__METHOD__ . ': validazione richiesta fallita', $validator->errors());
            return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
        }
        else {
            $parts = explode('|', $request->state);

            if (count($parts) !== 2) {
                Log::debug(__METHOD__ . ': Check "state" 1/2 failed', $parts);
                return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
            }
            else if (hash('md5', config('app.key') . $parts[0]) !== $parts[1]) {
                Log::debug(__METHOD__ . ': Check "state" 2/2 failed', ['clientIdx' => $parts[0], 'expectedMd5' => $parts[1], 'md5' => hash('md5', config('app.key') . $parts[0])]);
                return view(config('fz.keycloak.login.issuesViewName'), ['lead' => __('Callback da IdP fallita.')]);
            }
            else {
                $keycloakClientIdx = $parts[0];

                Log::debug('logout dal clientIdx "' . $keycloakClientIdx . '"');
                Auth::guard(config('fz.default.keycloak.authGuardName'))->doUserLogout($keycloakClientIdx);

                Log::debug('redirect alla rotta "fz.keycloak.logout.postRouteName" (' . config('fz.keycloak.logout.postRouteName') . ' = ' . route(config('fz.keycloak.logout.postRouteName')) . ')');
                return redirect()->route(config('fz.keycloak.logout.postRouteName'));
            }
        }
    })->name('fz_end_user_session_callback');
});

Route::middleware(['web', 'api'])->group(function () {
    Route::get('/.well-known/jwks', function () {
        $file = storage_path('app/jwks.json');

        if (file_exists($file)) {
            return response(file_get_contents($file), 200)->header('content-type', 'application/json');
        }
        else {
            Log::warning(__METHOD__ . ': File "' . $file . '" not found');
            return response('', 404);
        }
    });
});
<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CaptureUtmParameters;
use App\Http\Middleware\CheckModule;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\DoctorAuth;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PatientAuth;
use App\Http\Middleware\SecretaryAuth;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\WebmasterAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->prefix('doctor')
                ->group(base_path('routes/doctor.php'));

            Route::middleware('web')
                ->prefix('secretary')
                ->group(base_path('routes/secretary.php'));

            Route::middleware('web')
                ->prefix('webmaster')
                ->group(base_path('routes/webmaster.php'));

            Route::middleware('web')
                ->prefix('{locale}/patient')
                ->where(['locale' => 'ar|en'])
                ->group(base_path('routes/patient.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
            CaptureUtmParameters::class,
            \App\Http\Middleware\QueryCountMonitor::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/paymob',
            'webhooks/stripe',
            'webhooks/whatsapp',
            'webhooks/sms/*',
            'unsubscribe/*',
        ]);

        $middleware->alias([
            'admin.auth' => AdminAuth::class,
            'doctor.auth' => DoctorAuth::class,
            'secretary.auth' => SecretaryAuth::class,
            'webmaster.auth' => WebmasterAuth::class,
            'patient.auth' => PatientAuth::class,
            'permission' => CheckPermission::class,
            'module' => CheckModule::class,
            'branch.context' => \App\Http\Middleware\SetActiveBranch::class,
        ]);

        // SetActiveBranch must pin the active branch BEFORE route-model binding
        // (SubstituteBindings) resolves {model} params — otherwise implicit
        // binding queries run under the fallback branch and a non-default-branch
        // user can't open their own records (and cross-branch isolation relies on
        // the wrong branch). Give it priority just ahead of SubstituteBindings.
        $middleware->priority([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            AdminAuth::class,
            DoctorAuth::class,
            SecretaryAuth::class,
            WebmasterAuth::class,
            PatientAuth::class,
            \App\Http\Middleware\SetActiveBranch::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            CheckPermission::class,
            CheckModule::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle PostTooLargeException - redirect back with error message
        $exceptions->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The uploaded file is too large. Please upload a file smaller than 20MB.',
                ], 413);
            }

            return back()->with([
                'error' => 'حجم الملف كبير جداً. الرجاء رفع ملف أصغر من 20 ميجابايت. / The uploaded file is too large. Please upload a file smaller than 20MB.',
            ]);
        });

        $exceptions->respond(function ($response, $exception, $request) {
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                return \Inertia\Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            } elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'error' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();

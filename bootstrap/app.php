<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    // Menentukan routing web, command Artisan, dan pemeriksaan kesehatan.
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Mendaftarkan alias middleware berdasarkan peran pengguna.
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    // Menyiapkan penanganan pengecualian terpusat aplikasi.
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (
                $exception->getStatusCode() === 419
                && $request->routeIs('logout')
                && $request->isMethod('post')
                && auth()->guest()
            ) {
                return redirect()->route('login')->with(
                    'status',
                    'Sesi Anda sudah berakhir. Silakan masuk kembali.'
                );
            }

            return null;
        });
    })->create();

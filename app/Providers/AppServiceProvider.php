<?php

namespace App\Providers;

use App\Http\Middleware\AdminAuthentication;
use App\Http\Middleware\TeacherAuthentication;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            AdminAuthentication::class,
            TeacherAuthentication::class,
        ]);

        Str::macro('linkify', fn (?string $text): string => preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener" class="text-blue-600 hover:underline">$1</a>',
            e($text ?? '')
        ));
    }
}

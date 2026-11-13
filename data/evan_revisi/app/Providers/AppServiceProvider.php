<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Material;
use App\Models\MataKuliah;
use App\Models\Submission;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

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
        User::observe(AuditObserver::class);
        MataKuliah::observe(AuditObserver::class);
        Material::observe(AuditObserver::class);
        Assignment::observe(AuditObserver::class);
        Submission::observe(AuditObserver::class);
    }
}

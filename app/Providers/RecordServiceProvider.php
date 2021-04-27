<?php

namespace App\Providers;

use App\Services\APIRecordService;
use App\Services\RecordService;
use Illuminate\Support\ServiceProvider;

class RecordServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RecordService::class, APIRecordService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

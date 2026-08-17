<?php

namespace App\Providers;

use App\Models\VolunteerCampaign;
use App\Observers\VolunteerCampaignObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        VolunteerCampaign::observe(VolunteerCampaignObserver::class);
    }
}
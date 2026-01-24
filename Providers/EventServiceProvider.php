<?php

namespace Modules\CHNetTRAK\Providers;

use App\Events\CronFiveMinute;
use App\Events\TestEvent;
use Modules\CHNetTRAK\Listeners\ProcessVatsimFlights;
use Modules\CHNetTRAK\Listeners\TestEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        CronFiveMinute::class => [
            ProcessVatsimFlights::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot()
    {
        parent::boot();
    }
}

<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SocialiteWasCalled::class => [
            // إضافة مزود Microsoft
            'SocialiteProviders\\Microsoft\\MicrosoftExtendSocialite@handle',
        ],
        // Login::class => [
        //     \App\Listeners\LinkMicrosoftAccount::class,
        // ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Queue::after(function (JobProcessed $event) {
            if ($event->job->resolveName() === 'App\\Jobs\\Domain\\CreateDomainProceesJob') {
                Log::info('✅ CreateloginAndTokenJob completed - Chain finished!');

            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

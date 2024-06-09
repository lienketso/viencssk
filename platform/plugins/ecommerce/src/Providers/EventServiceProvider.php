<?php

namespace Botble\Ecommerce\Providers;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Ecommerce\Events\OrderCompletedEvent;
use Botble\Ecommerce\Events\RegisterCollaboratorEvent;
use Botble\Ecommerce\Listeners\AddLanguageForVariantsListener;
use Botble\Ecommerce\Listeners\OrderCompleteListener;
use Botble\Ecommerce\Listeners\RegisterCollaboratorListener;
use Botble\Ecommerce\Listeners\RenderingSiteMapListener;
use Botble\Ecommerce\Listeners\SendMailsAfterCustomerRegistered;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        RenderingSiteMapEvent::class => [
            RenderingSiteMapListener::class,
        ],
        CreatedContentEvent::class  => [
            AddLanguageForVariantsListener::class,
        ],
        UpdatedContentEvent::class  => [
            AddLanguageForVariantsListener::class,
        ],
        Registered::class  => [
            SendMailsAfterCustomerRegistered::class,
        ],
        RegisterCollaboratorEvent::class => [
            RegisterCollaboratorListener::class,
        ],
        OrderCompletedEvent::class => [
            OrderCompleteListener::class
        ]
    ];
}

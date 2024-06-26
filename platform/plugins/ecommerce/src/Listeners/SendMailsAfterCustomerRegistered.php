<?php

namespace Botble\Ecommerce\Listeners;

use Botble\Ecommerce\Models\Customer;
use EcommerceHelper;
use EmailHandler;
use Illuminate\Auth\Events\Registered;

class SendMailsAfterCustomerRegistered
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Registered $event)
    {
        $customer = $event->user;

        if (get_class($customer) == Customer::class) {
            EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'customer_name' => $customer->name,
                ])
                ->sendUsingTemplate('welcome', $customer->email);

            if (EcommerceHelper::isEnableEmailVerification()) {

                // Notify the user
                $notificationConfig = config('plugins.ecommerce.general.customer.notification');
                $customer->sendEmailVerificationNotification();
                if ($notificationConfig) {
                    $notification = app($notificationConfig);
                    $customer->notify($notification);
                }
            }
        }
    }
}

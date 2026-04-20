<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact & demo request inbox
    |--------------------------------------------------------------------------
    |
    | The contact form sends email FROM mail.from (MAIL_FROM_ADDRESS / MAIL_FROM_NAME)
    | TO this address. Set CONTACT_EMAIL to your operations inbox (e.g. a Gmail address).
    | If CONTACT_EMAIL is unset, MAIL_FROM_ADDRESS is used as the recipient fallback.
    |
    */

    'recipient' => env('CONTACT_EMAIL') ?: env('MAIL_FROM_ADDRESS', 'support@picklecorner.ph'),

];

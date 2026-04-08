<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact & demo request inbox
    |--------------------------------------------------------------------------
    |
    | Submissions from the public contact form are sent here. Defaults to
    | MAIL_FROM_ADDRESS when CONTACT_EMAIL is not set (override in .env).
    |
    */

    'recipient' => env('CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Privacy policy version
    |--------------------------------------------------------------------------
    |
    | Bump when the published privacy policy materially changes so consent
    | records can be correlated (Philippines DPA / NPC accountability).
    |
    */
    'policy_version' => env('DATA_PRIVACY_POLICY_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Effective date (YYYY-MM-DD) for the published policy text
    |--------------------------------------------------------------------------
    */
    'policy_effective_date' => env('DATA_PRIVACY_POLICY_EFFECTIVE_DATE', '2026-04-17'),

    /*
    |--------------------------------------------------------------------------
    | Public contact for privacy inquiries
    |--------------------------------------------------------------------------
    |
    | Used in the privacy policy and registration copy. Should reach someone
    | who can handle data subject requests (access, correction, deletion, etc.).
    |
    */
    'contact_email' => env('DATA_PRIVACY_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];

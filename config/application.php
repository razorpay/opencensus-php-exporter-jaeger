<?php

return [
    'experiments' => [
        'phantom_oauth_onboarding_exp_id' => env('PHANTOM_OAUTH_ONBOARDING_EXPERIMENT_ID'),
        'partner_custom_tnc_exp_id'       => env('PARTNER_CUSTOM_TNC_EXP_ID')
    ],

    'phantom_onboarding_url' => env("DASHBOARD_URL_PHANTOM_ONBOARDING")
];

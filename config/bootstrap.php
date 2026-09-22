<?php

return [
    /*
    |--------------------------------------------------------------------------
    | First-install administrator
    |--------------------------------------------------------------------------
    |
    | A brand-new production database must receive this value explicitly.
    | Existing administrators are never re-passworded by database seeding.
    |
    */
    'initial_admin_password' => env('INITIAL_ADMIN_PASSWORD'),
];

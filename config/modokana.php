<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mo Dokana settings
    |--------------------------------------------------------------------------
    |
    | Set ADMIN_APPROVAL_ENABLED=true when newly registered shops must be
    | approved by an administrator before protected APIs can be used.
    |
    */
    'admin_approval_enabled' => env('ADMIN_APPROVAL_ENABLED', false),
];

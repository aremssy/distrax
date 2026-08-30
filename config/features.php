<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    |
    | P3 modules (Distrax Escrow, Distrax Invest) are stub-only pending
    | regulatory sign-off. They are surfaced as informational screens
    | with no money movement. Flip the flag only after launch approval.
    |
    */

    'escrow_invest' => (bool) env('FEATURE_ESCROW_INVEST', false),

];

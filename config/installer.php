<?php

return [

    'app_version' => env('APP_VERSION', '1.0.0'),

    'php_min_version' => '8.3',

    'required_extensions' => [
        'bcmath',
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'gd',
        'intl',
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'pdo_mysql',
        'tokenizer',
        'xml',
        'zip',
    ],

    'required_folders' => [
        'bootstrap/cache',
        'storage',
        'storage/app',
        'storage/app/public',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ],

    'writable_folders' => [
        'bootstrap/cache',
        'storage',
        'storage/app',
        'storage/app/public',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ],

    'envato_api_url' => env('ENVATO_API_URL', 'https://api.envato.com/v3/market/author/sale'),

    'envato_personal_token' => env('ENVATO_PERSONAL_TOKEN', ''),

    // Explicit, opt-in escape hatch for local/demo installs without an Envato
    // token. Must never default to true: license enforcement should fail
    // closed when unconfigured, not silently auto-verify every purchase code.
    'allow_manual_activation' => env('INSTALLER_ALLOW_MANUAL_ACTIVATION', false),

];

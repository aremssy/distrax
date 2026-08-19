<?php

use Knuckles\Scribe\Config\AuthIn;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies;

return [
    'title' => 'Rentdo API',

    'description' => 'REST API for the Rentdo platform — covering property listings, hotel bookings, technician marketplace, reviews, notifications, and admin operations.',

    'intro_text' => <<<'INTRO'
        This documentation covers all endpoints for the Rentdo API (v1).

        <aside>All authenticated endpoints require a Bearer token obtained via <code>POST /api/v1/auth/login</code>. Pass it as <code>Authorization: Bearer {token}</code>.</aside>
        INTRO,

    'base_url' => config('app.url'),

    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/v1/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],

    // Static output → public/docs (portable HTML + Postman)
    'type' => 'static',

    'theme' => 'default',

    'static' => [
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        'add_routes' => false,
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],

    'external' => [
        'html_attributes' => [],
    ],

    'try_it_out' => [
        'enabled' => true,
        'base_url' => null,
        'use_csrf' => false,
        'csrf_url' => '/sanctum/csrf-cookie',
    ],

    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => AuthIn::BEARER->value,
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{YOUR_AUTH_TOKEN}',
        'extra_info' => 'Obtain a token via <code>POST /api/v1/auth/login</code>. Pass it as <code>Authorization: Bearer {token}</code>.',
    ],

    'example_languages' => ['bash', 'javascript', 'php'],

    'postman' => [
        'enabled' => true,
        'overrides' => [
            'info.version' => '1.0.0',
        ],
    ],

    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',
        'overrides' => [
            'info.version' => '1.0.0',
        ],
        'generators' => [],
    ],

    'groups' => [
        'default' => 'General',
        'order' => [
            'Auth',
            'Config',
            'Listings',
            'Hotels',
            'Bookings',
            'Technicians',
            'Reviews',
            'Notifications',
            'Payments',
            'Admin',
        ],
    ],

    'logo' => false,

    'last_updated' => 'Last updated: {date:F j, Y}',

    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryMake', 'factoryCreate', 'databaseFirst'],
    ],

    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ],
        'urlParameters' => [...Defaults::URL_PARAMETERS_STRATEGIES],
        'queryParameters' => [...Defaults::QUERY_PARAMETERS_STRATEGIES],
        'bodyParameters' => [...Defaults::BODY_PARAMETERS_STRATEGIES],
        // Response calls disabled — requires a running DB. Re-enable once DB is available.
        'responses' => [
            Strategies\Responses\UseResponseAttributes::class,
            Strategies\Responses\UseTransformerTags::class,
            Strategies\Responses\UseApiResourceTags::class,
            Strategies\Responses\UseResponseTag::class,
            Strategies\Responses\UseResponseFileTag::class,
        ],
        'responseFields' => [...Defaults::RESPONSE_FIELDS_STRATEGIES],
    ],

    // Empty: no DB transactions needed when response calls are disabled.
    'database_connections_to_transact' => [],

    'fractal' => [
        'serializer' => null,
    ],
];

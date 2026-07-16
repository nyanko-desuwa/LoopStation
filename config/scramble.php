<?php

use App\Http\Middleware\SetDocsLocale;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Which routes to document. See vendor default config for full option docs.
     */
    'api_path' => 'api',

    'api_domain' => null,

    'export_path' => 'api.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Title + description are localized at runtime in AppServiceProvider::boot()
         * via Scramble::extendOpenApi() (config files run before the translator is
         * available, so __() cannot be called here). Messages live in the "docs" lang files.
         */
        'description' => '',
    ],

    'ui' => [
        'title' => null,
    ],

    /*
     * Use the modern Scalar API reference UI instead of the default Stoplight Elements.
     */
    'renderer' => 'scalar',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'default',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        SetDocsLocale::class,
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    'security_strategy' => null,
];

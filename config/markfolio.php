<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Directory
    |--------------------------------------------------------------------------
    |
    | This is the directory where your markdown files will be stored.
    | The path is relative to the base_path() of your Laravel application.
    |
    */
    'content_directory' => resource_path('content'),

    /*
    |--------------------------------------------------------------------------
    | Default Layout
    |--------------------------------------------------------------------------
    |
    | This is the default layout that will be used to render markdown pages.
    | You can override this on a per-page basis by setting the layout
    | in the front matter of each markdown file.
    | Set to null to disable the default layout.
    |
    */
    'default_layout' => env('MARKFOLIO_DEFAULT_LAYOUT', null),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for markdown pages.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When debug mode is enabled, more information will be logged.
    |
    */
    'debug' => env('APP_DEBUG', false),
];

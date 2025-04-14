<?php

// Laravel helper functions
if (! function_exists('config_path')) {
    function config_path($path = '')
    {
        return app()->basePath().'/config'.($path ? '/'.$path : $path);
    }
}

if (! function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return app()->basePath().'/resources'.($path ? '/'.$path : $path);
    }
}

if (! function_exists('app_path')) {
    function app_path($path = '')
    {
        return app()->basePath().'/app'.($path ? '/'.$path : $path);
    }
}

if (! function_exists('base_path')) {
    function base_path($path = '')
    {
        return app()->basePath().($path ? '/'.$path : $path);
    }
}

if (! function_exists('now')) {
    function now()
    {
        return \Illuminate\Support\Carbon::now();
    }
}

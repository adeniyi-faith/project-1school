<?php
// Start-up file for the test web server: loads the old-PHP helper (only when needed), then hands over to Laravel.
require_once __DIR__.'/php83-polyfill.php';
return require __DIR__.'/../../../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';

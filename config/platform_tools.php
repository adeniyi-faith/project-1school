<?php

/*
| The platform owner's developer tools. Each one can be switched off in an environment by setting its
| variable to false in .env (for example PLATFORM_TOOL_FAILED_JOBS=false). A tool that is switched off has
| no page, no menu item and no address at all, so nothing can reach it.
*/
return [
    'health' => env('PLATFORM_TOOL_HEALTH', true),
    'failed_jobs' => env('PLATFORM_TOOL_FAILED_JOBS', true),
    'error_log' => env('PLATFORM_TOOL_ERROR_LOG', true),
    'scheduled_tasks' => env('PLATFORM_TOOL_SCHEDULED_TASKS', true),

    // Optional: the address of an outside error tracker (for example a Sentry project). Shown as a button on the error log.
    'error_tracker_url' => env('PLATFORM_ERROR_TRACKER_URL'),
];

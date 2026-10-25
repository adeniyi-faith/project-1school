<?php

/*
| The platform owner's developer tools. Each one can be switched off in an environment by setting its
| variable to false in .env (for example PLATFORM_TOOL_COMMANDS=false). A tool that is switched off has
| no page, no menu item and no address at all, so nothing can reach it.
*/
return [
    'health' => env('PLATFORM_TOOL_HEALTH', true),
    // Download a copy of one school's data (School page > Download data)
    'school_export' => env('PLATFORM_TOOL_SCHOOL_EXPORT', true),
];

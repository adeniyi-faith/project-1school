<?php

/*
| Settings for the platform team's own tools. Each tool can be switched off per environment (for example in
| .env on a test site) without a code change.
*/
return [
    // Write down what platform staff do (who entered which school, what they changed, from where)
    'audit' => (bool) env('PLATFORM_AUDIT', true),
];

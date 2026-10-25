<?php

/*
| Settings for the platform team's own tools. Each tool can be switched off per environment (for example in
| .env on a test site) without a code change.
*/
return [
    // Write down what platform staff do (who entered which school, what they changed, from where)
    'audit' => (bool) env('PLATFORM_AUDIT', true),

    // Risky actions (deleting a school, a login, a package ...) ask for a written reason and, for the worst ones, a typed name
    'risky_actions' => (bool) env('PLATFORM_RISKY_ACTIONS', true),

    // When on, platform staff (not the owner) cannot finish the worst risky actions alone: someone with the
    // approvals permission must approve first. Off by default.
    'second_approval' => (bool) env('PLATFORM_SECOND_APPROVAL', false),

    // Platform people must set up an authenticator app (a second sign-in step) before they can use anything
    'require_2fa' => (bool) env('PLATFORM_REQUIRE_2FA', true),
];

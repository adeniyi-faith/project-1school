<?php

namespace App\Support;

/** Which of the platform owner's developer tools are switched on in this environment (see config/platform_tools.php) */
class PlatformTools
{
    public static function enabled(string $tool): bool
    {
        return (bool) config("platform_tools.$tool", false);
    }
}

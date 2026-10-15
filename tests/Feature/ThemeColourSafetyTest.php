<?php

namespace Tests\Feature;

use App\Website\Themes;
use PHPUnit\Framework\TestCase;

class ThemeColourSafetyTest extends TestCase
{
    public function test_any_colour_choice_stays_readable(): void
    {
        $picks = [null, '#ffffff', '#000000', '#ffe14d', '#9ad0f5', '#1e3a8a', '#d4d4d4', '#c6f432'];

        foreach (array_keys(Themes::ALL) as $theme) {
            foreach ($picks as $one) {
                foreach ($picks as $two) {
                    $v = Themes::vars($theme, $one, $two);

                    $this->assertGreaterThanOrEqual(4.5, Themes::contrast($v['brand-t'], $v['bg']), "$theme $one $two brand text");
                    $this->assertGreaterThanOrEqual(4.5, Themes::contrast($v['band'], '#ffffff'), "$theme $one $two band");
                    $this->assertGreaterThanOrEqual(7, Themes::contrast($v['deep'], '#ffffff'), "$theme $one $two deep");
                }
            }
        }
    }
}

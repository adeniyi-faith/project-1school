<?php

namespace Tests\Feature;

use App\Website\LogoFit;
use App\Website\Themes;
use PHPUnit\Framework\TestCase;

class LogoFitTest extends TestCase
{
    /** A 200x200 test logo: a disc (or box) of the given colours on a see-through or solid background */
    private function logo(array $disc, ?array $inner = null, ?array $box = null, int $w = 200): string
    {
        $im = imagecreatetruecolor($w, 200);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, $box ? imagecolorallocate($im, ...$box) : imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagefilledellipse($im, (int) ($w / 2), 100, 160, 160, imagecolorallocate($im, ...$disc));
        if ($inner) {
            imagefilledellipse($im, (int) ($w / 2), 100, 70, 70, imagecolorallocate($im, ...$inner));
        }
        ob_start();
        imagepng($im);

        return ob_get_clean();
    }

    public function test_button_text_picks_whichever_of_dark_or_white_reads_better(): void
    {
        foreach (['#e8852b', '#f59e0b', '#fb923c', '#c89b32', '#00a062', '#22c55e', '#ffcc00', '#990000', '#002855', '#0f766e'] as $bg) {
            $ink = Themes::ink($bg);
            $best = max(Themes::contrast('#ffffff', $bg), Themes::contrast('#1c1917', $bg));
            $this->assertEqualsWithDelta($best, Themes::contrast($ink, $bg), 0.001, $bg);
        }
        foreach (array_keys(Themes::ALL) as $theme) {
            $v = Themes::vars($theme, null, null);
            $this->assertGreaterThanOrEqual(4.5, Themes::contrast($v['brand2-ink'], $v['brand2']), "$theme second colour");
        }
    }

    public function test_every_theme_reports_its_menu_background(): void
    {
        foreach (array_keys(Themes::ALL) as $theme) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', Themes::vars($theme, null, null)['menu-bg'], $theme);
        }
    }

    public function test_it_reads_a_logo_shape_and_whether_it_comes_in_a_box(): void
    {
        $plain = LogoFit::analyse($this->logo([0, 40, 85], [193, 2, 48]));
        $this->assertFalse($plain['boxed']);
        $this->assertEquals(1.0, $plain['ratio']);

        $boxed = LogoFit::analyse($this->logo([0, 100, 40], [250, 204, 21], [255, 255, 255]));
        $this->assertTrue($boxed['boxed']);

        $wide = LogoFit::analyse($this->logo([0, 40, 85], null, null, 400));
        $this->assertEquals(2.0, $wide['ratio']);

        $this->assertNull(LogoFit::analyse('not a picture'));
    }

    public function test_a_logo_that_would_vanish_is_flagged_and_one_that_shows_is_not(): void
    {
        $white = LogoFit::analyse($this->logo([255, 255, 255]));
        $navy = LogoFit::analyse($this->logo([0, 40, 85], [193, 2, 48]));

        $this->assertLessThan(0.6, LogoFit::visibleShare($white, '#ffffff'));
        $this->assertLessThan(0.6, LogoFit::visibleShare($white, '#ffcc00'));
        $this->assertGreaterThan(0.9, LogoFit::visibleShare($white, '#002855'));

        $this->assertLessThan(0.6, LogoFit::visibleShare($navy, '#002855'));
        $this->assertGreaterThan(0.9, LogoFit::visibleShare($navy, '#ffffff'));
    }

    public function test_every_theme_and_colour_choice_still_works_with_a_logo_on_any_background(): void
    {
        $facts = LogoFit::analyse($this->logo([20, 20, 20]));
        foreach (array_keys(Themes::ALL) as $theme) {
            foreach ([null, '#ffffff', '#000000', '#ffe14d', '#1e3a8a'] as $colour) {
                $v = Themes::vars($theme, $colour, null);
                foreach (['hdr-bg', 'menu-bg', 'ftr-bg'] as $key) {
                    $share = LogoFit::visibleShare($facts, $v[$key]);
                    $this->assertGreaterThanOrEqual(0.0, $share);
                    $this->assertLessThanOrEqual(1.0, $share);
                }
            }
        }
    }
}

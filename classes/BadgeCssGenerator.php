<?php
/**
 * Generates the front stylesheet: base rules, style presets and device visibility classes.
 *
 * The file name contains a content hash (badges-{hash}.css), so browsers and CCC never serve
 * an outdated version; older files are removed. Regenerated after a style is saved or deleted
 * and when the mobile breakpoint changes.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeCssGenerator
{
    public const VERSION_KEY = 'INFINITYBADGES_CSS_VERSION';

    /**
     * Change when build() output changes for the same data: files of other generator versions
     * are regenerated automatically after a code update.
     */
    private const GENERATOR = 'g6';

    /**
     * @param int|null $breakpoint Breakpoint to use instead of the saved one (settings being saved)
     */
    public static function generate(?int $breakpoint = null): bool
    {
        $dir = self::getDir();
        $css = self::build($breakpoint);
        $version = self::GENERATOR . '-' . Tools::substr(md5($css), 0, 10);
        $file = $dir . 'badges-' . $version . '.css';

        if (!is_file($file) && @file_put_contents($file, $css, LOCK_EX) === false) {
            PrestaShopLogger::addLog('infinitybadges: cannot write ' . $file, 3, null, 'BadgeStyle', 0, true);

            return false;
        }

        foreach (glob($dir . 'badges-*.css') ?: [] as $old) {
            if ($old !== $file) {
                @unlink($old);
            }
        }
        Configuration::updateValue(self::VERSION_KEY, $version);

        return true;
    }

    /**
     * Stylesheet path relative to the shop root (for registerStylesheet), generated when missing.
     */
    public static function getRelativePath(): ?string
    {
        $version = (string) Configuration::get(self::VERSION_KEY);
        if (strpos($version, self::GENERATOR . '-') !== 0 || !is_file(self::getDir() . 'badges-' . $version . '.css')) {
            if (!self::generate()) {
                return null;
            }
            $version = (string) Configuration::get(self::VERSION_KEY);
        }

        return 'modules/infinitybadges/views/css/generated/badges-' . $version . '.css';
    }

    public static function isWritable(): bool
    {
        return is_writable(self::getDir());
    }

    public static function build(?int $breakpoint = null): string
    {
        $breakpoint = $breakpoint ?? (int) Configuration::get('INFINITYBADGES_MOBILE_BREAKPOINT');
        if ($breakpoint < 320) {
            $breakpoint = 767;
        }

        $css = "/* infinitybadges - generated, do not edit */\n"
            // Base rules for all badges (also the base style), specific enough to override theme flag rules.
            . "html body .infinitybadge.infinitybadge{pointer-events:none;min-width:auto}\n"
            . '@media (max-width:' . $breakpoint . "px){.infinitybadge.ib-hide-mobile{display:none!important}}\n"
            . '@media (min-width:' . ($breakpoint + 1) . "px){.infinitybadge.ib-hide-desktop{display:none!important}}\n";

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'infinitybadges_style` ORDER BY `id_style`'
        ) ?: [];

        $mobile = '';
        foreach ($rows as $row) {
            $selector = self::selector((int) $row['id_style']);
            $css .= $selector . '{'
                . self::background($row) . ';'
                . 'color:' . self::color($row['text_color'], '#ffffff') . ';'
                . 'font-weight:' . (int) $row['font_weight'] . ';'
                . 'text-transform:' . ((int) $row['uppercase'] ? 'uppercase' : 'none') . ';'
                . 'border-radius:' . (int) $row['border_radius'] . 'px;'
                . self::border($row) . ';'
                . 'font-size:' . (int) $row['font_size_desktop'] . 'px;'
                . 'line-height:1.2;'
                . 'padding:' . (int) $row['padding_y_desktop'] . 'px ' . (int) $row['padding_x_desktop'] . 'px;'
                . 'margin-top:' . (int) $row['margin_desktop'] . 'px'
                . "}\n";
            $mobile .= $selector . '{'
                . 'font-size:' . (int) $row['font_size_mobile'] . 'px;'
                . 'padding:' . (int) $row['padding_y_mobile'] . 'px ' . (int) $row['padding_x_mobile'] . 'px;'
                . 'margin-top:' . (int) $row['margin_mobile'] . 'px'
                . '}';
        }
        if ($mobile !== '') {
            $css .= '@media (max-width:' . $breakpoint . 'px){' . $mobile . "}\n";
        }

        return $css . self::buildImageRules($breakpoint);
    }

    /**
     * Image badges: the image is the flag background, the text (alternative text) is hidden visually.
     */
    private static function buildImageRules(int $breakpoint): string
    {
        // Pełna wersja.
        return '';
    }

    /**
     * Uses only the module classes, so it works whatever element and classes the theme uses
     * for flags. Repeated classes raise specificity to (0,4,2), above typical theme rules such as
     * ".product-flags li.product-flag.new" (0,3,1).
     */
    private static function selector(int $idStyle): string
    {
        return 'html body .infinitybadge.infinitybadge.ib-style-' . $idStyle . '.ib-style-' . $idStyle;
    }

    /**
     * Solid background as #rrggbb, translucent one as rgba(), fully transparent as "transparent".
     */
    public static function background(array $row): string
    {
        $color = self::color($row['bg_color'] ?? '', '#222222');
        $opacity = max(0, min(100, (int) ($row['bg_opacity'] ?? 100)));

        if ($opacity >= 100) {
            return 'background:' . $color;
        }
        if ($opacity === 0) {
            return 'background:transparent';
        }

        return 'background:rgba(' . hexdec(substr($color, 1, 2)) . ',' . hexdec(substr($color, 3, 2)) . ','
            . hexdec(substr($color, 5, 2)) . ',' . rtrim(rtrim(number_format($opacity / 100, 2, '.', ''), '0'), '.') . ')';
    }

    /**
     * "border:none" also removes a border the theme may set on flags.
     */
    public static function border(array $row): string
    {
        $width = (int) ($row['border_width'] ?? 0);

        return $width > 0
            ? 'border:' . $width . 'px solid ' . self::color($row['border_color'] ?? '', '#000000')
            : 'border:none';
    }

    private static function color($value, string $default): string
    {
        return BadgeStyle::isHexColor((string) $value) ? (string) $value : $default;
    }

    private static function getDir(): string
    {
        return _PS_MODULE_DIR_ . 'infinitybadges/views/css/generated/';
    }
}

<?php
/**
 * Badge style preset. Sizes in px, separate values for desktop and mobile.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeStyle extends ObjectModel
{
    public const FONT_WEIGHTS = [400, 500, 600, 700, 800];

    /** @var string */
    public $name;
    /** @var string #rrggbb */
    public $bg_color = '#222222';
    /** @var int Background opacity in %, 0 = transparent, 100 = solid */
    public $bg_opacity = 100;
    /** @var string #rrggbb */
    public $text_color = '#ffffff';
    /** @var int */
    public $font_weight = 600;
    /** @var bool */
    public $uppercase = false;
    /** @var int */
    public $border_radius = 3;
    /** @var int 0 = no border */
    public $border_width = 0;
    /** @var string #rrggbb */
    public $border_color = '#000000';
    /** @var int */
    public $font_size_desktop = 13;
    /** @var int */
    public $font_size_mobile = 11;
    /** @var int */
    public $padding_y_desktop = 4;
    /** @var int */
    public $padding_x_desktop = 8;
    /** @var int */
    public $padding_y_mobile = 3;
    /** @var int */
    public $padding_x_mobile = 6;
    /** @var int */
    public $margin_desktop = 4;
    /** @var int */
    public $margin_mobile = 3;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public static $definition = [
        'table' => 'infinitybadges_style',
        'primary' => 'id_style',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'bg_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'required' => true, 'size' => 7],
            'bg_opacity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'text_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'required' => true, 'size' => 7],
            'font_weight' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'uppercase' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'border_radius' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'border_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'border_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'font_size_desktop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'font_size_mobile' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'padding_y_desktop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'padding_x_desktop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'padding_y_mobile' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'padding_x_mobile' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'margin_desktop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'margin_mobile' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        BadgeCssGenerator::generate();

        return true;
    }

    public function update($null_values = false)
    {
        if (!parent::update($null_values)) {
            return false;
        }
        BadgeCssGenerator::generate();

        return true;
    }

    /**
     * Rules using the style fall back to the base style (id_style = 0).
     */
    public function delete()
    {
        $id = (int) $this->id;
        if (!parent::delete()) {
            return false;
        }

        $result = Db::getInstance()->update('infinitybadges_rule', ['id_style' => 0], '`id_style` = ' . $id);
        BadgeCssGenerator::generate();

        return $result;
    }

    /**
     * @return string[] Translated error messages
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        if (!self::isHexColor((string) $this->bg_color)) {
            $errors[] = InfinityBadges::translate('The background color must be in #RRGGBB format.');
        }
        if (!preg_match('/^\d{1,3}$/', (string) $this->bg_opacity) || (int) $this->bg_opacity > 100) {
            $errors[] = InfinityBadges::translate('Background opacity must be between 0 and 100%.');
        }
        if (!self::isHexColor((string) $this->text_color)) {
            $errors[] = InfinityBadges::translate('The text color must be in #RRGGBB format.');
        }
        if (!in_array((int) $this->font_weight, self::FONT_WEIGHTS, true)) {
            $errors[] = InfinityBadges::translate('Invalid font weight.');
        }
        foreach ([(int) $this->font_size_desktop, (int) $this->font_size_mobile] as $size) {
            if ($size < 8 || $size > 40) {
                $errors[] = InfinityBadges::translate('Font size must be between 8 and 40 px.');
                break;
            }
        }
        foreach ([
            $this->padding_y_desktop, $this->padding_x_desktop, $this->padding_y_mobile,
            $this->padding_x_mobile, $this->margin_desktop, $this->margin_mobile,
        ] as $spacing) {
            if ((int) $spacing < 0 || (int) $spacing > 40) {
                $errors[] = InfinityBadges::translate('Spacing values must be between 0 and 40 px.');
                break;
            }
        }
        if ((int) $this->border_radius < 0 || (int) $this->border_radius > 50) {
            $errors[] = InfinityBadges::translate('Border radius must be between 0 and 50 px.');
        }
        if ((int) $this->border_width < 0 || (int) $this->border_width > 10) {
            $errors[] = InfinityBadges::translate('Border width must be between 0 and 10 px.');
        }
        if ((int) $this->border_width > 0 && !self::isHexColor((string) $this->border_color)) {
            $errors[] = InfinityBadges::translate('The border color must be in #RRGGBB format.');
        }

        return $errors;
    }

    public function countRules(): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'infinitybadges_rule` WHERE `id_style` = ' . (int) $this->id
        );
    }

    /**
     * @return array<int, array{id_style: int, name: string}>
     */
    public static function getAll(): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_style`, `name` FROM `' . _DB_PREFIX_ . 'infinitybadges_style` ORDER BY `name`'
        ) ?: [];

        return array_map(static function (array $row): array {
            return ['id_style' => (int) $row['id_style'], 'name' => (string) $row['name']];
        }, $rows);
    }

    public static function isHexColor(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }
}

<?php
/**
 * Badge rule: what the badge shows (feature value or static text), where and when.
 * Criteria (groups and exclusions) are stored separately - see BadgeConditionSet.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeRule extends ObjectModel
{
    public const TYPE_FEATURE = 'feature';
    public const TYPE_STATIC = 'static';
    /** Image (JPG/PNG/SVG) shown as the flag background; the text is the alternative text. */
    public const TYPE_IMAGE = 'image';
    public const TYPES = [self::TYPE_FEATURE, self::TYPE_STATIC, self::TYPE_IMAGE];

    public const IMAGE_HEIGHT_MIN = 8;
    public const IMAGE_HEIGHT_MAX = 200;

    /** Feature value as label text. */
    public const TEXT_MODE_VALUE = 'value';
    /** Feature name as label text (e.g. "Motion sensor = Yes" gives "Motion sensor"). */
    public const TEXT_MODE_FEATURE_NAME = 'feature_name';
    /** Custom text from rule_lang.text. */
    public const TEXT_MODE_CUSTOM = 'custom';
    public const TEXT_MODES = [self::TEXT_MODE_VALUE, self::TEXT_MODE_FEATURE_NAME, self::TEXT_MODE_CUSTOM];

    /** Core flags (ProductLazyArray::getFlags()) that a shown badge can hide, keyed by flag type. */
    public const SYSTEM_FLAGS = ['discount', 'on-sale', 'new', 'pack', 'online-only', 'out_of_stock'];

    /** @var string */
    public $type = self::TYPE_STATIC;
    /** @var string Admin name */
    public $name;
    /** @var bool */
    public $active = false;
    /** @var int Higher wins */
    public $priority = 0;
    /** @var int Order among rules with the same priority */
    public $position = 0;
    /** @var string|null */
    public $date_from;
    /** @var string|null */
    public $date_to;
    /** @var int 0 = base style from front.css */
    public $id_style = 0;
    /** @var int Bitmask of InfinityBadges::DEVICE_* */
    public $devices = InfinityBadges::DEVICE_ALL;
    /** @var int Feature shown by a feature badge */
    public $id_feature = 0;
    /** @var string */
    public $text_mode = self::TEXT_MODE_VALUE;
    /** @var string Values hiding the badge, one per line, case-insensitive */
    public $hide_values = '';
    /** @var string System flag types hidden when the badge is shown, comma separated */
    public $hide_flags = '';
    /** @var string Image file name in img/infinitybadges/ (image badge) */
    public $image = '';
    /** @var int Intrinsic image width */
    public $image_width = 0;
    /** @var int Intrinsic image height */
    public $image_height = 0;
    /** @var int Display height on desktop (px) */
    public $image_height_desktop = 32;
    /** @var int Display height on mobile (px) */
    public $image_height_mobile = 24;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    /** @var string|array Multilang */
    public $prefix;
    /** @var string|array Multilang */
    public $suffix;
    /** @var string|array Multilang: static badge text or custom text of a feature badge */
    public $text;

    public static $definition = [
        'table' => 'infinitybadges_rule',
        'primary' => 'id_rule',
        'multilang' => true,
        'fields' => [
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16, 'values' => self::TYPES],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'date_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'id_style' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'devices' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_feature' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'text_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16, 'values' => self::TEXT_MODES],
            'hide_values' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 4000],
            'hide_flags' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'image' => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'size' => 64],
            'image_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'image_height' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'image_height_desktop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'image_height_mobile' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            'prefix' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 64],
            'suffix' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 64],
            'text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        $this->normalizeDates();
        if (!$this->position) {
            $this->position = self::getNextPosition();
        }

        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        // Image badges are part of the generated stylesheet.
        BadgeCssGenerator::generate();

        return true;
    }

    public function update($null_values = false)
    {
        $this->normalizeDates();

        if (!parent::update($null_values)) {
            return false;
        }
        BadgeCssGenerator::generate();

        return true;
    }

    public function delete()
    {
        $id = (int) $this->id;
        if (!parent::delete()) {
            return false;
        }

        $result = BadgeConditionSet::deleteForRule($id);
        BadgeCssGenerator::generate();

        return $result;
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * Copy of the rule with its criteria, saved as inactive.
     */
    public function duplicate(): ?BadgeRule
    {
        if (!Validate::isLoadedObject($this)) {
            return null;
        }

        // Loaded without id_lang, so multilang fields hold all languages.
        $copy = new BadgeRule((int) $this->id);
        $copy->id = null;
        $copy->active = false;
        $copy->position = 0;
        $copy->name = Tools::substr(
            InfinityBadges::translate('%name% (copy)', ['%name%' => $this->name]),
            0,
            128
        );
        // Pełna wersja: image badges (the copy gets its own image file).
        $copy->image = '';

        if (!$copy->add()) {
            return null;
        }

        if (!BadgeConditionSet::copy((int) $this->id, (int) $copy->id)) {
            $copy->delete();

            return null;
        }

        return $copy;
    }

    /**
     * Business validation beyond field definitions.
     *
     * @return string[] Translated error messages
     */
    public function validateBusinessRules(): array
    {
        $errors = [];
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');

        if (!in_array($this->type, self::TYPES, true)) {
            $errors[] = InfinityBadges::translate('Unknown badge type.');
        }
        if (!in_array($this->text_mode, self::TEXT_MODES, true)) {
            $errors[] = InfinityBadges::translate('Unknown text mode.');
        }

        if ($this->type === self::TYPE_FEATURE) {
            if ((int) $this->id_feature <= 0) {
                $errors[] = InfinityBadges::translate('Select a feature for a feature badge.');
            } elseif (!Db::getInstance()->getValue(
                'SELECT 1 FROM `' . _DB_PREFIX_ . 'feature` WHERE `id_feature` = ' . (int) $this->id_feature
            )) {
                $errors[] = InfinityBadges::translate('The selected feature does not exist.');
            }
        }

        if ($this->needsText() && trim((string) $this->getLangValue('text', $idLangDefault)) === '') {
            $errors[] = InfinityBadges::translate('Enter the badge text in the default language.');
        }

        if ($this->isImage()) {
            if ((string) $this->image === '') {
                $errors[] = InfinityBadges::translate('Upload an image for an image badge.');
            }
            foreach ([(int) $this->image_height_desktop, (int) $this->image_height_mobile] as $height) {
                if ($height < self::IMAGE_HEIGHT_MIN || $height > self::IMAGE_HEIGHT_MAX) {
                    $errors[] = InfinityBadges::translate('The image height must be between %min% and %max% px.', [
                        '%min%' => self::IMAGE_HEIGHT_MIN,
                        '%max%' => self::IMAGE_HEIGHT_MAX,
                    ]);
                    break;
                }
            }
        }

        if (!empty($this->date_from) && !empty($this->date_to) && strtotime($this->date_from) > strtotime($this->date_to)) {
            $errors[] = InfinityBadges::translate('The start date cannot be later than the end date.');
        }

        if (array_diff(self::parseHideFlags((string) $this->hide_flags, false), self::SYSTEM_FLAGS)) {
            $errors[] = InfinityBadges::translate('Unknown system flag.');
        }

        $devices = (int) $this->devices;
        if ($devices <= 0 || $devices > InfinityBadges::DEVICE_ALL) {
            $errors[] = InfinityBadges::translate('Select at least one device.');
        }

        if ((int) $this->id_style > 0 && !Validate::isLoadedObject(new BadgeStyle((int) $this->id_style))) {
            $errors[] = InfinityBadges::translate('The selected style does not exist.');
        }

        return $errors;
    }

    /**
     * True when the text is required (static badge or feature badge in custom text mode).
     * For image badges the text is optional alternative text.
     */
    public function needsText(): bool
    {
        return $this->type === self::TYPE_STATIC
            || ($this->type === self::TYPE_FEATURE && $this->text_mode === self::TEXT_MODE_CUSTOM);
    }

    /**
     * @return string[] Lowercased values hiding the badge
     */
    public function getHideValues(): array
    {
        return self::parseHideValues((string) $this->hide_values);
    }

    /**
     * @return string[]
     */
    public static function parseHideValues(string $raw): array
    {
        $values = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $values[] = mb_strtolower($line, 'UTF-8');
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @param bool $knownOnly Drop unknown flag types
     *
     * @return string[] System flag types
     */
    public static function parseHideFlags(string $raw, bool $knownOnly = true): array
    {
        $flags = array_values(array_unique(array_filter(array_map('trim', explode(',', $raw)), 'strlen')));

        return $knownOnly ? array_values(array_intersect($flags, self::SYSTEM_FLAGS)) : $flags;
    }

    public static function getNextPosition(): int
    {
        return 1 + (int) Db::getInstance()->getValue(
            'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . 'infinitybadges_rule`'
        );
    }

    /**
     * Value of a multilang field for a language, whether the object was loaded with or without id_lang.
     */
    private function getLangValue(string $field, int $idLang)
    {
        $value = $this->{$field};

        return is_array($value) ? ($value[$idLang] ?? null) : $value;
    }

    /**
     * Empty dates are stored as NULL, never as 0000-00-00.
     */
    private function normalizeDates(): void
    {
        foreach (['date_from', 'date_to'] as $field) {
            if (empty($this->{$field}) || strpos((string) $this->{$field}, '0000-00-00') === 0) {
                $this->{$field} = null;
            }
        }
    }
}

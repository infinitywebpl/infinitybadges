<?php
/**
 * Evaluates rules for products: label text, priorities, duplicates and limits.
 *
 * Demo version: every rule applies to all products (criteria are in the full version),
 * feature badges show the feature value with prefix and suffix, static badges their text.
 * getBadges() is used on the front office (active rules within dates, results cached per request).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeEngine
{
    /** @var array<string, BadgeEngine> */
    private static $instances = [];

    /** @var int */
    private $idLang;

    /** @var int */
    private $idLangDefault;

    /** @var BadgeRepository */
    private $repository;

    /** @var array|null Active rules within dates */
    private $activeRules;

    /** @var array<int, array> id_product => badges */
    private $cache = [];

    public function __construct(int $idLang, int $idShop)
    {
        $this->idLang = $idLang;
        $this->idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->repository = new BadgeRepository($idShop, $this->getLangIds());
    }

    /**
     * Shared instance for a request (front office).
     */
    public static function getInstance(int $idLang, int $idShop): self
    {
        $key = $idLang . '-' . $idShop;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($idLang, $idShop);
        }

        return self::$instances[$key];
    }

    /**
     * @param int[] $productIds
     */
    public function prefetch(array $productIds): void
    {
        $this->repository->prefetch($productIds);
    }

    /**
     * Badges of a product for the front office.
     *
     * @return array<int, array{id_rule: int, text: string, id_style: int, hide_desktop: bool, hide_mobile: bool}>
     */
    public function getBadges(int $idProduct): array
    {
        if (isset($this->cache[$idProduct])) {
            return $this->cache[$idProduct];
        }

        $badges = [];
        $product = $this->repository->get($idProduct);
        if ($product !== null && $product['active']) {
            if ($this->activeRules === null) {
                $now = date('Y-m-d H:i:s');
                $this->activeRules = array_values(array_filter(
                    BadgeRuleSet::load($this->getLangIds()),
                    static function (array $rule) use ($now): bool {
                        return BadgeRuleSet::isWithinDates($rule, $now);
                    }
                ));
            }
            $badges = $this->evaluate($product, $this->activeRules);
        }

        return $this->cache[$idProduct] = $badges;
    }

    /**
     * Feature value text in the engine language, with fallback to the default language.
     *
     * @param array<int, string> $byLang
     */
    public function pickLang(array $byLang): string
    {
        $current = trim((string) ($byLang[$this->idLang] ?? ''));
        if ($current !== '') {
            return $current;
        }

        return trim((string) ($byLang[$this->idLangDefault] ?? ''));
    }

    /**
     * Demo: rules in priority order, one badge per feature and per text, global limits per device.
     */
    private function evaluate(array $product, array $rules): array
    {
        $badges = [];
        $seenFeatures = [];
        $seenTexts = [];
        $limitDesktop = InfinityBadges::getLimit(InfinityBadges::DEVICE_DESKTOP);
        $limitMobile = InfinityBadges::getLimit(InfinityBadges::DEVICE_MOBILE);
        $countDesktop = 0;
        $countMobile = 0;

        foreach ($rules as $rule) {
            $text = $this->buildLabelText($rule, $product);
            if ($text === '') {
                continue;
            }

            $isFeature = $rule['type'] === BadgeRule::TYPE_FEATURE;
            $textKey = mb_strtolower($text, 'UTF-8');
            if (($isFeature && isset($seenFeatures[$rule['id_feature']])) || isset($seenTexts[$textKey])) {
                continue;
            }

            $showDesktop = ($rule['devices'] & InfinityBadges::DEVICE_DESKTOP) && $countDesktop < $limitDesktop;
            $showMobile = ($rule['devices'] & InfinityBadges::DEVICE_MOBILE) && $countMobile < $limitMobile;
            if (!$showDesktop && !$showMobile) {
                continue;
            }

            if ($isFeature) {
                $seenFeatures[$rule['id_feature']] = true;
            }
            $seenTexts[$textKey] = true;
            $countDesktop += $showDesktop ? 1 : 0;
            $countMobile += $showMobile ? 1 : 0;

            $badges[] = [
                'id_rule' => $rule['id'],
                'text' => $text,
                'id_style' => $rule['id_style'],
                'hide_desktop' => !$showDesktop,
                'hide_mobile' => !$showMobile,
            ];
        }

        return $badges;
    }

    /**
     * Demo: static text, or all values of the feature with prefix and suffix ('' = no badge).
     * Image badges, the [discount] placeholder, text modes and hidden values are in the full version.
     */
    private function buildLabelText(array $rule, array $product): string
    {
        if ($rule['type'] === BadgeRule::TYPE_STATIC) {
            $text = $this->ruleText($rule, 'text');
        } elseif ($rule['type'] === BadgeRule::TYPE_FEATURE) {
            $texts = [];
            foreach ($product['features'][$rule['id_feature']] ?? [] as $byLang) {
                $text = $this->pickLang($byLang);
                if ($text !== '') {
                    $texts[mb_strtolower($text, 'UTF-8')] = $text;
                }
            }
            if (!$texts) {
                return '';
            }
            $texts = array_values($texts);
            usort($texts, 'strnatcasecmp');
            $text = $this->applyAffixes(
                implode(' / ', $texts),
                $this->ruleText($rule, 'prefix', false),
                $this->ruleText($rule, 'suffix', false)
            );
        } else {
            // Pełna wersja.
            return '';
        }

        // Pełna wersja: [discount] is replaced with the product discount.
        if (mb_stripos($text, BadgeDiscount::PLACEHOLDER, 0, 'UTF-8') !== false) {
            return '';
        }

        return $text;
    }

    /**
     * Prefix / suffix are not added when the value already starts / ends with them
     * (compared case-insensitively, ignoring whitespace).
     */
    private function applyAffixes(string $value, string $prefix, string $suffix): string
    {
        $normalize = static function (string $text): string {
            return mb_strtolower((string) preg_replace('/\s+/u', '', $text), 'UTF-8');
        };
        $normalizedValue = $normalize($value);

        $normalizedPrefix = $normalize($prefix);
        if ($normalizedPrefix !== '' && mb_strpos($normalizedValue, $normalizedPrefix, 0, 'UTF-8') !== 0) {
            $value = $prefix . $value;
        }

        $normalizedSuffix = $normalize($suffix);
        if ($normalizedSuffix !== '') {
            $tail = mb_substr($normalizedValue, -mb_strlen($normalizedSuffix, 'UTF-8'), null, 'UTF-8');
            if ($tail !== $normalizedSuffix) {
                $value .= $suffix;
            }
        }

        return $value;
    }

    /**
     * Rule text (text / prefix / suffix) in the engine language, with fallback to the default language.
     * Prefixes and suffixes keep their spaces.
     */
    private function ruleText(array $rule, string $field, bool $trim = true): string
    {
        $current = (string) ($rule['lang'][$this->idLang][$field] ?? '');
        if (trim($current) !== '') {
            return $trim ? trim($current) : $current;
        }

        $default = (string) ($rule['lang'][$this->idLangDefault][$field] ?? '');
        if (trim($default) === '') {
            return '';
        }

        return $trim ? trim($default) : $default;
    }

    /**
     * @return int[]
     */
    private function getLangIds(): array
    {
        return array_values(array_unique(array_filter([$this->idLang, $this->idLangDefault])));
    }
}

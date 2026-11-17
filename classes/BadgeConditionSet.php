<?php
/**
 * Criteria of a badge rule: "always exclude" list and groups of criteria.
 *
 * Demo version: criteria are in the full version. A rule without criteria applies to all products,
 * so the demo stores no criteria and the engine shows every active rule on all products.
 *
 * Structure:
 * [
 *   'exclusions' => [condition, ...],
 *   'groups' => [['conditions' => [condition, ...]], ...],
 * ]
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeConditionSet
{
    public const KIND_CATEGORY = 'category';
    public const KIND_MANUFACTURER = 'manufacturer';
    public const KIND_SUPPLIER = 'supplier';
    public const KIND_FEATURE = 'feature';
    public const KIND_PRICE = 'price';
    public const KIND_DISCOUNT = 'discount';
    public const KIND_PRODUCT = 'product';
    public const KINDS = [
        self::KIND_CATEGORY,
        self::KIND_MANUFACTURER,
        self::KIND_SUPPLIER,
        self::KIND_FEATURE,
        self::KIND_PRICE,
        self::KIND_DISCOUNT,
        self::KIND_PRODUCT,
    ];

    public const MODE_INCLUDE = 'include';
    public const MODE_EXCLUDE = 'exclude';
    public const MODES = [self::MODE_INCLUDE, self::MODE_EXCLUDE];

    /**
     * @return array{exclusions: array, groups: array}
     */
    public static function emptySet(): array
    {
        return ['exclusions' => [], 'groups' => []];
    }

    /**
     * @return array{exclusions: array, groups: array}
     */
    public static function load(int $idRule): array
    {
        // Pełna wersja.
        return self::emptySet();
    }

    /**
     * @param mixed $raw Array or JSON string
     *
     * @return array{set: array, errors: array}
     */
    public static function normalize($raw): array
    {
        // Pełna wersja.
        return ['set' => self::emptySet(), 'errors' => []];
    }

    public static function save(int $idRule, array $set): bool
    {
        // Pełna wersja.
        return true;
    }

    public static function deleteForRule(int $idRule): bool
    {
        // Pełna wersja.
        return true;
    }

    public static function copy(int $fromRule, int $toRule): bool
    {
        // Pełna wersja.
        return true;
    }
}

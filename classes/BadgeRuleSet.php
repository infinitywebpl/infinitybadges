<?php
/**
 * Loads rules with texts for the engine, in a fixed number of queries.
 *
 * Demo version: 'exclusions' and 'groups' are always empty, so every rule applies to all products
 * (criteria are in the full version).
 *
 * Rule structure:
 * [
 *   'id' => int, 'name' => string, 'type' => string, 'active' => bool,
 *   'priority' => int, 'position' => int, 'date_from' => ?string, 'date_to' => ?string,
 *   'id_style' => int, 'devices' => int,
 *   'id_feature' => int, 'text_mode' => string, 'hide_values' => string[] (lowercase),
 *   'hide_flags' => string[] (system flag types hidden when the badge is shown),
 *   'lang' => [id_lang => ['prefix' => string, 'suffix' => string, 'text' => string]],
 *   'exclusions' => [], 'groups' => [],
 * ]
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeRuleSet
{
    /**
     * @return array<int, array>
     */
    private static function select(string $sql): array
    {
        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * @param int[] $langIds Languages of texts to load (current and default)
     * @param bool $includeInactive Also inactive rules (admin preview)
     *
     * @return array<int, array> Rules ordered by priority DESC, position ASC, id ASC
     */
    public static function load(array $langIds, bool $includeInactive = false): array
    {
        $p = _DB_PREFIX_;

        $rows = self::select(
            'SELECT * FROM `' . $p . 'infinitybadges_rule`'
            . ($includeInactive ? '' : ' WHERE `active` = 1')
            . ' ORDER BY `priority` DESC, `position` ASC, `id_rule` ASC'
        ) ?: [];
        if (!$rows) {
            return [];
        }

        $rules = [];
        foreach ($rows as $row) {
            $rules[(int) $row['id_rule']] = [
                'id' => (int) $row['id_rule'],
                'name' => (string) $row['name'],
                'type' => (string) $row['type'],
                'active' => (bool) $row['active'],
                'priority' => (int) $row['priority'],
                'position' => (int) $row['position'],
                'date_from' => $row['date_from'] ?: null,
                'date_to' => $row['date_to'] ?: null,
                'id_style' => (int) $row['id_style'],
                'devices' => (int) $row['devices'],
                'id_feature' => (int) $row['id_feature'],
                'text_mode' => (string) $row['text_mode'],
                'hide_values' => BadgeRule::parseHideValues((string) $row['hide_values']),
                'hide_flags' => BadgeRule::parseHideFlags((string) $row['hide_flags']),
                'image' => (string) $row['image'],
                'lang' => [],
                'exclusions' => [],
                'groups' => [],
            ];
        }
        $ids = implode(',', array_keys($rules));

        $langIds = array_values(array_unique(array_filter(array_map('intval', $langIds))));
        if ($langIds) {
            $langRows = self::select(
                'SELECT * FROM `' . $p . 'infinitybadges_rule_lang`
                WHERE `id_rule` IN (' . $ids . ') AND `id_lang` IN (' . implode(',', $langIds) . ')'
            ) ?: [];
            foreach ($langRows as $row) {
                $rules[(int) $row['id_rule']]['lang'][(int) $row['id_lang']] = [
                    'prefix' => (string) $row['prefix'],
                    'suffix' => (string) $row['suffix'],
                    'text' => (string) $row['text'],
                ];
            }
        }

        // Pełna wersja: criteria ("always exclude" and groups) are loaded here.

        return array_values($rules);
    }

    /**
     * Rule is within its validity dates at the given time (Y-m-d H:i:s, shop timezone).
     */
    public static function isWithinDates(array $rule, string $now): bool
    {
        return ($rule['date_from'] === null || $rule['date_from'] <= $now)
            && ($rule['date_to'] === null || $rule['date_to'] >= $now);
    }
}

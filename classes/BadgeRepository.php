<?php
/**
 * Product data needed by the engine, fetched in bulk: 2 queries for any number of products.
 *
 * Demo version: status and features only (categories, manufacturers, suppliers and prices are
 * used by criteria in the full version).
 *
 * Product structure:
 * [
 *   'id' => int, 'active' => bool,
 *   'features' => [id_feature => [id_feature_value => [id_lang => value]]],
 * ]
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeRepository
{
    /** @var int */
    private $idShop;

    /** @var int[] */
    private $langIds;

    /** @var array<int, array|null> null = product not found */
    private $products = [];

    /**
     * @param int[] $langIds Languages of feature values (current and default)
     */
    public function __construct(int $idShop, array $langIds)
    {
        $this->idShop = $idShop;
        $this->langIds = array_values(array_unique(array_filter(array_map('intval', $langIds))));
    }

    /**
     * Loads products not loaded yet.
     *
     * @param int[] $ids
     */
    public function prefetch(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $ids = array_values(array_filter($ids, function (int $id): bool {
            return !array_key_exists($id, $this->products);
        }));
        if (!$ids) {
            return;
        }

        foreach ($ids as $id) {
            $this->products[$id] = null;
        }

        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $p = _DB_PREFIX_;
        $in = implode(',', $ids);

        $rows = $db->executeS(
            'SELECT `id_product`, `active` FROM `' . $p . 'product_shop`
            WHERE `id_shop` = ' . $this->idShop . ' AND `id_product` IN (' . $in . ')'
        ) ?: [];
        foreach ($rows as $row) {
            $id = (int) $row['id_product'];
            $this->products[$id] = [
                'id' => $id,
                'active' => (bool) $row['active'],
                'features' => [],
            ];
        }

        if (!$this->langIds) {
            return;
        }
        $rows = $db->executeS(
            'SELECT fp.`id_product`, fp.`id_feature`, fp.`id_feature_value`, fvl.`id_lang`, fvl.`value`
            FROM `' . $p . 'feature_product` fp
            LEFT JOIN `' . $p . 'feature_value_lang` fvl
                ON fvl.`id_feature_value` = fp.`id_feature_value` AND fvl.`id_lang` IN (' . implode(',', $this->langIds) . ')
            WHERE fp.`id_product` IN (' . $in . ')'
        ) ?: [];
        foreach ($rows as $row) {
            $id = (int) $row['id_product'];
            if (!isset($this->products[$id])) {
                continue;
            }
            $values = &$this->products[$id]['features'][(int) $row['id_feature']][(int) $row['id_feature_value']];
            if ($values === null) {
                $values = [];
            }
            if ($row['id_lang'] !== null) {
                $values[(int) $row['id_lang']] = (string) $row['value'];
            }
            unset($values);
        }
    }

    public function get(int $idProduct): ?array
    {
        if (!array_key_exists($idProduct, $this->products)) {
            $this->prefetch([$idProduct]);
        }

        return $this->products[$idProduct] ?? null;
    }
}

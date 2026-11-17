<?php
/**
 * Demo version: contact addresses and the list of full version features.
 *
 * The demo shows badges from a feature value (with prefix and suffix) and static text badges
 * on all products, with styles, priorities, dates, devices and limits. Criteria, image badges,
 * the [discount] placeholder, text modes, hidden values, hiding system flags, the preview,
 * matching products, export / import and the debug mode are in the full version of the module.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class InfinityBadgesDemo
{
    /** Contact about the full version of the module. */
    public const FULL_VERSION_URL = 'https://www.infinityweb.pl/oferta/moduly-prestashop';

    public const CONTACT_EMAIL = 'biuro@infinityweb.pl';

    public const SITE_URL = 'https://www.infinityweb.pl';

    public const GITHUB_URL = 'https://github.com/infinitywebpl';

    /**
     * Full version features (source texts, translated in translations/).
     *
     * @return string[]
     */
    public static function fullVersionFeatures(): array
    {
        return [
            InfinityBadges::translate('criteria: groups of categories (with subcategories), manufacturers, suppliers, features, price, discount and products, with "Always exclude"'),
            InfinityBadges::translate('image badges (JPG, PNG, SVG)'),
            InfinityBadges::translate('[discount] placeholder, e.g. "SALE -[discount]%"'),
            InfinityBadges::translate('feature name or custom text instead of the value, hidden values (e.g. "No")'),
            InfinityBadges::translate('hiding PrestaShop flags (e.g. "-20%") where the badge is shown'),
            InfinityBadges::translate('preview on a product with the reason of each decision, and matching products of a rule'),
            InfinityBadges::translate('export and import of the configuration, debug mode'),
        ];
    }
}

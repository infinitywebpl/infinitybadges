<?php
/**
 * Discount of a product in percent for the [discount] placeholder.
 *
 * Demo version: only the placeholder. Badges with [discount] are not shown; replacing it with the
 * product discount and the discount criterion are in the full version.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BadgeDiscount
{
    /** Placeholder replaced in the badge text with the discount in whole percent (e.g. "20"). */
    public const PLACEHOLDER = '[discount]';
}

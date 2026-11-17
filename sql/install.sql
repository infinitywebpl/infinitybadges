CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule` (
    `id_rule` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(16) NOT NULL DEFAULT 'static',
    `name` VARCHAR(128) NOT NULL,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `priority` INT(11) NOT NULL DEFAULT 0,
    `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `date_from` DATETIME NULL DEFAULT NULL,
    `date_to` DATETIME NULL DEFAULT NULL,
    `id_style` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `devices` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
    `id_feature` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `text_mode` VARCHAR(16) NOT NULL DEFAULT 'value',
    `hide_values` TEXT NULL,
    `hide_flags` VARCHAR(255) NOT NULL DEFAULT '',
    `image` VARCHAR(64) NOT NULL DEFAULT '',
    `image_width` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `image_height` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `image_height_desktop` TINYINT(3) UNSIGNED NOT NULL DEFAULT 32,
    `image_height_mobile` TINYINT(3) UNSIGNED NOT NULL DEFAULT 24,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_rule`),
    KEY `active` (`active`, `date_from`, `date_to`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule_lang` (
    `id_rule` INT(10) UNSIGNED NOT NULL,
    `id_lang` INT(10) UNSIGNED NOT NULL,
    `prefix` VARCHAR(64) NOT NULL DEFAULT '',
    `suffix` VARCHAR(64) NOT NULL DEFAULT '',
    `text` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id_rule`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule_group` (
    `id_group` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_rule` INT(10) UNSIGNED NOT NULL,
    `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_group`),
    KEY `id_rule` (`id_rule`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule_condition` (
    `id_condition` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_rule` INT(10) UNSIGNED NOT NULL,
    `id_group` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `kind` VARCHAR(16) NOT NULL,
    `mode` VARCHAR(8) NOT NULL DEFAULT 'include',
    `with_children` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `id_feature` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `price_from` DECIMAL(20,6) NULL DEFAULT NULL,
    `price_to` DECIMAL(20,6) NULL DEFAULT NULL,
    `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_condition`),
    KEY `rule_group` (`id_rule`, `id_group`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule_condition_value` (
    `id_condition` INT(10) UNSIGNED NOT NULL,
    `id_value` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id_condition`, `id_value`),
    KEY `id_value` (`id_value`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_rule_condition_category` (
    `id_condition` INT(10) UNSIGNED NOT NULL,
    `id_category` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id_condition`, `id_category`),
    KEY `id_category` (`id_category`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_infinitybadges_style` (
    `id_style` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    `bg_color` CHAR(7) NOT NULL DEFAULT '#222222',
    `bg_opacity` TINYINT(3) UNSIGNED NOT NULL DEFAULT 100,
    `text_color` CHAR(7) NOT NULL DEFAULT '#ffffff',
    `font_weight` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 600,
    `uppercase` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `border_radius` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
    `border_width` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `border_color` CHAR(7) NOT NULL DEFAULT '#000000',
    `font_size_desktop` TINYINT(3) UNSIGNED NOT NULL DEFAULT 13,
    `font_size_mobile` TINYINT(3) UNSIGNED NOT NULL DEFAULT 11,
    `padding_y_desktop` TINYINT(3) UNSIGNED NOT NULL DEFAULT 4,
    `padding_x_desktop` TINYINT(3) UNSIGNED NOT NULL DEFAULT 8,
    `padding_y_mobile` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
    `padding_x_mobile` TINYINT(3) UNSIGNED NOT NULL DEFAULT 6,
    `margin_desktop` TINYINT(3) UNSIGNED NOT NULL DEFAULT 4,
    `margin_mobile` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_style`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `PREFIX_infinitybadges_style` (`name`, `date_add`, `date_upd`)
SELECT 'Default', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `PREFIX_infinitybadges_style`);

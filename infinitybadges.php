<?php
/**
 * Infinity Badges - automatic product labels (badges) based on product features
 * and rule criteria, displayed as product flags.
 *
 * Demo version: badges from a feature value and static text badges on all products.
 * The other features are visible in the back office and work in the full version (InfinityBadgesDemo).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// The admin base controller is required by the admin controllers themselves (not needed on the front office).
require_once __DIR__ . '/classes/BadgeRule.php';
require_once __DIR__ . '/classes/BadgeStyle.php';
require_once __DIR__ . '/classes/BadgeConditionSet.php';
require_once __DIR__ . '/classes/BadgeRuleSet.php';
require_once __DIR__ . '/classes/BadgeRepository.php';
require_once __DIR__ . '/classes/BadgeDiscount.php';
require_once __DIR__ . '/classes/BadgeEngine.php';
require_once __DIR__ . '/classes/BadgeCssGenerator.php';
require_once __DIR__ . '/classes/InfinityBadgesDemo.php';

class InfinityBadges extends Module
{
    public const DEVICE_DESKTOP = 1;
    public const DEVICE_MOBILE = 2;
    public const DEVICE_ALL = 3;

    public const TRANSLATION_DOMAIN = 'Modules.Infinitybadges.Admin';

    /** Configuration keys with default values. */
    public const CONFIG_DEFAULTS = [
        // Master switch - when disabled, nothing is added on the front office.
        'INFINITYBADGES_FRONT_ENABLED' => 1,
        // Max visible badges per product and device (see getLimit()).
        'INFINITYBADGES_LIMIT_DESKTOP' => 3,
        'INFINITYBADGES_LIMIT_MOBILE' => 2,
        // 'before' or 'after' system flags (new, on-sale, discount...).
        'INFINITYBADGES_FLAGS_POSITION' => 'after',
        // Max viewport width (px) treated as mobile in generated CSS.
        'INFINITYBADGES_MOBILE_BREAKPOINT' => 767,
        'INFINITYBADGES_DEBUG' => 0,
        // Category tree checksum used to detect stale expanded categories.
        'INFINITYBADGES_TREE_CHECKSUM' => '',
        // Version (hash) of the generated stylesheet, used for cache busting.
        'INFINITYBADGES_CSS_VERSION' => '',
    ];

    public const DEVICE_NAMES = [
        self::DEVICE_DESKTOP => 'DESKTOP',
        self::DEVICE_MOBILE => 'MOBILE',
    ];

    public const HOOKS = [
        'actionProductFlagsModifier',
        'actionProductSearchAfter',
        'actionFrontControllerSetMedia',
        'actionObjectCategoryAddAfter',
        'actionObjectCategoryUpdateAfter',
        'actionObjectCategoryDeleteAfter',
        'actionObjectManufacturerDeleteAfter',
        'actionObjectSupplierDeleteAfter',
        'actionObjectFeatureDeleteAfter',
        'actionObjectFeatureValueDeleteAfter',
        'actionObjectProductDeleteAfter',
        'displayBeforeBodyClosingTag',
    ];

    public function __construct()
    {
        $this->name = 'infinitybadges';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'InfinityWeb.pl';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Product badges (demo)', [], self::TRANSLATION_DOMAIN);
        $this->description = $this->trans(
            'Demo version: product labels from feature values and static texts, displayed as product flags. Criteria, image badges and more in the full version.',
            [],
            self::TRANSLATION_DOMAIN
        );
        $this->confirmUninstall = $this->trans(
            'All badge rules and styles will be deleted. Continue?',
            [],
            self::TRANSLATION_DOMAIN
        );

        // Registered and removed automatically by PrestaShop on install/uninstall.
        $this->tabs = [
            [
                'name' => self::tabName('Product badges', 'Etykiety produktów'),
                'class_name' => 'AdminInfinityBadgesRules',
                'parent_class_name' => 'AdminCatalog',
                'visible' => true,
                'wording' => 'Product badges',
                'wording_domain' => self::TRANSLATION_DOMAIN,
            ],
            [
                'name' => self::tabName('Badge styles', 'Style etykiet'),
                'class_name' => 'AdminInfinityBadgesStyles',
                'parent_class_name' => 'AdminCatalog',
                'visible' => false,
                'wording' => 'Badge styles',
                'wording_domain' => self::TRANSLATION_DOMAIN,
            ],
            [
                'name' => self::tabName('Badge preview', 'Podgląd etykiet'),
                'class_name' => 'AdminInfinityBadgesPreview',
                'parent_class_name' => 'AdminCatalog',
                'visible' => false,
                'wording' => 'Badge preview',
                'wording_domain' => self::TRANSLATION_DOMAIN,
            ],
            [
                'name' => self::tabName('Badge settings', 'Ustawienia etykiet'),
                'class_name' => 'AdminInfinityBadgesSettings',
                'parent_class_name' => 'AdminCatalog',
                'visible' => false,
                'wording' => 'Badge settings',
                'wording_domain' => self::TRANSLATION_DOMAIN,
            ],
            [
                'name' => self::tabName('Badge license', 'Licencja etykiet'),
                'class_name' => 'AdminInfinityBadgesLicense',
                'parent_class_name' => 'AdminCatalog',
                'visible' => false,
                'wording' => 'Badge license',
                'wording_domain' => self::TRANSLATION_DOMAIN,
            ],
        ];
    }

    /**
     * Tab names per language. ModuleTabRegister matches keys by locale or ISO code;
     * "wording" alone is not applied to existing languages on install.
     *
     * @return array<string, string>
     */
    private static function tabName(string $en, string $pl): array
    {
        return ['en' => $en, 'en-US' => $en, 'pl' => $pl, 'pl-PL' => $pl];
    }

    /**
     * Translation in the module's admin domain, for classes outside controllers.
     * The source text must be a literal listed in translations/.
     */
    public static function translate(string $text, array $parameters = []): string
    {
        return Context::getContext()->getTranslator()->trans($text, $parameters, self::TRANSLATION_DOMAIN);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        if (!parent::install()
            || !$this->executeSqlFile('install.sql')
            || !$this->registerHook(self::HOOKS)
            || !$this->installConfiguration()) {
            return false;
        }

        // Not fatal: the stylesheet is generated again on first use if the directory was not writable.
        BadgeCssGenerator::generate();

        return true;
    }

    public function uninstall(): bool
    {
        // Data is removed even if one of the steps fails, so a broken install can be cleaned up.
        $sql = $this->executeSqlFile('uninstall.sql');
        $config = $this->uninstallConfiguration();
        $this->deleteGeneratedCss();

        return parent::uninstall() && $sql && $config;
    }

    public function getContent(): void
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminInfinityBadgesRules'));
    }

    /**
     * Runs a file from sql/ split into single statements.
     * Placeholders: PREFIX_ (table prefix), ENGINE_TYPE (MySQL engine).
     * Statements are separated by ";" at the end of a line; the files contain no comments.
     */
    private function executeSqlFile(string $file): bool
    {
        $path = __DIR__ . '/sql/' . $file;
        if (!is_readable($path)) {
            return false;
        }

        $sql = str_replace(
            ['PREFIX_', 'ENGINE_TYPE'],
            [_DB_PREFIX_, _MYSQL_ENGINE_],
            (string) file_get_contents($path)
        );

        $db = Db::getInstance();
        foreach (preg_split('/;\s*[\r\n]+/', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            if (!$db->execute($statement)) {
                return false;
            }
        }

        return true;
    }

    private function installConfiguration(): bool
    {
        foreach (self::CONFIG_DEFAULTS as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallConfiguration(): bool
    {
        $result = true;
        foreach (array_keys(self::CONFIG_DEFAULTS) as $key) {
            $result = Configuration::deleteByName($key) && $result;
        }

        return $result;
    }

    public static function getLimitKey(int $device): string
    {
        return 'INFINITYBADGES_LIMIT_' . (self::DEVICE_NAMES[$device] ?? 'DESKTOP');
    }

    /**
     * Max visible badges per product on a device (0 = none).
     */
    public static function getLimit(int $device): int
    {
        $key = self::getLimitKey($device);
        $value = Configuration::get($key);

        return max(0, $value === false ? (int) (self::CONFIG_DEFAULTS[$key] ?? 0) : (int) $value);
    }

    private function deleteGeneratedCss(): void
    {
        $files = glob(__DIR__ . '/views/css/generated/*.css');
        foreach ($files ?: [] as $file) {
            @unlink($file);
        }
    }

    /*
     * Front office.
     */

    /**
     * Adds badges to product flags (ProductLazyArray::getFlags()). $params['flags'] is a reference.
     *
     * @param array{flags: array, product: mixed} $params
     */
    public function hookActionProductFlagsModifier(array $params): void
    {
        if (!$this->isFrontEnabled() || !isset($params['flags']) || !is_array($params['flags'])) {
            return;
        }
        if (!$this->context->controller instanceof FrontController) {
            return;
        }
        $idProduct = $this->extractProductId($params['product'] ?? null);
        if ($idProduct <= 0) {
            return;
        }

        $badges = $this->getEngine()->getBadges($idProduct);
        if (!$badges) {
            return;
        }

        $flags = [];
        foreach ($badges as $badge) {
            // The first token is for themes that use "type" as a modifier of the first class only
            // (e.g. class="product-flags__flag--{$flag.type}"); thanks to the repeated token the
            // element always has the "infinitybadge" class. Themes printing "type" as plain classes
            // just get it twice.
            $classes = ['infinitybadge', 'infinitybadge', 'ib-style-' . (int) $badge['id_style'], 'ib-rule-' . (int) $badge['id_rule']];
            if ($badge['hide_mobile']) {
                $classes[] = 'ib-hide-mobile';
            }
            if ($badge['hide_desktop']) {
                $classes[] = 'ib-hide-desktop';
            }
            // "label" is printed as escaped text.
            $flags['infinitybadge-' . (int) $badge['id_rule']] = [
                'type' => implode(' ', $classes),
                'label' => $badge['text'],
            ];
        }

        // Pełna wersja: system flags replaced by a shown badge are removed here.

        $params['flags'] = Configuration::get('INFINITYBADGES_FLAGS_POSITION') === 'before'
            ? $flags + $params['flags']
            : $params['flags'] + $flags;
    }

    /**
     * Core listings (category, search, manufacturer, new products, prices drop...): bulk prefetch.
     */
    public function hookActionProductSearchAfter(array $params): void
    {
        if (!$this->isFrontEnabled() || !is_iterable($params['products'] ?? null)) {
            return;
        }

        $ids = [];
        foreach ($params['products'] as $product) {
            $id = $this->extractProductId($product);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids) {
            $this->getEngine()->prefetch($ids);
        }
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isFrontEnabled()) {
            return;
        }

        $path = BadgeCssGenerator::getRelativePath();
        if ($path !== null) {
            $this->context->controller->registerStylesheet('module-infinitybadges', $path, [
                'media' => 'all',
                'priority' => 250,
            ]);
        }
    }

    /**
     * @param mixed $product Presented product (array / lazy array) or Product object
     */
    private function extractProductId($product): int
    {
        if (is_array($product) || $product instanceof ArrayAccess) {
            $id = $product['id_product'] ?? null;
            if (!$id) {
                $id = $product['id'] ?? null;
            }

            return (int) $id;
        }
        if (is_object($product) && isset($product->id)) {
            return (int) $product->id;
        }

        return 0;
    }

    private function getEngine(): BadgeEngine
    {
        $idShop = (int) $this->context->shop->id ?: (int) Configuration::get('PS_SHOP_DEFAULT');

        return BadgeEngine::getInstance((int) $this->context->language->id, $idShop);
    }

    /**
     * Debug summary as an HTML comment at the end of the page.
     */
    public function hookDisplayBeforeBodyClosingTag(array $params): string
    {
        // Pełna wersja.
        return '';
    }

    private function isFrontEnabled(): bool
    {
        return (bool) Configuration::get('INFINITYBADGES_FRONT_ENABLED');
    }

    /*
     * Category tree changes and deleted objects update the criteria in the full version.
     * The hooks are registered in the demo too, so the full version works without a module reset.
     */

    public function hookActionObjectCategoryAddAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectCategoryUpdateAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectCategoryDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectManufacturerDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectSupplierDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectFeatureDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectFeatureValueDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }

    public function hookActionObjectProductDeleteAfter(array $params): void
    {
        // Pełna wersja.
    }
}

<?php
/**
 * Module settings (Configuration values) rendered with HelperOptions.
 *
 * Demo version: the debug mode and export / import are visible, but work in the full version.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'infinitybadges/classes/InfinityBadgesAdminController.php';

class AdminInfinityBadgesSettingsController extends InfinityBadgesAdminController
{
    private const MAX_LIMIT = 20;

    public function __construct()
    {
        $this->table = 'configuration';
        $this->className = 'Configuration';

        parent::__construct();

        $this->fields_options = [
            'general' => [
                'title' => $this->t('General'),
                'icon' => 'icon-cogs',
                'fields' => [
                    'INFINITYBADGES_FRONT_ENABLED' => [
                        'title' => $this->t('Show badges on the front office'),
                        'desc' => $this->t('When disabled, the module does not change anything for customers.'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    'INFINITYBADGES_FLAGS_POSITION' => [
                        'title' => $this->t('Badge position'),
                        'desc' => $this->t('Relative to system flags such as New or On sale.'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 'after', 'name' => $this->t('After system flags')],
                            ['id' => 'before', 'name' => $this->t('Before system flags')],
                        ],
                    ],
                    'INFINITYBADGES_MOBILE_BREAKPOINT' => [
                        'title' => $this->t('Mobile breakpoint'),
                        'desc' => $this->t('Maximum screen width treated as mobile.'),
                        'type' => 'text',
                        'suffix' => 'px',
                        'class' => 'fixed-width-sm',
                        'validation' => 'isUnsignedInt',
                        'cast' => 'intval',
                    ],
                    'INFINITYBADGES_DEBUG' => [
                        'title' => $this->t('Debug mode'),
                        'desc' => $this->t('Adds an HTML comment with the number of module queries and engine time at the end of the page. Shown only to IP addresses listed in the maintenance settings.')
                            . ' ' . $this->t('Full version.'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                ],
                'submit' => ['title' => $this->t('Save')],
            ],
            'limits' => [
                'title' => $this->t('Badge limits'),
                'icon' => 'icon-list-ol',
                'description' => $this->t('Maximum number of visible badges per product (0 = no badges). Badges with higher priority are kept.'),
                'fields' => $this->getLimitFields(),
                'submit' => ['title' => $this->t('Save')],
            ],
        ];
    }

    /**
     * Settings form followed by the export / import panel (demo: disabled, full version).
     */
    public function renderOptions()
    {
        return parent::renderOptions()
            . $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/transfer.tpl');
    }

    /**
     * Range checks not covered by Validate (runs before the values are saved).
     */
    public function beforeUpdateOptions()
    {
        $breakpoint = Tools::getValue('INFINITYBADGES_MOBILE_BREAKPOINT');
        if ($breakpoint !== false && ((int) $breakpoint < 320 || (int) $breakpoint > 2000)) {
            $this->errors[] = $this->t('The mobile breakpoint must be between 320 and 2000 px.');
        }

        if (!in_array(Tools::getValue('INFINITYBADGES_FLAGS_POSITION', 'after'), ['after', 'before'], true)) {
            $this->errors[] = $this->t('Invalid badge position.');
        }

        foreach (array_keys(InfinityBadges::DEVICE_NAMES) as $device) {
            $value = Tools::getValue(InfinityBadges::getLimitKey($device));
            if ($value !== false && ((int) $value < 0 || (int) $value > self::MAX_LIMIT)) {
                $this->errors[] = $this->t('Limits must be between 0 and %max%.', ['%max%' => self::MAX_LIMIT]);

                return;
            }
        }

        // The breakpoint is part of the generated stylesheet; values are saved right after this method.
        if (!$this->errors && $breakpoint !== false) {
            BadgeCssGenerator::generate((int) $breakpoint);
        }
    }

    private function getLimitFields(): array
    {
        $fields = [];
        foreach ([InfinityBadges::DEVICE_DESKTOP => $this->t('Desktop'), InfinityBadges::DEVICE_MOBILE => $this->t('Mobile')] as $device => $label) {
            $fields[InfinityBadges::getLimitKey($device)] = [
                'title' => $label,
                'type' => 'text',
                'class' => 'fixed-width-sm',
                'validation' => 'isUnsignedInt',
                'cast' => 'intval',
            ];
        }

        return $fields;
    }
}

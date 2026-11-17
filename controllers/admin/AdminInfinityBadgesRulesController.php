<?php
/**
 * Badge rules: list (status, positions, duplicate) and form with the criteria editor.
 *
 * Demo version: the criteria section describes the full version, options outside the demo are marked
 * "full version" and disabled by views/js/admin-rules.js. Saving does not enforce them (the demo engine ignores them).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'infinitybadges/classes/InfinityBadgesAdminController.php';

class AdminInfinityBadgesRulesController extends InfinityBadgesAdminController
{
    public function __construct()
    {
        $this->table = 'infinitybadges_rule';
        $this->className = 'BadgeRule';
        $this->identifier = 'id_rule';
        $this->position_identifier = 'id_rule';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->_default_pagination = 300;
        $this->allow_export = false;

        parent::__construct();

        $idLang = (int) $this->context->language->id;
        $this->_select = 'fl.`name` AS feature_name, s.`name` AS style_name,
            (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'infinitybadges_rule_group` g WHERE g.`id_rule` = a.`id_rule`) AS groups_count';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl ON fl.`id_feature` = a.`id_feature` AND fl.`id_lang` = ' . $idLang . '
            LEFT JOIN `' . _DB_PREFIX_ . 'infinitybadges_style` s ON s.`id_style` = a.`id_style`';

        $this->addRowAction('edit');
        $this->addRowAction('matches');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->t('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->t('Delete selected rules?'),
            ],
        ];

        $this->fields_list = [
            'id_rule' => ['title' => $this->t('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->t('Name'), 'filter_key' => 'a!name'],
            'type' => [
                'title' => $this->t('Type'),
                'type' => 'select',
                'list' => [
                    BadgeRule::TYPE_FEATURE => $this->t('From feature'),
                    BadgeRule::TYPE_STATIC => $this->t('Static text'),
                    BadgeRule::TYPE_IMAGE => $this->t('Image'),
                ],
                'filter_key' => 'a!type',
                'callback' => 'renderTypeLabel',
            ],
            'feature_name' => ['title' => $this->t('Feature'), 'filter_key' => 'fl!name'],
            'groups_count' => ['title' => $this->t('Groups'), 'align' => 'center', 'search' => false, 'orderby' => false, 'class' => 'fixed-width-sm'],
            'style_name' => ['title' => $this->t('Style'), 'filter_key' => 's!name'],
            'priority' => ['title' => $this->t('Priority'), 'align' => 'center', 'class' => 'fixed-width-sm', 'filter_key' => 'a!priority'],
            'date_from' => ['title' => $this->t('Valid from'), 'type' => 'datetime', 'filter_key' => 'a!date_from'],
            'date_to' => ['title' => $this->t('Valid to'), 'type' => 'datetime', 'filter_key' => 'a!date_to'],
            'position' => ['title' => $this->t('Position'), 'position' => 'position', 'align' => 'center', 'class' => 'fixed-width-sm', 'filter_key' => 'a!position'],
            'active' => ['title' => $this->t('Status'), 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm', 'filter_key' => 'a!active'],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->assetUrl('views/js/admin-rules.js'));
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_rule'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->t('Add new rule'),
                'icon' => 'process-icon-new',
            ];
        } elseif ($this->display === 'edit' && ($idRule = (int) Tools::getValue($this->identifier)) > 0) {
            $this->page_header_toolbar_btn['matches'] = [
                'href' => $this->getMatchesHref($idRule),
                'desc' => $this->t('Matching products'),
                'icon' => 'process-icon-preview',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderTypeLabel($value): string
    {
        switch ($value) {
            case BadgeRule::TYPE_FEATURE:
                return $this->t('From feature');
            case BadgeRule::TYPE_IMAGE:
                return $this->t('Image');
        }

        return $this->t('Static text');
    }

    public function displayMatchesLink($token = null, $id = 0, $name = null): string
    {
        return '<a href="' . htmlspecialchars($this->getMatchesHref((int) $id), ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="icon-bar-chart"></i> ' . htmlspecialchars($this->t('Matching products'), ENT_QUOTES, 'UTF-8') . '</a>';
    }

    private function getMatchesHref(int $idRule): string
    {
        return $this->context->link->getAdminLink('AdminInfinityBadgesPreview') . '&ib_rule=' . $idRule;
    }

    public function displayDuplicateLink($token = null, $id = 0, $name = null): string
    {
        $href = self::$currentIndex . '&' . $this->identifier . '=' . (int) $id
            . '&ib_duplicate=1&token=' . ($token ?: $this->token);

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="icon-copy"></i> ' . htmlspecialchars($this->t('Duplicate'), ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function postProcess()
    {
        if (Tools::getValue('ib_duplicate')) {
            $this->processDuplicateRule();

            return false;
        }

        return parent::postProcess();
    }

    /**
     * Drag & drop in the list. Posted rows: "tr_{group}_{id}_{position}" in the new order.
     */
    public function ajaxProcessUpdatePositions(): void
    {
        $rows = Tools::getValue($this->table);
        if (!is_array($rows) || $this->access('edit') !== true) {
            $this->sendJson(['hasError' => true]);
        }

        $db = Db::getInstance();
        foreach (array_values($rows) as $index => $row) {
            $parts = explode('_', (string) $row);
            $id = (int) ($parts[2] ?? 0);
            if ($id > 0) {
                $db->update($this->table, ['position' => $index + 1], '`id_rule` = ' . $id);
            }
        }

        $this->sendJson(['hasError' => false]);
    }

    public function renderForm()
    {
        /** @var BadgeRule|false $rule */
        $rule = $this->loadObject(true);
        if (!$rule) {
            return '';
        }

        $isSubmitted = Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitAdd' . $this->table . 'AndStay');
        foreach ([InfinityBadges::DEVICE_DESKTOP, InfinityBadges::DEVICE_MOBILE] as $bit) {
            $this->fields_value['device_' . $bit] = $isSubmitted
                ? (bool) Tools::getValue('device_' . $bit)
                : (bool) ((int) $rule->devices & $bit);
        }
        $hiddenFlags = BadgeRule::parseHideFlags((string) $rule->hide_flags);
        foreach (BadgeRule::SYSTEM_FLAGS as $type) {
            $this->fields_value['hide_flag_' . $type] = $isSubmitted
                ? (bool) Tools::getValue('hide_flag_' . $type)
                : in_array($type, $hiddenFlags, true);
        }

        $this->multiple_fieldsets = true;
        $this->fields_form = [
            ['form' => $this->getBadgeFieldset($rule)],
            ['form' => $this->getCriteriaFieldset($rule)],
            ['form' => $this->getDisplayFieldset()],
        ];

        return parent::renderForm();
    }

    /**
     * Adds business validation of the rule to the standard field validation.
     * Pełna wersja: validation of criteria and of the uploaded image.
     */
    public function validateRules($class_name = false)
    {
        parent::validateRules($class_name);

        $id = (int) Tools::getValue($this->identifier);
        $rule = $id ? new BadgeRule($id) : new BadgeRule();
        $this->copyFromPost($rule, $this->table);

        foreach ($rule->validateBusinessRules() as $error) {
            $this->errors[] = $error;
        }
    }

    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        $object->devices = $this->bitmaskFromPost('device_', [InfinityBadges::DEVICE_DESKTOP, InfinityBadges::DEVICE_MOBILE]);
        $object->priority = (int) Tools::getValue('priority');
        $object->id_feature = $object->type === BadgeRule::TYPE_FEATURE ? (int) Tools::getValue('id_feature') : 0;

        $hiddenFlags = [];
        foreach (BadgeRule::SYSTEM_FLAGS as $type) {
            if (Tools::getValue('hide_flag_' . $type)) {
                $hiddenFlags[] = $type;
            }
        }
        $object->hide_flags = implode(',', $hiddenFlags);
    }

    private function processDuplicateRule(): void
    {
        if ($this->access('add') !== true) {
            $this->errors[] = $this->t('You do not have permission to add rules.');

            return;
        }

        $rule = new BadgeRule((int) Tools::getValue($this->identifier));
        if (Validate::isLoadedObject($rule) && $rule->duplicate()) {
            Tools::redirectAdmin(self::$currentIndex . '&conf=3&token=' . $this->token);
        }

        $this->errors[] = $this->t('The rule could not be duplicated.');
    }

    private function getBadgeFieldset(BadgeRule $rule): array
    {
        $features = Feature::getFeatures((int) $this->context->language->id);
        $fullVersion = ' (' . $this->t('full version') . ')';

        return [
            'legend' => ['title' => $this->t('Badge'), 'icon' => 'icon-tag'],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->t('Name'),
                    'name' => 'name',
                    'required' => true,
                    'maxlength' => 128,
                    'desc' => $this->t('Internal name, not shown to customers.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->t('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->t('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->t('No')],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Badge type'),
                    'name' => 'type',
                    'options' => [
                        'query' => [
                            ['id' => BadgeRule::TYPE_FEATURE, 'name' => $this->t('From feature')],
                            ['id' => BadgeRule::TYPE_STATIC, 'name' => $this->t('Static text')],
                            ['id' => BadgeRule::TYPE_IMAGE, 'name' => $this->t('Image') . $fullVersion],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Feature'),
                    'name' => 'id_feature',
                    'form_group_class' => 'ib-feature-only',
                    'options' => [
                        'query' => array_merge(
                            [['id_feature' => 0, 'name' => $this->t('-- Select a feature --')]],
                            $features ?: []
                        ),
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Text mode'),
                    'name' => 'text_mode',
                    'form_group_class' => 'ib-feature-only',
                    'options' => [
                        'query' => [
                            ['id' => BadgeRule::TEXT_MODE_VALUE, 'name' => $this->t('Feature value')],
                            ['id' => BadgeRule::TEXT_MODE_FEATURE_NAME, 'name' => $this->t('Feature name') . $fullVersion],
                            ['id' => BadgeRule::TEXT_MODE_CUSTOM, 'name' => $this->t('Custom text') . $fullVersion],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->t('"Feature name" shows e.g. "Motion sensor" for the value "Yes".'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->t('Hidden values'),
                    'name' => 'hide_values',
                    'form_group_class' => 'ib-feature-only',
                    'rows' => 3,
                    'desc' => $this->t('One value per line, case-insensitive (e.g. No). These values are not shown; without other values there is no badge.')
                        . ' ' . $this->t('Full version.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->t('Prefix'),
                    'name' => 'prefix',
                    'lang' => true,
                    'form_group_class' => 'ib-feature-only',
                    'class' => 'fixed-width-md',
                    'desc' => $this->t('E.g. "IP " or "Ø ". Not added when the value already starts with it.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->t('Suffix'),
                    'name' => 'suffix',
                    'lang' => true,
                    'form_group_class' => 'ib-feature-only',
                    'class' => 'fixed-width-md',
                    'desc' => $this->t('E.g. " cm". Not added when the value already ends with it.'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->t('Text'),
                    'name' => 'text',
                    'lang' => true,
                    'form_group_class' => 'ib-text-field',
                    'desc' => $this->t('Badge text; for an image badge the alternative text (optional). Missing translations fall back to the default language.')
                        . ' ' . $this->t('[discount] is replaced with the product discount in whole percent (e.g. "SALE -[discount]%"); without a discount the badge is not shown.')
                        . ' ' . $this->t('In the demo version a badge with [discount] is not shown.'),
                ],
            ],
            'submit' => ['title' => $this->t('Save')],
            'buttons' => [$this->getSaveAndStayButton()],
        ];
    }

    private function getCriteriaFieldset(BadgeRule $rule): array
    {
        return [
            'legend' => ['title' => $this->t('Criteria'), 'icon' => 'icon-filter'],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'ib_conditions_editor',
                    'html_content' => $this->renderConditionsEditor($rule),
                ],
            ],
            'submit' => ['title' => $this->t('Save')],
            'buttons' => [$this->getSaveAndStayButton()],
        ];
    }

    private function getDisplayFieldset(): array
    {
        $styles = array_map(static function (array $style): array {
            return ['id' => $style['id_style'], 'name' => $style['name']];
        }, BadgeStyle::getAll());

        return [
            'legend' => ['title' => $this->t('Display'), 'icon' => 'icon-eye'],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->t('Priority'),
                    'name' => 'priority',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->t('Higher priority badges are shown first and win when the limit is reached.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->t('Style'),
                    'name' => 'id_style',
                    'form_group_class' => 'ib-not-image',
                    'options' => [
                        'query' => array_merge([['id' => 0, 'name' => $this->t('Base style')]], $styles),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->t('Devices'),
                    'name' => 'device',
                    'values' => [
                        'query' => [
                            ['id' => InfinityBadges::DEVICE_DESKTOP, 'name' => $this->t('Desktop')],
                            ['id' => InfinityBadges::DEVICE_MOBILE, 'name' => $this->t('Mobile')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->t('Hide system flags'),
                    'name' => 'hide_flag',
                    'values' => [
                        'query' => [
                            ['id' => 'discount', 'name' => $this->t('Discount (e.g. "-20%")')],
                            ['id' => 'on-sale', 'name' => $this->t('On sale')],
                            ['id' => 'new', 'name' => $this->t('New')],
                            ['id' => 'pack', 'name' => $this->t('Pack')],
                            ['id' => 'online-only', 'name' => $this->t('Online only')],
                            ['id' => 'out_of_stock', 'name' => $this->t('Out of stock')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->t('Selected PrestaShop flags are removed from products where this badge is shown (on at least one device). Where the badge is not shown, they stay.')
                        . ' ' . $this->t('Full version.'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->t('Valid from'),
                    'name' => 'date_from',
                    'desc' => $this->t('Leave empty for no limit.'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->t('Valid to'),
                    'name' => 'date_to',
                    'desc' => $this->t('Leave empty for no limit.'),
                ],
            ],
            'submit' => ['title' => $this->t('Save')],
            'buttons' => [$this->getSaveAndStayButton()],
        ];
    }

    private function getSaveAndStayButton(): array
    {
        return [
            'title' => $this->t('Save and stay'),
            'name' => 'submitAdd' . $this->table . 'AndStay',
            'type' => 'submit',
            'class' => 'btn btn-default pull-right',
            'icon' => 'process-icon-save',
        ];
    }

    /**
     * Demo: description of the criteria instead of the editor (criteria are in the full version).
     */
    private function renderConditionsEditor(BadgeRule $rule): string
    {
        $this->context->smarty->assign('ib_criteria', [
            'demo' => $this->t('Demo version: the badge is shown on all products. For a feature badge, only on products that have a value of the feature.'),
            'intro' => $this->t('In the full version you choose which products get the badge:'),
            'items' => [
                $this->t('groups of criteria: the badge is shown when any group matches, and within a group all criteria must match;'),
                $this->t('criteria: category (with subcategories), manufacturer, supplier, feature value, price, discount and product, each with "include" or "exclude";'),
                $this->t('"Always exclude": products matching any of these criteria never get the badge, whatever the groups.'),
            ],
            'example' => $this->t('Example: "Made in Poland" for the products of one manufacturer, but never in the "Light bulbs" category.'),
            'buttons' => [$this->t('Add group'), $this->t('Add exclusion')],
            'full_version' => $this->t('full version'),
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/conditions.tpl');
    }

    private function bitmaskFromPost(string $prefix, array $bits): int
    {
        $mask = 0;
        foreach ($bits as $bit) {
            if (Tools::getValue($prefix . $bit)) {
                $mask |= $bit;
            }
        }

        return $mask;
    }

    private function sendJson($data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

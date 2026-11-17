<?php
/**
 * Badge style presets: list with preview and form with a live preview (desktop / mobile).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'infinitybadges/classes/InfinityBadgesAdminController.php';

class AdminInfinityBadgesStylesController extends InfinityBadgesAdminController
{
    public function __construct()
    {
        $this->table = 'infinitybadges_style';
        $this->className = 'BadgeStyle';
        $this->identifier = 'id_style';
        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = false;

        parent::__construct();

        $this->_select = '(SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'infinitybadges_rule` r WHERE r.`id_style` = a.`id_style`) AS rules_count';

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->t('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->t('Delete selected styles? Rules using them will use the base style.'),
            ],
        ];

        $this->fields_list = [
            'id_style' => ['title' => $this->t('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name' => ['title' => $this->t('Name')],
            'bg_color' => [
                'title' => $this->t('Preview'),
                'search' => false,
                'orderby' => false,
                'callback' => 'renderPreview',
            ],
            'rules_count' => ['title' => $this->t('Rules'), 'align' => 'center', 'search' => false, 'class' => 'fixed-width-sm'],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->assetUrl('views/js/admin-styles.js'));
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_style'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->t('Add new style'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function initContent()
    {
        if (!BadgeCssGenerator::isWritable()) {
            $this->warnings[] = $this->t('The directory %dir% is not writable: styles cannot be applied on the front office.', [
                '%dir%' => 'modules/infinitybadges/views/css/generated/',
            ]);
        }

        parent::initContent();
    }

    /**
     * List column: badge sample with the desktop values of the style.
     */
    public function renderPreview($value, array $row): string
    {
        return '<span class="ib-checker"><span class="ib-style-sample" style="' . htmlspecialchars($this->inlineStyle($row), ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($this->t('Sample badge'), ENT_QUOTES, 'UTF-8') . '</span></span>';
    }

    public function renderForm()
    {
        $style = $this->loadObject(true);
        if (!$style) {
            return '';
        }

        $px = ['class' => 'fixed-width-sm', 'suffix' => 'px'];

        $this->fields_form = [
            'legend' => ['title' => $this->t('Badge style'), 'icon' => 'icon-paint-brush'],
            'input' => [
                ['type' => 'text', 'label' => $this->t('Name'), 'name' => 'name', 'required' => true, 'maxlength' => 64],
                ['type' => 'color', 'label' => $this->t('Background color'), 'name' => 'bg_color', 'required' => true],
                [
                    'type' => 'text',
                    'label' => $this->t('Background opacity'),
                    'name' => 'bg_opacity',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'suffix' => '%',
                    'desc' => $this->t('100% = solid background, 0% = transparent background (only the text and the border are visible).'),
                ],
                ['type' => 'color', 'label' => $this->t('Text color'), 'name' => 'text_color', 'required' => true],
                [
                    'type' => 'select',
                    'label' => $this->t('Font weight'),
                    'name' => 'font_weight',
                    'options' => [
                        'query' => array_map(static function (int $weight): array {
                            return ['id' => $weight, 'name' => (string) $weight];
                        }, BadgeStyle::FONT_WEIGHTS),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->t('Uppercase'),
                    'name' => 'uppercase',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'uppercase_on', 'value' => 1, 'label' => $this->t('Yes')],
                        ['id' => 'uppercase_off', 'value' => 0, 'label' => $this->t('No')],
                    ],
                ],
                ['type' => 'text', 'label' => $this->t('Border radius'), 'name' => 'border_radius'] + $px,
                ['type' => 'text', 'label' => $this->t('Border width'), 'name' => 'border_width', 'desc' => $this->t('0 = no border.')] + $px,
                ['type' => 'color', 'label' => $this->t('Border color'), 'name' => 'border_color'],
                ['type' => 'text', 'label' => $this->t('Font size (desktop)'), 'name' => 'font_size_desktop'] + $px,
                ['type' => 'text', 'label' => $this->t('Vertical padding (desktop)'), 'name' => 'padding_y_desktop'] + $px,
                ['type' => 'text', 'label' => $this->t('Horizontal padding (desktop)'), 'name' => 'padding_x_desktop'] + $px,
                ['type' => 'text', 'label' => $this->t('Top margin (desktop)'), 'name' => 'margin_desktop', 'desc' => $this->t('Space above the badge, between flags.')] + $px,
                ['type' => 'text', 'label' => $this->t('Font size (mobile)'), 'name' => 'font_size_mobile'] + $px,
                ['type' => 'text', 'label' => $this->t('Vertical padding (mobile)'), 'name' => 'padding_y_mobile'] + $px,
                ['type' => 'text', 'label' => $this->t('Horizontal padding (mobile)'), 'name' => 'padding_x_mobile'] + $px,
                ['type' => 'text', 'label' => $this->t('Top margin (mobile)'), 'name' => 'margin_mobile'] + $px,
                [
                    'type' => 'html',
                    'name' => 'ib_style_preview',
                    'html_content' => $this->renderLivePreview(),
                ],
            ],
            'submit' => ['title' => $this->t('Save')],
            'buttons' => [[
                'title' => $this->t('Save and stay'),
                'name' => 'submitAdd' . $this->table . 'AndStay',
                'type' => 'submit',
                'class' => 'btn btn-default pull-right',
                'icon' => 'process-icon-save',
            ]],
        ];

        return parent::renderForm();
    }

    public function validateRules($class_name = false)
    {
        parent::validateRules($class_name);

        $id = (int) Tools::getValue($this->identifier);
        $style = $id ? new BadgeStyle($id) : new BadgeStyle();
        $this->copyFromPost($style, $this->table);
        foreach ($style->validateBusinessRules() as $error) {
            $this->errors[] = $error;
        }
    }

    private function renderLivePreview(): string
    {
        $this->context->smarty->assign([
            'ib_sample' => $this->t('Sample badge'),
            'ib_contrast_warning' => $this->t('Low contrast between text and background (below 4.5:1). The badge may be hard to read.'),
            'ib_translucent_note' => $this->t('With a translucent background the readability also depends on the product image under the badge. Contrast is checked against a white background.'),
            'ib_label_desktop' => $this->t('Desktop'),
            'ib_label_mobile' => $this->t('Mobile'),
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/style_preview.tpl');
    }

    private function inlineStyle(array $row): string
    {
        $color = BadgeStyle::isHexColor((string) $row['text_color']) ? $row['text_color'] : '#ffffff';

        return BadgeCssGenerator::background($row) . ';color:' . $color . ';font-weight:' . (int) $row['font_weight']
            . ';text-transform:' . ((int) $row['uppercase'] ? 'uppercase' : 'none')
            . ';border-radius:' . (int) $row['border_radius'] . 'px'
            . ';' . BadgeCssGenerator::border($row)
            . ';font-size:' . (int) $row['font_size_desktop'] . 'px'
            . ';padding:' . (int) $row['padding_y_desktop'] . 'px ' . (int) $row['padding_x_desktop'] . 'px';
    }
}

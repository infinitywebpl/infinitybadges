<?php
/**
 * Base back office controller of the module: common tab navigation
 * (Rules / Styles / Preview / Settings / License) rendered around the content,
 * and the demo version notice above it.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class InfinityBadgesAdminController extends ModuleAdminController
{
    /** Controller class name => label (translated in getNavItems()). */
    private const NAV = [
        'AdminInfinityBadgesRules' => 'Rules',
        'AdminInfinityBadgesStyles' => 'Styles',
        'AdminInfinityBadgesPreview' => 'Preview',
        'AdminInfinityBadgesSettings' => 'Settings',
        'AdminInfinityBadgesLicense' => 'License',
    ];

    /** Controller without the demo notice (it describes the demo version itself). */
    protected const LICENSE_CONTROLLER = 'AdminInfinityBadgesLicense';

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * No return type: AdminController::initContent() has none, so subclasses may override it freely.
     */
    public function initContent()
    {
        parent::initContent();

        // AdminController::initContent() has already assigned 'content'; wrap it in the tab navigation.
        $this->context->smarty->assign('content', $this->renderNav($this->content));
    }

    /**
     * No return type, as in AdminController::setMedia(). Subclasses add their own assets after parent::setMedia().
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->assetUrl('views/css/admin.css'));
    }

    /**
     * URL of a module asset (path relative to the module directory) versioned by file modification
     * time, so browsers always load the current file after an upload.
     */
    protected function assetUrl(string $path): string
    {
        $file = _PS_MODULE_DIR_ . 'infinitybadges/' . $path;

        return _MODULE_DIR_ . 'infinitybadges/' . $path . '?v=' . (is_file($file) ? filemtime($file) : '0');
    }

    protected function renderNav(string $content): string
    {
        $this->context->smarty->assign([
            'ib_nav' => $this->getNavItems(),
            'ib_content' => $content,
            'ib_license_notice' => $this->controller_name === self::LICENSE_CONTROLLER || !$this->viewAccess()
                ? ''
                : $this->renderDemoNotice(),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/nav.tpl'
        );
    }

    /**
     * Placeholder for controllers that are not implemented yet.
     */
    protected function renderPlaceholder(): string
    {
        $this->context->smarty->assign(
            'ib_message',
            $this->t('This section will be available in the next version of the module.')
        );

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/placeholder.tpl'
        );
    }

    /**
     * Demo version notice above the tabs, with a link to the License tab (features of the full version).
     */
    private function renderDemoNotice(): string
    {
        $email = InfinityBadgesDemo::CONTACT_EMAIL;

        return '<div class="alert alert-info ib-demo-notice"><strong>' . $this->t('Demo version.') . '</strong> '
            . $this->licenseHtml(
                $this->t('Badges from feature values and static texts work on all products. Criteria, image badges, [discount], the preview and the other options marked "full version" work in the full version: %email%.'),
                [],
                ['%email%' => '<a href="mailto:' . $this->escape($email) . '">' . $this->escape($email) . '</a>']
            )
            . ' <a href="' . $this->escape($this->context->link->getAdminLink(self::LICENSE_CONTROLLER)) . '">'
            . $this->escapeTranslated($this->t('Full version')) . ' &rarr;</a></div>';
    }

    /**
     * @return array<int, array{label: string, href: string, active: bool}>
     */
    private function getNavItems(): array
    {
        $items = [];
        foreach (self::NAV as $controller => $label) {
            $items[] = [
                'label' => $this->t($label),
                'href' => $this->context->link->getAdminLink($controller),
                'active' => $controller === $this->controller_name,
            ];
        }

        return $items;
    }

    /**
     * Translation shortcut for the module's admin domain.
     * Labels must be literal strings listed in translations/ to be translated.
     */
    protected function t(string $text, array $parameters = []): string
    {
        return $this->trans($text, $parameters, InfinityBadges::TRANSLATION_DOMAIN);
    }

    /**
     * The result of trans() in a controller is already passed through htmlspecialchars(ENT_NOQUOTES)
     * (Controller::trans() in 8.1), so it is not escaped again. %…% placeholders are replaced with escaped
     * values ($values) or ready HTML fragments ($html). Translations contain no HTML.
     *
     * @param array<string, string> $values
     * @param array<string, string> $html
     */
    protected function licenseHtml(string $translated, array $values = [], array $html = []): string
    {
        return strtr($translated, array_map(fn (string $value): string => $this->escape($value), $values) + $html);
    }

    protected function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escapes a trans() result (already htmlspecialchars(ENT_NOQUOTES)) without double-encoding entities,
     * but with quotes, so it is also safe in attributes. Smarty equivalent: `|escape:'html':'UTF-8':false`.
     */
    protected function escapeTranslated(string $translated): string
    {
        return htmlspecialchars($translated, ENT_QUOTES, 'UTF-8', false);
    }
}

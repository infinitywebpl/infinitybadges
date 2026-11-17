<?php
/**
 * License tab, demo variant: what the full version adds and how to get it.
 * No license key and no license status (the demo does not check licenses).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'infinitybadges/classes/InfinityBadgesAdminController.php';

class AdminInfinityBadgesLicenseController extends InfinityBadgesAdminController
{
    /**
     * AdminController::initContent() renders the list when no other view is set; this tab has no list.
     */
    public function renderList()
    {
        return $this->renderLicense();
    }

    /**
     * The template receives ready HTML from licenseHtml() (translations after htmlspecialchars).
     */
    private function renderLicense(): string
    {
        $email = InfinityBadgesDemo::CONTACT_EMAIL;
        $mailto = '<a href="mailto:' . $this->escape($email) . '"><strong>' . $this->escape($email) . '</strong></a>';

        $this->context->smarty->assign([
            'ib_license' => [
                'back_url' => $this->context->link->getAdminLink('AdminInfinityBadgesRules'),
                'github_url' => InfinityBadgesDemo::GITHUB_URL,
                'site_url' => InfinityBadgesDemo::SITE_URL,
                'features' => array_map(
                    fn (string $feature): string => $this->escapeTranslated($feature),
                    InfinityBadgesDemo::fullVersionFeatures()
                ),
                'html' => [
                    'header' => $this->licenseHtml(
                        $this->t('%name% · version %version% · Demo version · author InfinityWeb.pl'),
                        ['%name%' => (string) $this->module->displayName, '%version%' => (string) $this->module->version]
                    ),
                    'demo' => '<strong>' . $this->t('This is the demo version.') . '</strong> '
                        . $this->t('Some of the module features work in it. The others are visible in the back office and in the code, but work only in the full version.'),
                    'full' => $this->t('Full version:'),
                    'contact' => $this->licenseHtml(
                        $this->t('Want the full version? Write to %email% or visit %url%'),
                        [],
                        ['%email%' => $mailto, '%url%' => $this->externalLink(InfinityBadgesDemo::FULL_VERSION_URL)]
                    ),
                    'back' => $this->t('Back to list'),
                    'more_title' => $this->t('More PrestaShop modules from InfinityWeb.pl'),
                    'more_github' => $this->licenseHtml(
                        $this->t('Demos of other modules are available on GitHub: %url%'),
                        [],
                        ['%url%' => $this->externalLink(InfinityBadgesDemo::GITHUB_URL)]
                    ),
                    'more_site' => $this->licenseHtml(
                        $this->t('Descriptions, usage examples and contact: %url%'),
                        [],
                        ['%url%' => $this->externalLink(InfinityBadgesDemo::SITE_URL)]
                    ),
                    'more_github_button' => $this->t('Modules on GitHub'),
                    'footer' => $this->t('The demo version does not check the license and sends no data to the author.'),
                ],
            ],
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/license.tpl');
    }

    private function externalLink(string $url): string
    {
        return '<a href="' . $this->escape($url) . '" target="_blank" rel="noopener">' . $this->escape($url) . '</a>';
    }
}

<?php
/**
 * Preview: badges of a product with the decision (and its reason) for every rule, and products matching a rule.
 *
 * Demo version: the form is visible, the result is in the full version.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'infinitybadges/classes/InfinityBadgesAdminController.php';

class AdminInfinityBadgesPreviewController extends InfinityBadgesAdminController
{
    public function renderList()
    {
        $query = trim((string) Tools::getValue('ib_product', ''));
        $languages = Language::getLanguages(false);
        $idLang = (int) Tools::getValue('ib_lang', (int) $this->context->language->id);
        if (!in_array($idLang, array_map('intval', array_column($languages, 'id_lang')), true)) {
            $idLang = (int) $this->context->language->id;
        }

        $view = [
            'query' => $query,
            'id_lang' => $idLang,
            'languages' => array_map(static function (array $language): array {
                return ['id' => (int) $language['id_lang'], 'name' => (string) $language['name']];
            }, $languages),
            'token' => $this->token,
            'error' => null,
            'candidates' => [],
            'product' => null,
            'badges' => [],
            'trace' => [],
            'analysis' => null,
        ];

        // Pełna wersja.
        if ($query !== '' || (int) Tools::getValue('ib_rule') > 0) {
            $view['error'] = $this->t('Demo version: the preview on a product and matching products of a rule work in the full version.');
        }

        $this->context->smarty->assign('ib', $view);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'infinitybadges/views/templates/admin/preview.tpl');
    }
}

<?php
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Get all template directories found in catalog folder structure
 *
 * @since ZC v1.5.8
 */
function zen_get_catalog_template_directories(bool $include_template_default = false): array
{
    $resolver = new \Zencart\TemplateResolver\TemplateResolver();
    return $resolver->getSelectableTemplates((bool)$include_template_default);
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_search_directories(
    string $templateKey,
    array $subdirectories = [],
    bool $includeTemplateDefault = true,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): array
{
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $chain = $resolver->getTemplateInheritanceChain($templateKey);
    if ($includeTemplateDefault !== true) {
        $chain = array_values(array_filter($chain, static fn(string $item): bool => $item !== 'template_default'));
    }

    $directories = [];
    foreach ($chain as $chainTemplateKey) {
        $templatePath = $resolver->getTemplateFilesystemPath($chainTemplateKey);
        if ($templatePath === null) {
            continue;
        }

        if ($subdirectories === []) {
            $directories[] = rtrim($templatePath, '/') . '/';
            continue;
        }

        foreach ($subdirectories as $subdirectory) {
            $directories[] = rtrim($templatePath, '/') . '/' . trim($subdirectory, '/') . '/';
        }
    }

    return array_values(array_unique($directories));
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_inheritance_chain(
    string $templateKey,
    bool $includeTemplateDefault = true,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): array {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $chain = $resolver->getTemplateInheritanceChain($templateKey);
    if ($includeTemplateDefault !== true) {
        $chain = array_values(array_filter($chain, static fn(string $item): bool => $item !== 'template_default'));
    }

    return array_values(array_unique($chain));
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_catalog_override_directories(
    string $templateKey,
    string $catalogBasePath,
    bool $includeTemplateDefault = true,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): array {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $catalogBasePath = trim($catalogBasePath, '/');
    $directories = [];

    foreach (zen_get_template_inheritance_chain($templateKey, $includeTemplateDefault, $resolver) as $chainTemplateKey) {
        $record = $resolver->getTemplateRecord($chainTemplateKey);
        if ($record === null) {
            continue;
        }

        if (!empty($record['is_plugin_template'])) {
            $directories[] = 'zc_plugins/' . $record['plugin_key'] . '/' . $record['plugin_version'] . '/catalog/' . $catalogBasePath . '/' . $chainTemplateKey . '/';
            continue;
        }

        $directories[] = $catalogBasePath . '/' . $chainTemplateKey . '/';
    }

    return array_values(array_unique($directories));
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_language_override_directories(
    string $templateKey,
    string $languageRootPath,
    string $language,
    string $extraPath = '',
    bool $includeTemplateDefault = true,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): array {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $languageRootPath = rtrim($languageRootPath, '/') . '/';
    $extraPath = trim($extraPath, '/');
    $directories = [];

    foreach (zen_get_template_inheritance_chain($templateKey, $includeTemplateDefault, $resolver) as $chainTemplateKey) {
        $directory = $languageRootPath . $language . '/';
        if ($extraPath !== '') {
            $directory .= $extraPath . '/';
        }
        $directory .= $chainTemplateKey . '/';
        $directories[] = $directory;
    }

    return array_values(array_unique($directories));
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_first_language_directories(
    string $templateKey,
    string $languageRootPath,
    bool $includeTemplateDefault = true,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): array {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $languageRootPath = rtrim($languageRootPath, '/') . '/';
    $directories = [];

    foreach (zen_get_template_inheritance_chain($templateKey, $includeTemplateDefault, $resolver) as $chainTemplateKey) {
        $directories[] = $languageRootPath . $chainTemplateKey . '/';
    }

    return array_values(array_unique($directories));
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_init_file_path(
    string $templateKey,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): ?string {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $templatePath = $resolver->getTemplateFilesystemPath($templateKey);
    if ($templatePath === null) {
        return null;
    }

    return rtrim($templatePath, '/') . '/template_init.php';
}

/**
 * @since ZC v2.2.1
 */
function zen_get_template_screenshot_web_path(
    string $templateKey,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): ?string {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $record = $resolver->getTemplateRecord($templateKey);
    if ($record === null || empty($record['screenshot']) || empty($record['template_web_path'])) {
        return null;
    }

    return rtrim($record['template_web_path'], '/') . '/images/' . ltrim($record['screenshot'], '/');
}

/**
 * @since ZC v2.2.1
 */
function zen_resolve_template_key(
    string $templateKey,
    ?\Zencart\TemplateResolver\TemplateResolver $resolver = null
): string {
    $resolver = $resolver ?? new \Zencart\TemplateResolver\TemplateResolver();
    $record = $resolver->getTemplateRecord($templateKey) ?? $resolver->getTemplateRecord('template_default');
    return $record['template_key'] ?? 'template_default';
}

/**
 * @since ZC v1.5.8
 */
function zen_register_new_template(string $template_dir, int|string $language_id): false|int|string
{
    global $db;
    if (empty($template_dir) || empty($language_id)) {
        return false;
    }
    // check if template already registered for this language
    $sql = "SELECT *
            FROM " . TABLE_TEMPLATE_SELECT . "
            WHERE template_language = :lang:";
    $sql = $db->bindVars($sql, ':lang:', $language_id, 'integer');
    $check_query = $db->Execute($sql);
    if ($check_query->RecordCount() < 1) {
        $sql = "INSERT INTO " . TABLE_TEMPLATE_SELECT . " (template_dir, template_language)
                VALUES (:tpl:, :lang:)";
        $sql = $db->bindVars($sql, ':tpl:', $template_dir, 'string');
        $sql = $db->bindVars($sql, ':lang:', $language_id, 'integer');
        $db->Execute($sql);
        return $db->insert_ID();
    }
    return false;
}

/**
 * @return array of language_name and language_id entries
 * @since ZC v1.5.8
 */
function zen_get_template_languages_not_registered(): array
{
    global $db;
    $templates = [];
    $sql = "SELECT lng.name as language_name, lng.languages_id as language_id
            FROM " . TABLE_LANGUAGES . " lng
            WHERE lng.languages_id NOT IN (SELECT template_language FROM " . TABLE_TEMPLATE_SELECT . ")";
    $results = $db->Execute($sql);
    foreach ($results as $result) {
        $templates[] = $result;
    }
    return $templates;
}

/**
 * @param numeric $id
 * @param string $template_dir
 * @since ZC v1.5.8
 */
function zen_update_template_name_for_id(int|string $id, string $template_dir): void
{
    global $db;
    $sql = "UPDATE " . TABLE_TEMPLATE_SELECT . "
            SET template_dir = :tpl:
            WHERE template_id = :id:";
    $sql = $db->bindVars($sql, ':tpl:', $template_dir, 'string');
    $sql = $db->bindVars($sql, ':id:', $id, 'integer');
    $db->Execute($sql);
}

/**
 * @param numeric $id
 * @return bool whether template existed before delete
 * @since ZC v1.5.8
 */
function zen_deregister_template_id(int|string $id): bool
{
    global $db;
    $check_query = $db->Execute("SELECT template_language
                                 FROM " . TABLE_TEMPLATE_SELECT . "
                                 WHERE template_id = " . (int)$id);
    if ($check_query->RecordCount() && $check_query->fields['template_language'] != '0') {
        $db->Execute("DELETE FROM " . TABLE_TEMPLATE_SELECT . "
                      WHERE template_id = " . (int)$id);
        return true;
    }
    return false;
}

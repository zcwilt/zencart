<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Modules;

use Zencart\FileSystem\FileSystem;
use Zencart\Request\Request;
use Zencart\ResourceLoaders\ModuleFinder;

class ModulesController
{
    protected string $set = 'payment';
    protected string $moduleType = 'payment';
    protected string $moduleKey = 'MODULE_PAYMENT_INSTALLED';
    protected string $pageHeading = '';
    protected bool $isSslProtected = false;
    protected string $action = '';
    protected string $selectedModuleCode = '';
    protected string $moduleDirectory = '';
    protected array $availableNotifications = [];
    protected array $modulesFound = [];
    protected array $modulesForDisplay = [];
    protected array $groupedRows = ['enabled' => [], 'available' => []];
    protected array $infoBox = ['header' => [], 'content' => []];
    protected string $helpTitle = '';
    protected string $helpBody = '';

    public function __construct(
        protected Request $request,
        protected $messageStack,
        protected $languageLoader,
        protected array $installedPlugins = []
    ) {
    }

    public function processRequest(): void
    {
        $this->resolveModuleSet();
        $this->loadAvailableNotifications();
        $this->discoverModules();
        $this->action = $this->request->string('action', '');
        $this->processAction();
        $this->buildDisplayState();
        zen_update_modules_cache($this->moduleType);
    }

    public function pageHeading(): string
    {
        return $this->pageHeading;
    }

    public function currentSet(): string
    {
        return $this->set;
    }

    public function currentAction(): string
    {
        return $this->action;
    }

    public function isPaymentSet(): bool
    {
        return $this->set === 'payment';
    }

    public function isSslProtected(): bool
    {
        return $this->isSslProtected;
    }

    public function availableNotifications(): array
    {
        return $this->availableNotifications;
    }

    public function groupedRows(): array
    {
        return $this->groupedRows;
    }

    public function getBoxHeader(): array
    {
        return $this->infoBox['header'];
    }

    public function getBoxContent(): array
    {
        return $this->infoBox['content'];
    }

    public function moduleDirectory(): string
    {
        return $this->moduleDirectory;
    }

    public function helpTitle(): string
    {
        return $this->helpTitle;
    }

    public function helpBody(): string
    {
        return $this->helpBody;
    }

    protected function resolveModuleSet(): void
    {
        $requestedSet = $this->request->string('set', '');
        $this->isSslProtected = str_starts_with((string) HTTP_SERVER, 'https');

        switch ($requestedSet) {
            case 'shipping':
                $this->set = 'shipping';
                $this->moduleType = 'shipping';
                $this->moduleKey = 'MODULE_SHIPPING_INSTALLED';
                $this->pageHeading = HEADING_TITLE_MODULES_SHIPPING;
                $this->addShippingConfigurationWarnings();
                break;

            case 'ordertotal':
                $this->set = 'ordertotal';
                $this->moduleType = 'order_total';
                $this->moduleKey = 'MODULE_ORDER_TOTAL_INSTALLED';
                $this->pageHeading = HEADING_TITLE_MODULES_ORDER_TOTAL;
                break;

            case 'payment':
            default:
                $this->set = 'payment';
                $this->moduleType = 'payment';
                $this->moduleKey = 'MODULE_PAYMENT_INSTALLED';
                $this->pageHeading = HEADING_TITLE_MODULES_PAYMENT;
                break;
        }
    }

    protected function addShippingConfigurationWarnings(): void
    {
        $shippingErrors = '';
        if (zen_get_configuration_key_value('SHIPPING_ORIGIN_ZIP') === 'NONE' || zen_get_configuration_key_value('SHIPPING_ORIGIN_ZIP') === '') {
            $shippingErrors .= '<br>' . ERROR_SHIPPING_ORIGIN_ZIP;
        }
        if (zen_get_configuration_key_value('ORDER_WEIGHT_ZERO_STATUS') === '1' && (!defined('MODULE_SHIPPING_FREESHIPPER_STATUS') || MODULE_SHIPPING_FREESHIPPER_STATUS !== 'True')) {
            $shippingErrors .= '<br>' . ERROR_ORDER_WEIGHT_ZERO_STATUS;
        }
        if ($shippingErrors !== '') {
            $this->messageStack->add(ERROR_SHIPPING_CONFIGURATION . $shippingErrors, 'caution');
        }
    }

    protected function loadAvailableNotifications(): void
    {
        $moduleCode = $this->selectedModuleCodeFromRequest();
        $notificationType = $this->moduleType . ($moduleCode !== '' ? '-' . $moduleCode : '');
        $notifications = new \AdminNotifications();
        $this->availableNotifications = $notifications->getNotifications($notificationType, (int) ($_SESSION['admin_id'] ?? 0));
    }

    protected function discoverModules(): void
    {
        $moduleFinder = new ModuleFinder($this->moduleType, new FileSystem());
        $this->modulesFound = $moduleFinder->findFromFilesystem($this->installedPlugins);
        $this->moduleDirectory = DIR_FS_CATALOG . DIR_WS_MODULES . $this->moduleType;
    }

    protected function processAction(): void
    {
        if ($this->action === '') {
            return;
        }

        switch ($this->action) {
            case 'save':
                $this->processSaveAction();
                break;

            case 'install':
                $this->processInstallAction();
                break;

            case 'removeconfirm':
                $this->processRemoveConfirmAction();
                break;
        }
    }

    protected function processSaveAction(): void
    {
        global $db;

        $class = $this->selectedModuleCodeFromRequest();
        if ($this->isSslRestrictedModule($class)) {
            return;
        }

        foreach ($this->request->inputArray('configuration') as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
                $value = preg_replace('/, --none--/', '', $value);
            }

            $checks = $db->Execute("SELECT configuration_title, val_function FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $key . "'");
            if (!$checks->EOF && $checks->fields['val_function'] !== null) {
                require_once DIR_FS_ADMIN . 'includes/functions/configuration_checks.php';
                if (!zen_validate_configuration_entry((string) $value, $checks->fields['val_function'], $checks->fields['configuration_title'])) {
                    zen_redirect($this->buildUrl([
                        'set' => $this->set,
                        'module' => $class,
                        'action' => 'edit',
                    ], 'SSL'));
                }
            }

            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = '" . zen_db_input((string) $value) . "'
                  WHERE configuration_key = '" . zen_db_input((string) $key) . "'
                  LIMIT 1"
            );
        }

        $adminName = '{' . preg_replace('/\W/', '*', zen_get_admin_name()) . '[' . (int) $_SESSION['admin_id'] . ']}';
        $subjectTarget = $class !== '' ? $class : $this->set;
        $msg = sprintf(
            TEXT_EMAIL_MESSAGE_ADMIN_SETTINGS_CHANGED,
            preg_replace('/\W/', '*', $subjectTarget !== '' ? $subjectTarget : 'UNKNOWN'),
            $adminName
        );
        zen_record_admin_activity($msg, 'warning');
        zen_mail(
            STORE_NAME,
            STORE_OWNER_EMAIL_ADDRESS,
            TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED,
            $msg,
            STORE_NAME,
            EMAIL_FROM,
            ['EMAIL_MESSAGE_HTML' => nl2br($msg, false)],
            'admin_settings_changed'
        );

        zen_redirect($this->buildUrl([
            'set' => $this->set,
            'module' => $class,
        ], 'SSL'));
    }

    protected function processInstallAction(): void
    {
        $result = 'failed';
        $class = basename($this->request->string('module', ''));
        if ($this->isSslRestrictedModule($class)) {
            return;
        }

        $module = $this->loadModuleInstanceByClass($class);
        if ($module !== null) {
            $adminName = '{' . preg_replace('/\W/', '*', zen_get_admin_name()) . '[' . (int) $_SESSION['admin_id'] . ']}';
            $msg = sprintf(TEXT_EMAIL_MESSAGE_ADMIN_MODULE_INSTALLED, preg_replace('/\W/', '*', $class), $adminName);
            zen_record_admin_activity($msg, 'warning');
            zen_mail(
                STORE_NAME,
                STORE_OWNER_EMAIL_ADDRESS,
                TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED,
                $msg,
                STORE_NAME,
                EMAIL_FROM,
                ['EMAIL_MESSAGE_HTML' => nl2br($msg, false)],
                'admin_settings_changed'
            );
            $result = $module->install();
        }

        global $zco_notifier;
        $zco_notifier->notify('NOTIFY_ADMIN_MODULES_DO_INSTALL', ['module_name' => $class], $result);

        if ($result !== 'failed') {
            zen_redirect($this->buildUrl([
                'set' => $this->set,
                'module' => $class,
                'action' => 'edit',
            ], 'SSL'));
        }
    }

    protected function processRemoveConfirmAction(): void
    {
        $result = 'failed';
        $class = basename($this->request->string('module', ''));
        $module = $this->loadModuleInstanceByClass($class);
        if ($module !== null) {
            $adminName = '{' . preg_replace('/\W/', '*', zen_get_admin_name()) . '[' . (int) $_SESSION['admin_id'] . ']}';
            $msg = sprintf(TEXT_EMAIL_MESSAGE_ADMIN_MODULE_REMOVED, preg_replace('/\W/', '*', $class), $adminName);
            zen_record_admin_activity($msg, 'warning');
            zen_mail(
                STORE_NAME,
                STORE_OWNER_EMAIL_ADDRESS,
                TEXT_EMAIL_SUBJECT_ADMIN_SETTINGS_CHANGED,
                $msg,
                STORE_NAME,
                EMAIL_FROM,
                ['EMAIL_MESSAGE_HTML' => nl2br($msg, false)],
                'admin_settings_changed'
            );
            $result = $module->remove();
        }

        global $zco_notifier;
        $zco_notifier->notify('NOTIFY_ADMIN_MODULES_DO_UNINSTALL', ['module_name' => $class], $result);

        zen_redirect($this->buildUrl([
            'set' => $this->set,
            'module' => $class,
        ], 'SSL'));
    }

    protected function buildDisplayState(): void
    {
        $this->modulesForDisplay = [];

        foreach ($this->modulesFound as $moduleName => $moduleFileDir) {
            if (!$this->languageLoader->loadModuleLanguageFile($moduleName, $this->moduleType)) {
                $languageFileName = str_starts_with($moduleName, 'lang.') ? $moduleName : 'lang.' . $moduleName;
                $this->messageStack->add(ERROR_MODULE_FILE_NOT_FOUND . DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/' . $this->moduleType . '/' . $languageFileName, 'caution');
                continue;
            }

            require_once DIR_FS_CATALOG . $moduleFileDir . $moduleName;
            $class = pathinfo($moduleName, PATHINFO_FILENAME);
            if ($class === '' || !class_exists($class)) {
                continue;
            }

            $module = new $class();
            $check = $module->check();
            $enabled = $module->enabled ?? (bool) $check;
            $sortOrder = $module->sort_order ?? null;
            $paddedSortOrder = str_pad((string) (int) ($sortOrder ?? 0), 6, '0', STR_PAD_LEFT);

            $this->modulesForDisplay[$class] = [
                'class' => $class,
                'status' => $check,
                'code' => $module->code ?? '',
                'title' => $module->title ?? '**BROKEN**',
                'module' => $module,
                'enabled' => $enabled,
                'sort_order' => $sortOrder,
                'grouping_sort' => (int) $enabled * -1 . (is_numeric($sortOrder) ? '0' : '1') . $paddedSortOrder . ($module->title ?? ''),
            ];
        }

        uasort($this->modulesForDisplay, static fn(array $a, array $b): int => strnatcmp((string) $a['grouping_sort'], (string) $b['grouping_sort']));

        $this->selectedModuleCode = $this->resolveSelectedModuleCode();
        if ($this->selectedModuleCode !== '' && isset($this->modulesForDisplay[$this->selectedModuleCode])) {
            $this->augmentSelectedModule();
        }

        $this->buildGroupedRows();
        $this->buildInfoBox();
    }

    protected function resolveSelectedModuleCode(): string
    {
        $requestedModule = $this->selectedModuleCodeFromRequest();
        if ($requestedModule !== '' && isset($this->modulesForDisplay[$requestedModule])) {
            return $requestedModule;
        }

        $firstModule = $this->firstModuleDetail();
        return $firstModule['class'] ?? '';
    }

    protected function augmentSelectedModule(): void
    {
        global $db;

        $selected = $this->modulesForDisplay[$this->selectedModuleCode];
        $module = $selected['module'];

        $moduleInfo = [
            'code' => $module->code ?? '',
            'title' => $module->title ?? '',
            'description' => $module->description ?? '',
            'status' => $selected['status'],
            'keys' => [],
        ];

        foreach ($module->keys() as $nextKey) {
            $keyValue = $db->Execute(
                "SELECT configuration_title AS `title`, configuration_value AS `value`,
                            configuration_description AS `description`, use_function, set_function
                   FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = '" . zen_db_input($nextKey) . "'
                  LIMIT 1"
            );
            if (!$keyValue->EOF) {
                $moduleInfo['keys'][$nextKey] = $keyValue->fields;
            }
        }

        if (method_exists($module, 'get_configuration_errors')) {
            $moduleInfo['configuration_errors'] = $module->get_configuration_errors();
        }

        $this->modulesForDisplay[$this->selectedModuleCode] = array_merge($selected, $moduleInfo);
    }

    protected function buildGroupedRows(): void
    {
        $this->groupedRows = ['enabled' => [], 'available' => []];

        foreach ($this->modulesForDisplay as $class => $detail) {
            $selected = $class === $this->selectedModuleCode;
            $row = [
                'selected' => $selected,
                'title' => $detail['title'],
                'code' => $detail['code'],
                'sortOrder' => is_numeric($detail['sort_order']) ? (string) $detail['sort_order'] : '',
                'statusIcon' => match (true) {
                    !empty($detail['enabled']) && is_numeric($detail['sort_order']) => zen_icon('status-green'),
                    empty($detail['enabled']) && is_numeric($detail['sort_order']) => zen_icon('status-yellow'),
                    default => zen_icon('status-red'),
                },
                'orderStatus' => $this->isPaymentSet() ? $this->resolveModuleOrderStatus($detail['module']) : '',
                'infoLink' => $this->buildUrl(['set' => $this->set, 'module' => $class], 'SSL'),
                'rowLink' => $this->rowLink($class, $detail, $selected),
            ];

            if (!empty($detail['enabled'])) {
                $this->groupedRows['enabled'][] = $row;
            } else {
                $this->groupedRows['available'][] = $row;
            }
        }
    }

    protected function buildInfoBox(): void
    {
        $this->infoBox = ['header' => [], 'content' => []];
        $this->helpTitle = '';
        $this->helpBody = '';

        if ($this->selectedModuleCode === '' || !isset($this->modulesForDisplay[$this->selectedModuleCode])) {
            return;
        }

        $detail = $this->modulesForDisplay[$this->selectedModuleCode];
        $this->infoBox['header'][] = ['text' => '<h4>' . ($detail['title'] ?? '') . '</h4>'];

        switch ($this->action) {
            case 'remove':
                $this->buildRemoveInfoBox();
                break;

            case 'edit':
                if ($this->isSslRestrictedModule($this->selectedModuleCode)) {
                    return;
                }
                $this->buildEditInfoBox();
                break;

            default:
                $this->buildDefaultInfoBox();
                break;
        }
    }

    protected function buildRemoveInfoBox(): void
    {
        $this->infoBox['content']['form'] = zen_draw_form('module_delete', FILENAME_MODULES, '&action=removeconfirm&set=' . $this->set);
        $this->infoBox['content'][] = ['text' => zen_draw_hidden_field('set', $this->set)];
        $this->infoBox['content'][] = ['text' => zen_draw_hidden_field('module', $this->selectedModuleCode)];
        $this->infoBox['content'][] = ['text' => '<h5>' . TEXT_DELETE_INTRO . '</h5>'];
        $this->infoBox['content'][] = [
            'align' => 'text-center',
            'text' => '<button type="submit" class="btn btn-danger" id="removeButton">' . IMAGE_MODULE_REMOVE . '</button>&nbsp;'
                . '<a href="' . $this->buildUrl(['set' => $this->set, 'module' => $this->selectedModuleCode], 'SSL') . '" class="btn btn-default" role="button" id="cancelButton">' . IMAGE_CANCEL . '</a>',
        ];
    }

    protected function buildEditInfoBox(): void
    {
        $detail = $this->modulesForDisplay[$this->selectedModuleCode];
        $keysHtml = $this->renderConfigurationEditFields($detail['keys'] ?? []);
        $this->infoBox['content']['form'] = zen_draw_form(
            'modules',
            FILENAME_MODULES,
            'set=' . $this->set . '&module=' . $this->selectedModuleCode . '&action=save',
            'post',
            'class="form-horizontal"',
            true
        );

        if (ADMIN_CONFIGURATION_KEY_ON === '1') {
            $this->infoBox['content'][] = ['text' => '<strong>Module code: ' . ($detail['code'] ?? '') . '</strong><br>'];
        }

        $this->infoBox['content'][] = ['text' => $keysHtml];
        $this->infoBox['content'][] = [
            'align' => 'text-center',
            'text' => '<button type="submit" class="btn btn-danger" id="saveButton">' . IMAGE_UPDATE . '</button>&nbsp;'
                . '<a href="' . $this->buildUrl(['set' => $this->set, 'module' => $this->selectedModuleCode], 'SSL') . '" class="btn btn-default" role="button" id="cancelButton">' . IMAGE_CANCEL . '</a>',
        ];
    }

    protected function buildDefaultInfoBox(): void
    {
        $detail = $this->modulesForDisplay[$this->selectedModuleCode];
        $this->prepareHelpState();

        if (($detail['status'] ?? '') == '1') {
            if (ADMIN_CONFIGURATION_KEY_ON === '1') {
                $this->infoBox['content'][] = ['text' => '<strong>Module code: ' . ($detail['code'] ?? '') . '</strong><br>'];
            }

            if ($this->isSslRestrictedModule($detail['code'] ?? '')) {
                $this->infoBox['content'][] = ['align' => 'text-center', 'text' => TEXT_WARNING_SSL_EDIT];
            } else {
                $this->infoBox['content'][] = [
                    'align' => 'text-center',
                    'text' => '<a href="' . $this->buildUrl(['set' => $this->set, 'module' => $this->selectedModuleCode, 'action' => 'edit'], 'SSL') . '" class="btn btn-primary" role="button" id="editButton">' . IMAGE_EDIT . '</a>',
                ];
            }

            $this->infoBox['content'][] = [
                'align' => 'text-center',
                'text' => '<a href="' . $this->buildUrl(['set' => $this->set, 'module' => $detail['code'], 'action' => 'remove'], 'SSL') . '" class="btn btn-warning" role="button" id="removeButton"><i class="fa-solid fa-minus"></i> ' . IMAGE_MODULE_REMOVE . '</a>',
            ];

            $helpButton = $this->helpButtonContent();
            if ($helpButton !== null) {
                $this->infoBox['content'][] = $helpButton;
            }

            $this->infoBox['content'][] = ['text' => '<br>' . ($detail['description'] ?? '')];
            if (!empty($detail['configuration_errors'])) {
                $this->infoBox['content'][] = ['text' => $detail['configuration_errors'] . '<br>'];
            }
            $this->infoBox['content'][] = ['text' => '<br>' . $this->renderConfigurationDisplayFields($detail['keys'] ?? [])];
            return;
        }

        if ($this->isSslRestrictedModule($detail['code'] ?? '')) {
            $this->infoBox['content'][] = ['align' => 'text-center', 'text' => TEXT_WARNING_SSL_INSTALL];
        } else {
            $this->infoBox['content'][] = [
                'align' => 'text-center',
                'text' => zen_draw_form('install_module', FILENAME_MODULES, 'set=' . $this->set . '&action=install')
                    . zen_draw_hidden_field('module', $detail['code'])
                    . '<button type="submit" id="installButton" class="btn btn-primary"><i class="fa-solid fa-plus"></i> ' . IMAGE_MODULE_INSTALL . '</button></form>',
            ];
        }

        $helpButton = $this->helpButtonContent();
        if ($helpButton !== null) {
            $this->infoBox['content'][] = $helpButton;
        }
        $this->infoBox['content'][] = ['text' => '<br>' . ($detail['description'] ?? '')];
    }

    protected function prepareHelpState(): void
    {
        $this->helpTitle = '';
        $this->helpBody = '';

        $class = pathinfo($this->selectedModuleCode, PATHINFO_FILENAME);
        $fileExtension = $this->pageExtension();
        if (!is_file($this->moduleDirectory . $class . '.' . $fileExtension)) {
            return;
        }

        if (!$this->languageLoader->loadModuleDefinesFromFile('/modules/', $_SESSION['language'], $this->moduleType, $class . '.' . $fileExtension)) {
            return;
        }

        include_once $this->moduleDirectory . $class . '.' . $fileExtension;
        if (!class_exists($class)) {
            return;
        }

        $module = new $class();
        if (!method_exists($module, 'help')) {
            return;
        }

        $helpText = $module->help();
        if (!is_array($helpText)) {
            return;
        }

        if (isset($helpText['body'])) {
            $this->helpTitle = ($module->title ?? $class) . ' ' . IMAGE_MODULE_HELP;
            $this->helpBody = $helpText['body'];
        }

        if (isset($helpText['link'])) {
            $this->helpTitle = (string) $helpText['link'];
        }
    }

    protected function helpButtonContent(): ?array
    {
        if ($this->helpBody !== '') {
            return ['align' => 'text-center', 'text' => '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#helpModal">' . IMAGE_MODULE_HELP . '</button>'];
        }

        if ($this->helpTitle !== '' && str_starts_with($this->helpTitle, 'http')) {
            return ['align' => 'text-center', 'text' => '<a href="' . $this->helpTitle . '" target="_blank" rel="noreferrer noopener"><button type="submit" class="btn btn-primary" id="helpButton">' . IMAGE_MODULE_HELP . '</button></a>'];
        }

        return null;
    }

    protected function renderConfigurationEditFields(array $keys): string
    {
        $html = '';
        foreach ($keys as $key => $value) {
            $displayKey = ADMIN_CONFIGURATION_KEY_ON === '1' ? 'Key: ' . $key . '<br>' : '';
            $html .= '<b>' . $displayKey
                . zen_lookup_admin_menu_language_override('configuration_key_title', (string) $key, $value['title'] ?? '')
                . '</b><br>'
                . zen_lookup_admin_menu_language_override('configuration_key_description', (string) $key, $value['description'] ?? '')
                . '<br>';

            if (!empty($value['set_function'])) {
                eval('$html .= ' . $value['set_function'] . '"' . zen_output_string((string) ($value['value'] ?? ''), ['"' => '&quot;', '`' => 'null;return;exit;']) . '", "' . $key . '");');
            } else {
                $html .= zen_draw_input_field('configuration[' . $key . ']', htmlspecialchars((string) ($value['value'] ?? ''), ENT_COMPAT, CHARSET, true), 'class="form-control"');
            }
            $html .= '<br><br>';
        }

        return substr($html, 0, strrpos($html, '<br><br>') ?: 0);
    }

    protected function renderConfigurationDisplayFields(array $keys): string
    {
        $html = '';
        foreach ($keys as $key => $value) {
            $displayKey = ADMIN_CONFIGURATION_KEY_ON === '1' ? 'Key: ' . $key . '<br>' : '';
            $html .= '<b>' . $displayKey
                . zen_lookup_admin_menu_language_override('configuration_key_title', (string) $key, $value['title'] ?? '')
                . '</b><br>';

            if (!empty($value['use_function'])) {
                $useFunction = (string) $value['use_function'];
                if (str_contains($useFunction, '->')) {
                    [$className, $method] = explode('->', $useFunction, 2);
                    if (!class_exists($className)) {
                        include_once DIR_WS_CLASSES . $className . '.php';
                    }
                    if (!is_object($GLOBALS[$className] ?? null)) {
                        $GLOBALS[$className] = new $className();
                    }
                    $html .= zen_call_function($method, $value['value'] ?? '', $GLOBALS[$className]);
                } else {
                    $html .= zen_call_function($useFunction, $value['value'] ?? '');
                }
            } else {
                $html .= $value['value'] ?? '';
            }

            $html .= '<br><br>';
        }

        return substr($html, 0, strrpos($html, '<br><br>') ?: 0);
    }

    protected function resolveModuleOrderStatus(object $module): string
    {
        global $db;

        $orderStatus = $module->order_status ?? 0;
        if (!is_numeric($orderStatus)) {
            return '';
        }

        $ordersStatusName = $db->Execute(
            "SELECT orders_status_id, orders_status_name
               FROM " . TABLE_ORDERS_STATUS . "
              WHERE orders_status_id = " . (int) $orderStatus . "
                AND language_id = " . (int) $_SESSION['languages_id']
        );

        if (empty($ordersStatusName->fields['orders_status_id'])) {
            return TEXT_DEFAULT;
        }

        return (string) $ordersStatusName->fields['orders_status_name'];
    }

    protected function rowLink(string $class, array $detail, bool $selected): ?string
    {
        if (!$selected) {
            return $this->buildUrl(['set' => $this->set, 'module' => $class], 'SSL');
        }

        if (!empty($detail['status']) && $this->action !== 'edit') {
            return $this->buildUrl(['set' => $this->set, 'module' => $class, 'action' => 'edit'], 'SSL');
        }

        return null;
    }

    protected function loadModuleInstanceByClass(string $class): ?object
    {
        $fileExtension = $this->pageExtension();
        $classFile = $class . '.' . $fileExtension;
        if (!array_key_exists($classFile, $this->modulesFound)) {
            return null;
        }

        if (!$this->languageLoader->loadModuleLanguageFile($classFile, $this->moduleType)) {
            return null;
        }

        require DIR_FS_CATALOG . $this->modulesFound[$classFile] . $classFile;
        return class_exists($class) ? new $class() : null;
    }

    protected function selectedModuleCodeFromRequest(): string
    {
        return basename($this->request->string('module', ''));
    }

    protected function isSslRestrictedModule(string $class): bool
    {
        return !$this->isSslProtected && in_array($class, ['paypaldp', 'authorizenet_aim', 'authorizenet_echeck'], true);
    }

    protected function pageExtension(): string
    {
        $extension = pathinfo((string) $this->request->server('PHP_SELF', 'modules.php'), PATHINFO_EXTENSION);
        return $extension !== '' ? $extension : 'php';
    }

    protected function buildUrl(array $params = [], string $connection = 'NONSSL'): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $filtered[$key] = $value;
        }

        return zen_href_link(FILENAME_MODULES, http_build_query($filtered), $connection);
    }

    protected function firstModuleDetail(): ?array
    {
        foreach ($this->modulesForDisplay as $detail) {
            return $detail;
        }

        return null;
    }
}

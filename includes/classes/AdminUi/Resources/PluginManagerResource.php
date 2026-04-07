<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ListViewConfig;
use Zencart\AdminUi\Pages\ResourceListPage;
use Zencart\AdminUi\Resources\PluginManager\PluginManagerController;
use Zencart\AdminUi\Resources\PluginManager\PluginManagerDataSource;
use Zencart\Filters\FilterFactory;
use Zencart\Filters\FilterManager;
use Zencart\PluginManager\PluginManager;
use Zencart\PluginSupport\InstallerFactory;
use Zencart\PluginSupport\PluginStatus;
use Zencart\ViewBuilders\DerivedItemsManager;
use Zencart\ViewBuilders\SimpleDataFormatter;
use Zencart\ViewBuilders\TableViewDefinition;

class PluginManagerResource extends AdminResource
{
    public function __construct(
        \Zencart\Request\Request $request,
        $messageStack,
        protected PluginManager $pluginManager,
        protected InstallerFactory $installerFactory
    ) {
        parent::__construct($request, $messageStack);
    }

    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();
        $table = new TableViewDefinition($this->tableDefinition());

        $dataSource = new PluginManagerDataSource($table);
        $query = $dataSource->processRequest($this->request);

        $filterManager = new FilterManager($this->filterDefinitions(), new FilterFactory());
        $filterManager->build();
        $query = $filterManager->processRequest($this->request, $query);

        $queryResults = $dataSource->processQuery($query);
        $formatter = new SimpleDataFormatter($this->request, $table, $queryResults, new DerivedItemsManager());

        $tableController = new PluginManagerController($this->request, $this->messageStack, $table, $formatter);
        $tableController->init($this->pluginManager, $this->installerFactory);
        $tableController->processRequest();

        $page = (new ResourceListPage(
            HEADING_TITLE,
            $formatter,
            $tableController,
            new ListViewConfig(
                'status',
                [
                    PluginStatus::ENABLED,
                    PluginStatus::DISABLED,
                    PluginStatus::NOT_INSTALLED,
                ],
                [
                    PluginStatus::ENABLED => TEXT_INSTALLED_ENABLED,
                    PluginStatus::DISABLED => TEXT_INSTALLED_DISABLED,
                    PluginStatus::NOT_INSTALLED => TEXT_NOT_INSTALLED,
                ],
                [
                    1 => 'w-10',
                    2 => 'w-15',
                    3 => 'w-20',
                    4 => 'w-10',
                ]
            )
        ))->build();

        return $this->notifyBuildPageEnd($page, $context);
    }

    protected function tableDefinition(): array
    {
        return [
            'colKey' => 'unique_key',
            'maxRowCount' => 999,
            'defaultRowAction' => '',
            'columns' => [
                'name' => [
                    'title' => TABLE_HEADING_NAME,
                    'derivedItem' => [
                        'type' => 'local',
                        'method' => 'getLanguageTranslationForName',
                    ],
                    'class' => '',
                ],
                'version' => ['title' => TABLE_HEADING_VERSION_INSTALLED],
                'filespace' => [
                    'title' => TABLE_HEADING_FILE_SPACE,
                    'derivedItem' => [
                        'type' => 'local',
                        'method' => 'getPluginFileSize',
                    ],
                    'class' => '',
                ],
                'unique_key' => ['title' => TABLE_HEADING_KEY],
                'status' => [
                    'title' => TABLE_HEADING_STATUS,
                    'derivedItem' => [
                        'type' => 'local',
                        'method' => 'arrayReplace',
                        'params' => [
                            (string) PluginStatus::NOT_INSTALLED => zen_icon('status-red'),
                            (string) PluginStatus::ENABLED => zen_icon('status-green'),
                            (string) PluginStatus::DISABLED => zen_icon('status-yellow'),
                        ],
                    ],
                    'class' => static function ($value) {
                        return match ($value) {
                            PluginStatus::ENABLED => 'status-enabled',
                            PluginStatus::DISABLED => 'status-disabled',
                            default => 'status-not-installed',
                        };
                    },
                ],
            ],
        ];
    }

    protected function filterDefinitions(): array
    {
        return [
            [
                'type' => 'selectWhere',
                'field' => 'status',
                'label' => TEXT_LABEL_STATUS,
                'source' => 'options',
                'selectName' => 'plugin_status',
                'auto' => true,
                'options' => [
                    '*' => TEXT_ALL_STATUSES,
                    (string) PluginStatus::NOT_INSTALLED => TEXT_NOT_INSTALLED,
                    (string) PluginStatus::ENABLED => TEXT_INSTALLED_ENABLED,
                    (string) PluginStatus::DISABLED => TEXT_INSTALLED_DISABLED,
                ],
            ],
        ];
    }
}

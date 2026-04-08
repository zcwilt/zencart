<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Resources\Modules\ModulesController;

class ModulesResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        zen_define_default('TEXT_AVAILABLE', 'Available');
        zen_define_default('TEXT_DISABLED', 'Disabled');
        zen_define_default('TEXT_ENABLED', 'Enabled');

        global $installedPlugins, $languageLoader;

        $controller = new ModulesController(
            $this->request,
            $this->messageStack,
            $languageLoader,
            $installedPlugins ?? []
        );
        $controller->processRequest();

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/modules_resource.php',
            [
                'modulesController' => $controller,
                'pageHeading' => $controller->pageHeading(),
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }
}

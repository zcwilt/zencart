<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Resources\BannerManager\BannerManagerController;
use Zencart\DbRepositories\BannerManagerRepository;

class BannerManagerResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $controller = new BannerManagerController($this->request, $this->messageStack, new BannerManagerRepository($db));
        $controller->processRequest();

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/banner_manager_resource.php',
            [
                'bannerManagerController' => $controller,
                'pageHeading' => HEADING_TITLE,
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }
}

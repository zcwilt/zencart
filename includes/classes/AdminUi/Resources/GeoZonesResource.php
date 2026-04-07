<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Resources\GeoZones\GeoZonesController;
use Zencart\DbRepositories\GeoZonesRepository;

class GeoZonesResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $controller = new GeoZonesController($this->request, $this->messageStack, new GeoZonesRepository($db));
        $controller->processRequest();

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/geo_zones_resource.php',
            [
                'geoZonesController' => $controller,
                'pageHeading' => HEADING_TITLE,
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }
}

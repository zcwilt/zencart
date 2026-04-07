<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\CrudListPageBuilder;
use Zencart\AdminUi\Resources\TaxClass\TaxClassController;
use Zencart\AdminUi\Resources\TaxClass\TaxClassDataSource;

class TaxClassResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();
        $classesQueryRaw = "SELECT tax_class_id, tax_class_title, tax_class_description, last_modified, date_added
                              FROM " . TABLE_TAX_CLASS . "
                             ORDER BY tax_class_title";
        $currentPage = (int) $this->request->input('page', 1);

        $page = (new CrudListPageBuilder(
            $this->request,
            $this->messageStack,
            HEADING_TITLE
        ))
            ->withTableDefinition([
                'colKey' => 'tax_class_id',
                'colKeyName' => 'tID',
                'maxRowCount' => MAX_DISPLAY_SEARCH_RESULTS,
                'selectedRowAction' => 'edit',
                'columns' => [
                    'tax_class_id' => ['title' => TABLE_HEADING_TAX_CLASS_ID],
                    'tax_class_title' => ['title' => TABLE_HEADING_TAX_CLASSES],
                ],
            ])
            ->withDataSourceClass(TaxClassDataSource::class)
            ->withControllerClass(TaxClassController::class)
            ->withPagination(
                $classesQueryRaw,
                TEXT_DISPLAY_NUMBER_OF_TAX_CLASSES,
                MAX_DISPLAY_SEARCH_RESULTS
            )
            ->withPrimaryAction(
                $this->request->input('action', '') === '' ? zen_href_link(FILENAME_TAX_CLASSES, 'page=' . $currentPage . '&action=new') : null,
                $this->request->input('action', '') === '' ? IMAGE_NEW_TAX_CLASS : null
            )
            ->build();

        return $this->notifyBuildPageEnd($page, $context);
    }
}

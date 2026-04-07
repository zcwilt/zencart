<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Manufacturers;

use Zencart\Request\Request;
use Zencart\ViewBuilders\DataTableDataSource;
use Zencart\ViewBuilders\NativePaginator;

class ManufacturersDataSource extends DataTableDataSource
{
    protected string $currentPage = '1';
    protected ?string $linkPage = null;
    protected int $queryNumRows = 0;
    protected ?\splitPageResults $splitResults = null;

    protected function buildInitialQuery(Request $request): array
    {
        global $db;

        $searchTerm = $this->resolveSearchTerm($request);
        $whereClause = '';
        if ($searchTerm !== null) {
            $whereClause = " WHERE manufacturers_name LIKE '%" . zen_db_input($searchTerm) . "%'";
        }

        $baseQueryRaw = "SELECT *, (featured=1) AS weighted
                           FROM " . TABLE_MANUFACTURERS . "
                           " . $whereClause . "
                          ORDER BY weighted DESC, manufacturers_name";

        $this->linkPage = $this->resolveLinkPage($request);
        $this->currentPage = $this->resolveRequestedPage($request) ?? '1';

        $queryRaw = $baseQueryRaw;
        $queryNumRows = $this->queryNumRows;
        $currentPage = $this->currentPage;
        $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);

        $selectedManufacturerId = $this->selectedManufacturerId($request);
        if ($selectedManufacturerId !== null && $this->resolveRequestedPage($request) === null) {
            $currentPage = $this->findSelectedManufacturerPage($baseQueryRaw, $selectedManufacturerId);
            $queryRaw = $baseQueryRaw;
            $queryNumRows = 0;
            $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);
        }

        $this->currentPage = $currentPage;
        $this->queryNumRows = $queryNumRows;

        $rows = [];
        $query = $db->Execute($queryRaw);
        foreach ($query as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function processRequest(Request $request)
    {
        return $this->buildInitialQuery($request);
    }

    public function processQuery($query): NativePaginator
    {
        if (is_array($query)) {
            return new NativePaginator(
                $query,
                $this->queryNumRows,
                MAX_DISPLAY_SEARCH_RESULTS,
                (int) $this->currentPage,
                'page'
            );
        }

        return parent::processQuery($query);
    }

    public function getCurrentPage(): string
    {
        return $this->currentPage;
    }

    public function getLinkPage(): ?string
    {
        return $this->linkPage;
    }

    public function getQueryNumRows(): int
    {
        return $this->queryNumRows;
    }

    public function getSplitResults(): ?\splitPageResults
    {
        return $this->splitResults;
    }

    protected function resolveSearchTerm(Request $request): ?string
    {
        return parent::resolveSearchTerm($request);
    }

    protected function resolveRequestedPage(Request $request): ?string
    {
        $requestedPage = (string) $request->input('page', '');
        if ($requestedPage === '') {
            return null;
        }

        if (!ctype_digit($requestedPage) || (int) $requestedPage < 1) {
            return '1';
        }

        return (string) (int) $requestedPage;
    }

    protected function resolveLinkPage(Request $request): ?string
    {
        return $this->resolveRequestedPage($request);
    }

    protected function selectedManufacturerId(Request $request): ?int
    {
        $manufacturerId = $request->input('mID');
        if ($manufacturerId === null || !ctype_digit((string) $manufacturerId)) {
            return null;
        }

        return (int) $manufacturerId;
    }

    protected function createSplitResults(string &$currentPage, string &$queryRaw, int &$queryNumRows): \splitPageResults
    {
        $numericPage = (int) $currentPage;
        if ($numericPage < 1) {
            $numericPage = 1;
        }

        $split = new \splitPageResults($numericPage, MAX_DISPLAY_SEARCH_RESULTS, $queryRaw, $queryNumRows);
        $currentPage = (string) $numericPage;

        return $split;
    }

    protected function findSelectedManufacturerPage(string $queryRaw, int $selectedManufacturerId): string
    {
        global $db;

        $query = $db->Execute($queryRaw);
        $rowOffset = 0;
        foreach ($query as $row) {
            $currentId = (int) (($row['manufacturers_id'] ?? $row->manufacturers_id ?? 0));
            if ($currentId === $selectedManufacturerId) {
                return (string) ((int) floor($rowOffset / MAX_DISPLAY_SEARCH_RESULTS) + 1);
            }
            $rowOffset++;
        }

        return $this->currentPage;
    }
}

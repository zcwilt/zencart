<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Countries;

use Zencart\Request\Request;
use Zencart\ViewBuilders\DataTableDataSource;
use Zencart\ViewBuilders\NativePaginator;
use Zencart\ViewBuilders\SortState;

class CountriesDataSource extends DataTableDataSource
{
    protected string $currentPage = '';
    protected string $pageParameter = '';
    protected string $queryRaw = '';
    protected int $queryNumRows = 0;
    protected ?\splitPageResults $splitResults = null;
    protected ?SortState $sortState = null;
    protected bool $alphabeticMode = false;

    protected function buildInitialQuery(Request $request): array
    {
        global $db;

        $this->sortState = $this->resolveSortState($request);
        $this->alphabeticMode = $this->shouldUseAlphabeticMode($request);
        $this->currentPage = $this->resolveCurrentPage($request);
        $this->pageParameter = ($this->currentPage !== '') ? ('page=' . $this->currentPage . '&') : '';
        $searchTerm = $this->resolveSearchTerm($request);
        $statusFilter = $this->resolveFilterValue($request, 'status');
        $orderBy = $this->sortState?->toOrderByClause() ?? 'countries_name ASC';
        $orderBy .= ', countries_id ASC';
        $whereClauses = [];
        if ($searchTerm !== null) {
            $whereClauses[] = "countries_name LIKE '%" . zen_db_input($searchTerm) . "%'";
        }
        if ($statusFilter === '0' || $statusFilter === '1') {
            $whereClauses[] = 'status = ' . (int) $statusFilter;
        }
        $whereClause = $whereClauses === [] ? '' : ' WHERE ' . implode(' AND ', $whereClauses);

        $baseQueryRaw = "SELECT countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, status
                             FROM " . TABLE_COUNTRIES . "
                             " . $whereClause . "
                             ORDER BY " . $orderBy;
        $this->queryRaw = $baseQueryRaw;

        $queryRaw = $baseQueryRaw;
        $currentPage = $this->currentPage;
        $queryNumRows = $this->queryNumRows;
        $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);

        $selectedCountryId = $this->selectedCountryId($request);
        if ($selectedCountryId !== null) {
            $queryRaw = $baseQueryRaw;
            $queryNumRows = 0;
            if ($this->alphabeticMode) {
                if ($this->splitResults === null) {
                    $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);
                }
                if (method_exists($this->splitResults, 'findPage')) {
                    $this->splitResults->findPage($currentPage, MAX_DISPLAY_SEARCH_RESULTS, $queryRaw, 'countries_id', $selectedCountryId);
                } else {
                    $currentPage = $this->findSelectedCountryLetter($baseQueryRaw, $selectedCountryId);
                }
                $this->currentPage = (string) $currentPage;
                $this->pageParameter = ($this->currentPage !== '') ? ('page=' . $this->currentPage . '&') : '';
                $queryNumRows = 0;
                $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);
            } else {
                // Admin splitPageResults::findPage() currently over-advances rows near page boundaries
                // (for example, row 17 with 20-per-page resolves to page 2). Use our own row-offset
                // calculation here so the countries page resolves selected rows consistently.
                $currentPage = $this->findSelectedCountryPage($baseQueryRaw, $selectedCountryId);
                $currentPage = $this->normalizeCurrentPageValue($currentPage);
                $this->splitResults = $this->createSplitResults($currentPage, $queryRaw, $queryNumRows);
            }
        }

        $this->currentPage = (string) $currentPage;
        $this->pageParameter = ($this->currentPage !== '') ? ('page=' . $this->currentPage . '&') : '';
        $this->queryRaw = $queryRaw;
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
                $this->alphabeticMode ? 1 : (int) $this->currentPage,
                'page'
            );
        }

        return parent::processQuery($query);
    }

    public function resolveCurrentPage(Request $request): string
    {
        $requestedPage = trim((string) $request->input('page', ''));
        if ($this->alphabeticMode) {
            if ($requestedPage !== '' && ctype_alpha($requestedPage[0])) {
                return strtoupper($requestedPage[0]);
            }
            return '';
        }

        if (!ctype_digit($requestedPage) || (int) $requestedPage < 1) {
            return '1';
        }
        return (string) (int) $requestedPage;
    }

    public function getCurrentPage(): string
    {
        return $this->currentPage;
    }

    public function getPageParameter(): string
    {
        return $this->pageParameter;
    }

    public function getQueryNumRows(): int
    {
        return $this->queryNumRows;
    }

    public function getSplitResults(): ?\splitPageResults
    {
        return $this->splitResults;
    }

    public function getSortState(): ?SortState
    {
        return $this->sortState;
    }

    public function isAlphabeticMode(): bool
    {
        return $this->alphabeticMode;
    }

    protected function selectedCountryId(Request $request): ?int
    {
        $countryId = $request->input('cID');
        if ($countryId === null || !ctype_digit((string) $countryId)) {
            return null;
        }

        return (int) $countryId;
    }

    protected function createSplitResults(string &$currentPage, string &$queryRaw, int &$queryNumRows): \splitPageResults
    {
        if ($this->alphabeticMode) {
            $split = new \splitPageResults($currentPage, MAX_DISPLAY_SEARCH_RESULTS, $queryRaw, $queryNumRows, 'countries_name', 1);
            $currentPage = (string) $currentPage;

            return $split;
        }

        $numericPage = (int) $currentPage;
        if ($numericPage < 1) {
            $numericPage = 1;
        }

        $split = new \splitPageResults($numericPage, MAX_DISPLAY_SEARCH_RESULTS, $queryRaw, $queryNumRows);
        $currentPage = (string) $numericPage;

        return $split;
    }

    protected function normalizeCurrentPageValue($currentPage): string
    {
        if ($this->alphabeticMode) {
            $currentPage = trim((string) $currentPage);
            if ($currentPage !== '' && ctype_alpha($currentPage[0])) {
                return strtoupper($currentPage[0]);
            }

            return '';
        }

        if (!is_numeric((string) $currentPage)) {
            return '1';
        }

        $numericPage = (int) $currentPage;
        if ($numericPage < 1) {
            $numericPage = 1;
        }

        return (string) $numericPage;
    }

    protected function findSelectedCountryPage(string $queryRaw, int $selectedCountryId): string
    {
        global $db;

        $query = $db->Execute($queryRaw);
        $rowOffset = 0;
        foreach ($query as $row) {
            if ((int) ($row['countries_id'] ?? 0) === $selectedCountryId) {
                return (string) ((int) floor($rowOffset / MAX_DISPLAY_SEARCH_RESULTS) + 1);
            }
            $rowOffset++;
        }

        return $this->currentPage !== '' ? $this->currentPage : '1';
    }

    protected function findSelectedCountryLetter(string $queryRaw, int $selectedCountryId): string
    {
        global $db;

        $query = $db->Execute($queryRaw);
        foreach ($query as $row) {
            if ((int) ($row['countries_id'] ?? 0) !== $selectedCountryId) {
                continue;
            }

            $countryName = (string) ($row['countries_name'] ?? '');
            if ($countryName === '') {
                break;
            }

            return strtoupper((string) mb_substr($countryName, 0, 1));
        }

        return $this->currentPage;
    }

    protected function shouldUseAlphabeticMode(Request $request): bool
    {
        if ($this->resolveSearchTerm($request) !== null) {
            return false;
        }

        return !$this->hasExplicitNonDefaultSort($request);
    }

    protected function hasExplicitNonDefaultSort(Request $request): bool
    {
        $sortParameter = (string) $this->tableDefinition->getParameter('sortParameter');
        $directionParameter = (string) $this->tableDefinition->getParameter('sortDirectionParameter');
        $requestedColumn = trim((string) $request->input($sortParameter, ''));
        $requestedDirection = strtolower(trim((string) $request->input($directionParameter, '')));

        if ($requestedColumn === '' && $requestedDirection === '') {
            return false;
        }

        $defaultSort = $this->tableDefinition->getParameter('defaultSort');
        $defaultColumn = (string) ($defaultSort['column'] ?? '');
        $defaultDirection = strtolower((string) ($defaultSort['direction'] ?? 'asc'));

        if ($requestedColumn === '') {
            return $requestedDirection !== '' && $requestedDirection !== $defaultDirection;
        }

        if ($requestedColumn !== $defaultColumn) {
            return true;
        }

        if ($requestedDirection === '') {
            return false;
        }

        return $requestedDirection !== $defaultDirection;
    }
}

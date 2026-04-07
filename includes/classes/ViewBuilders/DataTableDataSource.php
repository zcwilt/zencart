<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2026 Feb 26 Modified in v2.2.1 $
 */

namespace Zencart\ViewBuilders;

use Zencart\Request\Request;
use Zencart\Traits\NotifierManager;

/**
 * @since ZC v1.5.8
 */
abstract class DataTableDataSource
{
    use NotifierManager;

    protected $tableDefinition;
    protected ?Request $activeRequest = null;

    public function __construct(TableViewDefinition $tableViewDefinition)
    {
        $this->tableDefinition = $tableViewDefinition;
        $this->notify('NOTIFY_DATASOURCE_CONSTRUCTOR_END');
    }

    /**
     * @since ZC v1.5.8
     */
    abstract protected function buildInitialQuery(Request $request);

    /**
     * @since ZC v1.5.8
     */
    public function processRequest(Request $request)
    {
        $this->activeRequest = $request;
        $query = $this->buildInitialQuery($request);
        $query = $this->applySearchFilter($request, $query);
        $this->notify('NOTIFY_DATASOURCE_PROCESSREQUEST', [], $query);
        return $query;
    }

    /**
     * @since ZC v1.5.8
     */
    public function processQuery($query): NativePaginator
    {
        $maxRows = $this->tableDefinition->isPaginated()
            ? (int)$this->tableDefinition->getParameter('maxRowCount')
            : 100000;
        $pagerVariable = (string) ($this->tableDefinition->getParameter('pagerVariable') ?? 'page');
        $page = $this->activeRequest?->integer($pagerVariable, 1) ?? 1;
        if ($page < 1) {
            $page = 1;
        }

        if (is_array($query)) {
            $total = count($query);
            $offset = ($page - 1) * $maxRows;
            if ($offset < 0) {
                $offset = 0;
            }
            $slice = array_slice($query, $offset, $maxRows);
            return new NativePaginator($slice, $total, $maxRows, $page, $pagerVariable);
        }

        if (is_object($query) && method_exists($query, 'paginate')) {
            /** @var NativePaginator $results */
            $results = $query->paginate($maxRows, '*', $pagerVariable, $page);
            return $results;
        }

        return new NativePaginator([], 0, $maxRows, $page, $pagerVariable);
    }

    /**
     * @since ZC v1.5.8
     */
    public function getTableDefinition(): TableViewDefinition
    {
        return $this->tableDefinition;
    }

    /**
     * @since ZC v1.5.8
     */
    public function setTableDefinition(TableViewDefinition $tableDefinition)
    {
        $this->tableDefinition = $tableDefinition;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSortState(Request $request): ?SortState
    {
        $sortColumn = $this->resolveSortColumn($request);
        if ($sortColumn === null) {
            return null;
        }

        $columnDefinition = $this->tableDefinition->getColumnDefinition($sortColumn);
        if ($columnDefinition === null) {
            return null;
        }

        $direction = $this->resolveSortDirection($request, $sortColumn, $columnDefinition);
        $sortExpression = $columnDefinition['sortKey'] ?? $sortColumn;

        return new SortState($sortColumn, $direction, $sortExpression);
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSortColumn(Request $request): ?string
    {
        $sortParameter = $this->tableDefinition->getParameter('sortParameter');
        $requestedColumn = (string) $request->input($sortParameter, '');
        if ($requestedColumn !== '' && $this->tableDefinition->isColumnSortable($requestedColumn)) {
            return $requestedColumn;
        }

        $defaultSort = $this->tableDefinition->getParameter('defaultSort');
        if (is_array($defaultSort) && !empty($defaultSort['column']) && $this->tableDefinition->isColumnSortable($defaultSort['column'])) {
            return $defaultSort['column'];
        }

        return null;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSortDirection(Request $request, string $sortColumn, array $columnDefinition): string
    {
        $directionParameter = $this->tableDefinition->getParameter('sortDirectionParameter');
        $requestedDirection = strtolower($request->string($directionParameter, ''));
        if ($requestedDirection === 'asc' || $requestedDirection === 'desc') {
            return $requestedDirection;
        }

        if (!empty($columnDefinition['defaultDirection'])) {
            return strtolower((string) $columnDefinition['defaultDirection']) === 'desc' ? 'desc' : 'asc';
        }

        $defaultSort = $this->tableDefinition->getParameter('defaultSort');
        if (is_array($defaultSort) && ($defaultSort['column'] ?? null) === $sortColumn && !empty($defaultSort['direction'])) {
            return strtolower((string) $defaultSort['direction']) === 'desc' ? 'desc' : 'asc';
        }

        return 'asc';
    }

    /**
     * @since ZC v2.2.1
     */
    protected function applySearchFilter(Request $request, $query)
    {
        $searchTerm = $this->resolveSearchTerm($request);
        if ($searchTerm === null) {
            return $query;
        }

        if (!is_array($query)) {
            return $query;
        }

        $searchableColumns = $this->tableDefinition->getSearchableColumns();
        if ($searchableColumns === []) {
            return $query;
        }

        return array_values(array_filter($query, function ($row) use ($searchTerm, $searchableColumns): bool {
            foreach ($searchableColumns as $field => $columnDefinition) {
                $haystack = $this->resolveSearchableValue($row, $field, $columnDefinition);
                if ($haystack !== '' && mb_stripos($haystack, $searchTerm) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSearchTerm(Request $request): ?string
    {
        if (!$this->tableDefinition->hasSearchableColumns()) {
            return null;
        }

        $searchParameter = (string) $this->tableDefinition->getParameter('searchParameter');
        $searchTerm = trim($request->string($searchParameter, ''));

        return $searchTerm === '' ? null : $searchTerm;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveFilterValue(Request $request, string $filterKey): ?string
    {
        $filterDefinition = $this->tableDefinition->getFilterDefinition($filterKey);
        if ($filterDefinition === null) {
            return null;
        }

        $parameter = (string) ($filterDefinition['parameter'] ?? $filterKey);
        $value = trim($request->string($parameter, ''));
        if ($value === '') {
            return null;
        }

        if (isset($filterDefinition['options']) && is_array($filterDefinition['options'])) {
            $allowedValues = array_map('strval', array_keys($filterDefinition['options']));
            if (!in_array($value, $allowedValues, true)) {
                return null;
            }
        }

        return $value;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSearchableValue($row, string $field, array $columnDefinition): string
    {
        $value = null;
        if (isset($columnDefinition['searchKey'])) {
            $value = $this->getRowField($row, (string) $columnDefinition['searchKey']);
        } else {
            $value = $this->getRowField($row, $field);
        }

        if ($value === null) {
            return '';
        }

        return trim(strip_tags((string) $value));
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getRowField($row, string $field, $default = null)
    {
        if (is_array($row)) {
            return $row[$field] ?? $default;
        }

        if ($row instanceof \ArrayAccess && isset($row[$field])) {
            return $row[$field];
        }

        if (is_object($row) && isset($row->$field)) {
            return $row->$field;
        }

        return $default;
    }
}

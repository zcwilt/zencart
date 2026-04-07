<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2026 Feb 26 Modified in v2.2.1 $
 */

namespace Zencart\ViewBuilders;

use Zencart\Request\Request;

/**
 * @since ZC v1.5.8
 */
class SimpleDataFormatter
{
    protected $request;
    protected $tableDefinition;
    protected $resultSet;
    protected $derivedItems;

    public function __construct(Request $request, TableViewDefinition $tableViewDefinition, NativePaginator $resultSet, $derivedItems)
    {
        $this->request = $request;
        $this->tableDefinition = $tableViewDefinition;
        $this->resultSet = $resultSet;
        $this->derivedItems = $derivedItems;
    }

    /**
     * @since ZC v1.5.8
     */
    public function getTableHeaders(): array
    {
        $colHeaders = [];
        $columns = $this->tableDefinition->getParameter('columns');
        foreach ($columns as $field => $column) {
            $headerClass = $this->getColHeaderMainClass($column);
            $sortHeader = $this->buildSortHeader($field, $column);
            $colHeaders[] = array_merge(
                ['headerClass' => $headerClass, 'title' => $column['title'], 'href' => null],
                $sortHeader
            );
        }
        return $colHeaders;
    }

    /**
     * @since ZC v1.5.8
     */
    public function getTableData()
    {
        $tableData = [];
        $columns = $this->tableDefinition->getParameter('columns');
        $fields = array_keys($columns);
        $columnData = [];
        foreach ($this->resultSet as $result) {
            foreach ($fields as $field) {
                $value = $this->derivedItems->process($result, $field, $columns[$field]);
                $originalValue = $this->getRowField($result, $field);

                $class = '';
                // if column class is set as a closure, call it and pass in the value from $result->field; else assume it is a string
                $classDef = $columns[$field]['class'] ?? null;
                if ($classDef instanceof \Closure || is_callable($classDef)) {
                    $class = $classDef($originalValue);
                } elseif (is_string($classDef)) {
                    $class = $classDef;
                }

                $columnData[$field] = ['value' => $value, 'class' => $class, 'original' => $originalValue];
            }
            $tableData[] = $columnData;
        }
        return $tableData;
    }

    /**
     * @since ZC v1.5.8
     */
    public function isRowSelected(array $tableRow): bool
    {
        $colKeyFromRequest = $this->request->input($this->tableDefinition->colKeyName());
        $colKeyField = $this->tableDefinition->getParameter('colKey');
        $currentRow = $this->currentRowFromRequest();
        if (is_null($colKeyFromRequest) && $currentRow->$colKeyField == $tableRow[$colKeyField]['value']) {
            return true;
        }
        if ($colKeyFromRequest == $tableRow[$colKeyField]['value']) {
            return true;
        }
        return false;
    }

    /**
     * @since ZC v1.5.8
     */
    public function currentRowFromRequest()
    {
        $colKeyFromRequest = $this->request->input($this->tableDefinition->colKeyName());
        $colKeyField = $this->tableDefinition->getParameter('colKey');
        if (!is_null($colKeyFromRequest)) {
            $result = null;
            foreach ($this->resultSet->getCollection() as $row) {
                if ((string)$row[$colKeyField] === (string)$colKeyFromRequest) {
                    $result = $row;
                    break;
                }
            }
        } else {
            $result = $this->resultSet->getCollection()[0] ?? null;
        }
        return $result;
    }

    /**
     * @since ZC v1.5.8
     */
    public function getSelectedRowLink(array $tableRow): string
    {
        $params = $this->getPersistedRequestParameters();
        $params[$this->tableDefinition->colKeyName()] = $tableRow[$this->tableDefinition->getParameter('colKey')]['value'];
        $selectedRowAction = $this->tableDefinition->getParameter('selectedRowAction');
        if (!empty($selectedRowAction)) {
            $params['action'] = $selectedRowAction;
        }
        return zen_href_link($this->request->input('cmd'), $this->buildQueryString($params));
    }

    /**
     * @since ZC v1.5.8
     */
    public function getNotSelectedRowLink(array $tableRow): string
    {
        $params = $this->getPersistedRequestParameters();
        $params[$this->tableDefinition->colKeyName()] = $tableRow[$this->tableDefinition->getParameter('colKey')]['value'];
        return zen_href_link($this->request->input('cmd'), $this->buildQueryString($params));

    }

    /**
     * @since ZC v2.2.1
     */
    public function getPersistentLinkParameters(array $exclude = []): string
    {
        return $this->buildQueryString($this->getPersistedRequestParameters($exclude));
    }

    /**
     * @since ZC v2.2.1
     */
    public function hasSearch(): bool
    {
        return $this->tableDefinition->hasSearchableColumns();
    }

    /**
     * @since ZC v2.2.1
     */
    public function hasFilters(): bool
    {
        return $this->tableDefinition->hasFilters();
    }

    /**
     * @since ZC v2.2.1
     */
    public function filters(): array
    {
        $filters = [];
        foreach ($this->tableDefinition->getFilters() as $filterKey => $definition) {
            $parameter = (string) ($definition['parameter'] ?? $filterKey);
            $filters[] = [
                'key' => $filterKey,
                'parameter' => $parameter,
                'label' => (string) ($definition['label'] ?? ucfirst($filterKey)),
                'type' => (string) ($definition['type'] ?? 'select'),
                'options' => $definition['options'] ?? [],
                'value' => trim((string) $this->request->input($parameter, '')),
            ];
        }

        return $filters;
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchValue(): string
    {
        $searchParameter = $this->tableDefinition->getParameter('searchParameter');
        return trim((string) $this->request->input($searchParameter, ''));
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchParameter(): string
    {
        return (string) $this->tableDefinition->getParameter('searchParameter');
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchPlaceholder(): string
    {
        return (string) $this->tableDefinition->getParameter('searchPlaceholder');
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchAction(): string
    {
        return (string) $this->request->input('cmd');
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchHiddenParameters(): array
    {
        return $this->getPersistedRequestParameters([
            $this->tableDefinition->getParameter('pagerVariable'),
            $this->tableDefinition->colKeyName(),
            'action',
            $this->tableDefinition->getParameter('searchParameter'),
        ]);
    }

    /**
     * @since ZC v2.2.1
     */
    public function toolbarHiddenParameters(): array
    {
        return $this->getPersistedRequestParameters(array_merge(
            [
                $this->tableDefinition->getParameter('pagerVariable'),
                $this->tableDefinition->colKeyName(),
                'action',
                $this->tableDefinition->getParameter('searchParameter'),
            ],
            $this->tableDefinition->getFilterParameters()
        ));
    }

    /**
     * @since ZC v2.2.1
     */
    public function searchResetHref(): string
    {
        return zen_href_link($this->searchAction(), $this->buildQueryString($this->searchHiddenParameters()));
    }

    /**
     * @since ZC v1.5.8
     */
    public function getResultSet()
    {
        return $this->resultSet;
    }

    /**
     * @since ZC v2.2.0
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

    /**
     * @since ZC v2.2.1
     */
    protected function getCurrentPagerValue()
    {
        return $this->request->input($this->tableDefinition->getParameter('pagerVariable'), 1);
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getPersistedRequestParameters(array $exclude = []): array
    {
        $excludeLookup = array_fill_keys($exclude, true);
        $params = [];
        foreach ($this->getPersistedParameterNames() as $parameterName) {
            if (isset($excludeLookup[$parameterName])) {
                continue;
            }

            if ($parameterName === $this->tableDefinition->getParameter('pagerVariable')) {
                $pageValue = $this->getCurrentPagerValue();
                if ($pageValue !== null && $pageValue !== '') {
                    $params[$parameterName] = $pageValue;
                }
                continue;
            }

            if ($this->request->has($parameterName)) {
                $params[$parameterName] = $this->request->input($parameterName);
            }
        }
        return $params;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getPersistedParameterNames(): array
    {
        return array_values(array_unique(array_merge(
            [
                $this->tableDefinition->getParameter('pagerVariable'),
                $this->tableDefinition->getParameter('sortParameter'),
                $this->tableDefinition->getParameter('sortDirectionParameter'),
                $this->tableDefinition->getParameter('searchParameter'),
            ],
            $this->tableDefinition->getFilterParameters(),
            $this->tableDefinition->getParameter('persistedParameters')
        )));
    }

    /**
     * @since ZC v2.2.1
     */
    protected function buildQueryString(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        return implode('&', $parts);
    }

    /**
     * @since ZC v2.2.1
     */
    protected function buildSortHeader(string $field, array $column): array
    {
        if (empty($column['sortable'])) {
            return [
                'isSortable' => false,
                'isSorted' => false,
                'sortField' => null,
                'sortDirection' => null,
                'nextSortDirection' => null,
                'sortIndicator' => '',
            ];
        }

        $activeSortColumn = $this->resolveSortColumn();
        $activeDirection = $this->resolveSortDirection($activeSortColumn);
        $isSorted = $activeSortColumn === $field;
        $nextDirection = $isSorted
            ? ($activeDirection === 'asc' ? 'desc' : 'asc')
            : $this->initialSortDirection($column);

        $params = $this->getPersistedRequestParameters([
            $this->tableDefinition->getParameter('pagerVariable'),
            $this->tableDefinition->getParameter('sortParameter'),
            $this->tableDefinition->getParameter('sortDirectionParameter'),
        ]);
        $params[$this->tableDefinition->getParameter('sortParameter')] = $field;
        $params[$this->tableDefinition->getParameter('sortDirectionParameter')] = $nextDirection;

        return [
            'href' => zen_href_link($this->request->input('cmd'), $this->buildQueryString($params)),
            'isSortable' => true,
            'isSorted' => $isSorted,
            'sortField' => $field,
            'sortDirection' => $isSorted ? $activeDirection : null,
            'nextSortDirection' => $nextDirection,
            'sortIndicator' => $isSorted ? ($activeDirection === 'asc' ? ' ↑' : ' ↓') : '',
        ];
    }

    /**
     * @since ZC v2.2.1
     */
    protected function resolveSortColumn(): ?string
    {
        $sortParameter = $this->tableDefinition->getParameter('sortParameter');
        $requestedColumn = (string) $this->request->input($sortParameter, '');
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
    protected function resolveSortDirection(?string $sortColumn): string
    {
        $directionParameter = $this->tableDefinition->getParameter('sortDirectionParameter');
        $requestedDirection = strtolower((string) $this->request->input($directionParameter, ''));
        if ($requestedDirection === 'asc' || $requestedDirection === 'desc') {
            return $requestedDirection;
        }

        if ($sortColumn !== null) {
            $columnDefinition = $this->tableDefinition->getColumnDefinition($sortColumn) ?? [];
            return $this->initialSortDirection($columnDefinition, $sortColumn);
        }

        return 'asc';
    }

    /**
     * @since ZC v2.2.1
     */
    protected function initialSortDirection(array $column, ?string $field = null): string
    {
        if (!empty($column['defaultDirection'])) {
            return strtolower((string) $column['defaultDirection']) === 'desc' ? 'desc' : 'asc';
        }

        $defaultSort = $this->tableDefinition->getParameter('defaultSort');
        if (
            is_array($defaultSort)
            && !empty($defaultSort['direction'])
            && ($field === null || ($defaultSort['column'] ?? null) === $field)
        ) {
            return strtolower((string) $defaultSort['direction']) === 'desc' ? 'desc' : 'asc';
        }

        return 'asc';
    }

    /**
     * @since ZC v1.5.8
     */
    public function hasRowActions()
    {
        return $this->tableDefinition->hasRowActions();
    }

    /**
     * @since ZC v1.5.8
     */
    public function getRowActions($tableRow)
    {
        $rowActions = $this->tableDefinition->getRowActions();
        $processed = [];
        foreach ($rowActions as $rowAction) {
            $processed[] = $this->processRowAction($rowAction, $tableRow);
        }
        return $processed;
    }

    /**
     * @since ZC v1.5.8
     */
    public function hasButtonActions()
    {
         $buttonActions = $this->getRawButtonActions();
         if (count($buttonActions) == 0) {
             return false;
         }
         return (count($buttonActions) > 0);
    }

    /**
     * @since ZC v1.5.8
     */
    public function getButtonActions()
    {
        $buttonActions = $this->getRawButtonActions();
        $processed = [];
        foreach ($buttonActions as $buttonAction) {
            $buttonAction['hrefLink'] = $this->processButtonActionLink($buttonAction);
            $processed[] = $buttonAction;
        }
        return $processed;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function getRawButtonActions()
    {
        $buttonActions = $this->tableDefinition->getButtonActions();
        if (count($buttonActions) == 0) {
            return [];
        }
        $processed = [];
        foreach ($buttonActions as $buttonAction) {
            if ($this->buttonPassesWhiteList($buttonAction) && $this->buttonPassesBlackList($buttonAction)) {
                $processed[] = $buttonAction;
            }
        }
        return $processed;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function processButtonActionLink($buttonAction)
    {
        $link = 'action=' . $buttonAction['action'];
        return $link;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function buttonPassesWhiteList($buttonAction)
    {
        $action = $this->request->input('action');
        if (!isset($buttonAction['whitelist'])) {
            return true;
        }
        if (in_array($action, $buttonAction['whitelist'])) {
            return true;
        }
        return false;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function buttonPassesBlackList($buttonAction)
    {
        $action = $this->request->input('action');
        if (!isset($buttonAction['blacklist'])) {
            return true;
        }
        if (in_array($action, $buttonAction['blacklist'])) {
            return false;
        }
        return true;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function processRowAction($rowAction, $tableRow)
    {
        $processed = $rowAction;
        $link = $this->buildRowActionLink($rowAction, $tableRow);
        $processed['hrefLink'] = $link;
        return $processed;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function buildRowActionLink($rowAction, $tableRow)
    {
        $pagerVar = $this->tableDefinition->getParameter('pagerVariable');
        $link = $pagerVar . '=' . $this->request->input($pagerVar, 1);
        $link .= '&action='  . $rowAction['action'];
        $tableRowLink = $this->processRowActionTableRowLink($rowAction, $tableRow);
        $tableRowLink = rtrim($tableRowLink, '&');
        $link .= '&' . $tableRowLink;
        return $link;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function processRowActionTableRowLink($rowAction, $tableRow)
    {
        $link = '';
        if (!isset($rowAction['linkParams'])) {
            return $link;
        }
        foreach ($rowAction['linkParams'] as $linkParams) {
            if ($linkParams['source'] !== 'tableRow') continue;
            $link .= $linkParams['param'] . '=' . $tableRow[$linkParams['field']]['original'] . '&';
        }
        return $link;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function getColHeaderMainClass($colDef)
    {
        $mainClass = "dataTableHeadingContent";
        return $mainClass;
    }
}

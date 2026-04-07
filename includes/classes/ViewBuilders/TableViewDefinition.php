<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2026 Feb 26 Modified in v2.2.1 $
 */

namespace Zencart\ViewBuilders;

/**
 * @since ZC v1.5.8
 */
class TableViewDefinition
{
    /**
     * $definition is an array holding the table definition
     * @var array
     */
    protected $definition = [];
    public function __construct(array $definition = [])
    {
        $this->definition = $definition;
        $this->setDefaults();
    }

    /**
     * @since ZC v1.5.8
     */
    public function getDefinition() : array
    {
        return $this->definition;
    }

    /**
     * @since ZC v1.5.8
     */
    public function setParameter(string $field, $definition) : TableViewDefinition
    {
        $this->definition[$field] = $definition;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function getParameter(string $field)
    {
        $def = $this->definition[$field] ?? null;
        return $def;
    }

    /**
     * @since ZC v1.5.8
     */
    public function addButtonAction($definition) : TableViewDefinition
    {
        $this->definition['buttonActions'][] = $definition;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function addRowAction($definition) : TableViewDefinition
    {
        $this->definition['rowActions'][] = $definition;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function addColumn(string $field, $definition) : TableViewDefinition
    {
        $this->definition['columns'][$field] = $definition;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function addColumnBefore($index, $newKey, $data) : TableViewDefinition
    {
        $columns = $this->definition['columns'];
        $columns = $this->insertBefore($columns, $index, $newKey, $data);
        $this->definition['columns'] = $columns;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function addColumnAfter($index, $newKey, $data) : TableViewDefinition
    {
        $columns = $this->definition['columns'];
        $columns = $this->insertAfter($columns, $index, $newKey, $data);
        $this->definition['columns'] = $columns;
        return $this;
    }

    /**
     * @since ZC v1.5.8
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->definition['columns'] as $column) {
            $headers[] = $column['title'] ?? '';
        }
        return $headers;
    }

    /**
     * @since ZC v1.5.8
     */
    public function isPaginated() : bool
    {
        return ($this->definition['paginated']);
    }

    /**
     * @since ZC v1.5.8
     */
    public function colKeyName() : string
    {
        return $this->definition['colKeyName'];
    }

    /**
     * @since ZC v1.5.8
     */
    public function hasRowActions() : bool
    {
        return (count($this->definition['rowActions']) >0);
    }

    /**
     * @since ZC v1.5.8
     */
    public function getRowActions() : array
    {
        return $this->definition['rowActions'];
    }

    /**
     * @since ZC v1.5.8
     */
    public function getButtonActions() : array
    {
        return $this->definition['buttonActions'];
    }

    /**
     * @since ZC v2.2.1
     */
    public function getColumnDefinition(string $field): ?array
    {
        return $this->definition['columns'][$field] ?? null;
    }

    /**
     * @since ZC v2.2.1
     */
    public function isColumnSortable(string $field): bool
    {
        $column = $this->getColumnDefinition($field);
        return !empty($column['sortable']);
    }

    /**
     * @since ZC v2.2.1
     */
    public function isColumnSearchable(string $field): bool
    {
        $column = $this->getColumnDefinition($field);
        return !empty($column['searchable']);
    }

    /**
     * @since ZC v2.2.1
     */
    public function hasSearchableColumns(): bool
    {
        foreach (array_keys($this->definition['columns']) as $field) {
            if ($this->isColumnSearchable($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @since ZC v2.2.1
     */
    public function getSearchableColumns(): array
    {
        $searchable = [];
        foreach ($this->definition['columns'] as $field => $column) {
            if ($this->isColumnSearchable($field)) {
                $searchable[$field] = $column;
            }
        }

        return $searchable;
    }

    /**
     * @since ZC v2.2.1
     */
    public function hasFilters(): bool
    {
        return !empty($this->definition['filters']);
    }

    /**
     * @since ZC v2.2.1
     */
    public function getFilters(): array
    {
        return $this->definition['filters'];
    }

    /**
     * @since ZC v2.2.1
     */
    public function getFilterDefinition(string $filterKey): ?array
    {
        return $this->definition['filters'][$filterKey] ?? null;
    }

    /**
     * @since ZC v2.2.1
     */
    public function getFilterParameters(): array
    {
        $parameters = [];
        foreach ($this->getFilters() as $filterKey => $definition) {
            $parameters[] = (string) ($definition['parameter'] ?? $filterKey);
        }

        return array_values(array_unique($parameters));
    }

    /**
     * @since ZC v1.5.8
     */
    protected function setDefaults()
    {
        $this->definition['paginated'] = $this->definition['paginated'] ?? true;
        $this->definition['columns'] = $this->definition['columns'] ?? [];
        $this->definition['buttonActions'] = $this->definition['buttonActions'] ?? [];
        $this->definition['rowActions'] = $this->definition['rowActions'] ?? [];
        $this->definition['maxRowCount'] = $this->definition['maxRowCount'] ?? 10;
        $this->definition['colKeyName'] = $this->definition['colKeyName'] ?? 'colKey';
        $this->definition['pagerVariable'] = $this->definition['pagerVariable'] ?? 'page';
        $this->definition['colKey'] = $this->definition['colKey'] ?? 'id';
        $this->definition['sortParameter'] = $this->definition['sortParameter'] ?? 'sort';
        $this->definition['sortDirectionParameter'] = $this->definition['sortDirectionParameter'] ?? 'direction';
        $this->definition['defaultSort'] = $this->definition['defaultSort'] ?? null;
        $this->definition['persistedParameters'] = $this->definition['persistedParameters'] ?? [];
        $this->definition['searchParameter'] = $this->definition['searchParameter'] ?? 'search';
        $this->definition['searchPlaceholder'] = $this->definition['searchPlaceholder'] ?? 'Search';
        $this->definition['filters'] = $this->definition['filters'] ?? [];
    }

    /**
     * @since ZC v1.5.8
     */
    protected function addDefinitions($original, $addition)
    {
        return $original + $addition;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function insertBefore($input, $index, $newKey, $element) {
        if (!array_key_exists($index, $input)) {
            return $input;
        }
        $tmpArray = array();
        foreach ($input as $key => $value) {
            if ($key === $index) {
                $tmpArray[$newKey] = $element;
            }
            $tmpArray[$key] = $value;
        }
        return $tmpArray;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function insertAfter($input, $index, $newKey, $element) {
        if (!array_key_exists($index, $input)) {
            return $input;
        }
        $tmpArray = array();
        foreach ($input as $key => $value) {
            $tmpArray[$key] = $value;
            if ($key === $index) {
                $tmpArray[$newKey] = $element;
            }
        }
        return $tmpArray;
    }
}

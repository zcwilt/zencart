<?php

namespace Zencart\TableViewControllers;

use Zencart\FileSystem\FileSystem;

class FacetedSearchController extends BaseController
{
    protected function buildListQuery()
    {
        $queryParts['mainTable']['table'] = TABLE_PRODUCTS_FACETED_SEARCH;
        $queryParts['mainTable']['countField'] = 'id';
        $queryParts['joinTables'][] = ['type' => 'LEFT', 'table' => TABLE_PRODUCTS_DESCRIPTION, 'fkeyFieldRight' => 'products_id', 'addColumns' => true];
        $queryParts['whereClauses'][] = ['table' => TABLE_PRODUCTS_DESCRIPTION, 'value' => $_SESSION['languages_id'], 'field' => 'language_id'];
        return $queryParts;
    }
}
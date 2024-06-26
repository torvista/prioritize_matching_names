<?php
// -----
// Part of the "Search: Prioritize Matching Names" plugin for Zen Cart v1.5.8a and later
// Copyright (C) 2015-2024, Vinos de Frutas Tropicales (lat9)
//
// Last updated: v1.0.1
//
// @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
class zcObserverPrioritizeSearchMatchInName extends base
{
    protected string $order_by;

    public function __construct()
    {
        $this->attach(
            $this,
            [
                'NOTIFY_SEARCH_WHERE_STRING',
                'NOTIFY_SEARCH_ORDERBY_STRING',
            ]
        );
    }

    public function update(&$class, $eventID)
    {
        switch ($eventID) {
            case 'NOTIFY_SEARCH_WHERE_STRING':
                global $search_keywords, $keywords, $select_str;
                $in_name_select = '';
                if (function_exists('zen_build_keyword_where_clause') && !empty($keywords)) {
                    $in_name_select = zen_build_keyword_where_clause(['pd.products_name'], $keywords);
                    $in_name_select = substr($in_name_select, 5);   //- Remove unwanted ' AND (' lead-in
                } elseif (!empty($search_keywords)) {
                    $in_name_select = $this->buildInNameSelectClause($search_keywords);
                }
                if ($in_name_select !== '') {
                    $select_str .= ", IF ($in_name_select, 1, 0) AS in_name ";
                    $this->order_by = ' in_name DESC,';
                }
                break;

            case 'NOTIFY_SEARCH_ORDERBY_STRING':
                if (isset($this->order_by)) {
                    global $listing_sql;
                    $listing_sql = str_ireplace('order by', 'order by' . $this->order_by, $listing_sql);
                }
                break;

            default:
                break;
        }
    }

    protected function buildInNameSelectClause($search_keywords)
    {
        global $db;
        $in_name_select = '';
        foreach ($search_keywords as $current_keyword) {
            switch ($current_keyword) {
                case '(':
                case ')':
                    break;

                case 'and':
                case 'or':
                    $in_name_select .= " $current_keyword ";
                    break;

                default:
                    $in_name_select .= $db->bindVars("pd.products_name LIKE '%:keywords%'", ':keywords', $current_keyword, 'noquotestring');
                    break;
            }
        }
        return $in_name_select;
    }
}

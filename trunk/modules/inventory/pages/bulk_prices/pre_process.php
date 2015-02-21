<?php
// +-----------------------------------------------------------------+
// |                   PhreeBooks Open Source ERP                    |
// +-----------------------------------------------------------------+
// | Copyright(c) 2008-2015 PhreeSoft      (www.PhreeSoft.com)       |
// +-----------------------------------------------------------------+
// | This program is free software: you can redistribute it and/or   |
// | modify it under the terms of the GNU General Public License as  |
// | published by the Free Software Foundation, either version 3 of  |
// | the License, or any later version.                              |
// |                                                                 |
// | This program is distributed in the hope that it will be useful, |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of  |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the   |
// | GNU General Public License for more details.                    |
// +-----------------------------------------------------------------+
//  Path: /modules/inventory/pages/bulk_prices/pre_process.php
//
$security_level = \core\classes\user::validate(SECURITY_ID_PRICE_SHEET_MANAGER);
/**************  include page specific files    *********************/
/**************   page specific initialization  *************************/
history_filter('inv_bulk');
/***************   hook for custom actions  ***************************/
$custom_path = DIR_FS_WORKING . 'custom/pages/bulk_prices/extra_actions.php';
if (file_exists($custom_path)) { include($custom_path); }
/***************   Act on the action request   *************************/
switch ($_REQUEST['action']) {
  case 'save':
	$j = 1;
	while (true) {
		if (isset($_POST['id_' . $j])) {
			$id = db_prepare_input($_POST['id_' . $j]);
			$re_order   = $admin->currencies->clean_value($_POST['reOrd_' . $j]);
			$min_stock  = $admin->currencies->clean_value($_POST['min_'   . $j]);
			$lead_time  = $admin->currencies->clean_value($_POST['lead_'  . $j]);
			$item_cost  = $admin->currencies->clean_value($_POST['cost_'  . $j]);
			$full_price = $admin->currencies->clean_value($_POST['sell_'  . $j]);
			$admin->DataBase->query("update " . TABLE_INVENTORY . " set
				lead_time  = '$lead_time',
				item_cost  = '$item_cost',
				full_price = '$full_price',
				minimum_stock_level = '$min_stock',
				reorder_quantity = '$re_order'
				where id = $id");
		} else {
			break;
		}
		$j++;
	}
	gen_add_audit_log(TEXT_BULK_SKU_PRICING_ENTRY. ' - ' . TEXT_UPDATE);
	break;
  case 'go_first':    $_REQUEST['list'] = 1;       break;
  case 'go_previous': $_REQUEST['list'] = max($_REQUEST['list']-1, 1); break;
  case 'go_next':     $_REQUEST['list']++;         break;
  case 'go_last':     $_REQUEST['list'] = 99999;   break;
  case 'search':
  case 'search_reset':
  case 'go_page':
  default:
}
/*****************   prepare to display templates  *************************/
$include_header   = true;
$include_footer   = true;
$heading_array = array(
  'sku'               => TEXT_SKU,
  'inactive'          => TEXT_INACTIVE,
  'description_short' => TEXT_DESCRIPTION,
  'lead_time'         => TEXT_LEAD_TIME_DAYS,
  'minimum_stock_level' => TEXT_MINIMUM_STOCK_LEVEL,
  'reorder_quantity'  => TEXT_REORDER_QUANTITY,
  'item_cost'         => TEXT_ITEM_COST . (ENABLE_MULTI_CURRENCY ? ' (' . DEFAULT_CURRENCY . ')' : ''),
  'full_price'        => TEXT_FULL_PRICE . (ENABLE_MULTI_CURRENCY ? ' (' . DEFAULT_CURRENCY . ')' : ''));
$result      = html_heading_bar($heading_array);
$list_header = $result['html_code'];
$disp_order  = $result['disp_order'];
// build the list for the page selected
$search = '';
if (isset($_REQUEST['search_text']) && $_REQUEST['search_text'] <> '') {
  $search_fields = array('sku', 'description_short', 'description_sales', 'description_purchase');
  // hook for inserting new search fields to the query criteria.
  if (is_array($extra_search_fields)) $search_fields = array_merge($search_fields, $extra_search_fields);
  $search = " where " . implode(" like %{$_REQUEST['search_text']}%' or ", $search_fields) . " like '%{$_REQUEST['search_text']}%";
}
$field_list = array('id', 'sku', 'inactive', 'description_short', 'lead_time', 'item_cost', 'full_price', 'minimum_stock_level', 'reorder_quantity');
// hook to add new fields to the query return results
if (is_array($extra_query_list_fields) > 0) $field_list = array_merge($field_list, $extra_query_list_fields);

$query_raw    = "select SQL_CALC_FOUND_ROWS " . implode(', ', $field_list)  . " from " . TABLE_INVENTORY . $search . " order by $disp_order";
$query_result = $admin->DataBase->query($query_raw, (MAX_DISPLAY_SEARCH_RESULTS * ($_REQUEST['list'] - 1)).", ".  MAX_DISPLAY_SEARCH_RESULTS);
$query_split  = new \core\classes\splitPageResults($_REQUEST['list'], '');
history_save('inv_bulk');

$include_template = 'template_main.php';
define('PAGE_TITLE', TEXT_BULK_SKU_PRICING_ENTRY);
?>
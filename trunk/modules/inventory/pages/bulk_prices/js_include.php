<?php
// +-----------------------------------------------------------------+
// |                   PhreeBooks Open Source ERP                    |
// +-----------------------------------------------------------------+
// | Copyright(c) 2008-2014 PhreeSoft      (www.PhreeSoft.com)       |
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
//  Path: /modules/inventory/pages/bulk_prices/js_include.php
//

?>
<script type="text/javascript">
<!--
// pass any php variables generated during pre-process that are used in the javascript functions.
// Include translations here as well.

function init() {
  document.getElementById('search_text').focus();
  document.getElementById('search_text').select();
}

function check_form() {
  return true;
}

// Insert other page specific functions here.
function priceMgr(rowID, itemID) {
	var rowCost = cleanCurrency(document.getElementById('cost_'+rowID).value);
	var rowPrice = cleanCurrency(document.getElementById('sell_'+rowID).value);
	window.open('index.php?module=inventory&page=popup_price_mgr&iID='+itemID+'&cost='+rowCost+'&price='+rowPrice,"price_mgr","width=800,height=400,resizable=1,scrollbars=1,top=150,left=200");
}

// -->
</script>
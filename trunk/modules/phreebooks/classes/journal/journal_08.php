<?php
// +-----------------------------------------------------------------+
// |                   PhreeBooks Open Source ERP                    |
// +-----------------------------------------------------------------+
// | Copyright(c) 2008-2013 PhreeSoft, LLC (www.PhreeSoft.com)       |
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
//  Path: /modules/phreebooks/classes/journal/journal_8.php
//
// Payroll Journal (8)
namespace phreebooks\classes\journal;
class journal_08 extends \core\classes\journal {


	/*******************************************************************************************************************/
	// START re-post Functions
	/*******************************************************************************************************************/
	function check_for_re_post() {
		global $admin;
		$admin->messageStack->debug("\n  Checking for re-post records ... ");
		$admin->messageStack->debug(" end check for Re-post with no action.");
		return array();
	}

	/*******************************************************************************************************************/
	// START Chart of Accout Functions
	/*******************************************************************************************************************/
	function Post_chart_balances() {
		global $admin;
		$admin->messageStack->debug("\n  Posting Chart Balances...");
		$admin->messageStack->debug(" end Posting Chart Balances with no action.");
		return true;
	}

	/**
	 * this function will un do the changes to the chart_of_account_history table
	 */
	function unPost_chart_balances() {
		global $admin;
		$admin->messageStack->debug("\n  unPosting Chart Balances...");
		$admin->messageStack->debug(" end unPosting Chart Balances with no action.");
	}

	// *********  chart of account support functions  **********
	function update_chart_history_periods($period = CURRENT_ACCOUNTING_PERIOD) {
		global $admin;
		// first find out the last period with data in the system from the current_status table
		$sql = $admin->DataBase->query("SELECT fiscal_year FROM " . TABLE_ACCOUNTING_PERIODS . " WHERE period = " . $period);
		if ($sql->fetch(\PDO::FETCH_NUM) == 0) throw new \core\classes\userException(GL_ERROR_BAD_ACCT_PERIOD); //@todo gebruiken ipv rowCount
		$fiscal_year = $sql->fetch(\PDO::FETCH_LAZY);
		$sql = "SELECT max(period) as period FROM " . TABLE_ACCOUNTING_PERIODS . " WHERE fiscal_year = " . $fiscal_year;
		$result = $admin->DataBase->query($sql);
		$max_period = $result['period'];
		$affected_acct_string = (is_array($this->affected_accounts)) ? implode("', '", array_keys($this->affected_accounts)) : '';
		$admin->messageStack->debug("\n  Updating chart history for fiscal year: $fiscal_year and period: $period for accounts: ('$affected_acct_string')");
		for ($i = $period; $i <= $max_period; $i++) {
			$this->validate_balance($i);//will throw exceptions
			// update future months
			$sql = "SELECT account_id, beginning_balance + debit_amount - credit_amount as beginning_balance FROM " . TABLE_CHART_OF_ACCOUNTS_HISTORY . "
			WHERE account_id in ('$affected_acct_string') and period = " . $i;
			$sql = $admin->DataBase->prepare($sql);
			$sql->execute();
			while ($result = $sql->fetch(\PDO::FETCH_LAZY)) {
				$sql = "UPDATE " . TABLE_CHART_OF_ACCOUNTS_HISTORY . " SET beginning_balance = {$result['beginning_balance']}
				WHERE period = " . ($i + 1) . " and account_id = '{$result->fields['account_id']}'";
				$admin->DataBase->exec($sql);
			}
		}
		// see if there is another fiscal year to roll into
		$result = $admin->DataBase->query("SELECT fiscal_year FROM " . TABLE_ACCOUNTING_PERIODS . " WHERE period = " . ($max_period + 1));
		if ($result->fetch(\PDO::FETCH_NUM) > 0) { // close balances for end of this fiscal year and roll post into next fiscal year
			// select retained earnings account
			$result = $admin->DataBase->query("SELECT id FROM " . TABLE_CHART_OF_ACCOUNTS . " WHERE account_type = 44");
			if ($result->fetch(\PDO::FETCH_NUM) <> 1) throw new \core\classes\userException(GL_ERROR_NO_RETAINED_EARNINGS_ACCOUNT);
			$retained_earnings_acct = $result['id'];
			$this->affected_accounts[$retained_earnings_acct] = 1;
			// select list of accounts that need to be closed, adjusted
			$sql = $admin->DataBase->prepare("SELECT id FROM " . TABLE_CHART_OF_ACCOUNTS . " WHERE account_type in (30, 32, 34, 42, 44)");
			$sql->execute();
			$acct_list = $sql->fetchAll();
			$acct_string = implode("','",$acct_list);
			// fetch the totals for the closed accounts
			$sql = "SELECT sum(beginning_balance + debit_amount - credit_amount) as retained_earnings
			  FROM " . TABLE_CHART_OF_ACCOUNTS_HISTORY . "
			  WHERE account_id in ('$acct_string') and period = " . $max_period;
			$result = $admin->DataBase->query($sql);
			$retained_earnings = $result['retained_earnings'];
			// clear out the expense, sales, cogs, and other year end accounts that need to be closed
			// needs to be before writing retained earnings account, since retained earnings is part of acct_string
			$result = $admin->DataBase->exec("UPDATE " . TABLE_CHART_OF_ACCOUNTS_HISTORY . " SET beginning_balance = 0 WHERE account_id in ('$acct_string') and period = " . ($max_period + 1));
			// update the retained earnings account
			$result = $admin->DataBase->exec("UPDATE " . TABLE_CHART_OF_ACCOUNTS_HISTORY . " SET beginning_balance = $retained_earnings WHERE account_id = '$retained_earnings_acct' and period = " . ($max_period + 1));
			// now continue rolling in current post into next fiscal year
			$this->update_chart_history_periods($max_period + 1);
		}
		// all historical chart of account balances from period on should be OK at this point.
		$admin->messageStack->debug("\n  end Updating chart history periods. Fiscal Year: " . $fiscal_year);;
		return true;
	}

	/*******************************************************************************************************************/
	// END Chart of Accout Functions
	/*******************************************************************************************************************/
	// START Customer/Vendor Account Functions
	/*******************************************************************************************************************/
	// Post the customers/vendors sales/purchases values for the given period
	function Post_account_sales_purchases() {
		global $admin;
		$admin->messageStack->debug("\n  Posting account sales and purchases ...");
		// nothing required to do
		$admin->messageStack->debug(" end Posting account sales and purchases with no action.");
		return true;
	}

	/**
	 * this function will delete the customer/vendor history for this journal
	 * @throws Exception
	 */

	function unPost_account_sales_purchases() {
		global $admin;
		$admin->messageStack->debug("\n  unPosting account sales and purchases ...");
		// nothing required to do
		$admin->messageStack->debug(" end unPosting account sales and purchases with no action.");
	}

	/*******************************************************************************************************************/
	// END Customer/Vendor Account Functions
	/*******************************************************************************************************************/
	// START Inventory Functions
	/*******************************************************************************************************************/
	function Post_inventory() {
		global $admin;
		$admin->messageStack->debug("\n  Posting Inventory ...");
		$admin->messageStack->debug(" end Posting Inventory not requiring any action.");
		return true;
	}

	function unPost_inventory() {
		global $admin;
		$admin->messageStack->debug("\n  unPosting Inventory ...");
		$admin->messageStack->debug(" end unPosting Inventory with no action.");
		return true;
	}


	// *********  inventory support functions  **********

	/**
	 *
	 * Enter description here ...
	 * @param array $item
	 * @param bool $return_cogs should cogs be returned
	 * @throws Exception
	 */
	function calculate_COGS($item, $return_cogs = false) {
		global $admin;
		$admin->messageStack->debug("\n    No COGS to be calculated in Payrol journal");
		return true;
	}

	/**
	 *
	 * @param string $sku
	 * @param number $qty
	 * @param string $serial_num
	 */
	function calculateCost($sku = '', $qty=1, $serial_num='') {
		global $admin;
		$admin->messageStack->debug("\n    nothing to calculate SKU cost for payrol");
		return 0;
	}

	/**
	 *
	 * @param string $sku
	 * @param number $price
	 * @param number $qty
	 */
	function calculate_avg_cost($sku = '', $price = 0, $qty = 1) {
		global $admin;
		$admin->messageStack->debug("\n    nothing to calculate avg SKU cost for payrol");
		return 0;
	}

	/**
	 *
	 * @param string $sku
	 * @param number $qty
	 */
	function fetch_avg_cost($sku = '', $qty=1) {
		global $admin;
		$admin->messageStack->debug("\n    nothing to fetch avg SKU cost for payrol");
		return 0;
	}

	/**
	 * Rolling back cost of goods sold required to unpost an entry involves only re-setting the inventory history.
	 * The cogs records and costing is reversed in the unPost_chart_balances function.
	 */
	function rollback_COGS() {
		global $admin;
		$admin->messageStack->debug("\n    Rolling back COGS ... ");
		// only calculate cogs for certain inventory_types
		$sql = $admin->DataBase->prepare("Select id, qty, inventory_history_id FROM " . TABLE_INVENTORY_COGS_USAGE . " WHERE journal_main_id = " . $this->id);
		$sql->execute();
		if ($sql->rowCount() == 0) {
			$admin->messageStack->debug(" ...Exiting COGS, no work to be done.");
			return true;
		}
		while ($result = $sql->fetch(\PDO::FETCH_LAZY)) {
			$admin->DataBase->exec("UPDATE " . TABLE_INVENTORY_HISTORY . " SET remaining = remaining + {$result['qty']} WHERE id = " . $result['inventory_history_id']);
		}
		$admin->messageStack->debug(" ... Finished rolling back COGS");
		return true;
	}

	function load_so_po_balance($ref_id, $id = '', $post = true) {
		global $admin;
		$admin->messageStack->debug("\n    Starting to load SO/PO balances ...");
		if ($ref_id) throw new \core\classes\userException('Error in classes/journal_08, function load_so_po_balance. Bad journal for this function.');
		$this->so_po_balance_array = array();
		$admin->messageStack->debug(" Finished loading SO/PO balances = " . print_r($item_array, true));
		return array();
	}


	/*******************************************************************************************************************/
	// END Inventory Functions
	/*******************************************************************************************************************/
	// START General Functions
	/*******************************************************************************************************************/

	function check_for_closed_po_so($action = 'Post') {
		global $admin;
		// closed can occur many ways including:
		//   forced closure through so/po form (from so/po journal - adjust qty on so/po)
		//   all quantities are reduced to zero (from so/po journal - should be deleted instead but it's possible)
		//   editing quantities on po/so to match the number received (from po/so journal)
		//   receiving all (or more) po/so items through one or more purchases/sales (from purchase/sales journal)
		$admin->messageStack->debug("\n  Checking for closed entry. action = " . $action);
		return true;
	}

	/**
	 * checks if the invoice nr is valid if allowed it will create a new one when empty
	 * @throws Exception
	 */
	function validate_purchase_invoice_id() {
		global $admin;
		$admin->messageStack->debug("\n  Start validating purchase_invoice_id ... ");
		if ($this->purchase_invoice_id <> '') {	// entered a so/po/invoice value, check for dups
			$sql = "SELECT purchase_invoice_id FROM " . TABLE_JOURNAL_MAIN . " WHERE purchase_invoice_id = '{$this->purchase_invoice_id}' and journal_id = '8'";
			if ($this->id) $sql .= " and id <> " . $this->id;
			$result = $admin->DataBase->query($sql);
			if ($result->rowCount() > 0) throw new \core\classes\userException(sprintf(TEXT_THE_YOU_ENTERED_IS_A_DUPLICATE,_PLEASE_ENTER_A_NEW_UNIQUE_VALUE_ARGS, $journal_types_list[8]['id_field_name']));
			$this->journal_main_array['purchase_invoice_id'] = $this->purchase_invoice_id;
			$admin->messageStack->debug(" specified ID but no dups, returning OK. ");
		} else {	// generate a new order/invoice value
			$this->journal_main_array['purchase_invoice_id'] = '';
			$admin->messageStack->debug(" generated ID, returning ID# " . $this->journal_main_array['purchase_invoice_id']);
		}
		return true;
	}

	function increment_purchase_invoice_id($force = false) {
		global $admin;
		$this->purchase_invoice_id = $this->journal_main_array['purchase_invoice_id'];
		return true;
	}

} // end class journal
?>
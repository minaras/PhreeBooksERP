<?php
namespace inventory\classes\type;
class ma extends \inventory\classes\inventory { //Item Assembly formerly know as 'as' but this resulted in problems with the php function as.
	public $inventory_type			= 'ma';
	public $title 					= TEXT_ITEM_ASSEMBLY;
	public $account_sales_income	= INV_ASSY_DEFAULT_SALES;
	public $account_inventory_wage	= INV_ASSY_DEFAULT_INVENTORY;
	public $account_cost_of_sales	= INV_ASSY_DEFAULT_COS;
	public $cost_method				= INV_ASSY_DEFAULT_COSTING;
	public $bom		 				= array();
	public $allow_edit_bom			= true;
//	public $posible_transactions	= array('sell','purchase');

	function __construct(){
		parent::__construct();
		$this->tab_list['bom'] = array('file'=>'template_tab_bom',	'tag'=>'bom',    'order'=>30, 'text'=>TEXT_BILL_OF_MATERIALS);
	}

	function get_item_by_id($id){
		$this->bom 			= null;
		parent::get_item_by_id($id);
		$this->get_bom_list();
		$this->allow_edit_bom = (($this->last_journal_date == '0000-00-00 00:00:00' || $this->last_journal_date == '') && ($this->quantity_on_hand == 0|| $this->quantity_on_hand == '')) ? true : false;
	}

	function get_item_by_sku($sku){
		$this->bom 			= null;
		parent::get_item_by_sku($sku);
		$this->get_bom_list();
		$this->allow_edit_bom = (($this->last_journal_date == '0000-00-00 00:00:00' || $this->last_journal_date == '') && ($this->quantity_on_hand == 0|| $this->quantity_on_hand == '')) ? true : false;
	}

	function copy($id, $newSku){
		global $admin;
		parent::copy($id, $newSku);
		$result = $admin->DataBase->query("select * from " . TABLE_INVENTORY_ASSY_LIST . " where ref_id = '$id'");
		while(!$result->EOF) {
			$bom_list = array(
			  	'ref_id'      => $this->id,
			  	'sku'         => $result->fields['sku'],
				'description' => $result->fields['description'],
				'qty'         => $result->fields['qty'],
			);
			db_perform(TABLE_INVENTORY_ASSY_LIST, $bom_list, 'insert');
			$result->MoveNext();
		}
		$this->get_bom_list();
		return true;
	}

	function get_bom_list(){
		global $admin;
		$this->assy_cost = 0;
		$result = $admin->DataBase->query("select i.id as inventory_id, l.id, l.sku, l.description, l.qty as qty from " . TABLE_INVENTORY_ASSY_LIST . " l join " . TABLE_INVENTORY . " i on l.sku = i.sku where l.ref_id = " . $this->id . " order by l.id");
		$x =0;
		while (!$result->EOF) {
	  		$this->bom[$x] = $result->fields;
	  		$prices = inv_calculate_sales_price(abs($result->fields['qty']), $result->fields['inventory_id'], 0, 'v');
			$this->bom[$x]['item_cost'] = strval($prices['price']);
			$this->assy_cost += $result->fields['qty'] * strval($prices['price']);
	  		$prices = inv_calculate_sales_price(abs($result->fields['qty']), $result->fields['inventory_id'], 0, 'c');
			$this->bom[$x]['full_price'] = strval($prices['price']);
	  		$x++;
	  		$result->MoveNext();
		}
	}

	function remove(){
		global $admin;
		parent::remove();
		$admin->DataBase->exec("delete from " . TABLE_INVENTORY_ASSY_LIST . " where sku = '" . $this->sku . "'");
	}

	function save(){
		global $admin, $messageStack;
		$bom_list = array();
		for($x=0; $x < count($_POST['assy_sku']); $x++) {
			$bom_list[$x] = array(
			  	'ref_id'      => $this->id,
			  	'sku'         => db_prepare_input($_POST['assy_sku'][$x]),
				'description' => db_prepare_input($_POST['assy_desc'][$x]),
				'qty'         => $admin->currencies->clean_value(db_prepare_input($_POST['assy_qty'][$x])),
			);
		  	$result = $admin->DataBase->query("select id from " . TABLE_INVENTORY . " where sku = '". $_POST['assy_sku'][$x]."'" );
		  	if (($result->rowCount() == 0 || $admin->currencies->clean_value($_POST['assy_qty'][$x]) == 0) && $_POST['assy_sku'][$x] =! '') {
		  		// show error, bad sku, negative quantity. error check sku is valid and qty > 0
				throw new \core\classes\userException(INV_ERROR_BAD_SKU . db_prepare_input($_POST['assy_sku'][$x]));
		  	}else{
		  		$prices = inv_calculate_sales_price(abs($this->bom[$x]['qty']), $result->fields['id'], 0, 'v');
				$bom_list[$x]['item_cost'] = strval($prices['price']);
		  		$prices = inv_calculate_sales_price(abs($this->bom[$x]['qty']), $result->fields['id'], 0, 'c');
				$bom_list[$x]['full_price'] = strval($prices['price']);
		  	}
		}
		$this->bom = $bom_list;
		parent::save();
		$result = $admin->DataBase->query("select last_journal_date, quantity_on_hand  from " . TABLE_INVENTORY . " where id = " . $this->id);
		$this->allow_edit_bom = (($result->fields['last_journal_date'] == '0000-00-00 00:00:00' || $result->fields['last_journal_date'] == '') && ($result->fields['quantity_on_hand'] == 0|| $result->fields['quantity_on_hand'] == '')) ? true : false;
	  	if ($this->allow_edit_bom == true) { // only update if no posting has been performed
	  		$result = $admin->DataBase->exec("delete from " . TABLE_INVENTORY_ASSY_LIST . " where ref_id = " . $this->id);
			while ($list_array = array_shift($bom_list)) {
				unset($list_array['item_cost']);
				unset($list_array['full_price']);
				db_perform(TABLE_INVENTORY_ASSY_LIST, $list_array, 'insert');
			}
	  	}
	  	return true;
	}
}
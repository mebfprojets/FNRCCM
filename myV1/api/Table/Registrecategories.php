<?php

class Table_Registrecategories extends Sirah_Model_Table
{
	protected $_name             = 'rccm_categories';
	
	protected $_primary          = 'catid';
	
	protected $_dependentTables  = array("Table_Registres");
	



}

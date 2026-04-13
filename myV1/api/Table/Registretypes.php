<?php

class Table_Registretypes extends Sirah_Model_Table
{
	protected $_name             = 'rccm_types';
	
	protected $_primary          = 'typeid';
	
	protected $_dependentTables  = array("Table_Registres");
	



}

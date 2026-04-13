<?php

class Table_Modificationtypes extends Sirah_Model_Table
{
	protected $_name             = "rccm_registre_modifications_type";
	
	protected $_primary          = 'type';
	
	protected $_dependentTables  = array("Table_Registremodifications");
	



}

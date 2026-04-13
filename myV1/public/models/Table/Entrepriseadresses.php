<?php

class Table_Entrepriseadresses extends Sirah_Model_Table
{
	protected $_name             = "rccm_registre_address";
	
	protected $_primary          = "addressid";
	
	protected $_dependentTables  = array("Table_Registres");
	



}

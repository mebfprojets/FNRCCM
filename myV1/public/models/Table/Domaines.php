<?php

class Table_Domaines extends Sirah_Model_Table
{
	protected $_name             = "rccm_domaines";
	
	protected $_primary          = 'domaineid';
	
	protected $_dependentTables  = array("Table_Registres");
	



}

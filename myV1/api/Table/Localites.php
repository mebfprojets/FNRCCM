<?php

class Table_Localites extends Sirah_Model_Table
{
	protected $_name             = "rccm_localites";
	
	protected $_primary          = 'localiteid';
	
	protected $_dependentTables  = array("Table_Registres");
	



}

<?php

class Table_Localiteprovinces extends Sirah_Model_Table
{
	protected $_name             = "rccm_localites_provinces";
	
	protected $_primary          = 'provinceid';
	
	protected $_dependentTables  = array("Table_Localitecommunes");
	
	protected $_referenceMap     = array("Region" => array("columns"       => "regionid",
     		                               		           "refTableClass" => "Table_Localiteregions",
     		                               		           "refColumns"    => array("regionid")));
	



}

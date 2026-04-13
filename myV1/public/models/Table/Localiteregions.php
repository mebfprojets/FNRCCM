<?php

class Table_Localiteregions extends Sirah_Model_Table
{
	protected $_name             = "rccm_localites_regions";
	
	protected $_primary          = 'provinceid';
	
	protected $_dependentTables  = array("Table_Localiteprovinces", "Table_Localitecommunes");
 
	



}

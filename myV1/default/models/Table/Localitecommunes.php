<?php

class Table_Localitecommunes extends Sirah_Model_Table
{
	protected $_name             = "rccm_localites_communes";
	
	protected $_primary          = 'communeid';
	
	protected $dependentTables   = array("Table_Registres");
	
	
	protected $_referenceMap     = array("Region" => array("columns"       => "regionid",
     		                               		           "refTableClass" => "Table_Localiteregions",
     		                               		           "refColumns"    => array("regionid")
													),
										"Province" => array("columns"      => "provinceid",
     		                               		           "refTableClass" => "Table_Localiteprovinces",
     		                               		           "refColumns"    => array("provinceid")
													)		
									);
	



}

<?php

class Table_Usageridentitetypes extends Sirah_Model_Table
{

	
	protected $_name           = "reservation_demandeurs_identite_types";

	
	protected $_primary        = "typeid";
	
	
	protected $dependentTables = array("Table_Usageridentites");
 
}


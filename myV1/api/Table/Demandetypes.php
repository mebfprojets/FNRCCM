<?php

class Table_Demandetypes extends Sirah_Model_Table
{
	protected $_name             = "reservation_demandes_types";
	
	protected $_primary          = 'typeid';
	
	protected $_dependentTables  = array("Table_Demandes");
	



}

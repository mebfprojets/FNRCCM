<?php

class Table_Suretetypes extends Sirah_Model_Table
{
	protected $_name             = "rccm_registre_suretes_type";
	
	protected $_primary          = 'type';
	
	protected $_dependentTables  = array("Table_Registresuretes");
	



}

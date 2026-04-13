<?php

class Table_Entrepriseformes extends Sirah_Model_Table
{
	protected $_name             = "rccm_registre_entreprises_forme_juridique";
	
	protected $_primary          = 'formid';
	
	protected $_dependentTables  = array("Table_Entreprises");
	



}

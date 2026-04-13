<?php

class Table_Documentcategories extends Sirah_Model_Table
{

	
	protected $_name             = "system_users_documents_categories";

	
	protected $_primary          = "id";
	
	
	protected $_dependentTables  = array("Table_Documents","Table_Productcategories");




}


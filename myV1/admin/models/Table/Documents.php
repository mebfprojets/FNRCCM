<?php

class Table_Documents extends Sirah_Model_Table
{
	
	protected $_name            = "system_users_documents";
	
	protected $_primary         = "documentid";
		
	protected $_dependentTables = array("Table_Profiledocuments" , "Table_Profilecvs" , "Table_Profileletters");
		
	protected $_referenceMap    = array(
			                            "Category"  => array(
			                            		              "columns"       => array("category"),
			                            		              "refTableClass" => "Table_Documentcategories",
			                            		              "refColumns"    => array("id")
			                            		               ),
			                            "User"      => array(
			                            		              "columns"       => array("userid"),
			                            		              "refTableClass" => "Sirah_User_Table",
			                            		              "refColumns"    => array("userid")
			                            		               )
			                            );




}


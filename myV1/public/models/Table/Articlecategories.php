<?php

class Table_Articlecategories extends Sirah_Model_Table
{

    protected $_name            = "erccm_crm_content_categories";

    protected $_primary         = array("catid");

    protected $_dependentTables = array("Table_Articles");
	 
	protected $_referenceMap    = array();
     
     
         
}


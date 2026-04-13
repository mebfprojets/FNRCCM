<?php

class Table_Articles extends Sirah_Model_Table
{
    protected $_name            = "erccm_crm_content_articles";

    protected $_primary         = array("articleid");
     
    protected $_dependentTables = array("Table_Documents","Table_Decaissements");
     
    protected $_referenceMap    = array("Categorie"=> array(
     		                                                "columns"       => array("catid") ,
     		                                                "refTableClass" => "Table_Articlecategories" ,
     		                                                "refColumns"    => array("catid")), 
     		                            "Gallery"  => array(
     				                                        "columns"       => array("galleryid"),
     				                                        "refTableClass" => "Table_Galleries",
     				                                        "refColumns"    => array("galleryid"))		 
								  );
         
  }


<?php

class Table_Productcategories extends Sirah_Model_Table
{
	protected $_name             = 'erccm_vente_products_categories';
	
	protected $_primary          = 'catid';
	
	protected $_dependentTables  = array("Table_Products","Table_Commandelignes");
	
	protected $_referenceMap     = array( 
			                             "DocumentCategorie"=>  array(
					                                          'columns'       => array("documentcatid"),
					                                          'refTableClass' => "Table_Documentcategories",
					                                          'refColumns'    => array("id")
			                                 )
                                  );
	
}

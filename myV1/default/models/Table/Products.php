<?php

class Table_Products extends Sirah_Model_Table
{
	protected $_name             = "erccm_vente_products";
	
	protected $_primary          = "productid";
	
	protected $_dependentTables  = array("Table_Commandelignes");
	
	protected $_referenceMap     = array(
                                         'Categorie' => array(
                                                              'columns'       => array("catid"),
                                                              'refTableClass' => "Table_Productcategories",
                                                              'refColumns'    => array("catid")
                                           ),
			                             "DocumentCategorie"=>  array(
					                                          'columns'       => array("documentcatid"),
					                                          'refTableClass' => "Table_Documentcategories",
					                                          'refColumns'    => array("id")
			                                 ),
									     "Document"=>  array(
					                                          'columns'       => array("documentid"),
					                                          'refTableClass' => "Table_Documents",
					                                          'refColumns'    => array("documentid")
			                                 ),
										 "Registre"=>  array(
					                                          'columns'       => array("registreid"),
					                                          'refTableClass' => "Table_Registres",
					                                          'refColumns'    => array("registreid")
			                                 ),
                                  );

}

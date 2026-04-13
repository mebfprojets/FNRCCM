<?php

class Table_Commandelignes extends Sirah_Model_Table
{
	
	protected $_name             = 'erccm_vente_commandes_ligne';
	
	protected $_primary          = array("commandeid" , "productid");
	
	protected $_referenceMap     = array(
	                                     'ProductCategorie' => array(
                                                              'columns'       => array("catid"),
                                                              'refTableClass' => "Table_Productcategories",
                                                              'refColumns'    => array("catid")
                                           ),
                                         'Produit' => array(
                                                            'columns'       =>  array("productid"),
                                         		            'refTableClass' => "Table_Commandeproduits",
                                         		            'refColumns'    =>  array("productid") ) ,
                                        'Commande' => array(
                                                            'columns'       => array("commandeid"),
                                                            'refTableClass' => "Table_Commandes",
                                                            'refColumns'    => array("commandeid"))
								 );



}

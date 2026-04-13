<?php

class Table_Commandelivraisons extends Sirah_Model_Table
{

    protected $_name            = "erccm_vente_commandes_livraisons";

    protected $_primary         = array("livraisonid");
	
	protected $_dependentTables = array("Table_Commandelivraisonlignes");
     
     
    protected $_referenceMap    = array("Commande"=> array(
     		                                                "columns"       => array("commandeid"),
     		                                                "refTableClass" => "Table_Commandes" ,
     		                                                "refColumns"    => array("commandeid")),
										"Facture" => array(
     		                                                "columns"       => array("invoiceid"),
     		                                                "refTableClass" => "Table_Commandefactures" ,
     		                                                "refColumns"    => array("invoiceid")),					
  									    "Member"  => array(
                                                            'columns'       => array("memberid"),
                                                            'refTableClass' => "Table_Members",
                                                            'refColumns'    => array("memberid"))   
                                  );
         
}


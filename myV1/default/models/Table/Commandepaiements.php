<?php

class Table_Commandepaiements extends Sirah_Model_Table
{

    protected $_name         = "erccm_vente_commandes_paiements";

    protected $_primary      = array( "paiementid");
     
     
    protected $_referenceMap = array("Commande"=>array(
     		                                            "columns"       => array("commandeid") ,
     		                                            "refTableClass" => "Table_Commandes" ,
     		                                            "refColumns"    => array("commandeid")
                                      ),
  									  "Member"=> array(
                                                            'columns'       => array("memberid"),
                                                            'refTableClass' => "Table_Members",
                                                            'refColumns'    => array("memberid")
                                                    ),
								     "Facture"	=>array(
     		                                            "columns"       => array("invoiceid") ,
     		                                            "refTableClass" => "Table_Commandefactures" ,
     		                                            "refColumns"    => array("invoiceid")
                                      )	,
                                     "Mode"	=>array(
     		                                            "columns"       => array("modepaiementid") ,
     		                                            "refTableClass" => "Table_Modepaiements" ,
     		                                            "refColumns"    => array("modepaiementid")
                                      )								  
                               );
         
  }


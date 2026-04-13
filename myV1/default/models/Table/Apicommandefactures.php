<?php

class Table_Apicommandefactures extends Sirah_Model_Table
{

    protected $_name         = "erccm_vente_commandes_invoices";

    protected $_primary      = array( "invoiceid");
	
	protected $_dependentTables  = array("Table_Commandepaiements","Table_Commandelivraisons");
     
     
    protected $_referenceMap = array("Commande"=>array(
     		                                            "columns"       => array("commandeid") ,
     		                                            "refTableClass" => "Table_Commandes" ,
     		                                            "refColumns"    => array("commandeid")
                                      ),
  									  "Member"=> array(
                                                            'columns'       => array("memberid"),
                                                            'refTableClass' => "Table_Members",
                                                            'refColumns'    => array("memberid")
                                                    )   
                               );
         
  }


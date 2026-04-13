<?php

class Table_Demanderetries extends Sirah_Model_Table
{

    protected $_name        = "reservation_demandes_retries";

    protected $_primary     = array( "demandeid");
    
    protected $_rowClass    = "Model_Demanderetry";
    
    protected $_referenceMap= array( "Demandeur"=> array(
     		                                              "columns"      => array("demandeurid"),
     		                                              "refTableClass"=> "Table_Demandeurs"  ,
     		                                              "refColumns"   => array("demandeurid")
                                                           ) ,
     		                         "Demande"  => array(
     				                                        "columns"      => array("demandeid"),
     				                                        "refTableClass"=> "Table_Demandes"  ,
     				                                        "refColumns"   => array("demandeid")
     		                                                )
							  );

 
     
     
}


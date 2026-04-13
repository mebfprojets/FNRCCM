<?php

class Table_Apidemandes extends Sirah_Model_Table
{

    protected $_name           = "api_reservation_demandes";

    protected $_primary        = array("demandeid","numero");
     
    protected $dependentTables = array();
     
    protected $_referenceMap   = array("Demandeur"=> array(
     		                                                "columns"      => array("demandeurid"),
     		                                                "refTableClass"=> "Table_Apidemandeurs"  ,
     		                                                "refColumns"   => array("demandeurid")
                                                           ) ,
     		                           "Localite" => array(
     				                                        "columns"      => array("localiteid"),
     				                                        "refTableClass"=> "Table_Localites"  ,
     				                                        "refColumns"   => array("localiteid")
     		                                                ),
									  "Statut"    => array(
     				                                        "columns"      => array("statutid"),
     				                                        "refTableClass"=> "Table_Demandestatuts"  ,
     				                                        "refColumns"   => array("statutid")
     		                                                ),
									  "Type"      => array(
     				                                        "columns"      => array("typeid"),
     				                                        "refTableClass"=> "Table_Demandetypes"  ,
     				                                        "refColumns"   => array("typeid")
     		                                                ),					
                                       "Entreprise"=> array(
     		                                                "columns"      => array("entrepriseid"),
     		                                                "refTableClass"=> "Table_Demandentreprises"  ,
     		                                                "refColumns"   => array("entrepriseid")
                                                           ),
									   "Localite" => array(
     		                                               "columns"       => array("localiteid"),
     		                                               "refTableClass" => "Table_Localites"  ,
     		                                               "refColumns"    => array("localiteid")
                                                           )														   
     		                             );     
}


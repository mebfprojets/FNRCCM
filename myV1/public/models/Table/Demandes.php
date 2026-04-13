<?php

class Table_Demandes extends Sirah_Model_Table
{

    protected $_name           = "reservation_demandes";

    protected $_primary        = array("demandeid","numero");
     
    protected $dependentTables = array("Table_Demanderetries","Table_Demandereservations","Table_Demandeverifications","Table_Demandeverifications","Table_Demandedocuments","Table_Demanderequests");
     
    protected $_referenceMap   = array("Demandeur"=> array(
     		                                                "columns"      => array("demandeurid"),
     		                                                "refTableClass"=> "Table_Demandeurs"  ,
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


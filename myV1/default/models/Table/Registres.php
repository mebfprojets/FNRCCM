<?php

class Table_Registres extends Sirah_Model_Table
{

    protected $_name           = 'rccm_registre';

    protected $_primary        = array("registreid", "numero");
     
    protected $dependentTables = array("Table_Registrephysiques","Table_Registremorales","Table_Products","Table_Registredocuments","Table_Registresuretes","Table_Registremodifications","Table_Products");
     
    protected $_referenceMap   = array("Domaine" => array(
     		                                                    "columns"      => array("domaineid"),
     		                                                    "refTableClass"=> "Table_Domaines"  ,
     		                                                    "refColumns"   => array("domaineid")
                                                           ) ,
     		                              "Localite" => array(
     				                                            "columns"      => array("localiteid"),
     				                                            "refTableClass"=> "Table_Localites"  ,
     				                                            "refColumns"   => array("localiteid")
     		                                                ),
										  "Statut" => array(
     				                                            "columns"      => array("statusid"),
     				                                            "refTableClass"=> "Table_Registrestatuts"  ,
     				                                            "refColumns"   => array("statusid")
     		                                                ),
                                          "Adresse" => array(
     		                                                    "columns"      => array("addressid"),
     		                                                    "refTableClass"=> "Table_Registreadresses"  ,
     		                                                    "refColumns"   => array("addressid")
                                                           ),
										  "Commune" => array(
     		                                                    "columns"      => array("communeid"),
     		                                                    "refTableClass"=> "Table_Localitecommunes"  ,
     		                                                    "refColumns"   => array("communeid")
                                                           ),
                                          "Caisse" => array(
     		                                                    "columns"      => array("caisseid"),
     		                                                    "refTableClass"=> "Table_Registrecaisses"  ,
     		                                                    "refColumns"   => array("caisseid")
                                                           ),
                                          "IFU" => array(
     		                                                    "columns"      => array("ifuid"),
     		                                                    "refTableClass"=> "Table_Registrefinances"  ,
     		                                                    "refColumns"   => array("ifuid")
                                                           )														   
     		                             );         
}


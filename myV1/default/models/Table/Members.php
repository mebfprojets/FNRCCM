<?php

class Table_Members extends Sirah_Model_Table
{

    protected $_name           = "rccm_members";

    protected $_primary        = array("memberid");
     
    protected $dependentTables = array("Table_Commandes","Table_Commandelivraisons");
     
    protected $_referenceMap   = array("Entreprise"=> array(
     		                                                "columns"      => array("entrepriseid"),
     		                                                "refTableClass"=> "Table_Entreprises"  ,
     		                                                "refColumns"   => array("entrepriseid")) ,
     		                           "Ville"     => array(
     				                                        "columns"      => array("city"),
     				                                        "refTableClass"=> "Table_Countrycities",
     				                                        "refColumns"   => array("id")),
     		                           "Pays"      => array(
     				                                        "columns"      => array("country"),
     				                                        "refTableClass"=> "Table_Countries",
     				                                        "refColumns"   => array("code")),
     		                           "Groupe"    => array(
     				                                        "columns"      => array("groupid"),
     				                                        "refTableClass"=> "Table_Membergroups",
     				                                        "refColumns"   => array("groupid"))
                                  );
         
}


<?php

class Table_Apimembers extends Sirah_Model_Table
{

    protected $_name         =  "api_rccm_members";

    protected $_primary      =  array("memberid");
        
    protected $_referenceMap =  array(
     		                           "Ville"    => array(
     				                                        "columns"      => array("city"),
     				                                        "refTableClass"=> "Table_Countrycities",
     				                                        "refColumns"   => array("localiteid")
     		                                               ),
     		                           "Pays"     => array(
     				                                        "columns"      => array("country"),
     				                                        "refTableClass"=> "Table_Countries",
     				                                       "refColumns"   => array("code")
     		                                               ),
     		                           "Groupe"   => array(
     				                                       "columns"      => array("groupid"),
     				                                       "refTableClass"=> "Table_Membergroups",
     				                                       "refColumns"   => array("groupid")
     		                                               )
                                              );
}


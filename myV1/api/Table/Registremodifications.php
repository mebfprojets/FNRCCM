<?php

class Table_Registremodifications extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_modifications';

     protected $_primary          = array("registreid");
     
     protected $dependentTables   = array();
     
     protected $_referenceMap     = array("Registre" => array(
     		                                                     "columns"      => array("registreid"),
     		                                                     "refTableClass"=> "Table_Registres"  ,
     		                                                     "refColumns"   => array("registreid")
                                                           ) ,
     		                              "Type"     => array(
     				                                             "columns"      => array("type"),
     				                                             "refTableClass"=> "Table_Modificationtypes"  ,
     				                                             "refColumns"   => array("type")
     		                                                )  
     		                             ); 
         
  }


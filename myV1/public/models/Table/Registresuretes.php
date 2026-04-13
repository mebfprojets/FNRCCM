<?php

class Table_Registresuretes extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_suretes';

     protected $_primary          = array("registreid");
     
     protected $dependentTables   = array();
     
     protected $_referenceMap     = array("Registre" => array(
     		                                                     "columns"      => array("registreid"),
     		                                                     "refTableClass"=> "Table_Registres"  ,
     		                                                     "refColumns"   => array("registreid")
                                                           ) ,
     		                              "Type"     => array(
     				                                             "columns"      => array("type"),
     				                                             "refTableClass"=> "Table_Suretetypes"  ,
     				                                             "refColumns"   => array("type")
     		                                                )  
     		                             ); 
         
  }


<?php

class Table_Registremorales extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre';

     protected $_primary          = array("registreid", "numero");
     
     protected $dependentTables   = array("Table_Registredocuments");
     
     protected $_referenceMap     = array("Domaine" => array(
     		                                                     "columns"      => array("domaineid"),
     		                                                     "refTableClass"=> "Table_Domaines"  ,
     		                                                     "refColumns"   => array("domaineid")
                                                           ) ,
     		                              "Localite" => array(
     				                                             "columns"      => array("localiteid"),
     				                                             "refTableClass"=> "Table_Localites"  ,
     				                                             "refColumns"   => array("localiteid")
     		                                                )  
     		                             ); 
         
  }


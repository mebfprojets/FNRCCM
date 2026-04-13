<?php

class Table_Registres extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre';

     protected $_primary          = array("registreid", "numero");
     
     protected $dependentTables   = array("Table_Registrephysiques", "Table_Registremorales", "Table_Registredocuments");
     
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


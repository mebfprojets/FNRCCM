<?php

class Table_Registrephysiques extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_physique';

     protected $_primary          = array("registreid");
     
     protected $_referenceMap     = array("Registre"  => array(
     		                                                   "columns"       => array("registreid"),
     		                                                   "refTableClass" => "Table_Registres"  ,
     		                                                   "refColumns"    => array("registreid")
                                                       ) ,
     		                              "Exploitant"=> array(
     		                                                   "columns"       => array("exploitantid"),
     		                                                   "refTableClass" => "Table_Exploitants"  ,
     		                                                   "refColumns"    => array("exploitantid")
                                                       )
                                              );
         
  }


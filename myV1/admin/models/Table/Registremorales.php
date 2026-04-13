<?php

class Table_Registremorales extends Sirah_Model_Table
{

     protected $_name          = 'rccm_registre_moral';

     protected $_primary       = array("registreid");
     
     protected $_referenceMap  = array("Registre"  => array(
     		                                                   "columns"       => array("registreid"),
     		                                                   "refTableClass" => "Table_Registres"  ,
     		                                                   "refColumns"    => array("registreid")
                                                       ) ,
     		                           "Entreprise"=> array(
     		                                                   "columns"       => array("entrepriseid"),
     		                                                   "refTableClass" => "Table_Entreprises"  ,
     		                                                   "refColumns"    => array("entrepriseid")
                                                       )
                                              );
         
  }


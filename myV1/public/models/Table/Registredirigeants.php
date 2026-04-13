<?php

class Table_Registredirigeants extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_dirigeants';

     protected $_primary          = array("registreid");
     
     protected $_referenceMap     = array("Registre"  => array(
     		                                                   "columns"       => array("registreid"),
     		                                                   "refTableClass" => "Table_Registres"  ,
     		                                                   "refColumns"    => array("registreid")
                                                       ) ,
     		                              "Dirigeant"=> array(
     		                                                   "columns"       => array("representantid"),
     		                                                   "refTableClass" => "Table_Representants"  ,
     		                                                   "refColumns"    => array("representantid")
                                                       )
                                              );
         
  }


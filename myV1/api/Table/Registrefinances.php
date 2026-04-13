<?php

class Table_Registrefinances extends Sirah_Model_Table
{

     protected $_name             = "rccm_registre_ifu";

     protected $_primary          = array("ifuid");
     
     protected $dependentTables   = array("Table_Registres");
 
         
}


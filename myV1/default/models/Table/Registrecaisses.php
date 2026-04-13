<?php

class Table_Registrecaisses extends Sirah_Model_Table
{

     protected $_name             = "rccm_registre_cnss";

     protected $_primary          = array("cnssid");
     
     protected $dependentTables   = array("Table_Registres");
 
         
  }


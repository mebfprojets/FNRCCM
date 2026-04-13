<?php

class Table_Registreadresses extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_address';

     protected $_primary          = array("addressid");
     
     protected $dependentTables   = array("Table_Registres");
 
         
  }


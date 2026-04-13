<?php

class Table_Registrestatuts extends Sirah_Model_Table
{

     protected $_name             = "rccm_registre_status";

     protected $_primary          = array("statusid");
     
     protected $dependentTables   = array("Table_Registres");
 
         
  }


<?php

class Table_Demandestatuts extends Sirah_Model_Table
{

     protected $_name             = "reservation_demandes_statuts";

     protected $_primary          = array("statutid");
     
     protected $dependentTables   = array("Table_Demandes");
 
         
}


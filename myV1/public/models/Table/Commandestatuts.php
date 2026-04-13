<?php

class Table_Commandestatuts extends Sirah_Model_Table
{

    protected $_name           = "erccm_vente_commandes_statuts";

    protected $_primary        = array("statutid");
     
    protected $dependentTables = array("Table_Commandes");
 
         
}


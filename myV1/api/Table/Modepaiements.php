<?php

class Table_Modepaiements extends Sirah_Model_Table
{
    protected $_name             ="erccm_vente_modepaiements";

    protected $_primary          = array("modepaiementid");

    protected $_dependentTables  = array("Table_Commandepaiements");
 
  }


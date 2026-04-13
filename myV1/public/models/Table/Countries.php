<?php

class Table_Countries extends Sirah_Model_Table
{

     protected $_name             = 'rccm_localites_countries';

     protected $_primary          = array("code");

     protected $_dependentTables  = array("Table_Countryregions", "Table_Countrycities", "Table_Profilecoordonnees", "Table_Entreprises","Table_Demandeurs");
     
     protected $_rowClass         = "Model_Country";
         
  }


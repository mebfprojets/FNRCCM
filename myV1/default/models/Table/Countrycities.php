<?php

class Table_Countrycities extends Sirah_Model_Table
{

     protected $_name             = 'system_countries_cities';

     protected $_primary          = array("id");
     
     protected $_rowClass         = "Model_Countrycity";
     
     protected $_dependentTables  = array("Table_Profilecoordonnees", "Table_Entreprises");
     
     protected $_referenceMap     = array("Country" => array("columns"       => "country",
     		                               		             "refTableClass" => "Table_Countries",
     		                               		             "refColumns"    => array("code")));
     
         
  }


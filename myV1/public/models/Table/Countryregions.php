<?php

class Table_Countryregions extends Sirah_Model_Table
{

     protected $_name          = 'system_countries_regions';

     protected $_primary       = array("id");
     
     protected $_referenceMap  = array("Country" => array("columns"       => "country",
     		                               		          "refTableClass" => "Model_Countries",
     		                               		          "refColumns"    => array("code")));
     
         
  }


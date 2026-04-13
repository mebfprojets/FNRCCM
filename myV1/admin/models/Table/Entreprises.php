<?php

class Table_Entreprises extends Sirah_Model_Table
{

     protected $_name             = "rccm_registre_entreprises";

     protected $_primary          = array("entrepriseid");

     protected $_dependentTables  = array("Table_Registremorales");
     
     protected $_referenceMap     = array(   		                               
     		                               "City"        =>  array(
     				                                               "columns"       => "city",
     				                                               "refTableClass" => "Table_Countrycities",
     				                                               "refColumns"    => array("id")
     		                                                  ),
     		                                "Country"    => array(
     				                                              "columns"        => "country",
     				                                              "refTableClass"  => "Table_Countries",
     				                                              "refColumns"     => array("code")
     		                                          )
     		                                );
         


  }


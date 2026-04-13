<?php

class Table_Projects extends Sirah_Model_Table
{

     protected $_name             = "rccm_projet_application";

     protected $_primary          = array("projectid");

     
     protected $_referenceMap     = array(
     		                               "Entreprise"  => array(
     				                                              "columns"       => "entrepriseid",
     				                                              "refTableClass" => "Table_Entreprises",
     				                                              "refColumns"    => array("entrepriseid")
     		                                                     ),
     		                               "City"        =>  array(
     				                                               "columns"      => "city",
     				                                               "refTableClass"=> "Table_Countrycities",
     				                                               "refColumns"   => array("id")
     		                                                  ),
     		                                "Country"    => array(
     				                                              "columns"       => "country",
     				                                              "refTableClass" => "Table_Countries",
     				                                              "refColumns"    => array("code")
     		                                          )
     		                                );
         


  }


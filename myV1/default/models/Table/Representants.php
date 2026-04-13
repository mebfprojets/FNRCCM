<?php

class Table_Representants extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_representants';

     protected $_primary          = array("representantid");

     protected $_dependentTables  = array("Table_Registredirigeants");
     
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


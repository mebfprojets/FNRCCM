<?php

class Table_Demandentreprises extends Sirah_Model_Table
{

    protected $_name            = "reservation_demandes_entreprises";

    protected $_primary         = array("entrepriseid");

    protected $_dependentTables = array("Table_Demandes");
     
    protected $_referenceMap    = array( 
                                            "Groupe"  => array(
     				                                              "columns"        => "groupid",
     				                                              "refTableClass"  => "Table_Entreprisegroups",
     				                                              "refColumns"     => array("groupid")
     		                                                     ),
                                            "Domaine"  => array(
     				                                              "columns"        => "domaineid",
     				                                              "refTableClass"  => "Table_Domaines",
     				                                              "refColumns"     => array("domaineid")
     		                                                     ),
                                           "Forme"  => array(
     				                                              "columns"        => "formid",
     				                                              "refTableClass"  => "Table_Entrepriseformes",
     				                                              "refColumns"     => array("formid")
     		                                                     ),																 
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


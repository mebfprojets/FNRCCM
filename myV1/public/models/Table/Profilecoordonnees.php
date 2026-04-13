<?php

class Table_Profilecoordonnees extends Sirah_Model_Table
{

     protected $_name            = 'system_users_profile_coordonnees';

     protected $_primary         = array("profileid");

     protected $_dependentTables = array();
          
     protected $_referenceMap    = array(
     		                              "Profile" => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ),
     		                              "City"    =>  array(
     		                    		                      "columns"        => "city",
     		                                                  "refTableClass"  => "Table_Countrycities",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
     		                              "Country" => array(
     				                                          "columns"        => "country",
     				                                          "refTableClass"  => "Table_Countries",
     				                                          "refColumns"     => array("code")
     		                                            )
     		                                     );


  }


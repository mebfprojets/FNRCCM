<?php

class Table_Profileprojects extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_projects';

     protected $_primary          = array("projectid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ) ,
     		                             "Beneficiaire" => array(
     		                    		                      "columns"        => "beneficiaireid",
     		                                                  "refTableClass"  => "Table_Entreprises",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
     		                             "Domaine"   => array(
     				                                          "columns"       => "domaineid",
     				                                          "refTableClass" => "Table_Domaines",
     				                                          "refColumns"    => array("id")
     		                                                ),
     		                              "Type"    => array(
     				                                          "columns"       => "type",
     				                                          "refTableClass" => "Table_Projectypes",
     				                                          "refColumns"    => array("id")
     		)
     		                                     );


  }


<?php

class Table_Profilecarreers extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_carreers';

     protected $_primary          = array("carreerid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ) ,
     		                             "Entreprise" => array(
     		                    		                      "columns"        => "entrepriseid",
     		                                                  "refTableClass"  => "Table_Entreprises",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
     		                             "Profession" => array(
     				                                           "columns"       => "professionid",
     				                                           "refTableClass" => "Table_Professions",
     				                                           "refColumns"    => array("id")
     		                                                  ),
     		                             "Domaine"   => array(
     				                                          "columns"       => "domaineid",
     				                                          "refTableClass" => "Table_Domaines",
     				                                          "refColumns"    => array("id")
     		                                                )
     		                                     );


  }


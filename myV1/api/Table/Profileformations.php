<?php

class Table_Profileformations extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_formations';

     protected $_primary          = array("formationid");

     protected $_dependentTables  = array();
          
      protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ) ,
     		                             "Etablissement" => array(
     		                    		                      "columns"        => "entrepriseid",
     		                                                  "refTableClass"  => "Table_Entreprises",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
      		                             "Beneficiaire"=> array(
     		                    		                      "columns"        => "beneficiaireid",
     		                                                  "refTableClass"  => "Table_Entreprises",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
      		                             "Education"   => array(
      		                              		              "columns"        => "educationid",
      		                              		              "refTableClass"  => "Table_Educations",
      		                              		              "refColumns"     => array("id")
      		                              		           ) 		                            
     		                                     );

  }


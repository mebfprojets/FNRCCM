<?php

class Table_Profilecvs extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_cversions';

     protected $_primary          = array("versionid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ) ,
     		                             "Domaine" => array(
     		                    		                      "columns"        => "domaineid",
     		                                                  "refTableClass"  => "Table_Domaines",
     		                                                  "refColumns"     => array("id")
     		                              		          ),
      		                             "Langue"  => array(
     		                    		                      "columns"        => "language",
     		                                                  "refTableClass"  => "Table_Languages",
     		                                                  "refColumns"     => array("code")
     		                              		          ),
      		                             "Document" => array(
      		                              		              "columns"        => "documentid",
      		                              		              "refTableClass"  => "Table_Documents",
      		                              		              "refColumns"     => array("documentid")
      		                              		           ) 		                            
     		                                     );

  }


<?php

class Table_Profileletters extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_letterversions';

     protected $_primary          = array("versionid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ) ,
      		                             "Langue"  => array(
     		                    		                      "columns"        => "language",
     		                                                  "refTableClass"  => "Table_Languages",
     		                                                  "refColumns"     => array("code")
     		                              		          ),
      		                             "Document" => array(
      		                              		              "columns"        => "documentid",
      		                              		              "refTableClass"  => "Table_Documents",
      		                              		              "refColumns"     => array("documentid")
      		                              		           ) );

  }


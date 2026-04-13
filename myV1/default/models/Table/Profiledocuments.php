<?php

class Table_Profiledocuments extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_documents';

     protected $_primary          = array("documentid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          ),
     		                               "Document"     => array( 
     		                               		                "columns"       => array("documentid") ,
     		                                                    "refTableClass" => "Table_Documents" ,
     		                                                    "refColumns"    => array("documentid")
     		                               		                        )  	 
     		                                     );


  }


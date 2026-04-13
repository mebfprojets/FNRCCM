<?php

class Table_Profileavatars extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_avatar';

     protected $_primary          = array("avatarid" , "profileid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          )
     		                                     );


  }


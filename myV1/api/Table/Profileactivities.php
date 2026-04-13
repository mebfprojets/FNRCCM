<?php

class Table_Profileactivities extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_activities';

     protected $_primary          = array("activityid");

     protected $_dependentTables  = array();
          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"        => "profileid",
     		                                                  "refTableClass"  => "Table_Profiles",
     		                                                  "refColumns"     => array("profileid")
     		                              		          )
     		                                     );


  }


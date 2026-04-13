<?php

class Table_Profiles extends Sirah_Model_Table
{
     protected $_name             = 'system_users_profile';

     protected $_primary          = array("profileid" , "userid");

     protected $_dependentTables  = array("Table_Profilecoordonnees", "Table_Profilecarreers", "Table_Profileformations", "Table_Profiledocuments","Table_Profileprojects");
          
     protected $_referenceMap     = array(
     		                               "User"  => array(
     		                    		                   "columns"        => "userid",
     		                                               "refTableClass"  => "Sirah_User_Table",
     		                                               "refColumns"     => array("userid")
     		                              		          )
     		                                     );
  }


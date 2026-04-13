<?php

class Table_Profiletags extends Sirah_Model_Table
{

     protected $_name             = 'system_users_profile_tags';

     protected $_primary          = array("tagid");

          
     protected $_referenceMap     = array(
     		                              "Profile"  => array(
     		                    		                      "columns"       => "profileid",
     		                                                  "refTableClass" => "Table_Profiles",
     		                                                  "refColumns"    => array("profileid")
     		                              		          ) ,
     		                             "Keyword"   => array(
     				                                          "columns"       => "keywordid",
     				                                          "refTableClass" => "Table_Keywords",
     				                                          "refColumns"    => array("id")
     		                                                )
     		                              );


  }


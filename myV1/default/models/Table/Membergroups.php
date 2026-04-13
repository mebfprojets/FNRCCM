<?php

class Table_Membergroups extends Sirah_Model_Table
{

     protected $_name             = "rccm_members_groups";

     protected $_primary          = array("groupid");
     
     protected $dependentTables   = array("Table_Members");
         
  }


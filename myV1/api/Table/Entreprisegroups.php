<?php

class Table_Entreprisegroups extends Sirah_Model_Table
{

     protected $_name             = 'rccm_registre_entreprises_groups';

     protected $_primary          = array("groupid");

     protected $_dependentTables  = array("Table_Entreprises");
         
  }


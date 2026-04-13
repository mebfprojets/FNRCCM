<?php

class Table_Registredocuments extends Sirah_Model_Table
{

     protected $_name          = "rccm_registre_documents";

     protected $_primary       = array("documentid", "registreid");
    
     
     protected $_referenceMap  = array("Registre"    => array(
     		                                                    "columns"       => array("registreid"),
     		                                                    "refTableClass" => "Table_Registres"  ,
     		                                                    "refColumns"    => array("registreid")
                                                           ) ,
     		                               "Document" => array(
     				                                             "columns"      => array("documentid"),
     				                                             "refTableClass"=> "Table_Documents",
     				                                             "refColumns"   => array("documentid")
     		                                               )
                                              );
         
  }


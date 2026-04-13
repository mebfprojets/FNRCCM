<?php

class Table_Demanderequests extends Sirah_Model_Table
{

    protected $_name           = "reservation_demandes_requests";

    protected $_primary        = array("demandeid","numero");
     
    protected $dependentTables = array("Table_Demanderequestoperators");
     
    protected $_referenceMap   = array("Demande"=> array(
     		                                            "columns"      => array("demandeid"),
     		                                            "refTableClass"=> "Table_Demandes"  ,
     		                                            "refColumns"   => array("demandeid")
                                                  ) 
								 );     
}


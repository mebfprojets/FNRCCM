<?php

class Table_Demanderequestoperators extends Sirah_Model_Table
{

    protected $_name         = "reservation_demandes_requests_operators";

    protected $_primary      = array("requestid","operatorid");
 
     
    protected $_referenceMap = array("Request"=> array(
     		                                            "columns"      => array("requestid"),
     		                                            "refTableClass"=> "Table_Demanderequests"  ,
     		                                            "refColumns"   => array("requestid")
                                                  )
												  
								);     
}


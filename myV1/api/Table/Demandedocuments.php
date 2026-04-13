<?php

class Table_Demandedocuments extends Sirah_Model_Table
{

    protected $_name         = 'reservation_demandes_documents';

    protected $_primary      = array("documentid");
	 
	protected $_referenceMap = array("Document"=> array(
     		                                              "columns"      => array("documentid"),
     		                                              "refTableClass"=> "Table_Documents"  ,
     		                                              "refColumns"   => array("documentid")
                                                           ) ,
     		                          "Demande"  => array(
     				                                        "columns"      => array("demandeid"),
     				                                        "refTableClass"=> "Table_Demandes"  ,
     				                                        "refColumns"   => array("demandeid")
     		                                                )
								);
     
     
}


<?php

class Table_Demandeurs extends Sirah_Model_Table
{

    protected $_name         = "reservation_demandeurs";

    protected $_primary      = array("demandeurid");
	
	protected $_referenceMap = array("Demandeur"=> array(
     		                                                "columns"      => array("identityid"),
     		                                                "refTableClass"=> "Table_Usageridentites"  ,
     		                                                "refColumns"   => array("identityid")
                                                           ),
									 "Nationalite" => array(
     		                                                "columns"      => array("nationalite"),
     		                                                "refTableClass"=> "Table_Countries"  ,
     		                                                "refColumns"   => array("code")
                                                           ) 					   
								   );

}


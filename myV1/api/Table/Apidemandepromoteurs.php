<?php

class Table_Apidemandepromoteurs extends Sirah_Model_Table
{

    protected $_name         = "api_reservation_promoteurs";

    protected $_primary      = array("promoteurid");
	
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


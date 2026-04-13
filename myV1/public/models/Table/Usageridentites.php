<?php

class Table_Usageridentites extends Sirah_Model_Table
{

    protected $_name           = "reservation_demandeurs_identite";

    protected $_primary        = array("identityid");
	
	protected $dependentTables = array("Table_Demandeurs","Table_Demandepromoteurs", "Table_Promoteurs");
	
	
	protected $_referenceMap   = array("Type" => array(
     		                                            "columns"      => array("typeyid"),
     		                                            "refTableClass"=> "Table_Usageridentitetypes"  ,
     		                                            "refColumns"   => array("typeid")
                                                 )
								   );
}


<?php

class Table_Promoteurs extends Sirah_Model_Table
{

    protected $_name         = "reservation_promoteurs";

    protected $_primary      = array("promoteurid");
	
	protected $_referenceMap = array("Identite"=> array(
     		                                                "columns"      => array("identityid"),
     		                                                "refTableClass"=> "Table_Usageridentites"  ,
     		                                                "refColumns"   => array("identityid")
                                                           )
								   );

}


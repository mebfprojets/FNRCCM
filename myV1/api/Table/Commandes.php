<?php

class Table_Commandes extends Sirah_Model_Table
{
	protected $_name            = "erccm_vente_commandes";
	
	protected $_primary         = "commandeid";
	
	protected $_dependentTables = array("Table_Commandelignes","Table_Commandefactures","Table_Commandelivraisons","Table_Commandepaiements");
	
	protected $_referenceMap    = array(
                                        "Member"=> array(
                                                          'columns'       => array("memberid"),
                                                          'refTableClass' => "Table_Members",
                                                          'refColumns'    => array("memberid")),
										"Statut"=> array(
                                                          'columns'       => array("statutid"),
                                                          'refTableClass' => "Table_Commandestatuts",
                                                          'refColumns'    => array("statutid")
                                                    )		
                                  );


}

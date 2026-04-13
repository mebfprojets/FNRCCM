<?php

class Table_Demandereservations extends Sirah_Model_Table
{

    protected $_name         = 'reservation_demandes_reservations';

    protected $_primary      = array("reservationid");
	 	 
	protected $_referenceMap = array("Demandeur"=> array(
     		                                              "columns"      => array("demandeurid"),
     		                                              "refTableClass"=> "Table_Demandeurs"  ,
     		                                              "refColumns"   => array("demandeurid")
                                                           ) ,
     		                         "Demande"  => array(
     				                                        "columns"      => array("demandeid"),
     				                                        "refTableClass"=> "Table_Demandes"  ,
     				                                        "refColumns"   => array("demandeid")
     		                                                )
								);
     
     
}


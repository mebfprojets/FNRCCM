<?php

class Table_Commandelivraisonlignes extends Sirah_Model_Table
{
	
	protected $_name         = 'erccm_vente_commandes_livraisons_ligne';
	
	protected $_primary      = array("livraisonid","commandeid","productid");
	
	protected $_referenceMap = array( 
									 'Produit'  => array(
														'columns'       =>  array("productid"),
														'refTableClass' => "Table_Commandeproduits",
														'refColumns'    =>  array("productid")) ,
									 'Commande' => array(
														'columns'       => array("commandeid"),
														'refTableClass' => "Table_Commandes",
														'refColumns'    => array("commandeid")),
									 'Livraison'=> array(
														'columns'       => array("livraisonid"),
														'refTableClass' => "Table_Commandelivraisons",
														'refColumns'    => array("livraisonid"))					
							  );
}

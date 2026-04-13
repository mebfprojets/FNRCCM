<?php

class Model_Commandelivraisonligne extends Sirah_Model_Default
{
	
	function getRow($livraisonid , $productid )
	{
		$livraisonid= intval( $livraisonid );
		$productid  = intval( $productid );
		 
		$table      = $this->_getTable();
		$select     = $table->select()->where("livraisonid=?", $livraisonid )
		                              ->where("productid =?", $productid  );
		return $table->fetchRow($select);
	}
	




}

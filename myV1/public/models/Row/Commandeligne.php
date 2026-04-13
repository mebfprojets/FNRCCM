<?php

class Model_Commandeligne extends Sirah_Model_Default
{
	
	function getRow( $commandeid , $productid )
	{
		$commandeid = intval( $commandeid );
		$productid  = intval( $productid );
		 
		$table      = $this->_getTable();
		$select     = $table->select()->where("commandeid=?", $commandeid )
		                              ->where("productid =?", $productid  );
		return $table->fetchRow($select);
	}
	




}

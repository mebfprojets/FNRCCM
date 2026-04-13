<?php

class Model_Profilecoordonnee extends Sirah_Model_Default
{
	
	public function emailIsValid( $email )
	{
		if( !empty( $email )) {
			$table      = $this->getTable();
			$dbAdapter  = $table->getAdapter();
			$select     = $dbAdapter->select()->from($table->info("name"))->where("email=?" , $email );
			$row        = $dbAdapter->fetchAll($select);
			return (count($row) < 1 );
		}
		return true;
	}



  }


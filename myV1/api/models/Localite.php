<?php

class Model_Localite extends Sirah_Model_Default
{
	public function getUsersList($localiteid = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
	
		$selectUsers  = $dbAdapter->select()->from(array("U" => $tablePrefix."system_users_account"),array("U.userid","name"=>new Zend_Db_Expr("CONCAT(U.firstname,' ',U.lastname)")))
		                                    ->join(array("UR"=> $tablePrefix."system_acl_useroles" ), "UR.userid=U.userid" , null)
	 	                                    ->join(array("R" => $tablePrefix."system_acl_roles"    ), "R.roleid =UR.roleid", null);
		if( $localiteid ) {
			$selectUsers->where("U.city = ?", $localiteid );
		}		
		return $dbAdapter->fetchPairs( $selectUsers , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectLocalite  = $dbAdapter->select()->from(array("L"=>$tableName));
	
		if( isset($filters["libelle"])  && !empty($filters["libelle"])){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && intval($filters["parentid"])) {
			$selectLocalite->where("L.parentid = ?", intval( $filters["parentid"] ) );
		}
		$selectLocalite->order(array("L.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectLocalite->limitPage($pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectLocalite, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectLocalite  = $dbAdapter->select()->from(array("L"=> $tableName), array("L.localiteid"));
	
		if(isset($filters["libelle"])   && !empty($filters["libelle"])){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && intval($filters["parentid"])) {
			$selectLocalite->where("L.parentid=?", intval( $filters["parentid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectLocalite );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectLocalite )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}	 
}
<?php

class Model_Localite extends Sirah_Model_Default
{
	
	public function getUsersList($localiteid = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		
		if( !intval( $agenceid )) {
			$agenceid = $this->agenceid;
		}		
		$selectUsers  = $dbAdapter->select()->from(array("U"=> $tablePrefix."system_users_account"),array("U.userid", "name" => new Zend_Db_Expr("CONCAT(U.firstname,' ',U.lastname)"))
		                                    ->join(array("UR"=> $tablePrefix."system_acl_useroles" ), "UR.userid = U.userid" , null)
	 	                                    ->join(array("R" => $tablePrefix."system_acl_roles"    ), "R.roleid  = UR.roleid", null)
		                                    ->where("R.rolename=?", "OPS" )->orWhere("R.rolename = ?", "GREFFIERS");
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
		$selectLocalite = $dbAdapter->select()->from(array("L" => $tablePrefix ."rccm_localites" ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && !empty($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectLocalite->where("L.parentid = ?", intval( $filters["parentid"] ) );
		}
		$selectLocalite->order(array("L.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectLocalite->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectLocalite, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectLocalite  = $dbAdapter->select()->from(array("L" => $tablePrefix ."rccm_localites" ), array("L.localiteid"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && !empty($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectLocalite->where("L.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectLocalite->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectLocalite );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectLocalite )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	 
}
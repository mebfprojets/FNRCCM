<?php

class Model_Demandeverificationsource extends Sirah_Model_Default
{

    protected $_error       = null;
	
	
	public function setError($error)
	{
		$this->_error       = $error;
		return $this;
	}
	
	public function getError()
	{
		return $this->_error;
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("S.libelle ASC"))
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectSource  = $dbAdapter->select()->from(array("S" => $tableName ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectSource->where("S.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectSource->where("S.code = ?", strip_tags($filters["code"]) );
		}
		if( isset($filters["sourceid"]) && intval($filters["sourceid"]) ) {
			$selectSource->where("S.sourceid = ?", intval( $filters["sourceid"] ) );
		}
		if( count($orders) && is_array($orders)) {
			$selectSource->order($orders);
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectSource->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectSource, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectSource  = $dbAdapter->select()->from(array("S" => $tableName), array("S.sourceid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectSource->where("S.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectSource->where("S.code = ?", strip_tags($filters["code"]) );
		}
	    if( isset($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectSource->where("S.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( isset($filters["special"]) && (null!==$filters["special"]) ) {
			$selectSource->where("S.special = ?", intval( $filters["special"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectSource->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectSource );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectSource )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
     
     
}


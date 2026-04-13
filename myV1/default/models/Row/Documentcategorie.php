<?php

class Model_Documentcategorie extends Sirah_Model_Default
{
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("D"=>$tablePrefix."system_users_documents_categories" ), array("D.id","D.icon","D.libelle","D.description","typeid"=>"D.id"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectDomaine->where("D.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["public"]) && intval($filters["public"]) ) {
			$selectDomaine->where("D.public = ?", intval( $filters["public"] ) );
		}
		$selectDomaine->order(array("D.id ASC","D.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectDomaine->limitPage($pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectDomaine, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents_categories" ), array("D.id"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectDomaine->where("D.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["public"]) && (null!==$filters["public"]) ) {
			$selectDomaine->where("D.public=?", intval( $filters["public"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectDomaine->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectDomaine );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectDomaine )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}




}


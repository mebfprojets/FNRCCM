<?php

class Model_Productcategorie extends Sirah_Model_Default
{
	
 
	
	public function count()
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectCategorie = $dbAdapter->select()->from(array("P"=> $table->info("name")),array("COUNT(P.catid)"));
		
		return $dbAdapter->fetchOne($selectCategorie);
	}
	
	public function getSelectListe( $defaultText = null , $columns = array() , $search=array() , $limit=0 , $callback = null , $cached = true)
	{
		$cache                     = $this->_cache;
		$adapter                   = $this->_table->getAdapter();
		$tableName                 = $this->_table->info("name");
		$tablePrefix               = $this->_table->info("namePrefix");
		$columns                   = (empty($columns)) ? array("C.catid", "libelle"=>new Zend_Db_Expr("CONCAT(C.code,':',C.libelle)")) : $columns;
		$orders                    = array();
		$cached                    = intval( $cached );
	
		if( isset( $search["orders"] ) ) {
			$orders                = $search["orders"];
			unset( $search["orders"] );
		}
		$cacheTags                 = (!empty($search)) ? Sirah_Functions_ArrayHelper::getKeys( $search , "string") : array("allTag");
		$cacheTags[]               = "limitTag".$limit;
		$cacheTags[]               = "selectlistTag";
		$prefixCacheId             = "selectListe".$this->_sanitizeCacheTagOrId($tableName);
		$cacheId                   = "modelRowsListe";
		$filters                   = $search;
	
		if( ( false  != ($cachedSelectListe = $this->fetchInCache( $cacheId , $prefixCacheId , array()))) && $cached ) {
			return $cachedSelectListe;
		}
		$select    =  $adapter->select()->from(array("C" => $tableName), $columns);
	    if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$select->where("C.libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}
        if( isset($filters["code"]) && !empty($filters["code"]) ){
			$select->where("C.code LIKE ?" , "%".strip_tags($filters["code"])."%");
		}
		 
		if( intval( $limit ) ) {
			$select->limit($limit);
		}
		if( !empty( $orders ) ) {
			$select->order( $orders );
		}
		$rows      =  $adapter->fetchPairs($select);
	
		if( null !== $defaultText ){
			$rows = array(0 => $defaultText ) + $rows;
		}
		if(is_callable($callback) && !empty($rows)){
			array_walk_recursive($rows , $callback);
		}
		if(null!==$cache){
			$this->saveToMemory( $rows , $cacheId , $prefixCacheId , array());
		}
		return $rows;
	}
	
	
	public function createCode()
	{
		$table               = $this->getTable();
		$dbAdapter           = $table->getAdapter();
		$tablePrefix         = $table->info("namePrefix");
		$tableName           = $table->info("name");
		
		$selectCategorie     = $dbAdapter->select()->from(array("C" => $tableName),array("total"=>"COUNT(C.catid)"));
		$nbreTotal           = $dbAdapter->fetchOne($selectCategorie)+1;
		$newCodeCategorie    = sprintf("CRA-%06d", $nbreTotal );
		while($existCategorie= $this->findRow($newCodeCategorie, "code", null, false)) {
			  $nbreTotal++;
			  $newCodeCategorie = sprintf("CRA-%06d", $nbreTotal );
		}
		
		return $newCodeCategorie;
	}
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$selectCategories = $dbAdapter->select()->from(    array("C"=> $tableName))
		                                        ->joinLeft(array("D"=> $tablePrefix."system_users_documents_categories"),"D.id=C.documentcatid",array("document"=>"D.libelle"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectCategories->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) ){
			$selectCategories->where("C.code LIKE ?" , "%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["documentcatid"]) && intval($filters["documentcatid"]) ){
			$selectCategories->where("C.documentcatid=?" ,intval($filters["documentcatid"]));
		} 
		$selectCategories->order(array("C.code DESC","C.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectCategories->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectCategories, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectCategories= $dbAdapter->select()->from(array("C" => $tableName), array("C.catid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectCategories->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) ){
			$selectCategories->where("C.code LIKE ?" , "%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["documentcatid"]) && intval($filters["documentcatid"]) ){
			$selectCategories->where("C.documentcatid=?" ,intval($filters["documentcatid"]));
		} 
		if( intval($pageNum) && intval($pageSize)) {
			$selectCategories->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectCategories );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectCategories )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
}

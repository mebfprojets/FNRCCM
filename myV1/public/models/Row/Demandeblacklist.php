<?php

class Model_Demandeblacklist extends Sirah_Model_Default
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
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$tableName        = $table->info("name");
		$selectItems      = $dbAdapter->select()->from(array("B"=> $tableName));
		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ) {
			$searchQuery  = trim($filters["searchQ"]);
			$searchQArray = preg_split("/[\s,]+/", trim($searchQuery));
			if( isset($searchQArray[1])) {
				$againstValue = "+".$searchQArray[0]."* ";
				unset($searchQArray[0]);
				foreach( $searchQArray as $searchWord ) {
					     if(empty($searchWord)) {
							 continue;
						 }
						 $againstValue  .="~".$searchWord."* ";
				}			
			} else {
				$searchWordArray = str_split($searchQuery, 8);
				if( isset( $searchWordArray[0] )) {
					$againstValue = "+".$searchWordArray[0]."* ";
					unset($searchWordArray[0]);
				}
				if( count(   $searchWordArray)) {
					foreach( $searchWordArray as $searchWord ) {
						     if(empty($searchWord)) {
								 continue;
							 }
							 $againstValue  .="~".$searchWord."* ";
					}
				}
			}
			$motsCles       = new Zend_Db_Expr("MATCH(B.libelle) AGAINST (\"".$againstValue."\" IN BOOLEAN MODE)");
			$selectItems->where("{$motsCles}");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectItems->where("B.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["entrepriseid"]) && intval($filters["entrepriseid"]) ) {
			$selectItems->where("B.entrepriseid = ?", intval( $filters["entrepriseid"] ) );
		}
		$selectItems->order(array("B.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectItems->limitPage( $pageNum , $pageSize);
		}
		//print_r($selectItems->__toString());die();
		return $dbAdapter->fetchAll( $selectItems, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$tableName        = $table->info("name");
		$selectItems      = $dbAdapter->select()->from(array("B" => $tableName), array("B.itemid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectItems->where("B.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ) {
			$searchQuery  = $filters["searchQ"];
			$searchQArray = preg_split("/[\s,]+/", $searchQuery,5);
			if( isset($searchQArray[1])) {
				$againstValue = "+".$searchQArray[0]."* ";
				unset($searchQArray[0]);
				foreach( $searchQArray as $searchWord ) {
						 $againstValue  .="~".$searchWord."* ";
				}			
			} else {
				$searchWordArray = str_split($searchQuery, 3);
				if( isset( $searchWordArray[0] )) {
					$againstValue = "+".$searchWordArray[0]."* ";
					unset($searchWordArray[0]);
				}
				if( count(   $searchWordArray)) {
					foreach( $searchWordArray as $searchWord ) {
							 $againstValue  .="~".$searchWord."* ";
					}
				}
			}
			$motsCles       = new Zend_Db_Expr("MATCH(B.libelle) AGAINST (\"".$againstValue."\")");
			$selectItems->where("{$motsCles}");
		}
	    if( isset($filters["entrepriseid"]) && intval($filters["entrepriseid"]) ) {
			$selectItems->where("B.entrepriseid = ?", intval( $filters["entrepriseid"] ) );
		}

		if( intval($pageNum) && intval($pageSize)) {
			$selectItems->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectItems );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectItems )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}

<?php

class Model_Galleryphoto extends Sirah_Model_Default
{
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectPhotos = $dbAdapter->select()->from(array("P"=>$tablePrefix."erccm_crm_content_gallery_photos"), array("P.photoid","P.libelle", "P.description", "photo" => "P.filepath"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectPhotos->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectPhotos->where("P.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		$selectPhotos->order(array("P.photoid DESC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectPhotos->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectPhotos, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectPhotos = $dbAdapter->select()->from(array("P" => $tablePrefix ."erccm_crm_content_gallery_photos"), array("P.photoid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectPhotos->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectPhotos->where("P.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectPhotos );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectPhotos )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
              
 }

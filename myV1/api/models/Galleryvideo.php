<?php

class Model_Articlevideo extends Sirah_Model_Default
{
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectVideos = $dbAdapter->select()->from(array("V"=>$tablePrefix."erccm_crm_content_gallery_videos" ), array("V.videoid","V.libelle","V.description","video"=>"V.filepath","V.videourl","V.videosrc"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectVideos->where("V.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectVideos->where("V.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		if(intval($pageNum) && intval($pageSize)) {
			$selectVideos->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectVideos, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectVideos = $dbAdapter->select()->from(array("P" => $tablePrefix ."erccm_crm_content_gallery_videos"), array("P.videoid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectVideos->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectVideos->where("P.galleryid=?", intval( $filters["galleryid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectVideos );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectVideos )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
              
}

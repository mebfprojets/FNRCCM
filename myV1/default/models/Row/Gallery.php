<?php

class Model_Gallery extends Sirah_Model_Default
{
	
	protected $_tableClass = "Table_Galleries";
	
	public function photos( $galleryid = null, $filters = array() ) 
	{
		if( !intval( $galleryid )) {
			$galleryid   = intval( $this->galleryid );
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectGalleries = $dbAdapter->select()->from(array("P"=> $tablePrefix."erccm_crm_content_gallery_photos"), array("photo"=>"P.filepath","P.libelle", "P.photoid", "P.description", "P.filepath"));
		
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectGalleries->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectGalleries->where("P.galleryid=?", $galleryid );
		return $dbAdapter->fetchAll( $selectGalleries, array() , Zend_Db::FETCH_ASSOC);		
	}
	
	public function videos( $galleryid = null, $filters = array() )
	{
		if( !intval( $galleryid )) {
			$galleryid= intval( $this->galleryid );
		}
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectVideos = $dbAdapter->select()->from(array("V" => $tablePrefix."erccm_crm_content_gallery_videos"), array("video"=>"V.filepath","V.libelle","V.videoid","V.description","V.filepath","V.videosrc","V.videourl"));
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectVideos->where("V.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectVideos->where("V.galleryid = ?", $galleryid );
		return $dbAdapter->fetchAll( $selectVideos, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectGalleries = $dbAdapter->select()->from(    array("G"=> $tablePrefix."erccm_crm_content_gallery"), array("G.galleryid", "G.libelle", "G.description"))
		                                       ->joinLeft(array("P"=> $tablePrefix."erccm_crm_content_gallery_photos"), "P.galleryid=G.galleryid",array("photo"=> "P.filepath"))
											   ->joinLeft(array("V"=> $tablePrefix."erccm_crm_content_gallery_videos"), "V.galleryid=G.galleryid",array("video"=> "V.filepath"));	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectGalleries->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}	
		if( intval( $filters["articleid"] ) ) {
			$selectGalleries->where("G.articleid = ?", intval( $filters["articleid"] ) );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectGalleries->where("G.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectGalleries->where("G.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		if( isset( $filters["galleryids"] ) && is_array( $filters["galleryids"] )) {
			if( count( $filters["galleryids"])) {
				$selectGalleries->where("G.galleryid IN (?)", array_map("intval",$filters["galleryids"]));
			}			
		}
		$selectGalleries->group(array("G.galleryid"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectGalleries->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectGalleries, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
	    $table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectGalleries = $dbAdapter->select()->from(array("G"=> $tablePrefix."erccm_crm_content_gallery" ), array("G.galleryid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectGalleries->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}	
		if( intval( $filters["articleid"] ) ) {
			$selectGalleries->where("G.articleid = ?", intval( $filters["articleid"] ) );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectGalleries->where("G.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectGalleries->where("G.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		if( isset( $filters["galleryids"] ) && is_array( $filters["galleryids"] )) {
			if( count( $filters["galleryids"])) {
				$selectGalleries->where("G.galleryid IN (?)", array_map("intval",$filters["galleryids"]));
			}			
		}
		$selectGalleries->group(array("G.galleryid"));
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectGalleries );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectGalleries )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
	
	public function getListVideos( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectGalleries = $dbAdapter->select()->from(array("G"=>$tablePrefix ."erccm_crm_content_gallery"), array("G.galleryid", "G.libelle", "G.description"))
		                                       ->join(array("V"=>$tablePrefix ."erccm_crm_content_gallery_videos"), "V.galleryid=G.galleryid", array("video" => "V.filepath", "V.filepath", "V.videoid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectGalleries->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}	
		if( intval( $filters["articleid"] ) ) {
			$selectGalleries->where("G.articleid = ?", intval( $filters["articleid"] ) );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectGalleries->where("G.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectGalleries->where("G.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		if( isset( $filters["galleryids"] ) && is_array( $filters["galleryids"] )) {
			if( count( $filters["galleryids"])) {
				$selectGalleries->where("G.galleryid IN (?)", array_map("intval",$filters["galleryids"]));
			}			
		}
		$selectGalleries->group(array("G.galleryid"))->order(array("G.galleryid DESC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectGalleries->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectGalleries, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListVideosPaginator($filters = array())
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectGalleries = $dbAdapter->select()->from(array("G" => $tablePrefix ."erccm_crm_content_gallery" ), array("G.galleryid"))
		                                       ->join(array("V" => $tablePrefix ."erccm_crm_content_gallery_videos"), "V.galleryid=G.galleryid", null );
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectGalleries->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}	
		if( intval( $filters["articleid"] ) ) {
			$selectGalleries->where("G.articleid = ?", intval( $filters["articleid"] ) );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectGalleries->where("G.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		if( intval( $filters["galleryid"] ) ) {
			$selectGalleries->where("G.galleryid = ?", intval( $filters["galleryid"] ) );
		}
		if( isset( $filters["galleryids"] ) && is_array( $filters["galleryids"] )) {
			if( count( $filters["galleryids"])) {
				$selectGalleries->where("G.galleryid IN (?)", array_map("intval",$filters["galleryids"]));
			}			
		}
		$selectGalleries->group(array("G.galleryid"));
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectGalleries );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectGalleries )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
  }


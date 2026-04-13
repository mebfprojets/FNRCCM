<?php

class Model_Article extends Sirah_Model_Default
{
	
	 
 
	
	public function createCode()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$tableName          = $table->info("name");
		
		$selectArticles   = $dbAdapter->select()->from(array("A"=> $tableName), array("COUNT(A.articleid)"));		
		$nbreArticles     = $dbAdapter->fetchOne($selectArticles)+1;		
		$newCode            = sprintf("Ac%05d", $nbreArticles);
		while($existRow     = $this->findRow($newCode, "code", null, false)) {
			  $nbreArticles++;
			  $newCode      = sprintf("Ac%05d",$nbreArticles);
		}	
		return $newCode;
	}
	
	public function gallery($articleid=0)
	{
		if(!$articleid )  {
			$articleid     = $this->articleid;
		}
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$tableName          = $table->info("name");
		
		$selectGallery      = $dbAdapter->select()->from(array("A"=> $tableName), array("A.galleryid"))
		                                          ->join(array("G"=> $tablePrefix."erccm_crm_content_gallery"),"G.galleryid=A.galleryid",array("gallerie"=>"G.libelle"))
												  ->where("A.articleid=?", intval($articleid));
		return $dbAdapter->fetchRow($selectGallery, array(), 5);										  
	}
	
	public function videos($articleid=0)
	{
		if(!$articleid )  {
			$articleid      = $this->articleid;
		}
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$selectVideos       = $dbAdapter->select()->from(array("V"=> $tablePrefix."erccm_crm_content_gallery_videos"), array("V.videoid","V.videosrc","V.videourl","V.libelle","V.description", "V.filepath","V.galleryid"))
		                                          ->join(array("G"=> $tablePrefix."erccm_crm_content_gallery"), "G.galleryid=V.galleryid", array("gallerie"=>"G.libelle"))
		                                          ->where("G.articleid = ?", intval( $articleid ));
		return $dbAdapter->fetchAll($selectVideos, array() , Zend_Db::FETCH_ASSOC );
	}
	
		
	public function photos($articleid=0)
	{
		if(!$articleid )  {
			$articleid      = $this->articleid;
		}
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$selectPhotos       = $dbAdapter->select()->from(array("P"=> $tablePrefix ."erccm_crm_content_gallery_photos"), array("P.photoid", "P.libelle", "P.description", "P.filepath", "P.galleryid"))
		                                          ->join(array("G"=> $tablePrefix ."erccm_crm_content_gallery"),"G.galleryid=P.galleryid", array("gallerie" => "G.libelle"))
		                                          ->where("G.articleid = ?", intval( $articleid ));
		return $dbAdapter->fetchAll( $selectPhotos, array() , Zend_Db::FETCH_ASSOC );
	}
	
	 
	public function documents($articleid=0)
	{
		if( !$articleid )  {
			 $articleid = $this->articleid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("D"=> $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata","D.creationdate","D.filedescription", "D.filesize", "D.documentid", "D.resourceid", "D.userid"))
				                               ->join(array("P"=> $tablePrefix ."erccm_crm_content_articles_documents" ),"P.documentid=D.documentid",array("P.documentid","P.articleid"))
				                               ->where("P.articleid = ?", $articleid  );
		$selectDocuments->order(array("P.articleid DESC","D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments, array() , Zend_Db::FETCH_ASSOC  );
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectArticles = $dbAdapter->select()->from(     array("A"=> $tablePrefix."erccm_crm_content_articles" ))
		                                       ->join(    array("C"=> $tablePrefix."erccm_crm_content_categories"),"C.catid=A.catid"        , array("categorie"=>"C.title"))
		                                       ->joinLeft(array("G"=> $tablePrefix."erccm_crm_content_gallery")   ,"G.galleryid=A.galleryid", array("galerie"  =>"G.libelle", "gallery"=>"G.libelle"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$likeLibelle  = new Zend_Db_Expr("A.title  LIKE '%".strip_tags($filters["libelle"])."%'");
			$likeKeywords = new Zend_Db_Expr("A.keywords LIKE '%".strip_tags($filters["libelle"])."%'");
			$selectArticles->where("{$likeLibelle} OR {$likeKeywords}");
		}
		if( isset($filters["title"]) && !empty($filters["title"])){
			$likeLibelle  = new Zend_Db_Expr("A.title    LIKE '%".strip_tags($filters["title"])."%'");
			$likeKeywords = new Zend_Db_Expr("A.keywords LIKE '%".strip_tags($filters["title"])."%'");
			$selectArticles->where("{$likeLibelle} OR {$likeKeywords}");
		}
		if( isset($filters["categorie"]) &&  !empty($filters["categorie"])) {
			$selectArticles->where("C.title=?", intval($filters["categorie"]) );
		}
		if( isset($filters["category"]) &&  intval($filters["category"])) {
			$selectArticles->where("A.catid=?", intval( $filters["category"]) );
		}
		if( isset($filters["catid"]) && intval($filters["catid"])) {
			$selectArticles->where("A.catid=?",intval($filters["catid"]));
		}
		if( isset( $filters["catids"] ) && is_array( $filters["catids"] )) {
			if( count( $filters["catids"])) {
				$selectArticles->where("A.catid IN (?)", array_map("intval",$filters["catids"]));
			}			
		}		 
		if( isset($filters["published"]) && ( $filters["published"] !== false )) {
			$selectArticles->where("A.published=?", intval( $filters["published"]) );
		}
		if( isset( $filters["periodend"] ) && !empty( $filters["periodend"] ) && (null !== $filters["periodend"]) ) {
			$selectArticles->where("? >= FROM_UNIXTIME(A.periodend,'%Y-%m-%d')",  $filters["periodend"] );
		}
		if( isset( $filters["periodstart"] ) && !empty( $filters["periodstart"] ) && (null !== $filters["periodstart"]) ) {
			$selectArticles->where("FROM_UNIXTIME(A.periodstart,'%Y-%m-%d')>=?",  $filters["periodstart"] );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectArticles->where("A.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectArticles->limitPage($pageNum , $pageSize);
		}		
		$selectArticles->order(array("A.creationdate DESC","A.catid DESC","A.articleid DESC"));
		//$rows = $dbAdapter->fetchAll( $selectArticles, array() , Zend_Db::FETCH_ASSOC);
		//print_r( $rows );
		//print_r($selectArticles->__toString()); die();
		return $dbAdapter->fetchAll( $selectArticles, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectArticles = $dbAdapter->select()->from(    array("A"=> $tablePrefix."erccm_crm_content_articles"), "A.articleid")
		                                      ->join(    array("C"=> $tablePrefix."erccm_crm_content_categories"),"C.catid=A.catid"        ,null)
		                                      ->joinLeft(array("G"=> $tablePrefix."erccm_crm_content_gallery")   ,"G.galleryid=A.galleryid",null);
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$likeLibelle  = new Zend_Db_Expr("A.title  LIKE '%".strip_tags($filters["libelle"])."%'");
			$likeKeywords = new Zend_Db_Expr("A.keywords LIKE '%".strip_tags($filters["libelle"])."%'");
			$selectArticles->where("{$likeLibelle} OR {$likeKeywords}");
		}
		if( isset($filters["title"]) && !empty($filters["title"])){
			$likeLibelle  = new Zend_Db_Expr("A.title    LIKE '%".strip_tags($filters["title"])."%'");
			$likeKeywords = new Zend_Db_Expr("A.keywords LIKE '%".strip_tags($filters["title"])."%'");
			$selectArticles->where("{$likeLibelle} OR {$likeKeywords}");
		}
		if( isset($filters["categorie"]) &&  !empty($filters["categorie"])) {
			$selectArticles->where("C.title=?", intval($filters["categorie"]) );
		}
		if( isset($filters["category"]) &&  intval($filters["category"])) {
			$selectArticles->where("A.catid=?", intval( $filters["category"]) );
		}
		if( isset($filters["catid"]) && intval($filters["catid"])) {
			$selectArticles->where("A.catid=?",intval($filters["catid"]));
		}
		if( isset( $filters["catids"] ) && is_array( $filters["catids"] )) {
			if( count( $filters["catids"])) {
				$selectArticles->where("A.catid IN (?)", array_map("intval",$filters["catids"]));
			}			
		}		 
		if( isset($filters["published"]) && ( $filters["published"] !== false )) {
			$selectArticles->where("A.published=?", intval( $filters["published"]) );
		}
		if( isset( $filters["periodend"] ) && !empty( $filters["periodend"] ) && (null !== $filters["periodend"]) ) {
			$selectArticles->where("? >= FROM_UNIXTIME(A.periodend,'%Y-%m-%d')",  $filters["periodend"] );
		}
		if( isset( $filters["periodstart"] ) && !empty( $filters["periodstart"] ) && (null !== $filters["periodstart"]) ) {
			$selectArticles->where("FROM_UNIXTIME(A.periodstart,'%Y-%m-%d')>=?",  $filters["periodstart"] );
		}
		if( isset( $filters["articleids"] ) && is_array( $filters["articleids"] )) {
			if( count( $filters["articleids"])) {
				$selectArticles->where("A.articleid IN (?)", array_map("intval",$filters["articleids"]));
			}			
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectArticles );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectArticles )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
           
}
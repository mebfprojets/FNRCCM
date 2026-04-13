<?php

class Model_Document extends Sirah_Model_Default
{
	public function canPreview( $filextension )
	{
		$allowedExtensions = array("png","jpg", "bmp", "jpeg", "gif");
		return ( in_array( $filextension , $allowedExtensions )) ;
	}
	
	public function getFileIcon( $filextension )
	{
		$icone  =  "glyph-icon icon-file";
		switch( $filextension ) {
			case "pdf":
				$icone = "icon-file-pdf";
				break;
			case "doc":
			case "docx":
				$icone  = "icon-file-word";
				break;
			case "xml":
				$icone  = "icon-file-xml";
				break;
			case "ppt":
				$icone  = "icon-file-powerpoint";
				break;
			case "xls":
			case "xlxs":
			case "csv":
				$icone  = "icon-file-excel";
				break;
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
			case "bmp":
				$icone = "icon-image";
				break;
		}
		return $icone;
	}
	
	public function filterIndex( $filename , $userid )
	{
		if( !$userid || (empty($filename)) ) {
			return false;
		}
		$filenameIndex  = "Fo-".$userid;
		return str_replace( $filenameIndex , "" , $filename );		
	}
	
	public function fileExists( $filename , $userid = 0 , $filepath = null)
	{	
		$userid            = intval($userid);	
		$table             = $this->getTable();		
		$documentSelect    = $table->select();				
		if( !empty( $filename ) && ( null != $filename )  ) {
			$documentSelect->where("filename = ?" , $filename);
		}
		if( $userid ) {
			$documentSelect->where("userid = ?" , $userid);
		}
		if( !empty( $filepath ) && ( null != $filepath )  ) {
			$documentSelect->where("filepath = ?" , $filepath);
		}		
		$document         = $table->fetchRow($documentSelect);		
		return $document;		
	}
	
	public function get( $filename , $userid = 0 , $filepath = null)
	{
		$userid            = intval($userid);
		$table             = $this->getTable();
		$documentSelect    = $table->select();	
		if( empty( $filename ) && ( null != $filename )  ) {
			$documentSelect->where("filename = ?" , $filename);
		}
		if( $userid ) {
			$documentSelect->where("userid = ?" , $userid);
		}
		if( empty( $filepath ) && ( null != $filepath )  ) {
			$documentSelect->where("filepath = ?" , $filepath);
		}
		$document         = $table->fetchRow($documentSelect);
		return $document;
	}
	
	public function rename( $filename , $userid )
	{
		if( empty( $filename ) || !$userid ) {
			return false;
		}
		$filenameIndex  = "Fo-".$userid;		
		$uniqueFilename = $filenameIndex . $filename;
		$i  = 0;
		while( $this->fileExists( $uniqueFilename , $userid ) ){
			   $uniqueFilename .= "_" . $i;
			   $i++;
		}
		return $uniqueFilename;
	}	
	
	
	public function getList($filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");				
		$selectDocument  = $dbAdapter->select()->from(array("D"     => $table->info("name")))
		                                       ->joinLeft(array("U" => $tablePrefix . "system_users_account") , "U.userid = D.userid" , array("U.firstname" , "U.lastname"))
		                                       ->joinLeft(array("C" => $tablePrefix . "system_users_documents_categories") , "C.id=D.category" , array("categorie" => "C.libelle"));		
		if( isset( $filters["userid"] ) && intval( $filters["userid"] ) ) {
			$selectDocument->where("D.userid = ?" , intval( $filters["userid"] ) );
		}
		if( isset( $filters["access"] ) && ( null != $filters["access"]) ) {
			$selectDocument->where("D.access = ?", intval( $filters["access"] ));
		}
		if( isset( $filters["category"] ) && intval( $filters["category"] ) ) {
			$selectDocument->where("D.category = ?" , intval( $filters["category"] ) );
		}
		if( isset( $filters["filename"] ) && !empty( $filters["filename"] ) ) {
			$selectDocument->where("D.filename  LIKE ?" , "%" . $filters["filename"] . "%" );
		}
		if( isset( $filters["filemetada"] ) && !empty( $filters["filemetadata"] ) ) {
			$selectDocument->where("D.filemetada  LIKE ?" , "%" . $filters["filemetada"] . "%" );
		}
		if( isset( $filters["filepath"] ) && !empty( $filters["filepath"] ) ) {
			$selectDocument->where("D.filepath  LIKE ?" , "%" . $filters["filepath"] . "%" );
		}
		if( isset( $filters["filetype"] ) && !empty( $filters["filetype"] ) ) {
			$selectDocument->where("D.filextension = ?", $filters["filetype"] );
		}
		if(isset($filters["username"]) && !empty($filters["username"]) && (null!==$filters["username"])){
			$selectDocument->where("U.username LIKE ?","%".strip_tags($filters["username"])."%") ;
		}
		if( isset($filters["userid"]) && !empty($filters["userid"]) && (null!==$filters["userid"])){
			$selectDocument->where("U.userid = ?" , intval($filters["userid"]) ) ;
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) && (null!==$filters["lastname"])){
			$selectDocument->where("U.lastname LIKE ?","%".strip_tags($filters["lastname"])."%") ;
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectDocument->where("U.firstname LIKE ?" , "%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectDocument->where("U.email = ? ",$filters["email"]);
		}
		if(intval($pageNum) && intval($pageSize)) {
			$selectDocument->limitPage( $pageNum , $pageSize);
		}
		$selectDocument->order(array("D.category DESC", "D.documentid DESC", "D.creationdate DESC" ));
		
		return $dbAdapter->fetchAll( $selectDocument , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
	    $table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");				
		$selectDocument    = $dbAdapter->select()->from(array("D"      => $table->info("name")), array("D.documentid"))
		                                         ->joinLeft(array("U"  => $tablePrefix . "system_users_account") , "U.userid = D.userid", null )
		                                         ->joinLeft(array("C"  => $tablePrefix . "system_users_documents_categories") , "C.id=D.category" , array("categorie" => "C.libelle"));		
		if( isset( $filters["userid"] ) && intval( $filters["userid"] ) ) {
			$selectDocument->where("D.userid = ?" , intval( $filters["userid"] ) );
		}
		if( isset( $filters["access"] ) && ( null != $filters["access"]) ) {
			$selectDocument->where("D.access = ?", intval( $filters["access"] ));
		}
		if( isset( $filters["category"] ) && intval( $filters["category"] ) ) {
			$selectDocument->where("D.category = ?" , intval( $filters["category"] ) );
		}
		if( isset( $filters["filename"] ) && !empty( $filters["filename"] ) ) {
			$selectDocument->where("D.filename  LIKE ?", "%" . $filters["filename"] . "%" );
		}
		if( isset( $filters["filemetada"] ) && !empty( $filters["filemetadata"] ) ) {
			$selectDocument->where("D.filemetada  LIKE ?" , "%" . $filters["filemetada"] . "%" );
		}
		if( isset( $filters["filepath"] ) && !empty( $filters["filepath"] ) ) {
			$selectDocument->where("D.filepath  LIKE ?" , "%" . $filters["filepath"] . "%" );
		}
	    if( isset( $filters["filetype"] ) && !empty( $filters["filetype"] ) ) {
			$selectDocument->where("D.filextension = ?", $filters["filetype"] );
		}
		if( isset( $filters["filedescription"] ) && !empty( $filters["filedescription"] ) ) {
			$selectDocument->where("D.filedescription  LIKE ?" , "%" . $filters["filedescription"] . "%" );
		}
		if(isset($filters["username"]) && !empty($filters["username"]) && (null!==$filters["username"])){
			$selectDocument->where("U.username LIKE ?","%".$filters["username"]."%") ;
		}
		if( isset($filters["userid"]) && !empty($filters["userid"]) && (null!==$filters["userid"])){
			$selectDocument->where("U.userid = ?" , intval($filters["userid"]) ) ;
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) && (null!==$filters["lastname"])){
			$selectDocument->where("U.lastname LIKE ?","%".$filters["lastname"]."%") ;
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectDocument->where("U.firstname LIKE ?" , "%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectDocument->where("U.email = ? " , $filters["email"]);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectDocument );
		$rowCount          = intval(count($dbAdapter->fetchAll( $selectDocument )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
			
		return $paginator;		
	}
}

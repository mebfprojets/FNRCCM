<?php

class Model_Apidocument extends Sirah_Model_Default
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
	
	public function document($documentid=0,$numRccm=null,$registreid=0)
	{
		 
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectDocument = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.documentid","D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.resourceid","D.userid"))
				                              ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","categorie"=>"C.libelle","C.icon","catid"=>"C.id"))
				                              ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",null)
				                              ->join(array("R" => $tablePrefix."rccm_registre"),"R.registreid=RD.registreid",array("R.registreid","numRccm"=>"R.numero","R.numero","nomcommercial"=>"R.libelle"));
		if( intval($registreid)) {
			$selectDocument->where("RD.registreid=?", intval($registreid)) 
			               ->where("R.registreid=?" , intval($registreid));
		}
		if( intval($documentid)) {
			$selectDocument->where("RD.documentid=?", intval($documentid))
			               ->where("D.documentid=?" , intval($documentid));
		}
		if((null!= $numRccm) && !empty($numRccm)) {
			$selectDocument->where("R.numero=?", strip_tags($numRccm));
		}
		return $dbAdapter->fetchRow( $selectDocument, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function filterIndex( $filename , $userid )
	{
		if( !$userid || (empty($filename)) ) {
			return false;
		}
		$filenameIndex  = "Fo-".$userid;
		return str_ireplace(array("_0", "_1","_2", "_3","_4","_5","",$filenameIndex) , "" , $filename );		
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
		$selectDocuments = $dbAdapter->select()->from(array("D" => $table->info("name")),array("D.documentid","D.filextension","filetype"=>"D.filextension","D.filepath","description"=>"D.filedescription","D.filemetadata","D.creationdate","D.creatoruserid"))
		                                       ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category",array("filename"=>"C.libelle","categorie" => "C.libelle"));		
		if( isset($filters["num_rccm"]) && !empty($filters["num_rccm"]) && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["num_rccm"];
		}
		if( isset($filters["numrccm"]) && !empty($filters["numrccm"])   && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["numrccm"];
		}
		if( isset($filters["rccm"]) && !empty($filters["rccm"]) && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["rccm"];
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) ) {
			$selectDocuments->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",array("RD.access"))
			                ->join(array("R" => $tablePrefix."rccm_registre"),"R.registreid=RD.registreid",array("R.registreid","numRccm"=>"R.numero","R.numero","nomcommercial"=>"R.libelle"))
							->where("R.numero=?", strip_tags($filters["numero"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
			$selectDocuments->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",array("RD.access"))
			                ->join(array("R" => $tablePrefix."rccm_registre"),"R.registreid=RD.registreid",array("R.registreid","numRccm"=>"R.numero","R.numero","nomcommercial"=>"R.libelle"))
							->where("R.registreid=?",intval($filters["registreid"]));
		}
		if( isset( $filters["userid"] ) && intval( $filters["userid"] ) ) {
			$selectDocuments->where("D.userid = ?" , intval( $filters["userid"] ) );
		}
		if( isset( $filters["access"] ) && ( null != $filters["access"]) ) {
			$selectDocuments->where("D.access = ?", intval( $filters["access"] ));
		}
		if( isset( $filters["type"] ) && !empty( $filters["type"] ) ) {
			$selectDocuments->where("C.libelle LIKE ?" , intval( $filters["type"] ) );
		}
		if( isset( $filters["category"] ) && intval( $filters["category"] ) ) {
			$selectDocuments->where("D.category = ?" , intval( $filters["category"] ) );
		}
	    if( isset( $filters["categoryLib"] ) && intval( $filters["categoryLib"] ) ) {
			$selectDocuments->where("C.libelle LIKE ?" , "%" . strip_tags( $filters["categoryLib"] ). "%" );
		}
		if( isset( $filters["filename"] )   && !empty( $filters["filename"] ) ) {
			$selectDocuments->where("D.filename  LIKE ?" , "%" . $filters["filename"] . "%" );
		}
		if( isset( $filters["filemetada"] ) && !empty( $filters["filemetadata"] ) ) {
			$selectDocuments->where("D.filemetada LIKE ?" , "%" . $filters["filemetada"] . "%" );
		}
		if( isset( $filters["filepath"] ) && !empty( $filters["filepath"] ) ) {
			$selectDocuments->where("D.filepath  LIKE ?" , "%" . $filters["filepath"] . "%" );
		}
		if( isset( $filters["filetype"] ) && !empty( $filters["filetype"] ) ) {
			$selectDocuments->where("D.filextension = ?", $filters["filetype"] );
		}
		if(isset($filters["username"])    && !empty($filters["username"]) ){
			$selectDocuments->where("U.username LIKE ?","%".strip_tags($filters["username"])."%") ;
		}
		if( isset($filters["userid"])  && intval($filters["userid"]) ){
			$selectDocuments->where("D.userid= ?" , intval($filters["userid"]) ) ;
		}
		if( isset($filters["documentid"])  && intval($filters["documentid"]) ){
			$selectDocuments->where("D.documentid= ?" , intval($filters["documentid"]) ) ;
		} 
		if( isset( $filters["documentids"] ) && is_array( $filters["documentids"] )) {
			if( count( $filters["documentids"])) {
				$selectDocuments->where("R.documentid IN (?)", array_map("intval",$filters["documentids"]));
			}			
		}
		if(intval($pageNum) && intval($pageSize)) {
			$selectDocuments->limitPage( $pageNum , $pageSize);
		}
		$selectDocuments->order(array("D.category DESC", "D.documentid DESC", "D.creationdate DESC" ));
		
		return $dbAdapter->fetchAll( $selectDocuments , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
	    $table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");				
		$selectDocuments = $dbAdapter->select()->from(array("D" => $table->info("name")),array("D.documentid"))
		                                       ->join(array("C" => $tablePrefix ."system_users_documents_categories"),"C.id=D.category",null);		
		if( isset($filters["num_rccm"]) && !empty($filters["num_rccm"]) && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["num_rccm"];
		}
		if( isset($filters["numrccm"]) && !empty($filters["numrccm"]) && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["numrccm"];
		}
		if( isset($filters["rccm"]) && !empty($filters["rccm"]) && !isset($filters["numero"])) {
			$filters["numero"]  = $filters["rccm"];
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) ) {
			$selectDocuments->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",null)
			                ->join(array("R" => $tablePrefix."rccm_registre"),"R.registreid=RD.registreid",null)
							->where("R.numero=?", strip_tags($filters["numero"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
			$selectDocuments->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",null)
			                ->join(array("R" => $tablePrefix."rccm_registre"),"R.registreid=RD.registreid",null)
							->where("R.registreid=?",intval($filters["registreid"]));
		}
		if( isset( $filters["userid"] ) && intval( $filters["userid"] ) ) {
			$selectDocuments->where("D.userid = ?" , intval( $filters["userid"] ) );
		}
		if( isset( $filters["access"] ) && ( null != $filters["access"]) ) {
			$selectDocuments->where("D.access = ?", intval( $filters["access"] ));
		}
		if( isset( $filters["type"] ) && !empty( $filters["type"] ) ) {
			$selectDocuments->where("C.libelle LIKE ?" , intval( $filters["type"] ) );
		}
		if( isset( $filters["category"] ) && intval( $filters["category"] ) ) {
			$selectDocuments->where("D.category = ?" , intval( $filters["category"] ) );
		}
	    if( isset( $filters["categoryLib"] ) && intval( $filters["categoryLib"] ) ) {
			$selectDocuments->where("C.libelle LIKE ?" , "%" . strip_tags( $filters["categoryLib"] ). "%" );
		}
		if( isset( $filters["filename"] )   && !empty( $filters["filename"] ) ) {
			$selectDocuments->where("D.filename  LIKE ?" , "%" . $filters["filename"] . "%" );
		}
		if( isset( $filters["filemetada"] ) && !empty( $filters["filemetadata"] ) ) {
			$selectDocuments->where("D.filemetada  LIKE ?" , "%" . $filters["filemetada"] . "%" );
		}
		if( isset( $filters["filepath"] ) && !empty( $filters["filepath"] ) ) {
			$selectDocuments->where("D.filepath  LIKE ?" , "%" . $filters["filepath"] . "%" );
		}
		if( isset( $filters["filetype"] ) && !empty( $filters["filetype"] ) ) {
			$selectDocuments->where("D.filextension = ?", $filters["filetype"] );
		}
		if(isset($filters["username"])    && !empty($filters["username"]) ){
			$selectDocuments->where("U.username LIKE ?","%".strip_tags($filters["username"])."%") ;
		}
		if( isset($filters["userid"])  && intval($filters["userid"]) ){
			$selectDocuments->where("D.userid= ?" , intval($filters["userid"]) ) ;
		}
		if( isset($filters["documentid"])  && intval($filters["documentid"]) ){
			$selectDocuments->where("D.documentid= ?" , intval($filters["documentid"]) ) ;
		} 
		if( isset( $filters["documentids"] ) && is_array( $filters["documentids"] )) {
			if( count( $filters["documentids"])) {
				$selectDocuments->where("R.documentid IN (?)", array_map("intval",$filters["documentids"]));
			}			
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectDocuments );
		$rowCount          = intval(count($dbAdapter->fetchAll(  $selectDocuments)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
			
		return $paginator;		
	}
}

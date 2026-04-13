<?php

class Model_Domaine extends Sirah_Model_Default
{
    
	public function myroot( $userid = 0)
	{
		if( !intval( $userid )) {
			$user   = Sirah_Fabric::getUser();
			$userid = $user->userid;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");		
		$selectDomaine = $dbAdapter->select()->from(array("UD" => $tablePrefix ."rccm_domaines_users" ), array("UD.domaineid"))->where("userid = ?", intval($userid));		
		return intval($dbAdapter->fetchOne($selectDomaine));				
	}
	
	public static function listTreeView( $parentid = 0 , $filters = array(), $parentRoot = 0, $showUrl = "#", $removeUrl = "#" )
	{
		$rows   = self::listTree( $parentid , $filters );
		if( count( $rows )) {
			if( $parentid == $parentRoot )
			    $output = "<ul class=\"nav-tree list-group\" >";
		    else 
		    	$output = "<ul class=\"list-group\" >";
			foreach( $rows as $rowid => $row ) {
				     $children       =  $row["children"];
				     $childrenOutput = "";
				     $linkInfos      = "#";
				     if( ($showUrl  != "#" ) && !empty( $showUrl )) {
				     	$linkInfos = $showUrl."/id/".$rowid;
				     }
				     if( count( $children ) && ! empty( $children )) {
				     	 $output .=" <li class=\"list-group-item has-children open\"> ";
				     	 $childrenOutput .= self::listTreeView($row["id"], $filters, $parentRoot, $showUrl , $removeUrl );
				     } else {
				     	 $output .=" <li class=\"list-group-item \"> ";
				     }				     	     
				         $output .="<b class=\"caret\"></b> <a href=\"".$linkInfos."\">
						            <span class=\"glyphicon glyphicon-folder-close\"> ".$row['libelle']." </span> </a> ";
				         $output .= $childrenOutput;
				     
				     $output .=" </li> ";
			}						
			$output .=  "</ul>";
		}				
		return	$output;				
	}
	
	public static function listTree( $parentid = 0 , $filters = array() )
	{
		$domaineRow          = new Model_Domaine();
		$search              = array();
		if( is_string( $filters )) {
			$search          = array("libelle" => $filters );
		} 
		$search["parentid"]  = $parentid;
		$children            = $domaineRow->getList( $search );
		$treeArray           = array();
		if( count(    $children ) ) {
			foreach ( $children as $child )  {
				      $domaineid             = intval( $child["domaineid"] );
					  $parentid              = intval( $child["parentid"]  );
				      $treeArray[$domaineid] = array("id" => $domaineid,"libelle" => $child["libelle"],"children" => self::listTree($domaineid,$filters), "parentid" => $parentid );
			}
		} 
		return $treeArray; 
	}
	

    public function getRangeList( $rangeLetters = array("A","M"), $filters = array(), $pageNum = 0 , $pageSize = 0)
	{	
	    $table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("D" => $tablePrefix ."rccm_domaines" ));
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDomaine->where("D.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectDomaine->where("D.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( isset($filters["special"]) && (null!==$filters["special"]) ) {
			$selectDomaine->where("D.special = ?", intval( $filters["special"] ) );
		}
		if(!is_array($rangeLetters) && !is_string($rangeLetters)) {
			return array();
		}
		if( is_string($rangeLetters)) {
			$rangeLetters   = array($rangeLetters);
		}
		if( count($rangeLetters) == 1) {
			$rangeVal     = $rangeLetters[0];
			$rangeLetters = explode("-", $rangeVal );
		}
		if( count($rangeLetters) == 2) {
			$selectDomaine->where("LOWER(D.libelle) RLIKE ?",sprintf("^[%s]", strip_tags(strtolower(implode("-", $rangeLetters )))))->order(array("D.libelle ASC"));
		    if( intval($pageNum) && intval($pageSize)) {
			    $selectDomaine->limitPage( $pageNum , $pageSize);
		    }
			return $dbAdapter->fetchAll( $selectDomaine, array() , Zend_Db::FETCH_ASSOC);
		}		
		return array();
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("D" => $tablePrefix ."rccm_domaines" ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDomaine->where("D.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectDomaine->where("D.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( isset($filters["special"]) && (null!==$filters["special"]) ) {
			$selectDomaine->where("D.special = ?", intval( $filters["special"] ) );
		}
		$selectDomaine->order(array("D.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectDomaine->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectDomaine, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("D" => $tablePrefix ."rccm_domaines" ), array("D.domaineid"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectDomaine->where("D.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectDomaine->where("D.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( isset($filters["special"]) && (null!==$filters["special"]) ) {
			$selectDomaine->where("D.special = ?", intval( $filters["special"] ) );
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
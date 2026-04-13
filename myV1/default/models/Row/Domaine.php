<?php

class Model_Domaine extends Sirah_Model_Default
{
	

    public function children($domaineid =0 )
	{
		if(!intval($domaineid) ) {
			$domaineid= $this->domaineid;
		}		
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectChildren  = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.domaineid","C.parentid","C.code","C.profondeur","C.libelle","C.description"))
											   ->where("C.parentid=?", intval($domaineid));
		return $dbAdapter->fetchAll($selectChildren, array(), Zend_Db::FETCH_ASSOC);
	}
	
	
	static public function treeFamily($parentid)
	{
		$categoryRow               = new Model_Domaine();
		$parentFamilyData          = $categoryRow->children($parentid);
		$familyRows                = array();
 
		if(!is_array($parentFamilyData)) {
			return array();
		}
		foreach( $parentFamilyData as $row ) {
			     $domaineid     = $row["domaineid"];
			     $rowChildren      = $categoryRow->children($domaineid);	
                 $familyRows[$domaineid] = $row["libelle"];				 
				 if( count( $rowChildren ) ) {					 
					 $familyRows   = $familyRows+Model_Domaine::treeFamily($domaineid);
				 }				 
		}
        return $familyRows;		
	}
	
	public function allChildrenIds($parentid, $cached=true)
	{
		$cache                     = $this->_cache;
		$adapter                   = $this->_table->getAdapter();
		$tableName                 = $this->_table->info("name");
		$prefixCacheId             = "selectListChildren".$this->_sanitizeCacheTagOrId("CatId".$parentid);
		$cacheId                   = "modelCatChildren";
		
		if( $cached && (false != ($cachedCatChildren = $this->fetchInCache($cacheId, $prefixCacheId , array())))) {
			$parentChildrenCached  = (is_array( $cachedCatChildren )) ? $cachedCatChildren : explode(",", $cachedCatChildren);
		    return $parentChildrenCached;
		}		
		$parentFamily              = self::treeFamily($parentid); 
        $parentChildren            = array_merge(array_keys($parentFamily), array());	
		array_unshift($parentChildren, $parentid);
		$this->saveToMemory($parentChildren, $cacheId , $prefixCacheId , array());
		return $parentChildren ;
	}
	
	
	public function getSelectListe( $defaultText = null , $columns = array() , $search=array() , $limit=0 , $callback = null , $cached = true)
	{
		$cache                     = $this->_cache;
		$adapter                   = $this->_table->getAdapter();
		$tableName                 = $this->_table->info("name");
		$columns                   = (empty($columns)) ?array("domaineid","CONCAT(code,' : ',libelle)"): $columns;
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
		$select                    =  $adapter->select()->from($tableName , $columns);
	    if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$select->where("libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$select->where("libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && !empty($filters["parentid"]) && (null !== $filters["parentid"])){
			$select->where("parentid=?" , intval($filters["parentid"]));
		}
		if( intval( $limit ) ) {
			$select->limit($limit);
		}
		if(!empty( $orders ) ) {
			$select->order( $orders );
		} else {
			$select->order( array("libelle ASC"));
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
	
	static public function fuelTreeData($parentid = 0)
	{
		$categoryRow    = new Model_Domaine();
		$table          = $categoryRow->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		
		$selectChildren = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.domaineid","C.parentid","C.code","C.libelle","C.description"))
											  ->where("C.parentid=?", intval($parentid));
		$children       = $dbAdapter->fetchAll($selectChildren, array(), Zend_Db::FETCH_ASSOC);	
		$treeData       = array();
		if( count( $children ) ) {
			foreach( $children as $child ) {
				     $categoryid  = $child["domaineid"];
					 $name        = sprintf("%s : %s", $child["code"], $child["libelle"]);
				     $hasChildren = count(self::fuelTreeData($categoryid));
				     $treeData[]  = array(
					                       "title" => $name,
										   "name"  => $name,
										   "key"   => $categoryid,
										   "folder" => ($hasChildren) ? true : false,
										   "children" => self::fuelTreeData($categoryid),
										   "attr" => array(
										             "id"          => $categoryid,
													 "data-icon"   => "glyph-icon icon-folder",
													 "hasChildren" => $hasChildren
										          )
					                       );
			}
		}
		return $treeData;
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
	
	static function buildTree($data, $parent = 0) {
		$tree = array();
		foreach ( $data as $d) {
			if ($d['parentid'] == $parent) {
				$children = self::buildTree($data, $d['id']);
				// set a trivial key
				if (!empty($children)) {
					$d['_children'] = $children;
				}
				$tree[] = $d;
			}
		}
		return $tree;
	}

	public static function listTree($defaultTextValue="Sélectionnez une catégorie", $parentid = 2, $filters = array() )
	{
		$categoryRow         = new Model_Domaine();
		$search              = array();
		if( is_string( $filters )) {
			$search          = array("libelle" => $filters );
		} 
		$children            = $categoryRow->getAllList( $search );
		$treeRows            = array(0=>array("id"=>0,"name" => $defaultTextValue, "parentid"=>"-1","depth"=>0));
		if( count(    $children ) ) {
			foreach ( $children as $child )  {
				      $domaineid  = intval($child["domaineid"] );
					  $parentid   = intval($child["parentid"] );
					  $profondeur = intval($child["profondeur"]);
					  $name       = sprintf("%s : %s", $child["code"], $child["libelle"]);
				      $treeRows[] = array("id"=>$domaineid,"name" => $name,"parentid"=>$parentid,"depth"=>$profondeur);
			}
		}		
		$rows = self::buildTree($treeRows, $parentid ); 
		
		return $rows;
				
	}
	
	public function parent($parentid = 0)
	{
		if(!intval( $parentid ) ) {
			$parentid    = $this->parentid;
		}
		
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectParent    = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.domaineid","C.parentid","C.code","C.profondeur","C.libelle","C.description"))
											   ->where("C.domaineid=?", intval($parentid));
		return $dbAdapter->fetchRow( $selectParent, array(), 5);									   
	}
	
	public function getAllList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.domaineid","C.parentid","C.code","C.profondeur","C.libelle","C.description"))
		                                     ->joinLeft(array("P"=>$table->info("name")), "P.domaineid=C.parentid",array("parent"=>"P.libelle"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDomaine->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectDomaine->where("C.code=?", strip_tags($filters["code"]));
		}
		if( isset( $filters["parentid"] )  && intval($filters["parentid"]) ) {
			$selectDomaine->where("C.parentid=?", intval($filters["parentid"]));
		}
		$selectDomaine->order(array("C.code ASC","C.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectDomaine->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectDomaine, array() , Zend_Db::FETCH_ASSOC);
	}

	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectDomaine = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.domaineid","C.parentid","C.code","C.libelle","C.description"))
		                                       ->joinLeft(array("P"=>$table->info("name")), "P.domaineid=C.parentid",array("parent"=>"P.libelle"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDomaine->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectDomaine->where("C.code=?", strip_tags($filters["code"]));
		}
		if( isset( $filters["parentid"] )  && intval($filters["parentid"]) ) {
			$selectDomaine->where("C.parentid=?", intval($filters["parentid"]));
		}
		$selectDomaine->order(array("C.code ASC","C.libelle ASC"));
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
		$selectDomaine = $dbAdapter->select()->from(array("C" => $table->info("name")), array("C.domaineid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDomaine->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectDomaine->where("C.code=?", strip_tags($filters["code"]));
		}
	    if(isset( $filters["parentid"] )  && intval($filters["parentid"]) ) {
		    $selectDomaine->where("C.parentid=?", intval($filters["parentid"]));
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectDomaine );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectDomaine )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}
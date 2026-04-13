<?php

class Model_Project extends Sirah_Model_Default
{
	
	static public function documentypes()
	{
		$modelDocumentCategory = new Model_Documentcategorie();
		return $modelDocumentCategory->getList(array("public"=>1));
	}
	
	static public function productypes()
	{
		$modelProductCategory = new Model_Productcategorie();
		return $modelProductCategory->getList();
	}

     public function replaceTags( $string )
	{
		$entreprise = $this->findParentRow("Table_Entreprises");		
		if( !$entreprise ) {
			return $string;
		}
	}
	
	public function defaultParams()
	{
		$params  = "nb_elements_page|20~default_year|'CURRENT'~default_period_start|0~default_period_end|0~default_find_documents|1~
              	    default_find_documents_src|'G:\BFRCCM'~default_indexation_folder_destination|'".APPLICATION_DATA_PATH. DS."GED'~
				    default_check_documents|1~default_pdf_margins|0~default_pdf_header|''~default_pdf_footer|''~default_pdf_width|0~default_domaineid|1";
		return 	$params;		
	}
	
	public function getParams()
	{
		$parametres    = $this->params;				
		if( empty( $parametres )) {
			$parametres= $this->params =  $this->defaultParams() ;
			$this->save();
		}
		$paramsExplode = explode("~", $parametres);
		
		$params        = new stdClass();
		if(count(    $paramsExplode)){
			foreach( $paramsExplode as $param) {
				     $element               = explode("|",$param);
				     $params->{$element[0]} = trim(stripslashes($element[1]) ,'\'"');
			}
		}
		return $params;
	}
	
	public function getParam( $name , $defaultVal = "")
	{
		$params   = $this->getParams();	
		if(empty($name) || null==$name || !isset($params->{$name})){
			return;
		}	
		if(!isset($params->{$name})){
			$params->{$name}  = trim(stripslashes($defaultVal), '\'"');
		}
		return $params->{$name};
	}
	
	function paramsToArray($params=array())
	{
		if(empty($params))
			$params       = $this->getParams();	
		if(is_object($params)){
			$array            =  get_object_vars($params);
			$params           = array();
	
			if(count(    $array)){
				foreach( $array as $k=>$val){
					     $params[$k] = $val;
				}
			}
		} elseif(is_string($params)) {
			$paramsExplode              = explode("~",$params);			
			if(count(    $paramsExplode )) {
				foreach( $paramsExplode as $param){
					     $element             = explode("|", $param);
					     $params[$element[0]] = trim( stripslashes($element[1]) , '\'"');
				}
			}
		}
		return $params;
	}
	 
	public function setParams($parametres=array(), $save = true )
	{
		$defaultParams = $this->paramsToArray();	
		$update_params = array_merge( $defaultParams, $parametres);
		$formatParams  = "";
		if(count(    $update_params)) {
			foreach( $update_params as $key=>$val){
				     $formatParams.=sprintf("%s|\"%s\"~", $key, addslashes($val));
			}
		}
		$this->params = substr($formatParams,0,-1);
		if(false == $save ) {
			return true;
		}
		return $this->save();
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectProject = $dbAdapter->select()->from(array("P" => $tablePrefix ."rccm_projet_application"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectProject->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
        if(isset($filters["entrepriseid"]) && intval($filters["entrepriseid"])) {
			$selectProject->where("P.entrepriseid = ?", intval($filters["entrepriseid"]));
		}
       if( isset($filters["startime"]) && ( intval($filters["startime"]) != 0 ) ) {
			$selectProject->where("P.endtime >= ? " , intval($filters["startime"]));
		}
		if( isset($filters["endtime"]) && ( intval($filters["endtime"]) != 0 ) ) {
			$selectProject->where("P.startime <= ? " , intval($filters["endtime"]));
		} 	
		$selectProject->order(array("P.current DESC", "P.startime DESC", "P.endtime DESC", "P.libelle ASC"));
		if(intval($pageNum) && intval( $pageSize)) {
			$selectProject->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectProject, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
	    $table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectProject = $dbAdapter->select()->from(array("P" => $tablePrefix ."rccm_projet_application"), array("P.projectid") );
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectProject->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
        if(isset($filters["entrepriseid"]) && intval($filters["entrepriseid"])) {
			$selectProject->where("P.entrepriseid = ?", intval($filters["entrepriseid"]));
		}
        if( isset($filters["startime"]) && ( intval($filters["startime"]) != 0 ) ) {
			$selectProject->where("D.endtime >= ? " , intval($filters["startime"]));
		}
		if( isset($filters["endtime"]) && ( intval($filters["endtime"]) != 0 ) ) {
			$selectProject->where("D.startime <= ? " , intval($filters["endtime"]));
		} 		
		$selectProject->order(array("P.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectProject->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectProject );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectProject )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
         
}
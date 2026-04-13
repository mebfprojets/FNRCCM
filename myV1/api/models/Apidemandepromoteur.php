<?php

class Model_Apidemandepromoteur extends Sirah_Model_Default
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
	
	public function getRow($data = array())
	{
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectPromoteur  = $dbAdapter->select()->from(array("D"=> $tableName))
		                                        ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("I.typeid","numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"));
	    if( isset($data["numidentite"])) {
			$selectPromoteur->where(  "D.numidentite=?",$data["numidentite"])
			                ->orWhere("I.numero=?", $data["numidentite"]);
		}
		if( isset($data["numero"])) {
			$selectPromoteur->where("I.numero=?",$data["numero"]);
		}
		if( isset($data["date_etablissement"])) {
			$selectPromoteur->where("I.date_etablissement=?",$data["date_etablissement"]);
		}
		if( isset($data["lieu_etablissement"])) {
			$selectPromoteur->where("I.lieu_etablissement=?",$data["lieu_etablissement"]);
		}
		if( isset($data["organisme_etablissement"])) {
			$selectPromoteur->where("I.organisme_etablissement=?",$data["organisme_etablissement"]);
		}
		if( isset($data["name"]) && !empty($data["name"]) && (null!==$data["name"])){
			$data["name"]  = str_replace('"', "", $data["name"]);
			$data["name"]  = str_replace("'", "", $data["name"]);
			$data["name"]  = str_replace("-", " ",$data["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($data["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom          = $searchSpaceArray[0];
				$prenom       = str_replace($nom,"", $data["name"]);
			}
			if( empty( $nom ) ) {
				$selectPromoteur->where(new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectPromoteur->where(new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($data["lastname"]) && !empty($data["lastname"]) && (null!==$data["lastname"])){
			$selectPromoteur->where("D.lastname LIKE ?","%".strip_tags($data["lastname"])."%")->orWhere("D.firstname LIKE ?","%".strip_tags($data["lastname"])."%");
		}
		if(isset($data["firstname"]) && !empty($data["firstname"]) && (null!==$data["firstname"])){
			$selectPromoteur->where("D.firstname LIKE ?","%".$data["firstname"]."%")->orWhere("D.lastname LIKE ?","%".$data["firstname"]."%");
		}
		
		return $dbAdapter->fetchRow($selectPromoteur, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function createCode()
	{
		$table                  = $this->getTable();
		$dbAdapter              = $table->getAdapter();
		$tablePrefix            = $table->info("namePrefix");
		
		$selectPromoteur        = $dbAdapter->select()->from(array("D" => $table->info("name")),array("total"=>"COUNT(D.demandeurid)"));
		$nbreTotal              = $dbAdapter->fetchOne($selectPromoteur)+1;
		$newCodePromoteur       = sprintf("Prom-%06d", $nbreTotal );
		while($existPromoteur   = $this->findRow($newCodePromoteur, "code", null, false)) {
			  $nbreTotal++;
			  $newCodePromoteur = sprintf("Prom-%06d", $nbreTotal );
		}
		
		return $newCodePromoteur;
	}
	
	public function documents($demandeurid=null)
	{
		if( !$demandeurid )  {
			 $demandeurid  = $this->demandeurid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                                 ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","C.icon"))
				                                 ->join(array("RD"=> $tablePrefix."reservation_demandes_documents"),"RD.documentid=D.documentid",array("RD.demandeid","RD.libelle"))
				                                 ->where("RD.demandeurid=?", $demandeurid  );
		$selectDocuments->order(array("RD.demandeid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	public function demandes($demandeurid=0)
	{
		if(!$demandeurid ) {
			$demandeurid  = $this->demandeurid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		
		$selectDemandes   = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"))
												->join(array("T"=> $tablePrefix."reservation_demandes_types")      ,"T.typeid=D.typeid", array("typeDemande"=>"T.libelle"))
												->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.entrepriseid=D.entrepriseid",array("E.nomcommercial","E.sigle","E.denomination"))
												->join(array("S"=> $tablePrefix."reservation_demandes_statuts")    ,"S.statutid=D.statutid", array("statut"=>"S.libelle"))
												->where("D.demandeurid=?", $demandeurid);
												
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);										
	}
	
	public function identite($identityid=0) 
	{
		if(!$identityid ) {
			$identityid   = $this->identityid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectIdentite   = $dbAdapter->select()->from(    array("I"=> $tablePrefix."reservation_demandeurs_identite") ,array("numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->joinLeft(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid", array("typePiece"  =>"T.libelle"))
												->where("I.identityid=?", $identityid);
	
	    return $dbAdapter->fetchRow($selectIdentite, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function getList($filters = array() , $pageNum = 0 , $pageSize = 0, $orders=array("D.creationdate DESC","D.promoteurid DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectPromoteurs = $dbAdapter->select()->from(    array("D"=> $tablePrefix."reservation_promoteurs"))
		                                        ->join(    array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("typePieceId"=>"I.typeid","numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->joinLeft(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"));
		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "",  $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "",  $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ", $filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["searchQ"])));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeName= new Zend_Db_Expr("MATCH(D.lastname,D.firstname,D.numidentite) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectPromoteurs->where("{$likeName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]  = str_replace('"', "", $filters["name"]);
			$filters["name"]  = str_replace("'", "", $filters["name"]);
			$filters["name"]  = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom          = $searchSpaceArray[0];
				$prenom       = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectPromoteurs->where(new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectPromoteurs->where(new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) && (null!==$filters["lastname"])){
			$selectPromoteurs->where("D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")->orWhere("D.firstname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if(isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectPromoteurs->where("D.firstname LIKE ?","%".$filters["firstname"]."%")->orWhere("D.lastname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["identityid"]) && intval($filters["identityid"])){
			$selectPromoteurs->where("I.identityid=?", intval($filters["identityid"]));
		}
		if( isset($filters["numidentite"]) && !empty($filters["numidentite"]) && (null!==$filters["numidentite"])){
			$selectPromoteurs->where("D.numidentite=?",$filters["numidentite"]);
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"])){
			$selectPromoteurs->where("I.numero=?",$filters["numero"]);
		}
		if(isset($filters["numidentitetype"]) && !empty($filters["numidentitetype"]) && (null!==$filters["numidentitetype"])){
			$selectPromoteurs->where("T.libelle=?",$filters["numidentitetype"]);
		}
		if(isset($filters["identitetypeid"]) && intval($filters["identitetypeid"])){
			$selectPromoteurs->where("I.typeid=?",intval($filters["numidentitetype"]));
		}
		if(isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectPromoteurs->where("D.email=?",$filters["email"]);
		}
		if(isset($filters["country"]) && !empty($filters["country"]) && (null!==$filters["country"])){
			$selectPromoteurs->where("D.country=?",$filters["country"]);
		}
		if(isset($filters["sexe"]) && !empty($filters["sexe"]) && (null!==$filters["sexe"])){
			$selectPromoteurs->where("D.sexe=?",$filters["sexe"]);
		}
	    if(isset($filters["telephone"]) && !empty($filters["telephone"]) && (null!==$filters["telephone"])){
			$selectPromoteurs->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}
		if(isset($filters["domicile"]) && !empty($filters["domicile"]) && (null!==$filters["domicile"])){
			$selectPromoteurs->where($dbAdapter->quote("D.tel_dom=".$filters["domicile"]));
		}	
		if( isset($filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectPromoteurs->where("D.creationdate>= ?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"])  && intval($filters["periode_end"])) {
			$selectPromoteurs->where("D.creationdate<= ?", intval($filters["periode_end"]));
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectPromoteurs->limitPage($pageNum , $pageSize);
		}
		if( is_array($orders) && count($orders)) {
			$selectPromoteurs->order($orders);
		} elseif(is_string($orders)) {
			$selectPromoteurs->order(array(sprintf("%s DESC", strip_tags($orders) )));
		}
		
		return $dbAdapter->fetchAll($selectPromoteurs , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{		
		$modelTable        = $this->getTable();
		$dbAdapter         = $modelTable->getAdapter();
		$tablePrefix       = $modelTable->info("namePrefix");
		
		$selectPromoteurs  = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_promoteurs"))
		                                         ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", null)
												 ->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , null);
		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ", $filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["searchQ"])));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeName= new Zend_Db_Expr("MATCH(D.lastname,D.firstname) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectPromoteurs->where("{$likeName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]  = str_replace('"', "", $filters["name"]);
			$filters["name"]  = str_replace("'", "", $filters["name"]);
			$filters["name"]  = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom          = $searchSpaceArray[0];
				$prenom       = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectPromoteurs->where(new Zend_Db_Expr("D.lastname  LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectPromoteurs->where(new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) && (null!==$filters["lastname"])){
			$selectPromoteurs->where("D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")->orWhere("D.firstname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectPromoteurs->where("D.firstname LIKE ?","%".$filters["firstname"]."%")->orWhere("D.lastname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectPromoteurs->where("D.email=?",$filters["email"]);
		}
		if( isset($filters["country"]) && !empty($filters["country"]) && (null!==$filters["country"])){
			$selectPromoteurs->where("D.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"]) && !empty($filters["sexe"]) && (null!==$filters["sexe"])){
			$selectPromoteurs->where("D.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"]) && (null!==$filters["telephone"])){
			$selectPromoteurs->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}	
		if( isset($filters["identityid"]) && intval($filters["identityid"])){
			$selectPromoteurs->where("I.identityid=?", intval($filters["identityid"]));
		}
		if( isset($filters["numidentite"]) && !empty($filters["numidentite"]) && (null!==$filters["numidentite"])){
			$selectPromoteurs->where("D.numidentite=?",$filters["numidentite"]);
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"])){
			$selectPromoteurs->where("I.numero=?",$filters["numero"]);
		}
		if(isset($filters["numidentitetype"]) && !empty($filters["numidentitetype"]) && (null!==$filters["numidentitetype"])){
			$selectPromoteurs->where("T.libelle=?",$filters["numidentitetype"]);
		}
		if(isset($filters["identitetypeid"]) && intval($filters["identitetypeid"])){
			$selectPromoteurs->where("I.typeid=?",intval($filters["numidentitetype"]));
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectPromoteurs);
		$rowCount          = intval(count($dbAdapter->fetchAll($selectPromoteurs)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator = new Zend_Paginator($paginationAdapter);
		 
		return $paginator;		
	}
	 
}


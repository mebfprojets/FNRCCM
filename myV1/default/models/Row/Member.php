<?php

class Model_Member extends Sirah_Model_Default
{
	
	public function accountidByEmail($email)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		
		$selectAccount  = $dbAdapter->select()->from(array("U"=> $tablePrefix."system_users_account"),array("U.userid"))->where("U.email=?",stripslashes($email));
	    return $dbAdapter->fetchOne($selectAccount);
	}
	
	public function accountidByUsername($username)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		
		$selectAccount  = $dbAdapter->select()->from(array("U"=> $tablePrefix."system_users_account"),array("U.userid"))->where("U.username=?",strip_tags(stripslashes($username)));
	    return $dbAdapter->fetchOne($selectAccount);
	}
	
	public function count( $annee = 0, $periodstart = 0, $periodend = 0)
	{
		$table         = $this->getTable();		
		$selectBilan   = $table->select();
		$valNbreTotal  = new Zend_Db_Expr("COUNT(memberid)");
		
		$selectBilan->from( $table,array("nbreTotal"=>$valNbreTotal));
		
		if( intval( $annee )) {
			$selectBilan->where("FROM_UNIXTIME(creationdate,'%Y')=?", intval($annee));
		}
		if( intval( $periodend )){
			$selectBilan->where("creationdate <= ? " , intval($periodend));
		}
		if( intval($periodstart) ){
			$selectBilan->where("creationdate >= ? " , intval($periodstart));
		}		
		$row = $table->fetchRow($selectBilan);
		return ($row)?$row->nbreTotal : 0;
	}
	
	public function accountid($memberid=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
 
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		$selectMemberId = $dbAdapter->select()->from(array("M"=>$tableName),array("M.accountid"));
		return $dbAdapter->fetchOne($selectMemberId);
	}
	
	public function account($accountid=0,$memberid=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
 
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		$selectAccount  = $dbAdapter->select()->from(array("U"=>$tablePrefix."system_users_account"));
		if( intval($accountid) ) {
			$selectAccount->where("U.userid=?", intval($accountid));
		}	
        if( intval($memberid) ) {
			$selectAccount->join(array("M"=>$tableName),"M.accountid=U.userid",array("M.accountid"))
			              ->where("M.accountid=?",intval($accountid))
			              ->where("M.memberid=?" ,intval($memberid));
		}			
		return $dbAdapter->fetchRow($selectAccount,array(),Zend_Db::FETCH_ASSOC);
	}
	
	public function demandeur($memberid=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
 
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		$selectDemandeur= $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandeurs"))
		                                      ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("I.typeid","numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
											  ->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"))
											  ->join(array("M"=> $tableName),"M.accountid=D.accountid",null)
											  ->where("M.memberid=?", intval($memberid));
	    return $dbAdapter->fetchRow($selectDemandeur,array(), Zend_Db::FETCH_OBJ);	
	}
	
	public function fromuser($accountid=0)
	{
		if(!intval( $accountid ) ) {
			$me       = Sirah_Fabric::getUser();
			$accountid= $me->userid;
		}
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");		
		$selectMember = $dbAdapter->select()->from(array("C"=>$tablePrefix."rccm_members"),array("C.memberid","C.lastname","C.firstname","C.code","C.tel1","C.tel2","C.accountid","C.email","C.groupid","C.birthday","C.birthaddress","C.sexe","C.nationalite","C.matrimonial"))
	                                        ->where("C.accountid=?", intval($accountid));
 								
		return $dbAdapter->fetchRow($selectMember,array(), Zend_Db::FETCH_OBJ);									   
	}
		
	public function createCode()
	{
		$table               = $this->getTable();
		$dbAdapter           = $table->getAdapter();
		$tablePrefix         = $table->info("namePrefix");
		
		$selectMembers       = $dbAdapter->select()->from(array("C" => $table->info("name")),array("total"=>"COUNT(C.memberid)"));
		$nbreTotal           = $dbAdapter->fetchOne($selectMembers)+1;
		$newCodeMember       = sprintf("Cust-%06d", $nbreTotal );
		while($existMember   = $this->findRow($newCodeMember, "code", null, false)) {
			  $nbreTotal++;
			  $newCodeMember = sprintf("Cust-%06d", $nbreTotal );
		}
		
		return $newCodeMember;
	}
	
	public function billing_address($accountid=0,$format = "array")
	{		
		if(!intval($accountid) ) {
			$me        = Sirah_Fabric::getUser();
		    $accountid = $me->userid;
		}
		$fetchMode     = ($format=="array")? Zend_Db::FETCH_ASSOC : 5;
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectAddress = $dbAdapter->select()->from(array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"))
		                                     ->where("AD.accountid=?",intval($accountid ));									   
        return 	$dbAdapter->fetchRow($selectAddress, array() ,$fetchMode);
	}
	
	public function invoices($memberid = null,$limit=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");		
		$selectInvoices = $dbAdapter->select()->from(    array("F" => $tablePrefix."erccm_vente_commandes_invoices"))
		                                      ->join(    array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"),"AD.invoiceid=F.invoiceid", array("AD.address","AD.city","AD.country","AD.email","AD.phone","AD.customerName"))											  
											  ->join(    array("C" => $tablePrefix."erccm_vente_commandes"),"C.commandeid=F.commandeid", array("numcommande"=>"C.ref","commande"=>"C.ref"))
											  ->joinLeft(array("S" => $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("statut"=>"S.libelle"))
											  ->where(  "F.memberid=?" ,intval($memberid))
											  ->orWhere("C.memberid=?" ,intval($memberid))
											  ->order(array("F.date DESC"));
	    if( intval($limit) ) {
			$selectInvoices->limitPage(1, $limit);
		}
	   return $dbAdapter->fetchAll($selectInvoices, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function commandes($memberid = null,$limit=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");		
		$selectCommandes= $dbAdapter->select()->from(array("C"=> $tablePrefix."erccm_vente_commandes"),array("C.commandeid","C.date","numero"=>"C.ref","C.ref","C.valeur","C.valeur_ttc","C.valeur_ht","C.statutid","dateCommande"=> "FROM_UNIXTIME(C.date,'%d/%m/%Y')"))
		                                      ->join(array("M"=> $tablePrefix."rccm_members"),"M.memberid=C.memberid", array("member"=>new Zend_Db_Expr("CONCAT_WS(' ',M.lastname,M.firstname)"),"M.tel1"))
											  ->join(array("S"=> $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("statut"=>"S.libelle"))
											  ->where("C.memberid=?",intval($memberid))
											  ->order(array("C.validated ASC","C.date DESC"))
											  ->group(array("C.memberid","C.commandeid"));
	    if( intval($limit) ) {
			$selectCommandes->limitPage(1, $limit);
		}
		return $dbAdapter->fetchAll($selectCommandes, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function paiements($memberid = null,$limit=0)
	{
		if( null == $memberid ) {
			$memberid   = $this->memberid;
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		
		$selectPaiements= $dbAdapter->select()->from(array("P" => $tablePrefix."erccm_vente_commandes_paiements"), array("P.paiementid","P.numero","P.montant", "P.totalAPayer","P.reste","P.totalPaid","date_paiement"=> "FROM_UNIXTIME(P.date,'%d/%m/%Y')"))
		                                      ->join(array("C" => $tablePrefix."erccm_vente_commandes"),"C.commandeid=P.commandeid", array("numcommande"=>"C.ref","C.memberid" ))
		                                      ->join(array("M" => $tablePrefix."rccm_members"),"M.memberid=P.memberid", array("member"=>new Zend_Db_Expr("CONCAT_WS(' ',M.lastname,M.firstname)"),"M.tel1"))
											  ->join(array("S" => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=P.statutid", array("statut"=>"S.libelle"))
											  ->join(array("MP"=> $tablePrefix."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid", array("transid"=>"MP.numero","trans_id"=>"MP.numero"))
											  ->where("P.memberid=?",intval($memberid))
											  ->order(array("P.validated ASC","P.date DESC"))
											  ->group(array("P.memberid","P.commandeid","P.paiementid"));
		if( intval($limit) ) {
			$selectPaiements->limitPage(1, $limit);
		}			
	    return $dbAdapter->fetchAll($selectPaiements, array(), Zend_Db::FETCH_ASSOC);
	}
		
	public function documents($memberid = null,$limit=10)
	{
		if( null == $memberid ) {
			$memberid    = $this->memberid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","catid"=>"D.category","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                               ->joinLeft(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("categorie"=>"C.libelle","C.icon"))
				                               ->join(array("M" => $tablePrefix."rccm_members"),"M.accountid=D.userid", array("member"=>new Zend_Db_Expr("CONCAT_WS(' ',M.lastname,M.firstname)"),"M.tel1"))  
				                               ->where("M.memberid=?", $memberid  );
		$selectDocuments->order(array("D.creationdate DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments,array(), Zend_Db::FETCH_ASSOC ); 
	}
	
 
	public function getList($filters= array(), $pageNum = 0 , $pageSize = 0 , $orders = array("C.memberid DESC","C.lastname ASC","C.firstname ASC"))
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectMembers= $dbAdapter->select()->from(    array("C"=> $table->info("name")))
		                                    ->join(    array("U"=> $tablePrefix."system_users_account"),"U.userid=C.accountid", array("U.userid","U.phone1","U.phone2","U.username","U.expired","U.blocked","U.locked","U.admin","U.registeredDate","U.lastConnectedDate","U.lastUpdatedDate","U.lastIpAddress","U.lastHttpClient","U.nb_connections"))
		                                    ->joinLeft(array("G"=> $tablePrefix."rccm_members_groups") ,"G.groupid=C.groupid" , array("groupe"=>"G.libelle"));	
		if( isset($filters["code"]) && !empty($filters["code"])){
			$selectMembers->where("C.code=?" , strip_tags($filters["code"]));
		}
		if( isset($filters["identifiant"]) && !empty($filters["identifiant"])){
			$likeLastname       = new Zend_Db_Expr("C.lastname  LIKE '%".strip_tags($filters["identifiant"])."%'");
			$likeFirstname      = new Zend_Db_Expr("C.firstname LIKE '%".strip_tags($filters["identifiant"])."%'");
			$likeCode           = new Zend_Db_Expr("C.code      LIKE '%".strip_tags($filters["identifiant"])."%'");
			$selectMembers->where("{$likeLastname} OR {$likeFirstname} OR {$likeCode}");
		}
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst .="+".$searchWord."* ";
				}
			}
			$likeMemberName     = new Zend_Db_Expr("MATCH(C.lastname,C.firstname,C.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectMembers->where("{$likeMemberName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["name"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeMemberName  = new Zend_Db_Expr("MATCH(C.lastname,C.firstname,C.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectMembers->where("{$likeMemberName}");
		}
		if( isset($filters["numpass"]) && !empty($filters["numpass"])){
			$selectMembers->where("C.passport LIKE ? ", "%".strip_tags($filters["numpass"])."%");
		}
		if(isset($filters["lastname"]) && !empty($filters["lastname"])){
			$selectMembers->where("C.lastname LIKE ?","%".strip_tags($filters["lastname"])."%") ;
		}
		if(isset($filters["firstname"]) && !empty($filters["firstname"])){
			$selectMembers->where("C.firstname LIKE ?" , "%".strip_tags($filters["firstname"])."%");
		}
		if(isset($filters["email"]) && !empty($filters["email"])){
			$selectMembers->where("C.email = ? ", strip_tags($filters["email"]));
		}
		if(isset($filters["country"]) && !empty($filters["country"])){
			$selectMembers->where("C.country = ? " , strip_tags($filters["country"]));
		}
		if(isset($filters["telephone"]) && !empty($filters["telephone"])){
			$selectMembers->where( "C.tel1 LIKE ?", strip_tags($filters["telephone"])."%")
			             ->orWhere("C.tel2 LIKE ?", strip_tags($filters["telephone"])."%");
		}
		if(isset($filters["nationalite"]) && !empty($filters["nationalite"])){
			$selectMembers->where("C.nationalite=?", strip_tags($filters["nationalite"]));
		}
		if( isset($filters["creatorid"]) && intval( $filters["creatorid"] )){
			$selectMembers->where("C.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectMembers->where("C.creationdate>= ?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"])  && intval($filters["periode_end"])) {
			$selectMembers->where("C.creationdate<= ?", intval($filters["periode_end"]));
		}
		if( isset($filters["groupid"]) && intval( $filters["groupid"] )){
			$selectMembers->where("C.groupid = ? ", intval( $filters["groupid"] ) );
		}
		if( isset($filters["entrepriseid"]) && intval( $filters["entrepriseid"] )){
			$selectMembers->where("C.entrepriseid = ? ", intval( $filters["entrepriseid"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectMembers->limitPage( $pageNum , $pageSize);
		}
		if(!empty( $orders ) ) {
			$selectMembers->order($orders);
		}	
		//var_dump($selectMembers->__toString()); die();
		return $dbAdapter->fetchAll( $selectMembers, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
	    $table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectMembers= $dbAdapter->select()->from(array("C"=> $table->info("name")), array("C.memberid"))
		                                    ->joinLeft(array("G"=> $tablePrefix."rccm_members_groups"),"G.groupid=C.groupid", null);	
		if( isset($filters["code"]) && !empty($filters["code"])){
			$selectMembers->where("C.code=?" , strip_tags($filters["code"]));
		}
		if( isset($filters["identifiant"]) && !empty($filters["identifiant"])){
			$likeLastname  = new Zend_Db_Expr("C.lastname  LIKE '%".strip_tags($filters["identifiant"])."%'");
			$likeFirstname = new Zend_Db_Expr("C.firstname LIKE '%".strip_tags($filters["identifiant"])."%'");
			$likeCode      = new Zend_Db_Expr("C.code      LIKE '%".strip_tags($filters["identifiant"])."%'");
			$selectMembers->where("{$likeLastname} OR {$likeFirstname} OR {$likeCode}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
			$likeLastname  = new Zend_Db_Expr("C.lastname  LIKE '%".strip_tags($filters["name"])."%'");
			$likeFirstname = new Zend_Db_Expr("C.firstname LIKE '%".strip_tags($filters["name"])."%'");
			$likeCode      = new Zend_Db_Expr("C.code      LIKE '%".strip_tags($filters["name"])."%'");
			$selectMembers->where("{$likeLastname} OR {$likeFirstname} OR {$likeCode}");
		}
		if( isset($filters["numpass"]) && !empty($filters["numpass"])){
			$selectMembers->where("C.passport LIKE ? ", "%".strip_tags($filters["numpass"])."%");
		}
		if(isset($filters["lastname"]) && !empty($filters["lastname"])){
			$selectMembers->where("C.lastname LIKE ?","%".strip_tags($filters["lastname"])."%") ;
		}
		if(isset($filters["firstname"]) && !empty($filters["firstname"])){
			$selectMembers->where("C.firstname LIKE ?" , "%".strip_tags($filters["firstname"])."%");
		}
		if(isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectMembers->where("C.email = ? ", strip_tags($filters["email"]));
		}
		if(isset($filters["country"]) && !empty($filters["country"])){
			$selectMembers->where("C.country = ? " , strip_tags($filters["country"]));
		}
		if(isset($filters["telephone"]) && !empty($filters["telephone"])){
			$selectMembers->where(  "C.tel1 LIKE ?", strip_tags($filters["telephone"])."%")
			             ->orWhere("C.tel2 LIKE ?", strip_tags($filters["telephone"])."%");
		}
		if(isset($filters["nationalite"]) && !empty($filters["nationalite"]) && (null!==$filters["nationalite"])){
			$selectMembers->where("C.nationalite = ? ", strip_tags($filters["nationalite"]));
		}
		if( isset($filters["creatorid"]) && intval( $filters["creatorid"] )){
			$selectMembers->where("C.creatorid = ? ", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["groupid"]) && intval( $filters["groupid"] )){
			$selectMembers->where("C.groupid = ? ", intval( $filters["groupid"] ) );
		}
		if( isset($filters["entrepriseid"]) && intval( $filters["entrepriseid"] )){
			$selectMembers->where("C.entrepriseid = ? ", intval( $filters["entrepriseid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectMembers);
		$rowCount          = intval(count($dbAdapter->fetchAll($selectMembers)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
			
		return $paginator;
	}	
}
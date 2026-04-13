<?php

class Model_Commandefacture extends Sirah_Model_Default
{
	
	
	public function getStatut($invoiceid=0)
	{
		if(!intval($invoiceid) ) {
		    $invoiceid = $this->invoiceid;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectStatut  = $dbAdapter->select()->from(array("F"=> $tableName),array("F.invoiceid"))
											 ->join(array("S"=> $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=F.statutid", array("S.*"))
											 ->where("F.invoiceid=?",intval($invoiceid));									   
        return 	$dbAdapter->fetchRow($selectStatut, array() ,5);
	}
	
	public function billing_address($invoiceid=0,$format = "array")
	{
		if(!intval($invoiceid) ) {
		    $invoiceid = $this->invoiceid;
		}
		$fetchMode     = ($format=="array")? Zend_Db::FETCH_ASSOC : 5;
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectAddress = $dbAdapter->select()->from(array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"))
		                                     ->where("AD.invoiceid=?",intval($invoiceid));
									   
        return 	$dbAdapter->fetchRow($selectAddress, array() ,$fetchMode);
	}
	
	public function documents($invoiceids=0)
	{
	    if( is_string($invoiceids)) {
			$invoiceids  = array($invoiceids);
		}
		if( empty($invoiceids) || !is_array($invoiceids)) {
			$invoiceids  = array(0=>$this->invoiceid);
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");		
		$selectDocuments = $dbAdapter->select()->from(array("D"=> $tablePrefix."system_users_documents"))
		                                       ->join(array("F"=> $tableName), "F.documentid=D.documentid", null )
		                                       ->where("F.invoiceid IN (?)", array_map("intval", $invoiceids));
									   
        return 	$dbAdapter->fetchAll($selectDocuments, array() , Zend_Db::FETCH_ASSOC);		
	}

    public function document($invoiceid=0)
	{
	    if(!intval($invoiceid) ) {
		    $invoiceid   = $this->invoiceid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		
		$selectDocument  = $dbAdapter->select()->from(array("C"=>$tableName),array("C.invoiceid"))
											   ->join(array("D"=>$tablePrefix."system_users_documents"),"D.documentid=C.documentid", array("D.documentid","D.filename","D.filepath","D.filextension","D.filesize"))
                                   			   ->where("C.invoiceid=?",intval($invoiceid));
        return 	$dbAdapter->fetchRow($selectDocument, array() , Zend_Db::FETCH_OBJ);		
	}
	
	public function generateKey( $annee = null, $month = null, $type=1)
	{
		if( null == $annee ) {
			$annee        = date("Y" );
		}
		if( null == $month ) {
			$month        = date("n");
		}
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
        $countInvoices    = new Zend_Db_Expr("COUNT(F.invoiceid)");
		$selectFacture    = $dbAdapter->select()->from(array("F"=> $table->info("name")) , $countInvoices)
		                                        ->where("FROM_UNIXTIME(F.date,'%Y-%m')=?", sprintf("%04d-%02d", $annee, $month))
												->group(array("FROM_UNIXTIME(F.date,'%Y-%m')"));
 								
		$nbreFacture      = $dbAdapter->fetchOne( $selectFacture );
		
		$numFactureInc    = ($nbreFacture +3);
		$numFacture       =  sprintf("%04d", $numFactureInc )."/". sprintf("%02d/%04d", $month, $annee);
        while( $this->findRow($numFacture,"numero", null, false) ) {
			   $numFactureInc = $numFactureInc+2;
			   $numFacture =  sprintf("%04d", $numFactureInc )."/". sprintf("%02d/%04d", $month, $annee);
		}			
		return $numFacture;
	}
	
	public function count($annee = null, $month = null, $type = 1)
	{		
		if( null == $annee ) {
			$annee        = date("Y" );
		}
		if( null == $month ) {
			$month        = date("n");
		}
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
        $countInvoices    = new Zend_Db_Expr("COUNT(F.invoiceid)");
		$selectFactures   = $dbAdapter->select()->from(array("F"=> $table->info("name")) , $countInvoices)
		                                        ->where("FROM_UNIXTIME(F.date,'%Y-%m')=?", sprintf("%04d-%02d", $annee, $month))
												->group(array("FROM_UNIXTIME(F.date , '%Y-%m')"));
 								
		$nbreFacture      = intval($dbAdapter->fetchOne( $selectFactures));
		return $nbreFacture;
	}
	
	public function getList( $filters = array(),$pageNum=0,$pageSize=0, $orders=array("F.date DESC","C.date DESC"))
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");	
		$tableName        = $table->info("name");
		$selectInvoices   = $dbAdapter->select()->from(    array("F" =>$tableName))
		                                        ->join(    array("C" =>$tablePrefix."erccm_vente_commandes")          ,"C.commandeid=F.commandeid", array("statutCommande"=>"C.statutid","numcommande"=>"C.ref","commande"=>"C.ref","C.ref","dateCommande"=>"C.date","C.validated","C.closed","C.canceled","C.valeur","C.valeur_ht","C.valeur_ttc","C.valeur_sub_total","C.apply_tva","C.apply_bic","C.valeur_tva","C.val_tva","C.val_bic","C.valeur_bic","C.valeur_remise","C.totalPaid","C.frais","C.observation"))
		                                        ->join(    array("P" =>$tablePrefix."erccm_vente_commandes_paiements"),"P.invoiceid=F.invoiceid"  , array("P.num_transaction","P.num_commande","P.frais_transaction","datePaiement"=>"P.date"))
												->join(    array("MP"=>$tablePrefix."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid AND MP.numero=P.num_commande",array("modePaiement"=>"MP.libelle","MP.banque"))
												->join(    array("A" =>$tablePrefix."erccm_vente_commandes_invoices_addresses"),"A.invoiceid=F.invoiceid",array("A.customerName","A.customerLastName","A.customerFirstName","customerEmail"=>"A.email","A.address","customerAddress"=>"A.address","customerPhone"=>"A.phone","A.phone","A.zip","A.city"))
												->join(    array("M" =>$tablePrefix."rccm_members"),"M.memberid=F.memberid",array("M.passport","M.birthday","M.birthaddress","M.nationalite","M.civilite","M.name"))
												->join(    array("U" =>$tablePrefix."system_users_account"),  "U.userid=F.accountid"        , array("U.userid","U.lastname","U.firstname","U.email","U.username","U.avatar","U.phone1","U.phone2","U.sexe"))
												->join(    array("DM"=>$tablePrefix."reservation_demandeurs"),"DM.accountid=F.accountid"    , array("DM.demandeurid","DM.identityid"))
												->join(    array("S" =>$tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=F.statutid", array("statut"=>"S.libelle"))
												->joinLeft(array("D" =>$tablePrefix."system_users_documents"),"D.documentid=F.documentid"   , array("documentname"=>"D.filename","documentpath"=>"D.filepath"))
												->where("C.validated=1")
												->where("P.validated=1");
	
	    if( isset($filters["date"])        && !empty($filters["date"]) && Zend_Date::isDate($filters["date"],"Y-m-d")) {
			$selectInvoices->where("FROM_UNIXTIME(F.date ,'%Y-%m-%d')=?", $filters["date"]);
		} 
	    if( isset($filters["montant"])     && floatval($filters["montant"]) ) {
			$selectInvoices->where("F.montant<= ?", floatval($filters["montant"]));
		}
		if( isset($filters["valeur"])      &&  floatval($filters["valeur"]) ) {
			$selectInvoices->where("F.montant = ?", floatval($filters["valeur"]));
		}
		if( isset($filters["numero"])      && !empty($filters["numero"])) {
			$selectInvoices->where("F.numero = ?", $filters["numero"]);
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectInvoices->where("C.ref= ? " , $filters["numcommande"]);
		}	    
		if( isset($filters["matricule"])   && !empty($filters["matricule"]) ){
			$selectInvoices->where("U.username = ?", trim( $filters["matricule"] )) ;
		}
		if( isset($filters["type"]) && !empty($filters["type"])){
			$likeType = new Zend_Db_Expr("F.type  LIKE \"%".$filters["type"]."%\"");
			$selectInvoices->where("{$likeType} ");
		}
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["searchQ"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeUserName     = new Zend_Db_Expr("MATCH(U.lastname,U.firstname,U.username) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$likeInvoiceRef   = new Zend_Db_Expr("F.numero LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeCommandeRef  = new Zend_Db_Expr("C.ref    LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$selectInvoices->where("{$likeUserName} OR {$likeInvoiceRef} OR {$likeCommandeRef}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["name"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeUserName  = new Zend_Db_Expr("MATCH(U.lastname,U.firstname,U.username) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectInvoices->where("{$likeUserName}");
		}
		if( isset($filters["lastname"])   && !empty($filters["lastname"])){
			$selectInvoices->where("U.lastname LIKE ?","%".strip_tags($filters["lastname"])."%") ;
		}
		if( isset($filters["firstname"])  && !empty($filters["firstname"])){
			$selectInvoices->where("U.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])      && !empty($filters["email"])){
			$selectInvoices->where("U.email=?",$filters["email"]);
		}
		if( isset($filters["country"])    && !empty($filters["country"])){
			$selectInvoices->where("U.country=?" , $filters["country"]);
		}	 
		if( isset($filters["userid"])     && intval($filters["userid"])) {
			$selectInvoices->where("F.accountid=?" , intval($filters["userid"]));
		}
		if( isset($filters["accountid"])  && intval($filters["accountid"])) {
			$selectInvoices->where("F.accountid=?" , intval($filters["accountid"]));
		}
		if( isset($filters["memberid"])   && intval($filters["memberid"])) {
			$selectInvoices->where("F.memberid=?" , intval($filters["memberid"]));
		}
		if( isset($filters["commandeid"]) && intval($filters["commandeid"])) {
			$selectInvoices->where("F.commandeid=?" , intval($filters["commandeid"]));
		}
		if( isset($filters["statutid"])   && intval($filters["statutid"])) {
			$selectInvoices->where("F.statutid=?" , intval($filters["statutid"]));
		}
		if( isset($filters["invoiceids"]) && is_array( $filters["invoiceids"] )) {
			if( count( $filters["invoiceids"])) {
				$selectInvoices->where("F.invoiceid IN (?)", array_map("intval",$filters["invoiceids"]));
			}			
		}
		if( isset($filters["dailylist"])  && $filters["dailylist"]&& isset($filters["today"]) && Zend_Date::isDate($filters["today"], "Y-MM-dd")) {
			$selectInvoices->where(new Zend_Db_Expr("DATE(?)=DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d'))"), $filters["today"]);
		}
		if( isset($filters["weeklist"]) && $filters["weeklist"] && isset($filters["weekday"]) && Zend_Date::isDate($filters["weekday"], "Y-MM-dd")) {
			$selectInvoices->where(new Zend_Db_Expr("YEARWEEK(?)=YEARWEEK(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))"), $filters["weekday"]);
		}
		if( isset($filters["monthlist"]) && $filters["monthlist"] && isset($filters["monthday"]) && Zend_Date::isDate($filters["monthday"], "Y-MM-dd")) {
		    $pageNum   = $pageSize = 0;
			$selectInvoices->where(new Zend_Db_Expr("MONTH(?)=MONTH(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))"), $filters["monthday"])
			               ->where(new Zend_Db_Expr("YEAR(?)=YEAR(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))")  , $filters["monthday"]);
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && Zend_Date::isDate($filters["today"], "Y-MM-dd")) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectInvoices->where("FROM_UNIXTIME(F.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectInvoices->where("FROM_UNIXTIME(F.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectInvoices->where("F.date>=?", intval($filters["periode_start"]));
		}
	    if( isset($filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectInvoices->where("F.date<=?", intval($filters["periode_end"]  ));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectInvoices->where("F.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if(!empty( $orders ) ) {
			$selectInvoices->order( $orders );
		}
		//print_r($selectInvoices->__toString()); die();
		$selectInvoices->group(array("F.commandeid","F.invoiceid"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectInvoices->limitPage($pageNum , $pageSize);
		}
		/*if( isset($filters["date"]) && !empty($filters["date"]) && (null!=$filters["date"])){
		    print_r($selectInvoices->__toString());die();
		}*/
		return $dbAdapter->fetchAll( $selectInvoices , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");	
		$selectInvoices = $dbAdapter->select()->from(array("F" =>$table->info("name")), array("F.invoiceid"))
		                                      ->join(array("C" =>$tablePrefix."erccm_vente_commandes"), "C.commandeid=F.commandeid" ,null)
											  ->join(array("P" =>$tablePrefix."erccm_vente_commandes_paiements"),"P.invoiceid=F.invoiceid",null)
										      ->join(array("MP"=>$tablePrefix."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid AND MP.numero=P.num_commande",null)
											  ->join(array("S" =>$tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=F.statutid", array("statut"=>"S.libelle"))
											  ->join(array("M" =>$tablePrefix."rccm_members"),"M.memberid=F.memberid",null)
											  ->join(array("DM"=>$tablePrefix."reservation_demandeurs"),"DM.accountid=F.accountid",null)
		                                      ->join(array("U" =>$tablePrefix."system_users_account"),"U.userid=F.accountid",null);
											  	
	    if( isset($filters["date"])        && !empty($filters["date"]) && Zend_Date::isDate($filters["date"],"Y-m-d")) {
			$selectInvoices->where("FROM_UNIXTIME(F.date ,'%Y-%m-%d')=?", $filters["date"]);
		} 
	    if( isset($filters["montant"])     && floatval($filters["montant"]) ) {
			$selectInvoices->where("F.montant<= ?", floatval($filters["montant"]));
		}
		if( isset($filters["valeur"])      &&  floatval($filters["valeur"]) ) {
			$selectInvoices->where("F.montant = ?", floatval($filters["valeur"]));
		}
		if( isset($filters["numero"])      && !empty($filters["numero"])) {
			$selectInvoices->where("F.numero = ?", $filters["numero"]);
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectInvoices->where("C.ref= ? " , $filters["numcommande"]);
		}	    
		if( isset($filters["matricule"])   && !empty($filters["matricule"]) ){
			$selectInvoices->where("U.username = ?", trim( $filters["matricule"] )) ;
		}
		if( isset($filters["type"]) && !empty($filters["type"])){
			$likeType = new Zend_Db_Expr("F.type  LIKE \"%".$filters["type"]."%\"");
			$selectInvoices->where("{$likeType} ");
		}
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["searchQ"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeUserName     = new Zend_Db_Expr("MATCH(U.lastname,U.firstname,U.username) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$likeInvoiceRef   = new Zend_Db_Expr("F.numero LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeCommandeRef  = new Zend_Db_Expr("C.ref    LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$selectInvoices->where("{$likeUserName} OR {$likeInvoiceRef} OR {$likeCommandeRef}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["name"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeUserName     = new Zend_Db_Expr("MATCH(U.lastname,U.firstname,U.username) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectInvoices->where("{$likeUserName}");
		}
		if( isset($filters["lastname"])   && !empty($filters["lastname"])){
			$selectInvoices->where("U.lastname LIKE ?","%".strip_tags($filters["lastname"])."%") ;
		}
		if( isset($filters["firstname"])  && !empty($filters["firstname"])){
			$selectInvoices->where("U.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])      && !empty($filters["email"])){
			$selectInvoices->where("U.email=?",$filters["email"]);
		}
		if( isset($filters["country"])    && !empty($filters["country"])){
			$selectInvoices->where("U.country=?" , $filters["country"]);
		}	 
		if( isset($filters["userid"])     && intval($filters["userid"])) {
			$selectInvoices->where("F.accountid=?" , intval($filters["userid"]));
		}
		if( isset($filters["accountid"])  && intval($filters["accountid"])) {
			$selectInvoices->where("F.accountid=?" , intval($filters["accountid"]));
		}
		if( isset($filters["memberid"])   && intval($filters["memberid"])) {
			$selectInvoices->where("F.memberid=?" , intval($filters["memberid"]));
		}
		if( isset($filters["commandeid"]) && intval($filters["commandeid"])) {
			$selectInvoices->where("F.commandeid=?" , intval($filters["commandeid"]));
		}
		if( isset($filters["statutid"])   && intval($filters["statutid"])) {
			$selectInvoices->where("F.statutid=?" , intval($filters["statutid"]));
		}
		if( isset($filters["invoiceids"]) && is_array( $filters["invoiceids"] )) {
			if( count( $filters["invoiceids"])) {
				$selectInvoices->where("F.invoiceid IN (?)", array_map("intval",$filters["invoiceids"]));
			}			
		}
		if( isset($filters["dailylist"])  && $filters["dailylist"]&& isset($filters["today"]) && Zend_Date::isDate($filters["today"], "Y-MM-dd")) {
			$selectInvoices->where(new Zend_Db_Expr("DATE(?)=DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d'))"), $filters["today"]);
		}
		if( isset($filters["weeklist"]) && $filters["weeklist"] && isset($filters["weekday"]) && Zend_Date::isDate($filters["weekday"], "Y-MM-dd")) {
			$selectInvoices->where(new Zend_Db_Expr("YEARWEEK(?)=YEARWEEK(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))"), $filters["weekday"]);
		}
		if( isset($filters["monthlist"]) && $filters["monthlist"] && isset($filters["monthday"]) && Zend_Date::isDate($filters["monthday"], "Y-MM-dd")) {
		    $pageNum   = $pageSize = 0;
			$selectInvoices->where(new Zend_Db_Expr("MONTH(?)=MONTH(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))"), $filters["monthday"])
			               ->where(new Zend_Db_Expr("YEAR(?)=YEAR(DATE(FROM_UNIXTIME(F.date,'%Y-%m-%d')))")  , $filters["monthday"]);
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && Zend_Date::isDate($filters["today"], "Y-MM-dd")) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectInvoices->where("FROM_UNIXTIME(F.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectInvoices->where("FROM_UNIXTIME(F.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectInvoices->where("F.date>=?", intval($filters["periode_start"]));
		}
	    if( isset($filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectInvoices->where("F.date<=?", intval($filters["periode_end"]  ));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectInvoices->where("F.creatorid = ?", intval( $filters["creatorid"] ) );
		}		
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectInvoices);
		$rowCount          = intval(count($dbAdapter->fetchAll(  $selectInvoices)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
			
		return $paginator;
	}	
}


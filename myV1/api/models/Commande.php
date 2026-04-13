<?php

class Model_Commande extends Sirah_Model_Default
{
	
	public function documents($commandeid=null)
	{
		if(!$commandeid )  {
			$commandeid  = $this->commandeid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                               ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","C.icon"))
				                               ->join(array("CD"=> $tablePrefix."erccm_vente_commandes_documents"),"CD.documentid=D.documentid",array("CD.commandeid","CD.libelle"))
				                               ->where("CD.commandeid=?", $commandeid  );
		$selectDocuments->order(array("CD.commandeid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments,array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getStatut($commandeid=0)
	{
		if(!intval($commandeid) ) {
		    $commandeid= $this->commandeid;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectStatut  = $dbAdapter->select()->from(array("C" => $tableName),array("C.commandeid"))
											 ->join(array("S" => $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("S.*"))
											 ->where("C.commandeid=?",intval($commandeid));									   
        return 	$dbAdapter->fetchRow($selectStatut, array() ,5);
	}
	
	public function getBilanByProductype( $productcatid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$dbAdapter   = $table->getAdapter();
		
		$valTotalExpr= new Zend_Db_Expr("SUM(CL.valeur)");
		
		$selectBilan = $dbAdapter->select()->from(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),array("nbreTotal"=>"SUM(CL.qte)","valTotal" => $valTotalExpr))
		                                   ->join(array("C" => $tablePrefix."erccm_vente_products_categories"),"C.catid=CL.productcatid", array("C.catid","categorie"=>"C.libelle","C.libelle"))
										   ->join(array("CM"=> $tableName),"CM.commandeid=CL.commandeid", null)
										   ->where("CM.validated=1");
		if( intval($productcatid)) {
			$selectBilan->where("C.catid= ?", intval($productcatid));
		}
		$selectBilan->group(array("C.catid","CL.productcatid"))->order(array("C.libelle ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanByProduct( $productid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$dbAdapter   = $table->getAdapter();
		
		$valTotalExpr= new Zend_Db_Expr("SUM(CL.valeur)");
		
		$selectBilan = $dbAdapter->select()->from(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),array("nbreTotal"=> "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                   ->join(array("P" => $tablePrefix."erccm_vente_products"),"P.productid=CL.productid", array("P.productid","product"=> "P.libelle"))
										   ->join(array("CM"=> $tableName),"CM.commandeid=CL.commandeid", null)
										   ->where("CM.validated=1");
		if( intval($productid)) {
			$selectBilan->where("P.productid= ?", intval($productcatid));
		}
		$selectBilan->group(array("P.productid","CL.productid"))->order(array("P.libelle ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanTotal( $annee = 0, $periodstart = 0, $periodend = 0)
	{
		$table         = $this->getTable();		
		$selectBilan   = $table->select();
		$valTotalExpr  = new Zend_Db_Expr("SUM(valeur_ttc)");
		
		$selectBilan->from( $table,array("nbreTotal"=>"COUNT(commandeid)","valTotal"=>$valTotalExpr))->where("validated=1");
		
		if( intval( $annee )) {
			$selectBilan->where("FROM_UNIXTIME(date,'%Y')=?", intval($annee));
		}
		if( intval( $periodend )){
			$selectBilan->where("date <= ? " , intval($periodend));
		}
		if( intval($periodstart) ){
			$selectBilan->where("date >= ? " , intval($periodstart));
		}		
		return $table->fetchRow($selectBilan);
	}	
	
	public function getBilanStatut($statutid=0,$productcatid=0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$dbAdapter   = $table->getAdapter();
		$tableName   = $table->info("name");
		$valTotalExpr= new Zend_Db_Expr("SUM(C.valeur_ttc)");
		$selectBilan = $dbAdapter->select()->from(array("C"=> $tableName), array("nombre"=>new Zend_Db_Expr("COUNT(C.statutid)"), "valTotal" => $valTotalExpr))
		                                   ->join(array("S"=> $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("statut" => "S.libelle"));
        if( intval( $statutid )) {
			$selectBilan->where("C.statutid=?", intval($statutid));
		}
		$selectBilan->group(array("C.statutid","S.statutid"))->order(array("S.statutid ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanMember( $memberid = 0, $periodstart = 0, $periodend = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$dbAdapter   = $table->getAdapter();
		
		$valTotalExpr= new Zend_Db_Expr("SUM(CL.valeur)");
		
		$selectBilan = $dbAdapter->select()->from(array("C" => $tableName), array("C.memberid"))
		                                   ->join(array("L" => $tablePrefix."erccm_vente_products_member"), "L.memberid = C.memberid", array("member" => "L.libelle"))
		                                   ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"), "CL.commandeid = C.commandeid", array("nbreTotal" => "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                   ->join(array("P" => $tablePrefix."erccm_vente_products"), "P.productid=CL.productid", array("P.productid", "produit" => "P.libelle"));
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		if( intval( $periodend )){
			$selectBilan->where("C.date <=?", intval($periodend));
		}
		if( intval($periodstart) ){
			$selectBilan->where("C.date >=?", intval($periodstart));
		}
		$selectBilan->group(array("C.memberid","CL.productid"))->order(array("L.libelle ASC", "P.libelle ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
		 	
	public function getBilanAnnuel( $memberid = 0)
	{
		$table         = $this->getTable();
		$tablePrefix   = $table->info("namePrefix");
		$dbAdapter     = $table->getAdapter();
		$tableName     = $table->info("name");
	
		$valTotalExpr  = new Zend_Db_Expr("SUM(CL.valeur)");	
		$selectBilan   = $dbAdapter->select()->from(array("C" => $tableName), array("annee" => "FROM_UNIXTIME(C.date,'%Y')"))
		                                     ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"), "CL.commandeid=C.commandeid", array("nbreTotal" => "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                     ->join(array("P" => $tablePrefix."erccm_vente_products"), "P.productid=CL.productid", array("P.productid", "produit" => "P.libelle"))
											 ->where("C.validated=1");
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(C.date,'%Y') ASC"))->group(array("FROM_UNIXTIME(C.date, '%Y')"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanMensuel($annee = 0,  $memberid = 0)
	{
		$table         = $this->getTable();
		$tablePrefix   = $table->info("namePrefix");
		$dbAdapter     = $table->getAdapter();
	
		$valTotalExpr  = new Zend_Db_Expr("SUM(CL.valeur)");
	
		$selectBilan   = $dbAdapter->select()->from(array("C" => $table->info("name")), array("annee"=>"FROM_UNIXTIME(C.date, '%Y')","mois"=>"FROM_UNIXTIME(C.date, '%m')"))
		                                     ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid = C.commandeid", array("nbreTotal" => "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                     ->join(array("P" => $tablePrefix."erccm_vente_products"), "P.productid=CL.productid", array("P.productid","produit"=> "P.libelle"))
											 ->where("C.validated=1");
		if( intval( $annee )) {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y')=?", intval( $annee ));
		} else {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y')=?", date("Y"));
		}
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(C.date, '%m') ASC", "P.libelle ASC"))->group(array("FROM_UNIXTIME(C.date, '%Y')","FROM_UNIXTIME(C.date, '%m')", "CL.productid"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanWeek($mois = 0, $annee = 0,  $memberid = 0)
	{
		$table         = $this->getTable();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$dbAdapter     = $table->getAdapter();
	
		$valTotalExpr  = new Zend_Db_Expr("SUM(CL.valeur)");
	    $weekExpr      = new Zend_Db_Expr("FROM_DAYS(TO_DAYS(C.date) -MOD(TO_DAYS(C.date) -2, 7))");
		$selectBilan   = $dbAdapter->select()->from(array("C" => $tableName), array("annee"=> "FROM_UNIXTIME(C.date,'%Y')","mois"=>"FROM_UNIXTIME(C.date, '%m')", "week" => $weekExpr))
		                                     ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", array("nbreTotal" => "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                     ->join(array("P" => $tablePrefix."erccm_vente_products"),"P.productid=CL.productid", array("P.productid", "produit" => "P.libelle"))
											 ->where("C.validated=1");
		if( intval( $annee )) {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y')=?", intval( $annee ));
		} else {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y')=?", date("Y"));
		}
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(C.date,'%m') ASC"))->group(array("FROM_UNIXTIME(C.date,'%m') ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
		
	public function getBilanDay($day = null,  $memberid = 0)
	{
		$table         = $this->getTable();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$dbAdapter     = $table->getAdapter();
	
		$valTotalExpr  = new Zend_Db_Expr("SUM(CL.valeur)");
		$selectBilan   = $dbAdapter->select()->from(array("C" => $tableName), array("C.memberid"))
		                                     ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"), "CL.commandeid = C.commandeid", array("nbreTotal" => "SUM(CL.qte)", "valTotal" => $valTotalExpr))
		                                     ->join(array("P" => $tablePrefix."erccm_vente_products"), "P.productid = CL.productid", array("P.productid", "produit" => "P.libelle"))
											 ->where("C.validated=1");
		if( $day != null ) {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y-%m-%d')=?", $day);
		} else {
			$selectBilan->where("FROM_UNIXTIME(C.date,'%Y-%m-%d')=?", date("Y-m-d"));
		}
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(C.date,'%Y-%m-%d') DESC"))->group(array("FROM_UNIXTIME(C.date,'%Y-%m-%d')"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function autoNum()
	{
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		
		$selectOrders      = $dbAdapter->select()->from(array("C"=> $table->info("name")),array("total"=>"COUNT(C.commandeid)"));
		$nbreTotal         = intval($dbAdapter->fetchOne($selectOrders))+5;
		$newCodeOrder      = sprintf("Ord-%08d", $nbreTotal );
		while($existOrder  = $this->findRow($newCodeOrder, "ref", null, false)) {
			  $nbreTotal++;
			  $newCodeOrder= sprintf("Ord-%08d", $nbreTotal );
		}		
		return $newCodeOrder;
	}
	
	public function billing_address($commandeid=0,$format = "array")
	{
		if(!intval($commandeid) ) {
		    $commandeid= $this->commandeid;
		}
		$fetchMode     = ($format=="array")? Zend_Db::FETCH_ASSOC : 5;
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectAddress = $dbAdapter->select()->from(array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"))
		                                     ->where("AD.commandeid=?",intval($commandeid));									   
        return 	$dbAdapter->fetchRow($selectAddress, array() ,$fetchMode);
	}
	
	public function invoice($commandeid=0,$format = "array")
	{
		if(!intval($commandeid) ) {
		    $commandeid= $this->commandeid;
		}
		$fetchMode     = ($format=="array")? Zend_Db::FETCH_ASSOC : 5;
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectInvoice = $dbAdapter->select()->from(array("F" => $tablePrefix."erccm_vente_commandes_invoices"))
		                                     ->join(array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"),"AD.invoiceid=F.invoiceid", array("AD.address","AD.city","AD.country","AD.email","AD.phone","AD.customerName"))
											 ->join(array("S" => $tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=F.statutid", array("statut"=>"S.libelle"))
											 ->where("F.commandeid=?",intval($commandeid))
											 ->where("AD.commandeid=?",intval($commandeid));									   
        return 	$dbAdapter->fetchRow($selectInvoice, array() ,$fetchMode);
	}
	
	public function paiements( $commandeid=null, $orders = array("P.date DESC", "P.paiementid DESC"))
	{
		if( null == $commandeid ) {
			$commandeid = intval($this->commandeid);
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectPaiements= $dbAdapter->select()->from(array("P" => $tablePrefix."erccm_vente_commandes_paiements"), array("P.paiementid","P.commandeid","P.memberid","P.accountid","P.invoiceid","P.statutid","P.numero","P.libelle","P.observation","P.montant","P.totalAPayer","P.reste","P.modepaiement","P.modepaiementid","P.totalPaid","P.validated","P.canceled","P.date","date_paiement"=> "FROM_UNIXTIME(P.date,'%d/%m/%Y')","P.creationdate","P.creatorid","P.updatedate"))
		                                      ->join(array("MP"=> $tablePrefix."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid", array("transid"=>"MP.numero","trans_id"=>"MP.numero","trans_libelle"=>"MP.libelle","MP.bankid","MP.banque","MP.processed","MP.address"))
											  ->join(array("C" => $tablePrefix."erccm_vente_commandes"),"C.commandeid=P.commandeid", array("numcommande"=>"C.ref","C.memberid"))
		                                      ->join(array("M" => $tablePrefix."rccm_members"),"M.memberid=P.memberid", array("member"=>new Zend_Db_Expr("CONCAT_WS(' ',M.lastname,M.firstname)"),"M.tel1"))
											  ->join(array("S" => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=P.statutid", array("statut"=>"S.libelle"))
											  ->where("P.commandeid=?",intval($commandeid))
											  ->group(array("P.memberid","P.commandeid","P.paiementid"));
		if( is_array($orders) && count($orders) ) {
			$selectPaiements->order($orders);
		} else {
			$selectPaiements->order(array("P.paiementid DESC"));
		}			
	    return $dbAdapter->fetchAll($selectPaiements, array(), Zend_Db::FETCH_ASSOC);										  
	} 
	
	function getLastpaiement( $id = 0 )
	{
		if( !$id ) {
			 $id    = $this->id;
		}	
		$table      = $this->getTable();
		$dbAdapter  = $table->getAdapter();
		$prefixName = $table->info("namePrefix");
		$select     = $dbAdapter->select()->from( $prefixName."erccm_vente_commandes_paiements")->where("commandeid=?",$id);
		 
		return $dbAdapter->fetchRow( $select , array() , 5);	
	}
	
	function getRow( $id = null , $reference = null)
	{
		$table    = $this->_getTable();
		$select   = $table->select();
		$id       = intval($id);
		if( $id ) {
			$select->where("id=?" , $id);
			return $table->fetchRow($select);
		}
		if( !empty( $reference ) ) {
			$select->where("ref= ? " , $reference );
		} else {
			return;
		}	
		return $table->fetchRow($select);
	}
	
	public function products($commandeid=null, $productid=0) 
	{
		$commandeid     = intval($commandeid);
		if(!$commandeid ) {
			$commandeid = $this->commandeid;
		}		
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$prefixName     = $table->info("namePrefix");		
		$ligneSelect    = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_ligne"), array("CL.reference","CL.libelle","CL.description","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.productcatid","CL.commandeid","CL.registreid","CL.demandeid","CL.accountid","CL.memberid","CL.documentid","CL.creationdate","CL.creatorid","CL.updateduserid","CL.updatedate"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid", array("P.code","produit"=>"P.libelle","produitDescription"=>"P.code","produitCode"=>"P.code","P.catid","P.documentcatid","P.registreid","P.cout_ttc","P.cout_ht","P.params"))
		                                      ->where("CL.commandeid=?", $commandeid );		
		if( intval($productid)){
			$ligneSelect->where("CL.productid=?" ,intval($productid));
		}
		return $dbAdapter->fetchAll( $ligneSelect,array() , Zend_Db::FETCH_ASSOC );		
	}
	
	public function listproducts($commandeid=null, $productid=0) 
	{
		$commandeid     = intval($commandeid);
		if(!$commandeid ) {
			$commandeid = $this->commandeid;
		}		
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$prefixName     = $table->info("namePrefix");		
		$ligneSelect    = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_ligne")    , array("CL.reference","CL.libelle","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.commandeid","CL.demandeid"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products")           ,"P.productid=CL.productid", array("produit"=>"P.libelle","produitCode"=>"P.code","P.catid","P.documentcatid","P.documentid","P.registreid","P.cout_ttc","P.cout_ht","P.params"))
		                                      ->join(array("C" => $prefixName."erccm_vente_products_categories"),"C.catid=P.catid", array("categorie" => "C.libelle"))
										      ->where("CL.commandeid=?",intval($commandeid));		
		if( intval($productid)){
			$ligneSelect->where("CL.productid=?" ,intval($productid));
		}
		return $dbAdapter->fetchAll( $ligneSelect, array() , Zend_Db::FETCH_ASSOC );		
	}
	
	public function getLignes( $commandeid = null,$productid=0) 
	{
		$commandeid     = intval( $commandeid );
		if(!$commandeid ) {
			$commandeid = $this->commandeid;
		}		
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$prefixName     = $table->info("namePrefix");		
		$ligneSelect    = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_ligne"), array("CL.reference","CL.libelle","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.documentid","CL.productcatid","CL.demandeid","CL.registreid","CL.commandeid"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid" , array("produit"=>"P.libelle","produitCode"=>"P.code"))
		                                      ->where("CL.commandeid=?",intval($commandeid));		
		if( intval($productid)){
			$ligneSelect->where("CL.productid=?" ,intval($productid));
		}
		return $dbAdapter->fetchAll( $ligneSelect , array() , Zend_Db::FETCH_ASSOC );			
	}
	
	public function undeliveredProducts($filters=array(),$pageNum=0,$pageSize=0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");	
		
		$selectRCCM     = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_ligne"), array("CL.reference","CL.libelle","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.documentid","CL.productcatid","CL.demandeid","CL.registreid","CL.commandeid"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid"  , array("produit"=>"P.libelle","produitCode"=>"P.code","P.documentcatid"))
											  ->join(array("C" => $tableName),"C.commandeid=CL.commandeid",array("numeroCommande"=>"C.ref","C.ref","dateCommande"=>"C.date","userid"=>"C.accountid","C.accountid","C.memberid","C.statutid","C.closed","C.validated"))
											  ->join(array("L" => $prefixName."erccm_vente_commandes_livraisons_ligne"),"L.commandeid=CL.commandeid", array("L.livraisonid","livraisonLibelle"=>"L.reference","L.delivered"))
											  ->where("L.delivered=0");		
		if( isset($filters["commandeid"]) && intval($filters["commandeid"])) {
			$selectRCCM->where("CL.commandeid=?",intval($filters["commandeid"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])) {
			$selectRCCM->where("CL.registreid=?",intval($filters["registreid"]));
		}
		if( isset($filters["demandeid"]) && intval($filters["demandeid"])) {
			$selectRCCM->where("CL.demandeid=?",intval($filters["demandeid"]));
		}
		if( intval($pageNum) && intval( $pageSize)) {
			$selectRCCM->limitPage($pageNum , $pageSize);
		}
		$selectRCCM->group(array("CL.commandeid","C.commandeid"))
		           ->order(array("CL.commandeid DESC"));	
		return $dbAdapter->fetchAll( $selectRCCM, array() , Zend_Db::FETCH_ASSOC );
	}
	
	
	public function undeliveredRCCM($filters=array(),$pageNum=0,$pageSize=0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");	
		
		$selectRCCM     = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_ligne"), array("CL.reference","CL.libelle","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.documentid","CL.productcatid","CL.demandeid","CL.registreid","CL.commandeid"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid"  , array("produit"=>"P.libelle","produitCode"=>"P.code","P.documentcatid"))
											  ->join(array("R" => $prefixName."rccm_registre")       ,"R.registreid=CL.registreid", array("numeroRCCM"=>"R.numero","R.numero","R.numifu","R.numcnss","nomCommercial"=>"R.libelle"))
											  ->join(array("C" => $tableName),"C.commandeid=CL.commandeid",array("numeroCommande"=>"C.ref","C.ref","dateCommande"=>"C.date","userid"=>"C.accountid","C.accountid","C.memberid","C.statutid","C.closed","C.validated"))
											  ->join(array("M" => $prefixName."rccm_members")       ,"M.memberid=C.memberid", array("M.name","M.lastname","M.firstname","M.email","M.civilite","M.sexe"))
											  ->join(array("L" => $prefixName."erccm_vente_commandes_livraisons_ligne")       ,"L.commandeid=CL.commandeid", array("L.livraisonid","livraisonLibelle"=>"L.reference","L.delivered"))
											  ->join(array("DC"=> $tablePrefix."system_users_documents_categories"),"C.id=P.documentcatid"      , array("documentType"=> "DC.libelle"))
											  ->where("L.delivered=0");		
		if( isset($filters["commandeid"]) && intval($filters["commandeid"])) {
			$selectRCCM->where("CL.commandeid=?",intval($filters["commandeid"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])) {
			$selectRCCM->where("CL.registreid=?",intval($filters["registreid"]));
		}
		if( isset($filters["demandeid"]) && intval($filters["demandeid"])) {
			$selectRCCM->where("CL.demandeid=?",intval($filters["demandeid"]));
		}
		if( intval($pageNum) && intval( $pageSize)) {
			$selectRCCM->limitPage($pageNum , $pageSize);
		}
		$selectRCCM->group(array("CL.commandeid","C.commandeid"))
		           ->order(array("CL.commandeid DESC"));	
		return $dbAdapter->fetchAll( $selectRCCM, array() , Zend_Db::FETCH_ASSOC );
	}
	
	
	public function undelivered($filters=array(),$pageNum=0,$pageSize=0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");	
		$selectCommandes= $dbAdapter->select()->from(array("C"=>$tableName))
		                                      ->join(array("M"=>$tablePrefix."rccm_members"),"M.memberid=C.memberid",array("client"=> "M.name","member"=>"M.name","M.name","M.lastname","M.firstname","M.passport","M.tel1","M.tel2","M.email","M.sexe","M.code"))
											  ->join(array("S"=>$tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("statut"=>"S.libelle"))
											  ->where("C.commandeid NOT IN (SELECT CL.commandeid FROM ".$tablePrefix."erccm_vente_commandes_livraisons CL WHERE CL.delivered=1)");
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeCommande       = new Zend_Db_Expr("MATCH(C.ref) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeMemberName     = new Zend_Db_Expr("MATCH(M.lastname,M.firstname,M.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
 
			$selectCommandes->where("{$likeCommande} OR {$likeMemberName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}		
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["reference"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["memberid"]) && intval($filters["memberid"])){
			$selectCommandes->where("C.memberid=?", intval($filters["memberid"]));
		}
		if( isset($filters["commandeid"]) && intval($filters["commandeid"])){
			$selectCommandes->where("C.commandeid=?" , intval($filters["commandeid"]));
		}
		if( isset( $filters["date"] ) && !empty( $filters["date"] ) && Zend_Date::isDate($filters["date"],"Y-m-d") ) {
			$selectCommandes->where("FROM_UNIXTIME(C.date ,'%Y-%m-%d')=?" ,  $filters["date"] );
		}
		if((isset($filters["productid"]) && intval($filters["productid"])) || (isset($filters["registreid"]) && intval($filters["registreid"]))
			|| (isset($filters["documentid"]) && intval($filters["documentid"]))) {
			$selectCommandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", null)
			                ->join(array("PL"=>$tablePrefix."erccm_vente_products")       ,"PL.productid=CL.productid", null);
			               
		    if( isset($filters["productid"]) && intval($filters["productid"]) ) {
				$selectCommandes->where("CL.productid=?" , intval($filters["productid"]));
			}
			if( isset($filters["documentid"]) && intval($filters["documentid"]) ) {
				$selectCommandes->where("CL.documentid=?", intval($filters["documentid"]));
			}
			if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
				$selectCommandes->where("CL.registreid=?", intval($filters["registreid"]));
			}
		}
		if( isset( $filters["statutid"])  && intval($filters["statutid"]) ) {
			$selectCommandes->where("C.statutid=?" , intval( $filters["statutid"] ) );
		}
		if( isset( $filters["validated"]) && (intval($filters["validated"])==1 || intval($filters["validated"])==0) ) {
			$selectCommandes->where("C.validated=?",intval($filters["validated"]));
		}
		if( isset( $filters["closed"]) && (intval($filters["closed"])==1 || intval($filters["closed"])==0) ) {
			$selectCommandes->where("C.closed=?",intval($filters["closed"]));
		}
		if( isset( $filters["canceled"]) && (intval($filters["canceled"])==1 || intval($filters["canceled"])==0) ) {
			$selectCommandes->where("C.canceled=?",intval($filters["canceled"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectCommandes->where("FROM_UNIXTIME(C.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectCommandes->where("C.date>=?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectCommandes->where("C.date<=?", intval($filters["periode_end"]  ));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectCommandes->where("C.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( intval($pageNum) && intval( $pageSize)) {
			$selectCommandes->limitPage($pageNum , $pageSize);
		}
		$selectCommandes->group(array("C.commandeid"))->order(array("C.date DESC","C.commandeid DESC"));		
		return $dbAdapter->fetchAll($selectCommandes, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getList($filters=array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");	
		$selectCommandes= $dbAdapter->select()->from(array("C"=>$tableName) , array("C.commandeid","token"=>"C.payment_token","C.payment_token","C.payment_url","C.date","C.validated","C.closed","numero"=>"C.ref","C.ref","C.valeur","C.valeur_ttc","C.valeur_ht","C.valeur_sub_total","C.valeur_tva","C.valeur_bic","C.valeur_remise","C.totalPaid","C.frais","C.statutid","dateCommande"=> "FROM_UNIXTIME(C.date,'%d/%m/%Y')"))
		                                      ->join(array("M"=>$tablePrefix."rccm_members"),"M.memberid=C.memberid",array("client"=> "M.name","member"=>"M.name","M.name","M.lastname","M.firstname","M.passport","M.tel1","M.tel2","M.email","M.sexe","M.code"))
											  ->join(array("S"=>$tablePrefix."erccm_vente_commandes_statuts"),"S.statutid=C.statutid", array("statut"=>"S.libelle"));
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeCommande       = new Zend_Db_Expr("MATCH(C.ref) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeMemberName     = new Zend_Db_Expr("MATCH(M.lastname,M.firstname,M.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
 
			$selectCommandes->where("{$likeCommande} OR {$likeMemberName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}		
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["reference"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["numcommande"])."%");
		}
		if( isset($filters["memberid"]) && intval($filters["memberid"])){
			$selectCommandes->where("C.memberid = ? " , intval($filters["memberid"]));
		}
		if( isset( $filters["date"] ) && !empty( $filters["date"] ) && (null !== $filters["date"]) ) {
			$selectCommandes->where("FROM_UNIXTIME(C.date ,'%Y-%m-%d')=?" ,  $filters["date"] );
		}
        if( isset($filters["lastname"]) && !empty($filters["lastname"]) ){
			$selectCommandes->where("M.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) ){
			$selectCommandes->where( "M.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectCommandes->where("M.email=?",$filters["email"]);
		}
		if( isset($filters["country"]) && !empty($filters["country"])){
			$selectCommandes->where("M.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"]) && !empty($filters["sexe"])){
			$selectCommandes->where(  "M.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"])){
			$selectCommandes->where($dbAdapter->quote("M.telephone=".$filters["telephone"]));
		}
		if((isset($filters["productid"]) && intval($filters["productid"])) || (isset($filters["registreid"]) && intval($filters["registreid"]))
			|| (isset($filters["documentid"]) && intval($filters["documentid"]))) {
			$selectCommandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", null)
			                ->join(array("PL"=>$tablePrefix."erccm_vente_products")       ,"PL.productid=CL.productid", null);
			               
		    if( isset($filters["productid"]) && intval($filters["productid"]) ) {
				$selectCommandes->where("CL.productid=?" , intval($filters["productid"]));
			}
			if( isset($filters["documentid"]) && intval($filters["documentid"]) ) {
				$selectCommandes->where("CL.documentid=?", intval($filters["documentid"]));
			}
			if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
				$selectCommandes->where("CL.registreid=?", intval($filters["registreid"]));
			}
		}
		if( isset( $filters["statutid"])  && intval($filters["statutid"]) ) {
			$selectCommandes->where("C.statutid=?" , intval( $filters["statutid"] ) );
		}
		if( isset( $filters["validated"]) && (intval($filters["validated"])==1 || intval($filters["validated"])==0) ) {
			$selectCommandes->where("C.validated=?",intval($filters["validated"]));
		}
		if( isset( $filters["closed"]) && (intval($filters["closed"])==1 || intval($filters["closed"])==0) ) {
			$selectCommandes->where("C.closed=?",intval($filters["closed"]));
		}
		if( isset( $filters["canceled"]) && (intval($filters["canceled"])==1 || intval($filters["canceled"])==0) ) {
			$selectCommandes->where("C.canceled=?",intval($filters["canceled"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectCommandes->where("FROM_UNIXTIME(C.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectCommandes->where("FROM_UNIXTIME(C.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectCommandes->where("C.date>=?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectCommandes->where("C.date<=?", intval($filters["periode_end"]  ));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectCommandes->where("C.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectCommandes->limitPage($pageNum , $pageSize);
		}
		$selectCommandes->group(array("C.commandeid"))->order(array("C.date DESC","C.commandeid DESC"));		
		return $dbAdapter->fetchAll($selectCommandes, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");
		$selectCommandes= $dbAdapter->select()->from(array("C"=>$tableName),array("C.commandeid"))
		                                      ->join(array("M"=>$tablePrefix."rccm_members"),"M.memberid=C.memberid", null);
		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeCommande       = new Zend_Db_Expr("MATCH(C.ref) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeMemberName     = new Zend_Db_Expr("MATCH(M.lastname,M.firstname,M.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
 
			$selectCommandes->where("{$likeCommande} OR {$likeMemberName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectCommandes->where(new Zend_Db_Expr("M.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}		
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["reference"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectCommandes->where("C.ref LIKE ?","%".strip_tags($filters["numcommande"])."%");
		}
		if( isset($filters["memberid"]) && intval($filters["memberid"])){
			$selectCommandes->where("C.memberid = ? " , intval($filters["memberid"]));
		}
		if( isset( $filters["date"] ) && !empty( $filters["date"] ) && (null !== $filters["date"]) ) {
			$selectCommandes->where("FROM_UNIXTIME(C.date ,'%Y-%m-%d')=?" ,  $filters["date"] );
		}
        if( isset($filters["lastname"])  && !empty($filters["lastname"]) ){
			$selectCommandes->where("M.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) ){
			$selectCommandes->where( "M.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])     && !empty($filters["email"])){
			$selectCommandes->where("M.email=?",$filters["email"]);
		}
		if( isset($filters["country"])   && !empty($filters["country"])){
			$selectCommandes->where("M.country=?", $filters["country"]);
		}
		if( isset($filters["sexe"]) && !empty($filters["sexe"])){
			$selectCommandes->where("M.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"])){
			$selectCommandes->where($dbAdapter->quote("M.telephone=".$filters["telephone"]));
		}
		if((isset($filters["productid"]) && intval($filters["productid"])) || (isset($filters["registreid"]) && intval($filters["registreid"]))
			|| (isset($filters["documentid"]) && intval($filters["documentid"]))) {
			$selectCommandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", null)
			                ->join(array("PL"=>$tablePrefix."erccm_vente_products")       ,"PL.productid=CL.productid" , null);
			               
		    if( isset($filters["productid"]) && intval($filters["productid"]) ) {
				$selectCommandes->where("CL.productid=?" , intval($filters["productid"]));
			}
			if( isset($filters["documentid"]) && intval($filters["documentid"]) ) {
				$selectCommandes->where("CL.documentid=?", intval($filters["documentid"]));
			}
			if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
				$selectCommandes->where("CL.registreid=?", intval($filters["registreid"]));
			}
		}
		if( isset( $filters["statutid"]) && intval($filters["statutid"]) ) {
			$selectCommandes->where("C.statutid=?" , intval( $filters["statutid"] ) );
		}	
		if( isset( $filters["validated"]) && (intval($filters["validated"])==1 || intval($filters["validated"])==0) ) {
			$selectCommandes->where("C.validated=?",intval($filters["validated"]));
		}
		if( isset( $filters["closed"]) && (intval($filters["closed"])==1 || intval($filters["closed"])==0) ) {
			$selectCommandes->where("C.closed=?",intval($filters["closed"]));
		}
		if( isset( $filters["canceled"]) && (intval($filters["canceled"])==1 || intval($filters["canceled"])==0) ) {
			$selectCommandes->where("C.canceled=?",intval($filters["canceled"]));
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectCommandes );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectCommandes )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	



}

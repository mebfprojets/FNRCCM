<?php

class Model_Commandepaiement extends Sirah_Model_Default
{
	
	public function getBilanStatut($statutid=0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$dbAdapter   = $table->getAdapter();
		$tableName   = $table->info("name");
		$valTotalExpr= new Zend_Db_Expr("SUM(CP.montant)");
		$selectBilan = $dbAdapter->select()->from(array("CP"=> $tableName), array("valTotal" => $valTotalExpr,"nombre"=>new Zend_Db_Expr("COUNT(CP.paiementid)")))
		                                   ->join(array("S" => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=CP.statutid", array("statut"=>"S.libelle","S.libelle"))
										   ->where("CP.validated=1");
        if( intval( $statutid )) {
			$selectBilan->where("CP.statutid=?", intval($statutid));
		}
		$selectBilan->group(array("CP.statutid","S.statutid"))->order(array("S.statutid ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanByProductype( $productcatid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$dbAdapter   = $table->getAdapter();
		
		$valTotalExpr= new Zend_Db_Expr("SUM(CL.valeur)");
		
		$selectBilan = $dbAdapter->select()->from(array("CP"=> $tableName),null)
										   ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=CP.commandeid",array("valTotal" => $valTotalExpr))
			                               ->join(array("C" => $tablePrefix."erccm_vente_products_categories"),"C.catid=CL.productcatid",array("C.catid","categorie"=>"C.libelle","C.libelle"))
										   ->where("CP.validated=1");
		if( intval($productcatid)) {
			$selectBilan->where("CL.productcatid= ?", intval($productcatid));
		}
		$selectBilan->group(array("CL.commandeid","CL.productcatid","C.catid"))->order(array("C.libelle ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanByProduct( $productid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$dbAdapter   = $table->getAdapter();
		
		$valTotalExpr= new Zend_Db_Expr("SUM(CP.montant)");
		
		$selectBilan = $dbAdapter->select()->from(array("CP"=> $tableName),array("valTotal" => $valTotalExpr))
										   ->join(array("CL"=> $tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=CP.commandeid",null)
			                               ->join(array("P" => $tablePrefix."erccm_vente_products"),"P.productid=CL.productid", array("P.productid","produit"=>"P.libelle","P.libelle"))
										   ->where("CP.validated=1");
		if( intval($productid)) {
			$selectBilan->where("CL.productid= ?", intval($productid));
		}
		$selectBilan->group(array("CP.commandeid","CL.commandeid","CL.productid"))->order(array("P.libelle ASC"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanTotal( $annee = 0, $periodstart = 0, $periodend = 0)
	{
		$table         = $this->getTable();
		$selectBilan   = $table->select();
		$valTotalExpr  = new Zend_Db_Expr("SUM(montant)");
	
		$selectBilan->from($table, array("nbreTotal"=>"COUNT(paiementid)","valTotal"=>$valTotalExpr))->where("validated=1");
	
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
	
	public function getBilanMember( $memberid = 0, $periodstart = 0, $periodend = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$dbAdapter   = $table->getAdapter();
	
		$valTotalExpr= new Zend_Db_Expr("SUM(CL.valeur)+ C.frais");
	
		$selectBilan = $dbAdapter->select()->from(array("CP"=> $table->info("name")), array("valTotal" => "SUM(CP.date)"))
		                                   ->join(array("C" => $tablePrefix."erccm_vente_commandes"), "C.commandeid = CP.commandeid", array("C.memberid"))
		                                   ->join(array("M" => $tablePrefix."rccm_members"),"M.memberid=C.memberid", array("member" => "M.name"));
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid=?", intval($memberid));
		}
		if( intval( $periodend )){
			$selectBilan->where("CP.date <= ?", intval($periodend));
		}
		if( intval($periodstart) ){
			$selectBilan->where("CP.date >= ? ", intval($periodstart));
		}
		$selectBilan->order(array("M.name ASC"))->group(array("C.memberid"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanAnnuel( $memberid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$dbAdapter   = $table->getAdapter();
	
		$selectBilan = $dbAdapter->select()->from(array("CP"=> $table->info("name")), array("valTotal"=> "SUM(CP.date)","annee"=>"FROM_UNIXTIME(CP.date, '%Y')"))
		                                   ->join(array("C" => $tablePrefix."erccm_vente_commandes"), "C.commandeid = CP.commandeid",null)
										   ->where("CP.validated=1");
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(CP.date,'%Y') ASC"))->group(array("FROM_UNIXTIME(CP.date, '%Y')"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getBilanMensuel( $memberid = 0)
	{
		$table       = $this->getTable();
		$tablePrefix = $table->info("namePrefix");
		$dbAdapter   = $table->getAdapter();	
		$selectBilan = $dbAdapter->select()->from(array("CP"=>$table->info("name")), array("valTotal"=>"SUM(CP.date)","mois"=>"FROM_UNIXTIME(C.date,'%m')","annee"=>"FROM_UNIXTIME(CP.date, '%Y')"))
		                                   ->join(array("C" =>$tablePrefix."erccm_vente_commandes"), "C.commandeid = CP.commandeid",null)
										   ->where("CP.validated=1");;
		if( intval( $memberid )) {
			$selectBilan->where("C.memberid = ?", intval( $memberid ));
		}
		$selectBilan->order(array("FROM_UNIXTIME(CP.date, '%m') ASC"))->group(array("FROM_UNIXTIME(CP.date, '%Y')","FROM_UNIXTIME(CP.date, '%m')"));
		return $dbAdapter->fetchAll($selectBilan, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function transaction($transid, $format="object")
	{
		$fetchType         = ($format=="array")?Zend_Db::FETCH_ASSOC:5;
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectPaiement    = $dbAdapter->select()->from(array("P"  => $table->info("name")))
		                                         ->join(array("MP" => $tablePrefix."erccm_vente_modepaiements")    ,"MP.modepaiementid=P.modepaiementid",array("MP.numero","MP.processed", "MP.address"))
		                                         ->join(array("MPW"=> $tablePrefix."erccm_vente_modepaiements_web"),"MPW.webpaiementid=MP.modepaiementid",array("MPW.phonenumber","MPW.countrycode","MPW.payeur","trans_id"=>"MPW.transactionid","MPW.method","MPW.status"))
												 ->join(array("S"  => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=P.statutid", array("statut"=>"S.libelle"))
												 ->join(array("M"  => $tablePrefix."rccm_members"),"M.memberid=P.memberid" , array("client"=>"M.name","member"=>"M.name","M.lastname","M.firstname","M.email","M.tel1","M.tel2"))
												 ->where("MP.numero=?",addslashes(strip_tags($transid)));
		return $dbAdapter->fetchRow( $selectPaiement, array(),$fetchType);										 
	}
	
	public function webpaiement($paiementid=0, $format="object")
	{
		if(!intval($paiementid)) {
			$paiementid    = $this->paiementid;
		}
		$fetchType         = ($format=="array")?Zend_Db::FETCH_ASSOC:5;
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectPaiement    = $dbAdapter->select()->from(array("P"  => $table->info("name")))
		                                         ->join(array("MP" => $tablePrefix."erccm_vente_modepaiements")    ,"MP.modepaiementid=P.modepaiementid",array("MP.numero","MP.processed", "MP.address"))
		                                         ->join(array("MPW"=> $tablePrefix."erccm_vente_modepaiements_web"),"MPW.webpaiementid=MP.modepaiementid",array("MPW.phonenumber","MPW.countrycode","MPW.payeur","trans_id"=>"MPW.transactionid","MPW.method","MPW.status"))
												 ->join(array("S"  => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=P.statutid", array("statut"=>"S.libelle"))
												 ->join(array("M"  => $tablePrefix."rccm_members"),"M.memberid=P.memberid" , array("client"=>"M.name","member"=>"M.name","M.lastname","M.email","M.firstname","M.tel1","M.tel2"))
												 ->where("P.paiementid=?",intval($paiementid));
		return $dbAdapter->fetchRow( $selectPaiement, array(),$fetchType);
	}
	
	public function autoNum($year=0)
	{
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		
		$selectPaiements   = $dbAdapter->select()->from(array("P"=> $table->info("name")),array("total"=>"COUNT(P.paiementid)"));
		if( intval($year)) {
			$selectPaiements->where("FROM_UNIXTIME(P.date,'%Y')=?",intval($year))->group(array("FROM_UNIXTIME(P.date,'%Y')"));
		}
		$nbreTotal         = $dbAdapter->fetchOne($selectPaiements)+5;
		$newNumPaiement    = (intval($year))?sprintf("Vers-%08d/%04d", $nbreTotal,intval($year)) : sprintf("Vers-%08d", $nbreTotal);
		while($existOrder  = $this->findRow($newNumPaiement,"numero", null, false)) {
			  $nbreTotal++;
			  $newNumPaiement = (intval($year))?sprintf("Vers-%08d/%04d", $nbreTotal,intval($year)) : sprintf("Vers-%08d", $nbreTotal);
		}		
		return $newNumPaiement;
	}
	
	public function transid($length = 8,$prefix=null)
	{		
	    $table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		do {
			$keyString= strtoupper(Sirah_Functions_Generator::getInteger($length));
			if(!empty($prefix)) {
				$keyString = sprintf("%s%d",$prefix,$keyString);
			}
			$stmt     = $dbAdapter->query("SELECT numero FROM ".$tablePrefix."erccm_vente_modepaiements where numero=?", array($keyString));
		} while(false!=$stmt->fetch()) ;
		return $keyString ;
	}
	
	public function cheque( $paiementid = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
	
		if( intval( $paiementid )) {
			$paiementid = $this->paiementid;
		}
		$selectPaiement = $dbAdapter->select()->from(    array("P"  => $prefixName."erccm_vente_commandes_paiements"))
		                                      ->join(    array("MP" => $prefixName."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid", array("MP.bankid","MP.date","MP.montant","MP.numero", "MP.processed", "MP.address"))
		                                      ->join(    array("MPC"=> $prefixName."erccm_vente_modepaiements_cheque"),"MPC.chequeid=MP.modepaiementid",array("MPC.numcompte"))
											  ->joinLeft(array("MPB"=> $prefixName."gestapp_projet_entreprises") , "MPB.entrepriseid=MP.bankid",array("banque"=> "MPB.libelle","bank" => "MPB.libelle"))
				                              ->where("P.paiementid = ?", $paiementid );
		return $dbAdapter->fetchRow( $selectPaiement, array(), 5 );
	}
	
	public function virement($paiementid = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
	
		if( intval( $paiementid )) {
			$paiementid = $this->paiementid;
		}
		$selectPaiement = $dbAdapter->select()->from(    array("P"   => $prefixName."erccm_vente_commandes_paiements"))
		                                      ->join(    array("MP"  => $prefixName."erccm_vente_modepaiements"), "MP.modepaiementid = P.modepaiementid", array("MP.bankid", "MP.date","MP.montant","MP.numero", "MP.processed", "MP.address"))
				                              ->join(    array("MPV" => $prefixName."erccm_vente_modepaiements_virement"), "MPV.virementid = MP.modepaiementid",array("MPV.comptsrc", "MPV.comptdest"))
											  ->joinLeft(array("MPB" => $prefixName."gestapp_projet_entreprises"), "MPB.entrepriseid = MP.bankid",array("bank" => "MPB.libelle"))
				                              ->where("P.paiementid = ?", $paiementid );
		return $dbAdapter->fetchRow( $selectPaiement, array(), 5 );
	}
	
	 
	
	
	
	public function getRow($numero=null ,$commandeid = 0)
	{
		$modelCommande = new Model_Commande();
		$table         = $modelCommande->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
	
		$select        = $table->select();
			
		$commandeid  = intval($commandeid);
		if( null !== $numero ){
			$select->where("ref =?",$numero)->orWhere("commandeid =?",$commande)->order(array("commandeid DESC"));
			return $table->fetchRow($select);
		}
			
		if( $commandeid )
			$select->where("commandeid =?", $commandeid)->order(array("commandeid DESC"));
			
		return $table->fetchRow($select);
	}
	
	public function getLast( $commandeid, $beforeDate = null )
	{
		$commandeid  = intval( $commandeid );
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
	
		$selectLast  = $table->select()->where("commandeid = ? ", $commandeid );
		
		if( intval( $beforeDate )) {
			$selectLast->where("date <= ?", $beforeDate );
		}		
		$selectLast->order("paiementid DESC");			
		return 	$table->fetchRow( $selectLast );
	}
	
	public function createNumpaiement( $commandeid = null )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$tableName          = $table->info("name");
		
		$selectPaiements   = $dbAdapter->select()->from(array("P"=> $tableName), array("COUNT(P.paiementid)"));		
		$nbrePaiements     = $dbAdapter->fetchOne($selectPaiements)+1;		
		$newCode            = sprintf("TR%05d", $nbrePaiements);
		while($existRow     = $this->findRow($newCode, "numero", null, false)) {
			  $nbrePaiements++;
			  $newCode      = sprintf("TR%05d",$nbrePaiements);
		}	
		return $newCode;
	}
	
	public function getBefore( $paiementid = 0, $commandeid = 0 )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
	
		if(!intval( $paiementid )) {
			$paiementid = $this->paiementid;
		}
		if(!intval($commandeid)) {
			$commandeid = $this->commandeid;
		}	
		$selectPaiement = $dbAdapter->select()->from(array("P" => $table->info("name")),array("P.paiementid","P.numero","P.montant","P.totalAPayer","P.reste","P.mode","P.totalPaid","date_paiement" => "FROM_UNIXTIME(P.date, '%d/%m/%Y')", "solde" => "P.reste" ))
				                             ->where("P.paiementid < ?", $paiementid)->where("P.commandeid = ?", $commandeid);
		return $dbAdapter->fetchAll( $selectPaiement, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function documents( $paiementid = null )
	{
		if( null === $paiementid) {
			$paiementid = $this->paiementid;
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$documents      = array();
		$selectDocument = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"))
		                                      ->join(array("PD"=> $tablePrefix ."gestapp_achat_paiements_documents"), "PD.documentid = D.documentid", null )
		                                      ->where("PD.paiementid = ?", intval($paiementid));
		$rows           = $dbAdapter->fetchAll($selectDocument);
		return $rows;
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("P.date DESC", "P.paiementid DESC" , "P.reste ASC"))
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$tableName      = $table->info("name");
		$selectPaiements= $dbAdapter->select()->from(array("P"  => $tableName), array("P.paiementid","P.commandeid","P.memberid","P.accountid","P.invoiceid","P.statutid","P.validated","P.canceled","P.numero","numpaiement"=>"P.numero","P.libelle","P.observation","P.montant","P.totalAPayer","P.reste","P.modepaiement","P.modepaiementid","P.totalPaid","P.date","date_paiement"=>"FROM_UNIXTIME(P.date,'%d/%m/%Y')","P.creationdate","P.creatorid","P.updatedate","P.updateduserid"))
		                                      ->join(array("C"  => $tablePrefix."erccm_vente_commandes")        ,"C.commandeid=P.commandeid", array("numcommande"=>"C.ref","C.memberid","C.valeur_ht","C.valeur_ttc"))
		                                      ->join(array("M"  => $tablePrefix."rccm_members")                 ,"M.memberid=C.memberid"    , array("client"=>"M.name","member"=>"M.name","M.lastname","M.firstname","M.tel1","M.tel2","M.email"))
											  ->join(array("MP" => $tablePrefix."erccm_vente_modepaiements")    ,"MP.modepaiementid=P.modepaiementid",array("modepaiement"=>"MP.libelle","transid"=>"MP.numero","trans_id"=>"MP.numero","trans_date"=>"MP.date","MP.bankid","MP.address","MP.processed","MP.banque","MP.compteid"))
											  ->joinLeft(array("MPW"=> $tablePrefix."erccm_vente_modepaiements_web"),"MPW.webpaiementid=P.modepaiementid",array("MPW.webpaiementid","MPW.transactionid","MPW.phonenumber","MPW.countrycode","MPW.payeur","MPW.method","MPW.status","MPW.status"))
											  ->joinLeft(array("S"  => $tablePrefix."erccm_vente_commandes_paiements_statuts"),"S.statutid=P.statutid"   ,array("statut"=>"S.libelle"));
	
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
			$searchQ            = $filters["searchQ"];
			$likePaiement       = new Zend_Db_Expr("MATCH(P.numero,P.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeTransaction    = new Zend_Db_Expr("MP.numero  LIKE \"%".strip_tags($searchQ)."%\"");
			$likeCommande       = new Zend_Db_Expr("MATCH(C.ref) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeMemberName     = new Zend_Db_Expr("MATCH(M.lastname,M.firstname,M.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
                  
			$selectPaiements->where("{$likePaiement} OR {$likeCommande} OR {$likeMemberName} OR {$likeTransaction}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
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
				$selectPaiements->where(new Zend_Db_Expr("M.lastname  LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectPaiements->where(new Zend_Db_Expr("M.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}		
		if( isset($filters["reference"])   && !empty($filters["reference"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["reference"])."%");
		}
		if( isset($filters["numero"])      && !empty($filters["numero"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["numcommande"])."%");
		}
		if( isset($filters["memberid"]) &&  intval($filters["memberid"])){
			$selectPaiements->where("C.memberid = ? " , intval($filters["memberid"]) );
		}
		if( isset($filters["commandeid"]) && intval($filters["commandeid"]) ) {
			$selectPaiements->where("C.commandeid  = ?" , intval($filters["commandeid"]));
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectPaiements->where("P.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectPaiements->where("P.date >= ? " , intval($filters["periode_start"]));
		}
		if((isset($filters["productid"]) && intval($filters["productid"])) || (isset($filters["registreid"]) && intval($filters["registreid"]))
			|| (isset($filters["documentid"]) && intval($filters["documentid"])) || (isset($filters["productcatid"]) && intval($filters["productcatid"]))) {
			$selectPaiements->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", null)
			                ->join(array("PL"=>$tablePrefix."erccm_vente_products")       ,"PL.productid=CL.productid", null);
			               
		    if( isset($filters["productid"]) && intval($filters["productid"]) ) {
				$selectPaiements->where("CL.productid=?" , intval($filters["productid"]));
			}
			if( isset($filters["productcatid"]) && intval($filters["productcatid"]) ) {
				$selectPaiements->where("CL.productcatid?" , intval($filters["productcatid"]));
			}
			if( isset($filters["documentid"]) && intval($filters["documentid"]) ) {
				$selectPaiements->where("CL.documentid=?", intval($filters["documentid"]));
			}
			if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
				$selectPaiements->where("CL.registreid=?", intval($filters["registreid"]));
			}
		}
		if( isset( $filters["statutid"] ) && intval($filters["statutid"])  ) {
			$selectPaiements->where("C.statutid = ? " , intval( $filters["statutid"] ) );
		}
		if( isset( $filters["date"] ) && !empty($filters["date"])  && Zend_Date::isDate($filters["date"],"Y-m-d")) {
			$selectPaiements->where("FROM_UNIXTIME(P.date,'%Y-%m-%d')=?" ,  $filters["date"] );
		}		
		if( !empty( $orders )) {
			$selectPaiements->order( $orders );
		}			
		if( intval($pageNum) && intval(  $pageSize)) {
			$selectPaiements->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectPaiements , array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectPaiements= $dbAdapter->select()->from(array("P" => $table->info("name")) , array("P.paiementid"))
		                                      ->join(array("C" => $tablePrefix."erccm_vente_commandes"),"C.commandeid=P.commandeid", null )
		                                      ->join(array("M" => $tablePrefix."rccm_members"),"M.memberid=C.memberid" , null )
											  ->join(array("MP"=> $tablePrefix."erccm_vente_modepaiements"),"MP.modepaiementid=P.modepaiementid",null);
	
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
			$searchQ            = $filters["searchQ"];
			$likePaiement       = new Zend_Db_Expr("MATCH(P.numero,P.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeTransaction    = new Zend_Db_Expr("MP.numero  LIKE \"%".strip_tags($searchQ)."%\"");
			$likeCommande       = new Zend_Db_Expr("MATCH(C.ref) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeMemberName     = new Zend_Db_Expr("MATCH(M.lastname,M.firstname,M.code) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
                  
		    $selectPaiements->where("{$likePaiement} OR {$likeCommande} OR {$likeMemberName} OR {$likeTransaction}");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
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
				$selectPaiements->where(new Zend_Db_Expr("M.lastname  LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectPaiements->where(new Zend_Db_Expr("M.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}		
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["reference"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcommande"]) && !empty($filters["numcommande"])){
			$selectPaiements->where("C.ref LIKE ?","%".strip_tags($filters["numcommande"])."%");
		}
		if( isset($filters["memberid"]) &&  intval($filters["memberid"])){
			$selectPaiements->where("C.memberid = ? " , intval($filters["memberid"]) );
		}
		if( isset($filters["commandeid"]) && intval($filters["commandeid"]) ) {
			$selectPaiements->where("C.commandeid  = ?" , intval($filters["commandeid"]));
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectPaiements->where("P.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectPaiements->where("P.date >= ? " , intval($filters["periode_start"]));
		}
		if((isset($filters["productid"]) && intval($filters["productid"])) || (isset($filters["registreid"]) && intval($filters["registreid"]))
			|| (isset($filters["documentid"]) && intval($filters["documentid"]))) {
			$selectPaiements->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.commandeid=C.commandeid", null)
			                ->join(array("PL"=>$tablePrefix."erccm_vente_products")       ,"PL.productid=CL.productid", null);
			               
		    if( isset($filters["productid"]) && intval($filters["productid"]) ) {
				$selectPaiements->where("CL.productid=?" , intval($filters["productid"]));
			}
			if( isset($filters["documentid"]) && intval($filters["documentid"]) ) {
				$selectPaiements->where("CL.documentid=?", intval($filters["documentid"]));
			}
			if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
				$selectPaiements->where("CL.registreid=?", intval($filters["registreid"]));
			}
		}
		if( isset( $filters["statutid"] ) && intval($filters["statutid"])  ) {
			$selectPaiements->where("C.statutid = ? " , intval( $filters["statutid"] ) );
		}
		if( isset( $filters["date"] ) && !empty($filters["date"])  && Zend_Date::isDate($filters["date"],"Y-m-d")) {
			$selectPaiements->where("FROM_UNIXTIME(P.date,'%Y-%m-%d')=?" ,  $filters["date"] );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectPaiements);
		$rowCount          = intval(count($dbAdapter->fetchAll($selectPaiements)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
			
		return $paginator;
	}
 }
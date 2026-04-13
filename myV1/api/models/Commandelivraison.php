<?php

class Model_Commandelivraison extends Sirah_Model_Default
{
	
	
	public function undeliveredproducts( $livraisonid= null) 
	{	
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$prefixName     = $table->info("namePrefix");
        $tableName      = $table->info("name"); 		
		$selectProducts = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_livraisons_ligne"), array("CL.reference","CL.libelle","CL.description","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.productcatid","CL.livraisonid","CL.commandeid","CL.demandeid","CL.accountid","CL.memberid","CL.documentid","CL.creationdate","CL.creatorid","CL.updateduserid","CL.updatedate"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid", array("P.code","produit"=>"P.libelle","produitCode"=>"P.code","P.catid","P.documentcatid","P.registreid","P.cout_ttc","P.cout_ht"))
		                                      ->join(array("L" => $tableName),"L.livraisonid=CL.livraisonid", array("livraisonRef"=>"L.numero","livraisonLibelle"=>"L.libelle"))
											  ->join(array("L" => $prefixName."erccm_vente_commandes"),"C.commandeid=CL.commandeid", array("commandeRef"=>"C.ref","commandeLibelle"=>"C.libelle","commandeDate"=>"C.date"))
											  ->join(array("M" => $prefixName."rccm_members"),"M.memberid=CL.memberid", array("membername"=>new Zend_Db_Expr("CONCAT_WS(' ',M.lastname,M.firstname)"),"M.lastname","M.firstname","M.tel1","M.tel2","M.civilite","membermail"=>"M.email"))
											  ->joinLeft(array("AD"=> $tablePrefix."erccm_vente_commandes_invoices_addresses"),"AD.invoiceid=CL.invoiceid", array("customerAddress"=>"AD.address","customerEmail"=>"AD.email","customerPhone"=>"AD.phone","AD.customerName"))
											  ->where("CL.delivered=0");		
		if( intval($livraisonid) ) {
			$selectProducts->where("CL.livraisonid=?" , intval($livraisonid ));	
		}		
		return $dbAdapter->fetchAll( $selectProducts , array() , Zend_Db::FETCH_ASSOC );		
	}
	
	public function products( $livraisonid= null) 
	{	
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$prefixName     = $table->info("namePrefix");		
		$selectProducts = $dbAdapter->select()->from(array("CL"=> $prefixName."erccm_vente_commandes_livraisons_ligne"), array("CL.reference","CL.libelle","CL.description","quantite"=>"CL.qte","CL.qte","CL.prix_unit","valeur_ttc"=>"CL.valeur","CL.valeur","CL.valeur_ht","CL.valeur_tva","CL.valeur_bic","CL.productid","CL.productcatid","CL.livraisonid","CL.commandeid","CL.demandeid","CL.accountid","CL.memberid","CL.documentid","CL.creationdate","CL.creatorid","CL.updateduserid","CL.updatedate"))
		                                      ->join(array("P" => $prefixName."erccm_vente_products"),"P.productid=CL.productid", array("P.code","produit"=>"P.libelle","produitCode"=>"P.code","P.catid","P.documentcatid","P.registreid","P.cout_ttc","P.cout_ht","P.params"));		
		if( intval($livraisonid) ) {
			$selectProducts->where("CL.livraisonid=?" , intval($livraisonid ));	
		}
		
		return $dbAdapter->fetchAll( $selectProducts , array() , Zend_Db::FETCH_ASSOC );		
	}
	
	
	 
}


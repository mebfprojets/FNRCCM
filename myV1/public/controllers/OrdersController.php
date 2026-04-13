<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */
defined("PAYMENT_GATEWAY_API_SHIPPING_COST")
    || define("PAYMENT_GATEWAY_API_SHIPPING_COST",2);	
defined("PAYMENT_GATEWAY_API_ENDPOINT")
    || define("PAYMENT_GATEWAY_API_ENDPOINT","https://app.ligdicash.com");	
defined("PAYMENT_GATEWAY_API_TOKEN")
    || define("PAYMENT_GATEWAY_API_TOKEN","eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF9hcHAiOiIyNDgyNyIsImlkX2Fib25uZSI6NzU5MzkwLCJkYXRlY3JlYXRpb25fYXBwIjoiMjAyNS0wNi0wMyAxMjowMDoxMiJ9.fGWi-mhBNNCgPpkrd8XZhaU1YIbxXMj9XVR1lMZto5o");
defined("PAYMENT_GATEWAY_APIKEY")
    || define("PAYMENT_GATEWAY_APIKEY","IKNCWCQD4H1K3W919");	
defined("PAYMENT_GATEWAY_SITE_ID")
    || define("PAYMENT_GATEWAY_SITE_ID","IKNCWCQD4H1K3W919");	 
defined("APPLICATION_ORDERCART_EXPIRATION")
    || define("APPLICATION_ORDERCART_EXPIRATION",86400);	

 

class OrdersController extends Sirah_Controller_Default
{
	
	protected $_member  = null;
	
	protected $_cart    = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		$me             = $loggedInUser = Sirah_Fabric::getUser();
		$application    = new Zend_Session_Namespace("erccmapp");
		$orderCart      = new Zend_Session_Namespace("ordercart");
		
		 
		$model          = $this->getModel("member");
		$accountMember  = $model->fromuser($me->userid);
		if(!$accountMember) {
			$returnToUrl= (!empty($actionName))?sprintf("public/orders/%s", $actionName) : "public/orders/create";
			$application->returnToUrl = $returnToUrl;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Cette page n'est accessible qu'aux membres"));
				exit;
			}
            $this->setRedirect("Vous devrez au préalable vous authentifier.","error");			
			$this->redirect("public/members/login");
		}
		$memberid        = $accountMember->memberid;
		$member          = $model->findRow( $memberid , "memberid", null , false );
		if(!$member ) {
			$returnToUrl = (!empty($actionName))?sprintf("public/orders/%s", $actionName) : "public/orders/create";
			$application->returnToUrl = $returnToUrl;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
			$this->redirect("public/members/login");	
		}
		$this->_member  = $member;
		if(!isset($orderCart->initialised) || !$orderCart->initialised ) {
			$orderCart->articles    = array();
			$orderCart->registres   = array();
			$orderCart->documents   = array();
			$orderCart->requests    = array();
			$orderCart->counter     = $orderCart->commandeValeur = $orderCart->montant_ht = $orderCart->montant = $orderCart->frais_transaction = $orderCart->montant_ttc = 0;
			$orderCart->initialised = true;
			$orderCart->payment_token= "";
			$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
		}
		
		$this->_cart  = &$orderCart;
		parent::init();
	}
	
	public function resetAction()
	{
		$application    = new Zend_Session_Namespace("erccmapp");
		$orderCart      = new Zend_Session_Namespace("ordercart");
		if((isset($orderCart->commandeid) || isset($orderCart->commande["ref"])) && intval($orderCart->commandeid)) {
			$commandeid                         = $orderCart->commandeid;
			$model                              = $modelCommande = $this->getModel("commande");
			
			$modelTable                         = $model->getTable();
			$dbAdapter                          = $modelTable->getAdapter();
			$prefixName                         = $modelTable->info("namePrefix");	
			$tableName                          = $modelTable->info("name");
			try {
				if( $dbAdapter->delete( $prefixName."erccm_vente_commandes"                   , array("commandeid=?"=>$commandeid))) {
					$dbAdapter->delete( $prefixName."erccm_vente_commandes_ligne"             , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete( $prefixName."erccm_vente_commandes_paiements"         , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete( $prefixName."erccm_vente_commandes_invoices"          , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete( $prefixName."erccm_vente_commandes_invoices_addresses", array("commandeid=?"=>$commandeid));	
					$dbAdapter->delete( $prefixName."erccm_vente_modepaiements"               , array("modepaiementid IN (SELECT modepaiementid FROM ".$prefixName."erccm_vente_commandes_paiements WHERE commandeid=?)"=>$commandeid));
					$dbAdapter->delete( $prefixName."erccm_vente_modepaiements_web"           , array("webpaiementid  IN (SELECT modepaiementid FROM ".$prefixName."erccm_vente_commandes_paiements WHERE commandeid=?)"=>$commandeid));				
				}
			} catch(Exception $e ) {
				echo $e->getMessage(); die();
			}
			$orderCart->commande                = array();
			$orderCart->invoice                 = array();
			$orderCart->invoiceAddress          = array();
			$orderCart->commandeItems           = array();
			$orderCart->commandeid              = 0;
			$orderCart->invoiceid               = 0;
			$orderCart->montant                 = $orderCart->commandeValeur = $orderCart->montant_ht = $orderCart->montant_ttc = 0;
            $orderCart->frais_transaction       = 0;
            $orderCart->payment_token           = "";			
		}
		$this->redirect("public/orders/create");
	}
	
	
	public function listAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		}
		$me                       = Sirah_Fabric::getUser(); 		
		$model                    = $this->getModel("commande");
		$modelProduit             = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$modelStatut              = $this->getModel("commandestatut");
		
		$paginator                = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"] ))? intval($params["maxitems"]) : 100;
		$searchQ                  = (isset($params["searchq"]  ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		
		$filters                  = array("searchQ"=>$searchQ,"numero"=>null,"name"=>null,"memberid"=>null,"productid"=> null,"registreid"=> null,"catid"=>null,"statutid"=>null);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$filters["creatorid"]     = $me->userid;
		$filters["memberid"]      = $this->_member->memberid;
		$filters["accountid"]     = $this->_member->accountid;
		$commandes                = $model->getList($filters,$pageNum, $pageSize);
		$paginator                = $model->getListPaginator($filters );
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->commandes    = $this->view->orders = $commandes;
 
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->categories   = $modelCategory->getSelectListe("Selectionnez un type de produits", array("catid"   ,"libelle"), array() , null , null , false );
        $this->view->statuts      = $modelStatut->getSelectListe(  "Selectionnez un statut"          , array("statutid","libelle"), array() , null , null , false );
	}
	
	
	public function infosAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		}
		$model                   = $this->getModel("commande");
		$modelLigne              = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $modelMember  = $this->getModel("member");
        $modelPaiement           = $this->getModel("commandepaiement");	
 	
		
		$commandeid              = intval( $this->_getParam("commandeid", $this->_getParam("id", 0 )));
		$commande                = $model->findRow( $commandeid , "commandeid", null, false );
		if(!$commande ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de cette commande. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette commande. Paramètres invalides", "error");
			$this->redirect("public/orders/list");
		}	
		if( $commande->memberid != $this->_member->memberid) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Vous n'êtes pas autorisé à consulter les informations de cette commande. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à consulter les informations de cette commande. Paramètres invalides", "error");
			$this->redirect("public/orders/list");
		}
		$this->view->commande       = $commande;
		$this->view->commandeid     = $commandeid;
		$this->view->invoice        = $commande->invoice($commandeid,"object");
		$this->view->member         = $this->view->client    = $this->_member;
		$this->view->statut         = $commande->getStatut($commandeid);
        $this->view->documents      = $commande->documents($commandeid);
		$this->view->products       = $this->view->lignes    = $products  = $commande->listproducts($commandeid);
		$this->view->reglements     = $this->view->paiements = $paiements = $modelPaiement->getList(array("commandeid"=> $commandeid), 0, 0, array("P.date ASC"));
		$this->view->title          = sprintf("Les informations de la commande numéro %s", $commande->ref);
		$this->view->lastReglement  = $modelPaiement->getLast($commandeid);
	}
	
	public function createAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax          = true;
		} else {
			$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		}
		$view                            = &$this->view;
		$orderCart                       = new Zend_Session_Namespace("ordercart");
		$application                     = new Zend_Session_Namespace("erccmapp");
		
		if( isset($orderCart->commande) && isset($orderCart->commandeid) &&
		    isset($orderCart->commande["ref"]) && intval($orderCart->commandeid)) {
			//$this->setRedirect(sprintf("Veuillez procéder au paiement de la commande n° %s", $orderCart->commande["ref"]),"error");
			$this->redirect("public/orders/update/commandeid/".$orderCart->commandeid);
		}
		
		$modelMember                     = $this->getModel("member");
		$me                              = Sirah_Fabric::getUser();
		$accountid                       = $me->userid;
		$member                          = $this->_member;
		if(!$member ) {
			$accountMember               = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                  = $modelMember->findRow($accountMember->memberid,"memberid",null,false);
			}
		}		
		$view->member                    = $member;
		$view->articles                  = $articles    = $orderCart->articles;
		$view->registres                 = $orderCart->registres;
		$view->documents                 = $orderCart->documents;
		$view->requests                  = $orderCart->requests;
		$view->returnToUrl               = $returnToUrl = $application->returnToUrl;
		
		$model                           = $this->getModel("commande");		
		$modelInvoice                    = $this->getModel("commandefacture");
		$modelProduct                    = $this->getModel("product");
		$modelProductCategory            = $this->getModel("productcategorie");
		$modelCountry                    = $this->getModel("country");
		
		$commandeDefaultData             = $model->getEmptyData();
		$invoiceDefaultData              = $modelInvoice->getEmptyData();
		$memberData                      = $member->toArray();
		$memberid                        = $member->memberid;
		$accountid                       = $me->userid;
		$invoiceBillingAddress           = $member->billing_address($accountid);
		$errorMessages                   = $commandeItems= array();
 
        $allowedModePaiements            = array(0=>"Sélectionnez un moyen de paiement",1=>"Mobile Money",2=>"Carte Visa");
		$view->countries                 = $countries    = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders" => array("libelle ASC")), null , null , false );
 		
		$defaultData                                     = (isset($invoiceBillingAddress["accountid"]))?array_merge($commandeDefaultData,$invoiceDefaultData,$memberData,$invoiceBillingAddress) : array_merge($commandeDefaultData,$invoiceDefaultData,$memberData);
		$defaultData["phone"]                            = $memberData["tel1"];
		$defaultData["email"]                            = $memberData["email"];
		$defaultData["customerName"]                     = $memberData["name"];
		$defaultData["customerLastName"]                 = $memberData["lastname"];
		$defaultData["customerFirstName"]                = $memberData["firstname"];
		$defaultData["country"]                          = $this->_getParam("country", "BF");
		$defaultData["city"]                             = $this->_getParam("city"   , "Ouagadougou");
		$defaultData["zip"]                              = (!empty($defaultData["country"]) && isset($countries[$defaultData["country"]]))?sprintf("%05d",$modelCountry->callingCode($defaultData["country"])) : "00226";
		
		if( $this->_request->isPost()  ) {
			$postData                                    = $this->_request->getPost();
			$commandeData                                = array_merge($commandeDefaultData, array_intersect_key($postData, $commandeDefaultData));
			$invoiceData                                 = array_merge($invoiceDefaultData , array_intersect_key($postData, $invoiceDefaultData ));
			$invoiceAddressData                          = array("commandeid"=>0,"invoiceid"=>0,"memberid"=>$memberid,"accountid"=>$accountid,"address"=>"","email"=>"","phone"=>"","country"=>"BF","zip"=>"",
			                                                     "customerName"=>$member->name,"customerLastName"=>$member->lastname,"customerFirstName"=>$member->firstname,"creationdate"=>time(),"creatorid"=>$accountid,"updatedate"=>0,"updateduserid"=>0);
			
			$modelTable                                  = $model->getTable();
			$dbAdapter                                   = $modelTable->getAdapter();
			$prefixName                                  = $modelTable->info("namePrefix");	
            $tableName                                   = $modelTable->info("name");			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                                = new Zend_Filter();
			$stringFilter->addFilter(                      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(                      new Zend_Filter_StripTags());
			
			$strNotEmptyValidator                        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));					
			$emailValidator                              = new Sirah_Validateur_Email();
			
			$invoiceData["memberid"]                     = $memberid;
			$invoiceData["accountid"]                    = $invoiceData["creatorid"]      = $accountid;
			$invoiceData["documentid"]                   = $invoiceData["commandeid"]     = 0;
			$invoiceData["statutid"]                     = 1;
			$invoiceData["modepaiement"]                 = (isset($postData["modepaiement"]) && intval($postData["modepaiement"]))?intval($postData["modepaiement"]) : 1;
			$invoiceData["libelle"]                      = $invoiceData["numero"]         = "";
			$invoiceData["contenu"]                      = $invoiceData["type"]           = "";
			$invoiceData["date"]                         = $invoiceData["creationdate"]   = time();
			$invoiceData["updatedate"]                   = $invoiceData["updateduserid"]  = 0;
			$invoiceData["discount_percent"]             = $invoiceData["montant_remise"] = 0;
			
			if(!intval($invoiceData["modepaiement"]) || !isset($allowedModePaiements[$invoiceData["modepaiement"]])) {
				$errorMessages[]                         = "Veuillez sélectionner le moyen de paiement que vous souhaitez utiliser";
			}			
			$invoiceAddressData["address"]               = (isset($postData["address"]          ))? $stringFilter->filter($postData["address"])           : $member->address;
			$invoiceAddressData["email"]                 = (isset($postData["email"]            ))? $stringFilter->filter($postData["email"])             : $member->email;
			$invoiceAddressData["phone"]                 = (isset($postData["phone"]            ))?$stringFilter->filter($postData["phone"])             : $member->tel1;
			$invoiceAddressData["country"]               = (isset($postData["country"]          ))?$stringFilter->filter($postData["country"])           : $member->country;
			$invoiceAddressData["city"]                  = (isset($postData["city"]             ))?$stringFilter->filter($postData["city"])              : $member->city;
			$invoiceAddressData["zip"]                   = (isset($postData["zip"]              ))?$stringFilter->filter($postData["zip"])               : $defaultData["zip"];
			$invoiceAddressData["customerName"]          = (isset($postData["customerName"]     ))?$stringFilter->filter($postData["customerName"])      : "";
			$invoiceAddressData["customerLastName"]      = (isset($postData["customerLastName"] ))?$stringFilter->filter($postData["customerName"])      : $member->lastname;
			$invoiceAddressData["customerFirstName"]     = (isset($postData["customerFirstName"]))?$stringFilter->filter($postData["customerFirstName"]) : $member->firstname;
			
			if( empty($invoiceAddressData["customerLastName"]) ) {
				$invoiceAddressData["customerLastName"]  = $member->lastname;
			}
            if( empty($invoiceAddressData["customerFirstName"]) ) {
				$invoiceAddressData["customerFirstName"] = $member->firstname;
			}			
			if( empty($invoiceAddressData["customerName"]) && !empty($invoiceAddressData["customerLastName"]) && !empty($invoiceAddressData["customerFirstName"])) {
			    $invoiceAddressData["customerName"]      = sprintf("%s %s",$invoiceAddressData["customerLastName"],$invoiceAddressData["customerFirstName"]);
			}
			if( empty($invoiceAddressData["country"]) ) {
				$errorMessages[]                         = "Veuillez saisir le pays dans vos coordonnées de facturation";
			}
			if( empty($invoiceAddressData["address"]) ) {
				$errorMessages[]                         = "Veuillez saisir votre adresse de facturation";
			}
			if(!$strNotEmptyValidator->isValid($invoiceAddressData["email"]) || !$emailValidator->isValid($invoiceAddressData["email"] )) {
				$invoiceAddressData["email"]             = $member->email;
			}
			if( empty($invoiceAddressData["phone"]) ) {
				$errorMessages[]                         = "Veuillez saisir le numéro de téléphone dans vos paramètres de facturation";
			}  			
			if( empty($errorMessages) ) {
				//On créé d'abord la commande
				$montantTotal      = $montantTotalTTC    = 0;
				foreach( $articles as $article ) {
						 $quantite                       = (isset( $article["quantite"]) && intval($article["quantite"]))?$article["quantite"] : 1;
						 $montantHT                      = (intval($quantite))?($article["cout_ht"]*$quantite)  : $article["cout_ht"];
						 $montantTTC                     = (intval($quantite))?($article["cout_ttc"]*$quantite) : $article["cout_ttc"];
						 $montantTotal                  += $montantHT;
						 $montantTotalTTC               += $montantTTC;
				}
				
				$commandeData["ref"]                     = $commandeReference             = $model->autoNum();
				$commandeData["memberid"]                = $memberid;
				$commandeData["accountid"]               = $commandeData["creatorid"]     = $accountid;
				$commandeData["date"]                    = $commandeData["creationdate"]  = time();
				$commandeData["valeur"]                  = $commandeData["valeur_ht"]     = $commandeData["valeur_sub_total"] = $montantTotal;
				$commandeData["valeur_ttc"]              = $invoiceData["montant_total"]  = $montantTotalTTC;
				$commandeData["valeur_tva"]              = $commandeData["valeur_bic"]    = $commandeData["apply_tva"]        = $commandeData["apply_bic"] = $commandeData["val_tva"] = $commandeData["val_bic"] = 0;
				$commandeData["frais"]                   = $commandeData["valeur_remise"] = $commandeData["totalPaid"]        = 0;
				$commandeData["validated"]               = 0;
				$commandeData["statutid"]                = 1;
				$commandeData["closed"]                  = $commandeData["canceled"]      = 0;
				$commandeData["updatedate"]              = $commandeData["updateduserid"] = 0;
				$commandeData["observation"]             = (isset($postData["observation"]))?$stringFilter->filter($postData["observation"]) : "";
				$cleanCommandeData                       = array_merge($commandeDefaultData, array_intersect_key($commandeData, $commandeDefaultData));
			    try {
					if( $dbAdapter->insert( $tableName, $cleanCommandeData)) {
						$commandeid                      = $commandeData["commandeid"]    = $dbAdapter->lastInsertId();
					}
				} catch(Exception $e ) {
					$error                               = $e->getMessage();
					if( stripos($error,"duplicata")!==false) {
						$cleanCommandeData["ref"]        = $commandeReference             = $model->autoNum();
						if( $dbAdapter->insert( $tableName, $cleanCommandeData)) {
							$commandeid                  = $commandeData["commandeid"]    = $dbAdapter->lastInsertId();
						}
					} else {
						$errorMessages[]                 = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
					}
				} catch( Exception $e  ) {
					$commandeid                          = 0;
					$errorMessages[]                     = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
				}
				if( empty($errorMessages) && $commandeid && count($articles)) {
					// On enregistre les lignes de commande
					$commandeLigneData            = array("commandeid"=>$commandeid,"memberid"=>$memberid,"accountid"=>$accountid,"creatorid"=>$accountid,"creationdate"=>time(),"updateduserid"=>0,"updatedate"=>0);
					$dbAdapter->delete($prefixName."erccm_vente_commandes_ligne", array("commandeid=?"=>$commandeid));
					foreach( $articles as $article ) {
						     $productLibelle                    = $article["libelle"];
							 $productDescription                = $article["description"];
							 $quantite                          = $article["quantite"];
							 $productRow                        = (intval($article["productid"]))? $modelProduct->findRow(intval($article["productid"]),"productid",null,false) : null;
							 if(!$productRow || !intval($quantite)) {
								 continue;
							 }
							 $productLibelle                      = (!empty($productLibelle))? $productLibelle : $productRow->libelle;
							 $productDescription                  = $productRow->description;
							 $coutTTC                             = (isset($article["cout_ttc"])     && floatval($article["cout_ttc"]  ))? floatval($article["cout_ttc"]) : $productRow->cout_ttc;
						     $coutHT                              = (isset($article["cout_ht"])      && floatval($article["cout_ht"]   ))? floatval($article["cout_ht"])  : $productRow->cout_ht;
							 $commandeLigneData["productid"]      = $productid  = $article["productid"];
							 $commandeLigneData["productcatid"]   = (isset($article["productcatid"]) && intval($article["productcatid"]))? $article["productcatid"] : $productRow->catid;
							 $commandeLigneData["documentid"]     = (isset($article["documentid"])   && intval($article["documentid"]  ))? $article["documentid"]   : $productRow->documentid;
							 $commandeLigneData["demandeid"]      = $demandeid  = (isset($article["demandeid"])  && intval($article["demandeid"]   ))? $article["demandeid"]    : $productRow->demandeid;
							 $commandeLigneData["registreid"]     = $registreid = (isset($article["registreid"]) && intval($article["registreid"]  ))? $article["registreid"]   : $productRow->registreid;
							 $commandeLigneData["reference"]      = (intval($demandeid))?sprintf("Art%d-%d:%d",$productid,$commandeid,$demandeid) : sprintf("Art%d-%d",$productid,$commandeid);
							 $commandeLigneData["libelle"]        = sprintf("%d %s", $quantite, $productLibelle);
							 $commandeLigneData["description"]    = $productDescription;
							 $commandeLigneData["qte"]            = $quantite;
							 $commandeLigneData["prix_unit"]      = (floatval($coutTTC))?$coutTTC : $coutHT;
							 $commandeLigneData["valeur"]         = $valeur = $commandeLigneData["prix_unit"]*$quantite;
							 $commandeLigneData["valeur_ht"]      = $commandeLigneData["valeur_ttc"] = $valeur;
							 $commandeLigneData["valeur_remise"]  = $commandeLigneData["valeur_tva"] = $commandeLigneData["valeur_bic"] = 0;
							 
							 if( intval($demandeid)) {
								 $commandeLigneData["registreid"] = $registreid = 0;
							 }
							 if( intval($registreid)) {
								 $commandeLigneData["demandeid"]  = $demandeid  = 0;
							 }
					         try {
								$dbAdapter->delete(     $prefixName."erccm_vente_commandes_ligne", array("commandeid=?"=>$commandeid,"productid=?"=>$productid,"demandeid=?"=>$demandeid,"registreid=?"=>$registreid));
								if(!$dbAdapter->insert( $prefixName."erccm_vente_commandes_ligne", $commandeLigneData)) {
									$errorMessages[]              = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
								} else {
									$commandeItems[$productid]    = $commandeLigneData;
								}
							 } catch(Exception $e ) {
								$errorMessages[]                  = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
							 }
					}		
                    //On enregistre les informations de la facture si tout est OK
                    if( empty($errorMessages) && $commandeid && floatval($commandeData["valeur"])) {
						$invoiceData["commandeid"]                = $commandeid;
						$invoiceData["numero"]                    = $modelInvoice->generateKey();
						$invoiceData["libelle"]                   = sprintf("Facture n° %s de la commande %s du %s",$invoiceData["numero"],$commandeData["ref"],date("d/m/Y"));
						$invoiceData["montant_total"]             = $commandeData["valeur"];
						$invoiceData["statutid"]                  = $commandeData["statutid"];
						try {
							    $dbAdapter->delete($prefixName."erccm_vente_commandes_invoices", array("commandeid=?"=>$commandeid));
							if(!$dbAdapter->insert($prefixName."erccm_vente_commandes_invoices", $invoiceData)) {
								$errorMessages[]                  = "Une erreur s'est produite dans l'exécution de cette opération";
							} else {
								$invoiceid                        = $dbAdapter->lastInsertId();
								$invoiceAddressData["invoiceid"]  = $invoiceid;
								$invoiceAddressData["commandeid"] = $commandeid;
								$dbAdapter->delete(     $prefixName."erccm_vente_commandes_invoices_addresses", array("accountid=?"=>$accountid,"commandeid=?"=>$commandeid));
								if(!$dbAdapter->insert( $prefixName."erccm_vente_commandes_invoices_addresses",$invoiceAddressData)) {
									$errorMessages[]              = "Une erreur s'est produite dans l'exécution de cette opération";
								}
							}
						} catch(Exception $e ) {
							$errorMessages[]                      = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
						}
					}						
				}
			}			
			//On vérifie pour voir s'il n'y a pas d'erreurs
			if(!intval($invoiceid)) {
				$errorMessages[]        = "La facture n'a pas été créée";
			}
			if( count($errorMessages) ) {
				$defaultData            = array_merge($memberData, $postData, $commandeData, $invoiceAddressData, $invoiceData);
				if( intval($commandeid) ) {
					$dbAdapter->delete($prefixName."erccm_vente_commandes"                   , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete($prefixName."erccm_vente_commandes_ligne"             , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete($prefixName."erccm_vente_commandes_invoices"          , array("commandeid=?"=>$commandeid));
					$dbAdapter->delete($prefixName."erccm_vente_commandes_invoices_addresses", array("commandeid=?"=>$commandeid));
				}
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
					exit;
				}
				foreach( $errorMessages as $errorMessage){
						 $this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			} else {
				if(!isset($orderCart->commande)) {
					$orderCart->commande          = array();
					$orderCart->invoice           = array();
					$orderCart->invoiceAddress    = array();
					$orderCart->commandeItems     = array();
					$orderCart->commandeid        = 0;
					$orderCart->invoiceid         = 0;
					$orderCart->montant           = $orderCart->commandeValeur = 0;
					$orderCart->frais_transaction = 0;
					$orderCart->payment_token     = "";
				}
				$orderCart->commande           = $commandeData;
				$orderCart->invoice            = $invoiceData;
				$orderCart->invoiceAddress     = $invoiceAddressData;
				$orderCart->commandeItems      = $commandeItems;
				$orderCart->commandeid         = $commandeid;
				$orderCart->invoiceid          = $invoiceid;
				$orderCart->commandeValeur     = $orderCart->montant = $orderCart->montant_ht = $orderCart->montant_ttc = $montantTotalTTC;
				$orderCart->frais_transaction  = 0;
				$this->setRedirect("Votre commande a été enregistrée avec succès.","success");
				$this->redirect("public/orders/checkout/id/".$commandeid);
			}
		}
		$defaultData["modepaiement"]           = (isset($defaultData["modepaiement"]))?$defaultData["modepaiement"] : 1;
		$view->data                            = $defaultData;   
		$view->title                           = "Les détails de votre commande : valider et poursuivre le paiement";
	}
	
	
	public function updateAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                                   = &$this->view;
		$orderCart                              = new Zend_Session_Namespace("ordercart");
		$application                            = new Zend_Session_Namespace("erccmapp");
		
		$model                                  = $modelCommande = $this->getModel("commande");
		$modelInvoice                           = $this->getModel("commandefacture");
		$modelProduct                           = $this->getModel("product");
		$modelProductCategory                   = $this->getModel("productcategorie");
		
		$modelTable                             = $model->getTable();
		$dbAdapter                              = $modelTable->getAdapter();
		$prefixName                             = $modelTable->info("namePrefix");	
        $tableName                              = $modelTable->info("name");			
			//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter                           =  new Zend_Filter();
		$stringFilter->addFilter(                  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(                  new Zend_Filter_StripTags());
 
		
		$errorMessages                          = $commandeItems = array();
		
		$commandeid                             = intval( $this->_getParam("commandeid", $this->_getParam("id", 0 )));
		$commande                               = $model->findRow( $commandeid , "commandeid", null, false );
		if(!$commande ) {
			$orderCart->commande                = array();
			$orderCart->commandeid              = 0;
			$orderCart->articles                = array();
			$orderCart->registres               = array();
			$orderCart->demandes                = array();
			$orderCart->documents               = array();
			$orderCart->requests                = array();
			$orderCart->counter                 = 0;
			$orderCart->initialised             = true;
			$orderCart->montant                 = $orderCart->commandeValeur = $orderCart->montant_ht = 0;
			$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=>"Veuillez procéder à l'enregistrement de la commande"));
				exit;
			}
			$this->setRedirect("Veuillez procéder à l'enregistrement de la commande", "error");
			$this->redirect("public/orders/create");
		}	
		if(!isset($orderCart->articles) || !count($orderCart->articles)) {
			$this->redirect("public/orders/checkout");
		}
		$articles                                         = $orderCart->articles;
		$commandeInvoice                                  = $modelInvoice->findRow($commandeid,"commandeid",null,false);
		$invoiceid                                        = ($commandeInvoice)?$commandeInvoice->invoiceid : 0;
		if( $commandeid && count($articles) && $commandeInvoice) {
			// On enregistre les lignes de commande
			$commandeValeur                               = 0;
			$commandeData                                 = $commande->toArray();			
			
			//$dbAdapter->delete($prefixName."erccm_vente_commandes_ligne", array("commandeid=?"=>$commandeid));
			foreach( $articles as $article ) {
				     $commandeLigneData                   = array("commandeid"=>$commandeid,"memberid"=>$memberid,"accountid"=>$accountid,"creatorid"=>$accountid,"creationdate"=>time(),"updateduserid"=>0,"updatedate"=>0);
					 $commandeLigneData["productid"]      = $productid = (isset($article["productid"]) && intval($article["productid"]))?$article["productid"] : 0;
					 $productLibelle                      = $article["libelle"];
					 $productDescription                  = $article["description"];
					 $quantite                            = (isset( $article["quantite"]) && intval($article["quantite"]))? $article["quantite"] : 1;
					 $productRow                          = (intval($productid))? $modelProduct->findRow(intval($productid),"productid",null,false) : null;
					 if(!$productRow ) {
						 continue;
					 }
					 $productLibelle                      = (!empty($productLibelle))?  $productLibelle : $productRow->libelle;
					 $productDescription                  = $productRow->description;
					 $coutTTC                             = (isset($article["cout_ttc"])     && floatval($article["cout_ttc"]  ))? floatval($article["cout_ttc"])    : $productRow->cout_ttc;
					 $coutHT                              = (isset($article["cout_ht"])      && floatval($article["cout_ht"]   ))? floatval($article["cout_ht"])     : $productRow->cout_ht;
					 
					 $commandeLigneData["productcatid"]   = (isset($article["productcatid"]) && intval($article["productcatid"]))? $article["productcatid"]          : $productRow->catid;
					 $commandeLigneData["documentid"]     = (isset($article["documentid"])   && intval($article["documentid"]  ))? $article["documentid"]            : $productRow->documentid;
					 $commandeLigneData["demandeid"]      = $demandeid  = (isset($article["demandeid"])  && intval($article["demandeid"] ))? $article["demandeid"]    : $productRow->demandeid;
					 $commandeLigneData["registreid"]     = $registreid = (isset($article["registreid"]) && intval($article["registreid"]))? $article["registreid"]   : $productRow->registreid;
					 $commandeLigneData["reference"]      = sprintf("Art%d-%d",$productid,$commandeid);
					 $commandeLigneData["libelle"]        = sprintf("%d %s"   , $quantite, $productLibelle);
					 $commandeLigneData["description"]    = $productDescription;
					 $commandeLigneData["qte"]            = $quantite;
					 $commandeLigneData["prix_unit"]      = (floatval($coutTTC))?$coutTTC    : $coutHT;
					 $commandeLigneData["valeur"]         = $valeur = $commandeLigneData["prix_unit"]*$quantite;
					 $commandeLigneData["valeur_ht"]      = $commandeLigneData["valeur_ttc"] = $valeur;
					 $commandeLigneData["valeur_remise"]  = $commandeLigneData["valeur_tva"] = $commandeLigneData["valeur_bic"] = 0;
					 if( intval($demandeid)) {
						 $commandeLigneData["registreid"] = $registreid = 0;
					 }
					 if( intval($registreid)) {
						 $commandeLigneData["demandeid"]  = $demandeid  = 0;
					 }
					 try {
						$dbAdapter->delete(     $prefixName."erccm_vente_commandes_ligne", array("commandeid=?"=>$commandeid,"productid=?"=>$productid,"demandeid=?"=>$demandeid,"registreid=?"=>$registreid));
						if(!$dbAdapter->insert( $prefixName."erccm_vente_commandes_ligne", $commandeLigneData)) {
							$errorMessages[]              = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
						} else {
							$commandeItems[$productid]    = $commandeLigneData;
							$commandeValeur               = $commandeValeur + $valeur;
						}
					 } catch(Exception $e ) {
						$errorMessages[]                  = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
					 }
			}		
			//On enregistre les informations de la facture si tout est OK
			if( empty($errorMessages) && $commandeid && floatval($commandeData["valeur"])) {
				$invoiceData["montant_total"]             = $commandeValeur;
				$invoiceData["statutid"]                  = $commandeData["statutid"];
				$invoiceData["updatedate"]                = time();
				try {
					if(!$dbAdapter->update($prefixName."erccm_vente_commandes_invoices", $invoiceData, array("commandeid=?"=>$commandeid))) {
						$errorMessages[]                  = "Une erreur s'est produite dans l'exécution de cette opération";
					}  
				} catch(Exception $e ) {
					$errorMessages[]                      = sprintf("Une erreur s'est produite dans l'exécution de cette opération : %s", $e->getMessage());
				}
			}						
		}
		if( count($errorMessages) && $invoiceid) {
			if( $dbAdapter->delete($prefixName."erccm_vente_commandes"                   , array("commandeid=?"=>$commandeid))) {
				$dbAdapter->delete($prefixName."erccm_vente_commandes_ligne"             , array("commandeid=?"=>$commandeid));
				$dbAdapter->delete($prefixName."erccm_vente_commandes_invoices"          , array("commandeid=?"=>$commandeid));
				$dbAdapter->delete($prefixName."erccm_vente_commandes_invoices_addresses", array("commandeid=?"=>$commandeid));				
			}
			$orderCart->commande                = array();
			$orderCart->invoice                 = array();
			$orderCart->invoiceAddress          = array();
			$orderCart->commandeItems           = array();
			$orderCart->commandeid              = 0;
			$orderCart->invoiceid               = $orderCart->commandeValeur = $orderCart->montant = $orderCart->montant_ht = 0;
			$orderCart->frais_transaction       = 0;
			$orderCart->payment_token           = "";
			$this->redirect("public/orders/create");
		} else {
			$orderCart->commande                = $commandeData;
			$orderCart->invoice                 = $invoiceData;
			$orderCart->invoiceAddress          = $invoiceAddressData;
			$orderCart->commandeItems           = $commandeItems;
			$orderCart->commandeid              = $commandeid;
			$orderCart->invoiceid               = $invoiceid;
			$orderCart->commandeValeur          = $orderCart->montant = $orderCart->montant_ht = $orderCart->montant_ttc = $commandeValeur;
			$orderCart->frais_transaction       = 0;
			$commandeData["valeur"]             = $commandeData["valeur_ht"] = $commandeData["valeur_ttc"] = $commandeData["valeur_sub_total"] = $commandeValeur;
			
			if( isset($commandeData["commandeid"])) {
				unset($commandeData["commandeid"]); 
			}
			$commande->setFromArray($commandeData);
			$commande->updatedate               = time();
			if( $commande->save() && $dbAdapter->delete($prefixName."erccm_vente_commandes_paiements", array("commandeid=?"=>$commandeid))) {
				$dbAdapter->delete($prefixName."erccm_vente_modepaiements"    ,array("modepaiementid IN (SELECT modepaiementid FROM ".$prefixName."erccm_vente_commandes_paiements WHERE commandeid=?)"=>$commandeid));
			    $dbAdapter->delete($prefixName."erccm_vente_modepaiements_web",array("webpaiementid  IN (SELECT modepaiementid FROM ".$prefixName."erccm_vente_commandes_paiements WHERE commandeid=?)"=>$commandeid));
			}
			
			$this->setRedirect("Votre commande a été mise à jour avec succès.","success");
			$this->redirect("public/orders/checkout");
		}
	}
	
	
	public function checkoutAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                                   = &$this->view;
		$orderCart                              = new Zend_Session_Namespace("ordercart");
		$application                            = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($orderCart->commande) || !isset($orderCart->commandeid) ||
		   !isset($orderCart->commande["ref"]) || !intval($orderCart->commandeid)) {
			$this->setRedirect("Veuillez enregistrer et valider d'abord la commande","error");
			$this->redirect("public/orders/create");
		}
		$model                                  = $this->getModel("commande");
		$modelPaiement                          = $this->getModel("commandepaiement");
		$modelInvoice                           = $this->getModel("commandefacture");
		$modelMember                            = $this->getModel("member");
		$modelCountry                           = $this->getModel("country");
		
		$modelTable                             = $model->getTable();
		$dbAdapter                              = $modelTable->getAdapter();
		$tablePrefix        = $prefixName       = $modelTable->info("namePrefix");
		
		$me                                     = Sirah_Fabric::getUser();
		$accountid                              = $me->userid;
		$member                                 = $this->_member;
		if(!$member ) {
			$accountMember                      = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                         = $modelMember->findRow($accountMember->memberid,"memberid",null,false);
			}
		}		
		$view->member                           = $member;
		
		$memberid                               = $member->memberid;
		$commandeid                             = $order_id   = $orderCart->commandeid;
		$invoiceid                              = $orderCart->invoiceid;
		$commandeRow                            = $invoiceRow = null;
		if(!$commandeRow                        = $model->findRow($commandeid,"commandeid",null,false)) {
			$orderCart->commande                = array();
			$orderCart->invoice                 = array();
			$orderCart->invoiceAddress          = array();
			$orderCart->commandeItems           = array();
			$orderCart->commandeid              = 0;
			$orderCart->invoiceid               = $orderCart->montant = $orderCart->commandeValeur = 0;
			$this->redirect("public/orders/create");
		}
		$commandePaiements                      = $commandeRow->paiements($commandeid);
		$invoiceRow                             = $modelInvoice->findRow($invoiceid,"invoiceid",null,false);
		$invoiceAddress                         = ($invoiceRow)?$invoiceRow->billing_address($invoiceid,"object") : null;
		$modePaiement                           = ($invoiceRow)?$invoiceRow->modepaiement : 1;
		$commandeItems                          = $orderCart->commandeItems;
		$numeroCommande                         = $commandeRow->ref;
		$montantTotal                           = $totalAPayer = (isset($orderCart->montant_ht) && floatval($orderCart->montant_ht))? $orderCart->montant_ht : $orderCart->commandeValeur;
		$memberCode                             = $member->code;
		//On génére le numéro de transaction  et on enregistre le paiement
		$transid                                = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
		$paiementData                           = $errorMessages = array();
		//var_dump($transid); die();
		if(!empty($transid) && !count($commandePaiements)) {
			//on enregistre le mode de paiement
			$methodPaiement                              = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
			$modePaiementData                            = array("numero"=>$transid);
		    $modePaiementData["libelle"]                 = sprintf("LIGDICASH %s n° %s du %s", $methodPaiement,$transid,date("d/m/Y"));
			$modePaiementData["bankid"]                  = 0;
			$modePaiementData["entrepriseid"]            = 0;
			$modePaiementData["banque"]                  = "LIGDICASH";
			$modePaiementData["address"]                 = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]                 = $montantTotal;
			$modePaiementData["processed"]               = 0;
			$modePaiementData["date"]                    = date("Y-m-d");
			$modePaiementData["creationdate"]            = time();
			$modePaiementData["creatorid"]               = $me->userid;
			$modePaiementData["updatedate"]              = $modePaiementData["updateduserid"] = 0;
			if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements", $modePaiementData)) {
				$modePaiementId                          = $dbAdapter->lastInsertId();
				$webPaiementData                         = array("webpaiementid"=>$modePaiementId,"transactionid"=>$transid);
				$webPaiementData["date"]                 = date("Y-m-d");
				$webPaiementData["status"]               = "UNVALIDATED";
				$webPaiementData["phonenumber"]          = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
				$webPaiementData["countrycode"]          = ($invoiceAddress )? $invoiceAddress->country : $member->country;
				$webPaiementData["payeur"]               = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)  : $member->name;
				$webPaiementData["method"]               = ($modePaiement==1)? "MOBILE_MONEY"           : "CARTE_VISA";
			    $callingCode                             = (!empty($webPaiementData["countrycode"]))?$modelCountry->callingCode($webPaiementData["countrycode"])  : "+226";
				if(!empty($callingCode)) {
					$webPaiementData["phonenumber"]      = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
				}
				if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements_web",$webPaiementData)) {
					$paiementData                        = array("commandeid"=>$commandeid,"invoiceid"=>$invoiceid,"accountid"=>$accountid,"memberid"=>$memberid,"modepaiement"=>$modePaiement,"modepaiementid"=>$modePaiementId,"validated"=>0,"canceled"=>0,"date"=>time(),"creationdate"=>time());
					$paiementData["numero"]              = $modelPaiement->autoNum(date("Y"));
					$paiementData["num_transaction"]     = "";
					$paiementData["num_commande"]        = $transid;
					$paiementData["libelle"]             = sprintf("Paiement par LIGDICASH n° %s du %s de la commande n° %s",$transid,date("d/m/Y"),$commandeRow->ref);
				    $paiementData["observation"]         = "";
					$paiementData["montant"]             = $montantTotal;
					$paiementData["totalAPayer"]         = $montantTotal;
					$paiementData["totalPaid"]           = $montantTotal;
					$paiementData["frais_transaction"]   = 0;
					$paiementData["reste"]               = 0;
					$paiementData["creatorid"]           = $me->userid;
					$paiementData["updatedate"]          = $paiementData["updateduserid"] = 0;
					if( $dbAdapter->insert( $prefixName."erccm_vente_commandes_paiements",$paiementData)) {
						$paiementId                      = $paiementData["paiementid"] = $dbAdapter->lastInsertId();
						$paiementData["transid"]         = $transid;
						$paiementData["status"]          = "UNVALIDATED";
						$paiementData["phonenumber"]     = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
						$paiementData["countrycode"]     = ($invoiceAddress )? $invoiceAddress->country : $member->country;
						$paiementData["city"]            = ($invoiceAddress )? $invoiceAddress->city    : $member->city;
						$paiementData["email"]           = ($invoiceAddress )? $invoiceAddress->email   : $member->email;
						$paiementData["zipcode"]         = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
						$paiementData["address"]         = ($invoiceAddress )? $invoiceAddress->address : $member->address;
						$paiementData["payeur"]          = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
						$paiementData["payeur_lastname"] = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			            $paiementData["payeur_firstname"]= ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
						$paiementData["method"]          = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
					}
				}
			}
		} elseif(!empty($transid) && isset($commandePaiements[0]["transid"]) && count($commandePaiements)) {
			/*if(!empty($commandePaiements[0]["transid"])) {
				$transid                     = $commandePaiements[0]["transid"];
			} else {
				$transid                     = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			}*/
			$transid                         = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			$modePaiementId                  = $commandePaiements[0]["modepaiementid"];
			$modePaiementData                = array("numero"=>$transid);
		    $modePaiementData["libelle"]     = sprintf("Paiement par LIGDICASH n° %s du %s",$transid,date("d/m/Y"));
			$modePaiementData["banque"]      = "LIGDICASH";
			$modePaiementData["address"]     = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]     = $montantTotal;
			$modePaiementData["processed"]   = 0;
			$modePaiementData["date"]        = date("Y-m-d");
			
			if( $dbAdapter->update( $prefixName."erccm_vente_modepaiements"      , $modePaiementData, array("modepaiementid=?"=>$modePaiementId))){
			    $dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"  , array("transactionid"=>$transid), array("webpaiementid=?"=>$modePaiementId));
			    $dbAdapter->update( $prefixName."erccm_vente_commandes_paiements", array("num_commande" =>$transid ,"num_transaction"=>""), array("modepaiementid=?"=>$modePaiementId));
			}
			$paiementData                    = $commandePaiements[0];
			$paiementId                      = $commandePaiements[0]["paiementid"];
			$paiementData["transid"]         = $transid;
			$paiementData["status"]          = "UNVALIDATED";
			$paiementData["phonenumber"]     = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
			$paiementData["countrycode"]     = ($invoiceAddress )? $invoiceAddress->country : $member->country;
			$paiementData["email"]           = ($invoiceAddress )? $invoiceAddress->email   : $member->email;
			$paiementData["city"]            = ($invoiceAddress )? $invoiceAddress->city    : $member->city;
			$paiementData["zipcode"]         = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
			$paiementData["address"]         = ($invoiceAddress )? $invoiceAddress->address : $member->address;
			$paiementData["payeur"]          = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
			$paiementData["payeur_lastname"] = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			$paiementData["payeur_firstname"]= ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
			$paiementData["method"]          = ($modePaiement==1)? "MOBILE_MONEY"           : "CREDIT_CARD";
			$callingCode                     = (!empty($paiementData["countrycode"]))?$modelCountry->callingCode($paiementData["countrycode"])  : "+226";
			if(!empty($callingCode)) {
				$webPaiementData["phonenumber"] = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
			}
		}
		if(!isset($paiementData["paiementid"]) || !intval($paiementData["paiementid"])) {
			$this->setRedirect("Le paiement ne peut s'effectuer pour des raisons de sécurité ","error");
			$this->redirect("public/orders/create");
		}
		$paiement_url         = $paiement_token    = $montant_ht       = "";
		$montant_ht           = $frais_transaction = $fraisTransaction = $totalAPayer = 0;
		try {
			$fraisTransaction = ceil(($montantTotal*2)/100);
			$callbackURL      = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"payments","action"=>"validate","params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$cancelURL        = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders"  ,"action"=>"refused" ,"params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$returnURL        = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders"  ,"action"=>"finalize","params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$API_ENDPOINT     = PAYMENT_GATEWAY_API_ENDPOINT; 
            $API_KEY          = PAYMENT_GATEWAY_APIKEY   ;
            $API_TOKEN        = PAYMENT_GATEWAY_API_TOKEN;
            $requestClient    = new Zend_Http_Client( $API_ENDPOINT."/pay/v01/redirect/checkout-invoice/create",array('keepalive'=> true,   
	                                                 'adapter'    => 'Zend_Http_Client_Adapter_Curl',  
									                 'curloptions'=> array(CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_HTTPHEADER=>array("Content-Type:application/json","Cache-Control: no-cache"))));
            $requestClient->setHeaders(array('ApiKey'=>$API_KEY,"Authorization"=>sprintf("Bearer %s", $API_TOKEN),"Accept"=>"application/json","Content-Type"=>"application/json"));

            $montant_ht       = $totalAPayer = $montantTotal;
            if( floatval($fraisTransaction)) {
				$montantTotal = $montantTotal + floatval($fraisTransaction);
			}
			$montantTotal     = ceil($montantTotal);
			 
			 
		    $payload          = array("commande"=> array(
														   "store"            => array (
																"name"        => "Fichier National RCCM",
																"website_url" => "https://fichiernationalrccm.bf"
														   ),
														   "actions"     => array(
																"cancel_url"  => $cancelURL,
																"return_url"  => $returnURL,
																"callback_url"=> $callbackURL
														   ),
														   "custom_data"      => array (
																"order_id"       => $commandeid,
																"transaction_id" => $transid
																
														   ),
														   "invoice"   => array(
																"total_amount"       => $montantTotal,
																"devise"             => "XOF",
																"description"        => sprintf("Cette commande n° %s concerne des demandes de réservations de noms commerciaux au profit du Fichier National RCCM", $numeroCommande),
																"customer"           => "",
																"customer_firstname" => $paiementData["payeur_firstname"],
																"customer_lastname"  => $paiementData["payeur_lastname"],
																"customer_email"     => $paiementData["email"],
																"external_id"        => "",
																"items"              => array()
														   )
												)
			);
			$item               = 0;
			if( count(   $commandeItems) ) {
				foreach( $commandeItems as $commandeItem ) {
					     $payload["commande"]["invoice"]["items"][$item] = array(
								  "name"                     => sprintf("%s", $commandeItem["libelle"]),
								  "description"              => sprintf("%s", $commandeItem["description"]),
								  "quantity"                 => sprintf("%d", $commandeItem["qte"]),
								  "unit_price"               => sprintf("%d", $commandeItem["prix_unit"]),
								  "total_price"              => sprintf("%d", $commandeItem["valeur"])
						 );
						 $item++;
				}
			}
			$payload["commande"]["invoice"]["items"][$item]["name"]        = "Frais de Transaction";
			$payload["commande"]["invoice"]["items"][$item]["description"] = sprintf("Frais de transaction de %d%% reversés à LIGDICASH", PAYMENT_GATEWAY_API_SHIPPING_COST);
			$payload["commande"]["invoice"]["items"][$item]["quantity"]    = 1;
			$payload["commande"]["invoice"]["items"][$item]["unit_price"]  = $fraisTransaction;
			$payload["commande"]["invoice"]["items"][$item]["total_price"] = $fraisTransaction;
			
			$requestClient->setMethod(Zend_Http_Client::POST);
			$requestClient->setRawData( json_encode($payload), 'application/json');
		   
			$paymentGatewayResult         = $requestClient->request();
			
			$paymentGatewayResultDataJson = ($paymentGatewayResult)? json_decode($paymentGatewayResult->getBody(), true) : array();
			//var_dump( $paymentGatewayResultDataJson); die();
			if( isset($paymentGatewayResultDataJson["response_code"]) && ($paymentGatewayResultDataJson["response_code"]=="01")) {
				$errorMessages[]          = sprintf("Une erreur s'est produite avec le module de paiement : %s", $paymentGatewayResultDataJson["response_text"]);
			}
			if( isset($paymentGatewayResultDataJson["response_code"]) && isset($paymentGatewayResultDataJson["response_text"]) && ($paymentGatewayResultDataJson["response_code"]=="00")) {
			    $paiement_url             = $orderCart->payment_url   = $paymentGatewayResultDataJson["response_text"];
				$paiement_token           = $orderCart->payment_token = $paymentGatewayResultDataJson["token"];
			} 
			if( empty($paiement_url) || empty($paiement_token)) {
				$errorMessages[]          = sprintf("Une erreur s'est produite dans le module de paiement. Veuillez en informer l'administrateur.");
			}
		} catch(Exception $e) {
			$errorMessage                 = $e->getMessage();
			/*if(!APPLICATION_DEBUG ){
				$errorMessage             = "";
			}*/
			$errorMessages[]              = sprintf("Une erreur technique s'est produite dans la connexion avec le moteur de paiement : %s. Bien vouloir réessayer dans quelques minutes oubien informer l'administrateur.", $errorMessage );
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages) ));
				exit;
			}
			foreach( $errorMessages as $key => $errorMessage ) {
					 $type = (is_numeric($key)) ? "error" : $key;
					 $this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} 
		$paiementData["frais_transaction"] = $fraisTransaction;
		$paiementData["montant_ht"]        = $montant_ht = $montantTotal - $fraisTransaction;
		$totalAPayer                       = $totalPaid  = $paiementData["totalAPayer"] = $paiementData["montant_ttc"] = $montantTotal;
		$apiNumTrans                       = $paiement_token;
		$orderCart->montant                = $totalAPayer;
		$orderCart->frais_transaction      = $fraisTransaction;
		$paiementDataUpdated               = array("totalPaid"=>$totalPaid,"totalAPayer"=>$totalAPayer,"frais_transaction"=>$fraisTransaction,"num_transaction"=>$apiNumTrans,"updatedate"=>time(),"updateduserid"=>26);
		try {
			$dbAdapter->update( $prefixName."erccm_vente_commandes_paiements", $paiementDataUpdated, array("paiementid=?"=>$paiementId));
			$dbAdapter->update( $prefixName."erccm_vente_commandes"          , array("payment_url"=>$paiement_url,"payment_token"=>$paiement_token,"valeur_ttc"=>$montantTotal,"valeur_ht"=>$montant_ht,"frais"=>$fraisTransaction), array("commandeid=?"=>$commandeid));
		} catch(Exception $e ) {
			$this->getHelper("Message")->addMessage( "Erreur technique sur la mise à jour des informations du paiement","error");
		}	
		//var_dump($orderCart->commandeid); die();
		//On génère l'url de notification
		$callbackURL            = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders","action"=>"finalize","params"=> array("id"=>$paiementId,"order_id"=>$commandeid),"orderid"=>$commandeid));
 		
		$orderCart->paiement    = $paiementData;
		$view->data             = $paiementData;
		$view->commande         = $commandeRow;
		$view->invoice          = $invoiceRow;
		$view->commandeid       = $commandeid;
		$view->paiementid       = $paiementId;
		$view->paiement_url     = $paiement_url;
		$view->paiement_token   = $paiement_token;
		$view->frais_transaction= $fraisTransaction;
		$view->montant_ht       = $montant_ht;
		$view->montant_ttc      = $totalAPayer;
		$view->customer         = $view->member = $member;
		$view->trans_id         = $transid;
		$view->notify_url       = $callbackURL;
		$view->apitoken         = (defined("PAYMENT_GATEWAY_API_TOKEN"))? PAYMENT_GATEWAY_API_TOKEN : "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF9hcHAiOiIyNDgyNyIsImlkX2Fib25uZSI6NzU5MzkwLCJkYXRlY3JlYXRpb25fYXBwIjoiMjAyNS0wNi0wMyAxMjowMDoxMiJ9.fGWi-mhBNNCgPpkrd8XZhaU1YIbxXMj9XVR1lMZto5o";
		$view->apikey           = (defined("PAYMENT_GATEWAY_APIKEY"   ))? PAYMENT_GATEWAY_APIKEY    : "IKNCWCQD4H1K3W919";
		$view->api_site_id      = (defined("PAYMENT_GATEWAY_SITE_ID"  ))? PAYMENT_GATEWAY_SITE_ID   : "IKNCWCQD4H1K3W919";
		$view->title            = "Procéder au paiement de la commande";
		$content                = ob_end_clean();
		$this->render("payment");
	}
	
	
	
	public function checkoutestAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                                   = &$this->view;
		$orderCart                              = new Zend_Session_Namespace("ordercart");
		$application                            = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($orderCart->commande) || !isset($orderCart->commandeid) ||
		   !isset($orderCart->commande["ref"]) || !intval($orderCart->commandeid)) {
			$this->setRedirect("Veuillez enregistrer et valider d'abord la commande","error");
			$this->redirect("public/orders/create");
		}
		$model                                  = $this->getModel("commande");
		$modelPaiement                          = $this->getModel("commandepaiement");
		$modelInvoice                           = $this->getModel("commandefacture");
		$modelMember                            = $this->getModel("member");
		$modelCountry                           = $this->getModel("country");
		
		$modelTable                             = $model->getTable();
		$dbAdapter                              = $modelTable->getAdapter();
		$tablePrefix        = $prefixName       = $modelTable->info("namePrefix");
		
		$me                                     = Sirah_Fabric::getUser();
		$accountid                              = $me->userid;
		$member                                 = $this->_member;
		if(!$member ) {
			$accountMember                      = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                         = $modelMember->findRow($accountMember->memberid,"memberid",null,false);
			}
		}		
		$view->member                           = $member;
		
		$memberid                               = $member->memberid;
		$commandeid                             = $order_id   = $orderCart->commandeid;
		$invoiceid                              = $orderCart->invoiceid;
		$commandeRow                            = $invoiceRow = null;
		if(!$commandeRow                        = $model->findRow($commandeid,"commandeid",null,false)) {
			$orderCart->commande                = array();
			$orderCart->invoice                 = array();
			$orderCart->invoiceAddress          = array();
			$orderCart->commandeItems           = array();
			$orderCart->commandeid              = 0;
			$orderCart->invoiceid               = $orderCart->montant = $orderCart->commandeValeur = 0;
			$this->redirect("public/orders/create");
		}
		$commandePaiements                      = $commandeRow->paiements($commandeid);
		$invoiceRow                             = $modelInvoice->findRow($invoiceid,"invoiceid",null,false);
		$invoiceAddress                         = ($invoiceRow)?$invoiceRow->billing_address($invoiceid,"object") : null;
		$modePaiement                           = ($invoiceRow)?$invoiceRow->modepaiement : 1;
		$commandeItems                          = $orderCart->commandeItems;
		$numeroCommande                         = $commandeRow->ref;
		$montantTotal                           = $totalAPayer = (isset($orderCart->montant_ht) && floatval($orderCart->montant_ht))? $orderCart->montant_ht : $orderCart->commandeValeur;
		$memberCode                             = $member->code;
		//On génére le numéro de transaction  et on enregistre le paiement
		$transid                                = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
		$paiementData                           = $errorMessages = array();
		//var_dump($transid); die();
		if(!empty($transid) && !count($commandePaiements)) {
			//on enregistre le mode de paiement
			$methodPaiement                              = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
			$modePaiementData                            = array("numero"=>$transid);
		    $modePaiementData["libelle"]                 = sprintf("LIGDICASH %s n° %s du %s", $methodPaiement,$transid,date("d/m/Y"));
			$modePaiementData["bankid"]                  = 0;
			$modePaiementData["entrepriseid"]            = 0;
			$modePaiementData["banque"]                  = "LIGDICASH";
			$modePaiementData["address"]                 = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]                 = $montantTotal;
			$modePaiementData["processed"]               = 0;
			$modePaiementData["date"]                    = date("Y-m-d");
			$modePaiementData["creationdate"]            = time();
			$modePaiementData["creatorid"]               = $me->userid;
			$modePaiementData["updatedate"]              = $modePaiementData["updateduserid"] = 0;
			if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements", $modePaiementData)) {
				$modePaiementId                          = $dbAdapter->lastInsertId();
				$webPaiementData                         = array("webpaiementid"=>$modePaiementId,"transactionid"=>$transid);
				$webPaiementData["date"]                 = date("Y-m-d");
				$webPaiementData["status"]               = "UNVALIDATED";
				$webPaiementData["phonenumber"]          = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
				$webPaiementData["countrycode"]          = ($invoiceAddress )? $invoiceAddress->country : $member->country;
				$webPaiementData["payeur"]               = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)  : $member->name;
				$webPaiementData["method"]               = ($modePaiement==1)? "MOBILE_MONEY"           : "CARTE_VISA";
			    $callingCode                             = (!empty($webPaiementData["countrycode"]))?$modelCountry->callingCode($webPaiementData["countrycode"])  : "+226";
				if(!empty($callingCode)) {
					$webPaiementData["phonenumber"]      = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
				}
				if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements_web",$webPaiementData)) {
					$paiementData                        = array("commandeid"=>$commandeid,"invoiceid"=>$invoiceid,"accountid"=>$accountid,"memberid"=>$memberid,"modepaiement"=>$modePaiement,"modepaiementid"=>$modePaiementId,"validated"=>0,"canceled"=>0,"date"=>time(),"creationdate"=>time());
					$paiementData["numero"]              = $modelPaiement->autoNum(date("Y"));
					$paiementData["num_transaction"]     = "";
					$paiementData["num_commande"]        = $transid;
					$paiementData["libelle"]             = sprintf("Paiement par LIGDICASH n° %s du %s de la commande n° %s",$transid,date("d/m/Y"),$commandeRow->ref);
				    $paiementData["observation"]         = "";
					$paiementData["montant"]             = $montantTotal;
					$paiementData["totalAPayer"]         = $montantTotal;
					$paiementData["totalPaid"]           = $montantTotal;
					$paiementData["frais_transaction"]   = 0;
					$paiementData["reste"]               = 0;
					$paiementData["creatorid"]           = $me->userid;
					$paiementData["updatedate"]          = $paiementData["updateduserid"] = 0;
					if( $dbAdapter->insert( $prefixName."erccm_vente_commandes_paiements",$paiementData)) {
						$paiementId                      = $paiementData["paiementid"] = $dbAdapter->lastInsertId();
						$paiementData["transid"]         = $transid;
						$paiementData["status"]          = "UNVALIDATED";
						$paiementData["phonenumber"]     = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
						$paiementData["countrycode"]     = ($invoiceAddress )? $invoiceAddress->country : $member->country;
						$paiementData["city"]            = ($invoiceAddress )? $invoiceAddress->city    : $member->city;
						$paiementData["email"]           = ($invoiceAddress )? $invoiceAddress->email   : $member->email;
						$paiementData["zipcode"]         = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
						$paiementData["address"]         = ($invoiceAddress )? $invoiceAddress->address : $member->address;
						$paiementData["payeur"]          = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
						$paiementData["payeur_lastname"] = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			            $paiementData["payeur_firstname"]= ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
						$paiementData["method"]          = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
					}
				}
			}
		} elseif(!empty($transid) && isset($commandePaiements[0]["transid"]) && count($commandePaiements)) {
			/*if(!empty($commandePaiements[0]["transid"])) {
				$transid                     = $commandePaiements[0]["transid"];
			} else {
				$transid                     = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			}*/
			$transid                         = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			$modePaiementId                  = $commandePaiements[0]["modepaiementid"];
			$modePaiementData                = array("numero"=>$transid);
		    $modePaiementData["libelle"]     = sprintf("Paiement par LIGDICASH n° %s du %s",$transid,date("d/m/Y"));
			$modePaiementData["banque"]      = "LIGDICASH";
			$modePaiementData["address"]     = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]     = $montantTotal;
			$modePaiementData["processed"]   = 0;
			$modePaiementData["date"]        = date("Y-m-d");
			
			if( $dbAdapter->update( $prefixName."erccm_vente_modepaiements"      , $modePaiementData, array("modepaiementid=?"=>$modePaiementId))){
			    $dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"  , array("transactionid"=>$transid), array("webpaiementid=?"=>$modePaiementId));
			    $dbAdapter->update( $prefixName."erccm_vente_commandes_paiements", array("num_commande" =>$transid ,"num_transaction"=>""), array("modepaiementid=?"=>$modePaiementId));
			}
			$paiementData                    = $commandePaiements[0];
			$paiementId                      = $commandePaiements[0]["paiementid"];
			$paiementData["transid"]         = $transid;
			$paiementData["status"]          = "UNVALIDATED";
			$paiementData["phonenumber"]     = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
			$paiementData["countrycode"]     = ($invoiceAddress )? $invoiceAddress->country : $member->country;
			$paiementData["email"]           = ($invoiceAddress )? $invoiceAddress->email   : $member->email;
			$paiementData["city"]            = ($invoiceAddress )? $invoiceAddress->city    : $member->city;
			$paiementData["zipcode"]         = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
			$paiementData["address"]         = ($invoiceAddress )? $invoiceAddress->address : $member->address;
			$paiementData["payeur"]          = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
			$paiementData["payeur_lastname"] = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			$paiementData["payeur_firstname"]= ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
			$paiementData["method"]          = ($modePaiement==1)? "MOBILE_MONEY"           : "CREDIT_CARD";
			$callingCode                     = (!empty($paiementData["countrycode"]))?$modelCountry->callingCode($paiementData["countrycode"])  : "+226";
			if(!empty($callingCode)) {
				$webPaiementData["phonenumber"] = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
			}
		}
		if(!isset($paiementData["paiementid"]) || !intval($paiementData["paiementid"])) {
			$this->setRedirect("Le paiement ne peut s'effectuer pour des raisons de sécurité ","error");
			$this->redirect("public/orders/create");
		}
		$paiement_url         = $paiement_token    = $montant_ht       = "";
		$montant_ht           = $frais_transaction = $fraisTransaction = $totalAPayer = 0;
		try {
			$fraisTransaction = ceil(($montantTotal*2)/100);
			$callbackURL      = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"payments","action"=>"validate","params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$cancelURL        = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders"  ,"action"=>"refused" ,"params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$returnURL        = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders"  ,"action"=>"finalize","params"=> array("id"=>$paiementId,"orderid"=>$commandeid,"trans_id"=>$transid)));
			$API_ENDPOINT     = PAYMENT_GATEWAY_API_ENDPOINT; 
            $API_KEY          = PAYMENT_GATEWAY_APIKEY   ;
            $API_TOKEN        = PAYMENT_GATEWAY_API_TOKEN;
            $requestClient    = new Zend_Http_Client( $API_ENDPOINT."/pay/v01/redirect/checkout-invoice/create",array('keepalive'=> true,   
	                                                 'adapter'    => 'Zend_Http_Client_Adapter_Curl',  
									                 'curloptions'=> array(CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_HTTPHEADER=>array("Content-Type:application/json","Cache-Control: no-cache"))));
            $requestClient->setHeaders(array('ApiKey'=>$API_KEY,"Authorization"=>sprintf("Bearer %s", $API_TOKEN),"Accept"=>"application/json","Content-Type"=>"application/json"));

            $montant_ht       = $totalAPayer = $montantTotal;
            if( floatval($fraisTransaction)) {
				$montantTotal = $montantTotal + floatval($fraisTransaction);
			}
			$montantTotal     = ceil($montantTotal);
			 
			 
		    $payload          = array("commande"=> array(
														   "store"            => array (
																"name"        => "Fichier National RCCM",
																"website_url" => "https://fichiernationalrccm.bf"
														   ),
														   "actions"     => array(
																"cancel_url"  => $cancelURL,
																"return_url"  => $returnURL,
																"callback_url"=> $callbackURL
														   ),
														   "custom_data"      => array (
																"order_id"       => $commandeid,
																"transaction_id" => $transid
																
														   ),
														   "invoice"   => array(
																"total_amount"       => 100,
																"devise"             => "XOF",
																"description"        => sprintf("Cette commande n° %s concerne des demandes de réservations de noms commerciaux au profit du Fichier National RCCM", $numeroCommande),
																"customer"           => "",
																"customer_firstname" => $paiementData["payeur_firstname"],
																"customer_lastname"  => $paiementData["payeur_lastname"],
																"customer_email"     => $paiementData["email"],
																"external_id"        => "",
																"items"              => array()
														   )
												)
			);
			$item               = 0;
			if( count(   $commandeItems) ) {
				foreach( $commandeItems as $commandeItem ) {
					     $payload["commande"]["invoice"]["items"][$item] = array(
								  "name"                     => sprintf("%s", $commandeItem["libelle"]),
								  "description"              => sprintf("%s", $commandeItem["description"]),
								  "quantity"                 => sprintf("%d", $commandeItem["qte"]),
								  "unit_price"               => sprintf("%d", $commandeItem["prix_unit"]),
								  "total_price"              => sprintf("%d", $commandeItem["valeur"])
						 );
						 $item++;
				}
			}
			$payload["commande"]["invoice"]["items"][$item]["name"]        = "Frais de Transaction";
			$payload["commande"]["invoice"]["items"][$item]["description"] = sprintf("Frais de transaction de %d%% reversés à LIGDICASH", PAYMENT_GATEWAY_API_SHIPPING_COST);
			$payload["commande"]["invoice"]["items"][$item]["quantity"]    = 1;
			$payload["commande"]["invoice"]["items"][$item]["unit_price"]  = $fraisTransaction;
			$payload["commande"]["invoice"]["items"][$item]["total_price"] = $fraisTransaction;
			
			$requestClient->setMethod(Zend_Http_Client::POST);
			$requestClient->setRawData( json_encode($payload), 'application/json');
		   
			$paymentGatewayResult         = $requestClient->request();
			
			$paymentGatewayResultDataJson = ($paymentGatewayResult)? json_decode($paymentGatewayResult->getBody(), true) : array();
			//var_dump( $paymentGatewayResultDataJson); die();
			if( isset($paymentGatewayResultDataJson["response_code"]) && ($paymentGatewayResultDataJson["response_code"]=="01")) {
				$errorMessages[]          = sprintf("Une erreur s'est produite avec le module de paiement : %s", $paymentGatewayResultDataJson["response_text"]);
			}
			if( isset($paymentGatewayResultDataJson["response_code"]) && isset($paymentGatewayResultDataJson["response_text"]) && ($paymentGatewayResultDataJson["response_code"]=="00")) {
			    $paiement_url             = $orderCart->payment_url   = $paymentGatewayResultDataJson["response_text"];
				$paiement_token           = $orderCart->payment_token = $paymentGatewayResultDataJson["token"];
			} 
			if( empty($paiement_url) || empty($paiement_token)) {
				$errorMessages[]          = sprintf("Une erreur s'est produite dans le module de paiement. Veuillez en informer l'administrateur.");
			}
		} catch(Exception $e) {
			$errorMessage                 = $e->getMessage();
			/*if(!APPLICATION_DEBUG ){
				$errorMessage             = "";
			}*/
			$errorMessages[]              = sprintf("Une erreur technique s'est produite dans la connexion avec le moteur de paiement : %s. Bien vouloir réessayer dans quelques minutes oubien informer l'administrateur.", $errorMessage );
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages) ));
				exit;
			}
			foreach( $errorMessages as $key => $errorMessage ) {
					 $type = (is_numeric($key)) ? "error" : $key;
					 $this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} 
		$paiementData["frais_transaction"] = $fraisTransaction;
		$paiementData["montant_ht"]        = $montant_ht = $montantTotal - $fraisTransaction;
		$totalAPayer                       = $totalPaid  = $paiementData["totalAPayer"] = $paiementData["montant_ttc"] = $montantTotal;
		$apiNumTrans                       = $paiement_token;
		$orderCart->montant                = $totalAPayer;
		$orderCart->frais_transaction      = $fraisTransaction;
		$paiementDataUpdated               = array("totalPaid"=>$totalPaid,"totalAPayer"=>$totalAPayer,"frais_transaction"=>$fraisTransaction,"num_transaction"=>$apiNumTrans,"updatedate"=>time(),"updateduserid"=>26);
		try {
			$dbAdapter->update( $prefixName."erccm_vente_commandes_paiements", $paiementDataUpdated, array("paiementid=?"=>$paiementId));
			$dbAdapter->update( $prefixName."erccm_vente_commandes"          , array("payment_url"=>$paiement_url,"payment_token"=>$paiement_token,"valeur_ttc"=>$montantTotal,"valeur_ht"=>$montant_ht,"frais"=>$fraisTransaction), array("commandeid=?"=>$commandeid));
		} catch(Exception $e ) {
			$this->getHelper("Message")->addMessage( "Erreur technique sur la mise à jour des informations du paiement","error");
		}	
		//var_dump($orderCart->commandeid); die();
		//On génère l'url de notification
		$callbackURL            = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"orders","action"=>"finalize","params"=> array("id"=>$paiementId,"order_id"=>$commandeid),"orderid"=>$commandeid));
 		
		$orderCart->paiement    = $paiementData;
		$view->data             = $paiementData;
		$view->commande         = $commandeRow;
		$view->invoice          = $invoiceRow;
		$view->commandeid       = $commandeid;
		$view->paiementid       = $paiementId;
		$view->paiement_url     = $paiement_url;
		$view->paiement_token   = $paiement_token;
		$view->frais_transaction= $fraisTransaction;
		$view->montant_ht       = $montant_ht;
		$view->montant_ttc      = $totalAPayer;
		$view->customer         = $view->member = $member;
		$view->trans_id         = $transid;
		$view->notify_url       = $callbackURL;
		$view->apitoken         = (defined("PAYMENT_GATEWAY_API_TOKEN"))? PAYMENT_GATEWAY_API_TOKEN : "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF9hcHAiOiIyNDgyNyIsImlkX2Fib25uZSI6NzU5MzkwLCJkYXRlY3JlYXRpb25fYXBwIjoiMjAyNS0wNi0wMyAxMjowMDoxMiJ9.fGWi-mhBNNCgPpkrd8XZhaU1YIbxXMj9XVR1lMZto5o";
		$view->apikey           = (defined("PAYMENT_GATEWAY_APIKEY"   ))? PAYMENT_GATEWAY_APIKEY    : "IKNCWCQD4H1K3W919";
		$view->api_site_id      = (defined("PAYMENT_GATEWAY_SITE_ID"  ))? PAYMENT_GATEWAY_SITE_ID   : "IKNCWCQD4H1K3W919";
		$view->title            = "Procéder au paiement de la commande";
		$content                = ob_end_clean();
		$this->render("payment");
	}
	
	public function errorlogAction()
	{
		$orderCart        = new Zend_Session_Namespace("ordercart");
		var_dump($orderCart);
		print_r(error_get_last());
		exit;
	}
	
	public function finalizeAction()
	{
		sleep(9);
		
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$model            = $modelCommande = $this->getModel("commande");
		$modelPaiement    = $this->getModel("commandepaiement");
		$modelInvoice     = $this->getModel("commandefacture");
		$modelMember      = $this->getModel("member");
 
		
		$me               = Sirah_Fabric::getUser();
        
		
		$view             = &$this->view;
		$orderCart        = new Zend_Session_Namespace("ordercart");
		$application      = new Zend_Session_Namespace("erccmapp");
		
		 
		$requestParams    = $this->_request->getParams();
 
		 
		$modelTable                             = $model->getTable();
		$dbAdapter                              = $modelTable->getAdapter();
		$tablePrefix      = $prefixName         = $modelTable->info("namePrefix");
		
		$me                                     = Sirah_Fabric::getUser();
		$accountid                              = $myAccountid = $me->userid;
		$member                                 = $this->_member;
		if(!$member ) {
			$accountMember                      = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                         = $modelMember->findRow($accountMember->memberid,"memberid",null,false);
			}
		}		
		$view->member                           = $member;
		
		$memberid                               = $member->memberid;
		$commandeid                             = $order_id   = $orderCart->commandeid;
		$invoiceid                              = $orderCart->invoiceid;
		$commandeRow                            = $invoiceRow = null;
		
		if(!intval($commandeid) && isset($requestParams["orderid"])) {
			$commandeid                         = intval($requestParams["orderid"]);
		}
		if( intval($commandeid) == 0 ) {  
		    $mToken                   = (isset($requestParams["token"]) && !empty($requestParams["token"]))?$requestParams["token"] : "";
			$payload                  = file_get_contents('php://input');
		    $event                    = json_decode($payload);
			if( empty($mToken) && $event) {
				$mToken               = $event->token;
			 
			}
		    if(!empty($mToken)) {
				$API_ENDPOINT         = PAYMENT_GATEWAY_API_ENDPOINT; 
				$API_KEY              = PAYMENT_GATEWAY_APIKEY   ;
				$API_TOKEN            = PAYMENT_GATEWAY_API_TOKEN;
				$requestClient        = new Zend_Http_Client( $API_ENDPOINT."/pay/v01/redirect/checkout-invoice/confirm?invoiceToken=".$mToken,array('keepalive'=> true,   
															  'adapter'    => 'Zend_Http_Client_Adapter_Curl',  
															  'curloptions'=> array(CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_HTTPHEADER=>array("Content-Type:application/json","Cache-Control: no-cache"))));
				$requestClient->setHeaders(array('ApiKey'=>$API_KEY,"Authorization"=>sprintf("Bearer %s", $API_TOKEN),"Accept"=>"application/json","Content-Type"=>"application/json"));
				
				$requestClient->setMethod(Zend_Http_Client::GET);
				$checkPaymentRes      = $requestClient->request();
				$checkPaymentData     = ($checkPaymentRes)? json_decode($checkPaymentRes->getBody(), true) : array();
				if( isset($checkPaymentData["custom_data"]) && !empty($checkPaymentData["custom_data"])) {
					$custom_data      = $checkPaymentData["custom_data"][0];
					$mOrder_id        = $commandeid = $order_id = (isset($custom_data->valueof_customdata))? $custom_data->valueof_customdata : $custom_data["valueof_customdata"];
				} 
			}
		}
		$commandeRow                  = $commande = $modelCommande->findRow($commandeid,"commandeid",null,false);
		if( null ==$commandeRow ) {
			$this->setRedirect( "Paiement sur une commande invalide : ".$commandeid, "error");
			$this->redirect("public/orders/create");
		}
        $isReservationRequest          = true;
		/*
		$invoiceRow                    = ($invoiceid )? $modelInvoice->findRow($invoiceid,"invoiceid",null,false) : null;
		$commandePaiements             = ($commandeid)? $model->paiements($commandeid) : array();
		*/
	  
	    $orderCart->articles           = array();
		$orderCart->registres          = array();
		$orderCart->documents          = array();
		$orderCart->requests           = array();
		$orderCart->commande           = array();
		$orderCart->invoice            = array();
		$orderCart->invoiceAddress     = array();
		$orderCart->commandeItems      = array();
		$orderCart->commandeid         = 0;
		$orderCart->invoiceid          = 0;
		$orderCart->counter            = 0;
		$orderCart->initialised        = true;
		$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);  
		if( $commandeRow->validated ) {			
			try {
			  $dbAdapter->update( $tablePrefix."reservation_payments_logs", array("finalized"=>1,"finalize_date"=>date("d/m/Y h:i:s")),array("commandeid=?"=>$commandeid));
			} catch(Exception $e ) {
			}
			$successMessage            = "Vous avez finalisé avec succès la commande. Votre demande sera traitée dans un délai de 48H maximum après que votre paiement ait été validé.";
			if( $isReservationRequest) {
				$successMessage        = "Votre commande a été validée avec succès. Votre nom commercial ou dénomination sociale est reservé sous réserve de confirmation par l'acteur compétent dans un délai de 48H maximum";
			}
			
			$this->setRedirect( $successMessage, "success");
			$this->redirect("public/members/dashboard");
		} else {
			try {
			  $dbAdapter->update( $tablePrefix."reservation_payments_logs", array("finalized"=>0,"finalize_date"=>date("d/m/Y h:i:s")),array("commandeid=?"=>$commandeid));
			} catch(Exception $e ) {
			}
			$this->setRedirect( "Votre opération de paiement a été enregistrée et est en attente de validation. Vérifiez votre tableau de bord dans quelques minutes", "success");
		    $this->redirect("public/members/dashboard");
		}
	}
	
	
	
	public function validatepaiementsAction()
	{
		$model              = $modelCommande = $this->getModel("commande");
		$modelPaiement      = $this->getModel("commandepaiement");
		$modelInvoice       = $this->getModel("commandefacture");
		$modelMember        = $this->getModel("member");
 
		
		$me                 = Sirah_Fabric::getUser();
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $prefixName = $table->info("namePrefix");
		$status             = "COMPLETED";
		$errorMessages      = array();
		$selectValidatedCommandes   = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_payments_logs"))
												->where("R.status=?", $status);
		$validatedCommandes = $dbAdapter->fetchAll($selectValidatedCommandes,array(),5); 
		var_dump($validatedCommandes); die();
        if( count(   $validatedCommandes) ) {
			foreach( $validatedCommandes as $validatedCommande ) {
			         $commandeid    = $validatedCommande["commandeid"];
					 if(!$commandeid) {
						 continue;
					 }
					 $commandeRow  = $commande = $modelCommande->findRow($commandeid,"commandeid",null,false);
					 if( null != $commandeRow ) {
						 if(!$commandeRow->validated ) {
							 $commandeRow->validated  = 1;
							 $commandeRow->updatedate = time();
							 $invoiceRow              = ($comandeid )? $modelInvoice->findRow($commandeid,"commandeid",null,false) : null;
							 $commandePaiements       = ($commandeid)? $modelCommande->paiements($commandeid) : array();
							
							 if(!count($commandePaiements) || !$commandeRow ) {
								 $date             = $checkPaymentData["date"];
								 $description      = $checkPaymentData["response_text"];
								 $errorMessages[]  = $errorMessage;
								 
							 }
							 if(!empty($commandePaiements[0]["transid"])) {
								$transid           = $paiementTransId = $trans_id = $commandePaiements[0]["transid"];
							 } else {
								$transid           = $paiementTransId = $trans_id = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
							 }
							 $paiementid           = $commandePaiements[0]["paiementid"];
							 $modePaiementId       = $commandePaiements[0]["modepaiementid"];
							 $transaction_id       = (isset($checkPaymentData["transaction_id"]))? $checkPaymentData["transaction_id"] : $transid;
							 $fraisTransaction     = "49";
							 $totalPaid            = $commandeRow->valeur_ht;
							 $totalAPayer          = $totalPaid+49;
							
							 $paiementDataUpdated  = array( "totalPaid"=>$totalPaid,"totalAPayer"=>$totalAPayer,"frais_transaction"=>$fraisTransaction,
														    "statutid" =>2,"validated"=>1,"num_transaction"=>$transaction_id,"num_commande"=>$transaction_id,"updatedate"=>time(),"updateduserid"=>26);
							 try {
								if( $dbAdapter->update( $prefixName."erccm_vente_commandes_paiements" ,$paiementDataUpdated , array("paiementid=?"=>$paiementid))) {
									$dbAdapter->update( $prefixName."erccm_vente_modepaiements"       ,array("processed"=>1), array("numero=\"".$trans_id."\""));
									$dbAdapter->update( $prefixName."erccm_vente_commandes_invoices"  ,array("statutid" =>3,"updatedate"=>time(),"updateduserid"=>26)              , array("commandeid=?"=>$commandeid));
									$dbAdapter->update( $prefixName."erccm_vente_commandes"           ,array("validated"=>1,"statutid"=>3,"updatedate"=>time(),"updateduserid"=>26), array("commandeid=?"=>$commandeid));
									$dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"   ,array("status"   =>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
									$commandeProducts       = $modelCommande->products($commandeid);
									if( count(   $commandeProducts) ) {
										foreach( $commandeProducts as  $commandeProduct) {
												 if( isset($commandeProduct["demandeid"]) && intval($commandeProduct["demandeid"])) {
													 $isReservationRequest = true;
													 $demandeid            = intval($commandeProduct["demandeid"]);
													 $dbAdapter->update( $prefixName."reservation_demandes",array("statutid"=>1,"updatedate"=>time(),"updateduserid"=>26), array("demandeid=?"=>$demandeid));
												 }
										}
									}
								} else {
									$errorMessages[]       = sprintf("Le paiement n'a pas été enregistré pour des raisons inconnues. Veuillez signaler à l'administrateur");
								}
							 } catch( Exception $e ) {
									$errorMessages[]       = sprintf("Le paiement n'a pas été enregistré pour des raisons inconnues. Veuillez signaler à l'administrateur");
							 }	 
		                 }
					 }
			}
		}			
		 
		
		if( count( $errorMessages )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout();
				echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message) {
					 $this->_helper->Message->addMessage( $message ) ;
			}
		} else {
			$this->setRedirect("Opération effectuée avec succès","success");
		}			
 	    $this->redirect("public/members/dashboard");
        
	}
	
	
	 
 
}


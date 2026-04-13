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
 
defined("APPLICATION_ORDERCART_EXPIRATION")
    || define("APPLICATION_ORDERCART_EXPIRATION",86400);	

class OrdercartController extends Sirah_Controller_Default
{
	
	protected $_member  = null;
	
	protected $_cart    = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		$me             = $loggedInUser = Sirah_Fabric::getUser();
		/*
		if(!$me->isOPERATEURS() && !$me->isPARTENAIRES() && !$me->isPARTNERS() && !$me->isPromoteurs() && !$me->isDirecteurs()) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Vous n'êtes pas autorisé à accéder à cette page" ));
				exit;
			}
			$this->redirect("public/account/login");
		}  
		$model                   = $this->getModel("member");
		$accountMember           = $model->fromuser($me->userid);
		if(!$accountMember ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
			$this->redirect("public/account/login");
		}
		$memberid                = $accountMember->memberid;
		$member                  = $model->findRow( $memberid , "memberid", null , false );
		if(!$member ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
			$this->redirect("public/account/login");	
		}
		$this->_member  = $member;
		*/
			
        $orderCart      = new Zend_Session_Namespace("ordercart");
		if(!isset($orderCart->initialised) || !$orderCart->initialised ) {
			$orderCart->articles    = array();
			$orderCart->registres   = array();
			$orderCart->demandes    = array();
			$orderCart->documents   = array();
			$orderCart->requests    = array();
			$orderCart->counter     = 0;
			$orderCart->initialised = true;
			$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
		}
		$this->_cart  = &$orderCart;
		parent::init();
	}
	
	
	public function counterAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$orderCart             = new Zend_Session_Namespace("ordercart");
		if( $this->_request->isXmlHttpRequest()) {			
			echo ZendX_JQuery::encodeJson(array("success"=>" Les informations de la carte","counter"=>$orderCart->counter,"value"=>$orderCart->counter));
			exit;
		}
		echo $orderCart->counter;
		exit;
	}
	
	
	public function addregistreAction()
	{
		$registreid            = intval($this->_getParam("registreid" , 0));		
		$modelProduct          = $this->getModel("product");
		$modelProductCategory  = $this->getModel("productcategorie");
		$modelRegistre         = $this->getModel("registre");
		$modelDocument         = $this->getModel("document");
		$modelCommande         = $model = $this->getModel("commande");
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$tablePrefix           = $prefixName  = $modelTable->info("namePrefix");
		
		$registre              = ($registreid)?$modelRegistre->findRow($registreid,"registreid",null,false) : null;
 
		$errorMessages         = array();
		$orderCart             = new Zend_Session_Namespace("ordercart");
		$application           = new Zend_Session_Namespace("erccmapp");
		
		if(!$registre) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>" Les paramètres fournis pour cette requête sont invalides." ));
				exit;
			}
			$this->setRedirect(" Les paramètres fournis pour cette requête sont invalides.","error");
			$this->redirect("public/registres/search");
		}
		$registreDocuments     = $modelRegistre->documents($registreid,null,null);
		if(!count($registreDocuments)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Aucun document n'a été trouvé dans le registre de l'entreprise" ));
				exit;
			}
			$this->setRedirect("Aucun document n'a été trouvé dans le registre de l'entreprise.","error");
			$this->redirect("public/registres/infos/registreid/".$registreid);
		}
		//var_dump($registreDocuments); die();
		if( count(   $registreDocuments)) {
			foreach( $registreDocuments as  $registreDocument ) {
				     $documentid         =  $registreDocument["documentid"];
					 $documentCatid      = (isset($registreDocument["catid"]) && intval($registreDocument["catid"]))?intval($registreDocument["catid"]):0;
					 
					 $documentProduct    =  $modelProduct->registredoc($documentid,$registreid);
					 $productCategorie   = (intval($documentCatid))?$modelProductCategory->findRow($documentCatid,"documentcatid",null,false) : null;
					 if(!isset($documentProduct["documentid"])) {						 			
						 $productData                  = $modelProduct->getEmptyData();
						 $productData["catid"]         = ($productCategorie)?$productCategorie->catid : 0;
						 $productData["documentcatid"] = $documentCatid;
						 $productData["documentid"]    = $documentid;
						 $productData["registreid"]    = $registreid;
						 $productData["code"]          = sprintf("Prod-%06d/%06d:%s",$registreid,$documentid,$registre->numero);
						 $productData["libelle"]       = sprintf("%s du RCCM n° %s" ,$registreDocument["categorie"],$registre->numero);
						 $productData["description"]   = sprintf("%s du RCCM n° %s.\nNom Commercial:%s;\nN° CNSS :%s;\nN° IFU : %s",
																$registreDocument["categorie"],$registre->numero,$registre->libelle,$registre->numcnss,$registre->numifu);
						 $productData["cout_ttc"]      = (isset($productCategorie->cout_ttc) && floatval($productCategorie->cout_ttc))?$productCategorie->cout_ttc : 0;
						 $productData["cout_ht"]       = (isset($productCategorie->cout_ht)  && floatval($productCategorie->cout_ht ))?$productCategorie->cout_ht  : 0;
						 $productData["params"]        = "";
						 $productData["creationdate"]  = time();
						 $productData["creatorid"]     = 1;
						 $productData["updateduserid"] = $productData["updatedate"] = 0;
						 $dbAdapter->delete(    $prefixName."erccm_vente_products",array("documentid=?"=>$documentid,"registreid=?"=>$registreid));
						 if(!$dbAdapter->insert($prefixName."erccm_vente_products",$productData)) {
							 $errorMessages[]          = "Les informations du document n'ont pas pu être rattachées à votre panier";
						 } else {				
							 $productid                = $productData["productid"] = $dbAdapter->lastInsertId();							
						 }
					 } else {
						     $productid                = $documentProduct["productid"];
							 if( $productRow           = $modelProduct->findRow($productid,"productid",null,false) ) {
								 $productData          = $productRow->toArray();
							 }
					 }
					 if( intval($productid)) {
						 if(!isset($orderCart->articles[$productid])) {
							 $orderCart->articles[$productid]= $productData;
							 $orderCart->counter             = $orderCart->counter+1;
						 }
						 $orderCart->articles[$productid]["quantite"] = 1;
						 $orderCart->registres[$registreid]  = $registre->toArray();
						 $orderCart->documents[$documentid]  = $registreDocument;
						 $orderCart->outputString            = "";
						 $this->_cart                        = $orderCart;
					 }
			}
		}		
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>implode(" ; ",$errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
					 $this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success"=>"Le RCCM a été ajouté au panier avec succès","counter"=>$orderCart->counter,"articles"=>$orderCart->articles));
				exit;
			}
			$this->setRedirect("Le RCCM a été ajouté au panier avec succès","success");
		}
		if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
			$returnToUrl              =  $application->returnToUrl;
			$application->returnToUrl = "";
			$this->redirect($returnToUrl);
		} else {
			$this->redirect("public/registres/infos/registreid/".$registreid);
		}
	}
	
	
	public function addregistredocAction()
	{
		$registreid            = intval($this->_getParam("registreid" , 0));
		$documentid            = intval($this->_getParam("documentid" , 0));
		
		$modelProduct          = $this->getModel("product");
		$modelProductCategory  = $this->getModel("productcategorie");
		$modelRegistre         = $this->getModel("registre");
		$modelDocument         = $this->getModel("document");
		$modelCommande         = $model = $this->getModel("commande");
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$tablePrefix           = $prefixName  = $modelTable->info("namePrefix");
		
		$registre              = ($registreid)?$modelRegistre->findRow($registreid,"registreid",null,false) : null;
		$document              = ($documentid)?$modelDocument->findRow($documentid,"documentid",null,false) : null;
		$errorMessages         = array();
		$orderCart             = new Zend_Session_Namespace("ordercart");
		$application           = new Zend_Session_Namespace("erccmapp");
		
		if(!$registre || !$document) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>" Les paramètres fournis pour cette requête sont invalides." ));
				exit;
			}
			$this->setRedirect(" Les paramètres fournis pour cette requête sont invalides.","error");
			$this->redirect("public/registres/search");
		}
		$registreDocument      = $modelRegistre->documents($registreid,null,$documentid);
		if(!isset($registreDocument[0]["documentid"])) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Aucun document n'a été trouvé dans le registre de l'entreprise" ));
				exit;
			}
			$this->setRedirect("Aucun document n'a été trouvé dans le registre de l'entreprise.","error");
			$this->redirect("public/registres/search");
		}
		//var_dump($registreDocument); die();
 
		$documentCatid                    = (isset($registreDocument[0]["catid"]) && intval($registreDocument[0]["catid"]))?intval($registreDocument[0]["catid"]):0;
		$documentProduct                  = $modelProduct->registredoc($documentid,$registreid);
		$documentRow                      = ($documentid)?$modelDocument->findRow($documentid,"documentid",null,false) : null;
		$productCategorie                 = (intval($documentCatid))?$modelProductCategory->findRow($documentCatid,"documentcatid",null,false) : null;

		if(!isset($documentProduct["documentid"]) && $documentRow) {						
			$productData                  = $modelProduct->getEmptyData();
			$productData["catid"]         = ($productCategorie)?$productCategorie->catid : 0;
			$productData["documentcatid"] = $documentCatid;
			$productData["documentid"]    = $documentid;
			$productData["registreid"]    = $registreid;
			$productData["code"]          = sprintf("Prod-%06d/%06d:%s",$registreid,$documentid,$registre->numero);
			$productData["libelle"]       = sprintf("%s du RCCM n° %s" ,$registreDocument[0]["categorie"],$registre->numero);
			$productData["description"]   = sprintf("%s du RCCM n° %s.\nNom Commercial:%s;\nN° CNSS :%s;\nN° IFU : %s",
			                                        $registreDocument[0]["categorie"],$registre->numero,$registre->libelle,$registre->numcnss,$registre->numifu);
		    $productData["cout_ttc"]      = (isset( $productCategorie->cout_ttc) && floatval($productCategorie->cout_ttc))?$productCategorie->cout_ttc : 0;
		    $productData["cout_ht"]       = (isset( $productCategorie->cout_ht)  && floatval($productCategorie->cout_ht ))?$productCategorie->cout_ht  : 0;
		    $productData["params"]        = "";
			$productData["creationdate"]  = time();
			$productData["creatorid"]     = 1;
			$productData["updateduserid"] = $productData["updatedate"] = 0;
			$dbAdapter->delete(    $prefixName."erccm_vente_products",array("documentid=?"=>$documentid,"registreid=?"=>$registreid));
			if(!$dbAdapter->insert($prefixName."erccm_vente_products",$productData)) {
				$errorMessages[]          = "Les informations du document n'ont pas pu être rattachées à votre panier";
			} else {				
				$productid                = $productData["productid"] = $dbAdapter->lastInsertId();				
			}
		}  else {
		    $productid                    = $documentProduct["productid"];
			if( $productRow  = $modelProduct->findRow($productid,"productid",null,false) ) {
				$productData = $productRow->toArray();
				$productData["productid"] = $productid;
		    }
		}
		if( intval($productid) && isset($productData["productid"])) {
			if(!isset($orderCart->articles[$productid]) && !isset($orderCart->registres[$registreid])) {
				$orderCart->articles[$productid]         = $productData;
				$orderCart->counter                      = $orderCart->counter+1;
			}
			$orderCart->articles[$productid]["quantite"] = 1;
			$orderCart->registres[$registreid]           = $registre->toArray();
			$orderCart->documents[$documentid]           = $registreDocument[0];
            $orderCart->outputString                     = "";			
			$this->_cart                                 = $orderCart;
		} else {
			$errorMessages[]                   = "Aucun document n'a été trouvé dans les articles de la plateforme";
		}
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>implode(" ; ",$errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
					 $this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success"=>"Le document a été ajouté au panier avec succès","counter"=>$orderCart->counter,"articles"=>$orderCart->articles));
				exit;
			}
			$this->setRedirect("Le document a été ajouté au panier avec succès","success");
		}
		if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
			$returnToUrl  = $application->returnToUrl;
			$application->returnToUrl = "";
			$this->redirect($returnToUrl);
		} else {
			$this->redirect("public/registres/search");
		}
	}
	
	
	public function addrequestAction()
	{
		$me                    = Sirah_Fabric::getUser();
		$demandeid             = intval($this->_getParam("demandeid" , $this->_getParam("id",0)));
		
		$modelProduct          = $this->getModel("product");
		$modelProductCategory  = $this->getModel("productcategorie");
		$modelDemandeur        = $this->getModel("demandeur");
		$modelDocument         = $this->getModel("document");
		$modelDemande          = $model      = $this->getModel("demande");
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$tablePrefix           = $prefixName = $modelTable->info("namePrefix");
		
		$demande               = $demandeRow = (intval($demandeid))?$model->findRow(intval($demandeid),"demandeid",null,false) : null;
		if(!$demande) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>" Les paramètres fournis pour cette requête sont invalides." ));
				exit;
			}
			$this->setRedirect(" Les paramètres fournis pour cette requête sont invalides.","error");
			$this->redirect("public/registres/search");
		} 		
		$errorMessages                    = $productData     = array();
		$reservationProductId             = $productid       = $modelProduct->reservationid($demandeRow->typeid);
		$orderCart                                           = new Zend_Session_Namespace("ordercart");
		$application                                         = new Zend_Session_Namespace("erccmapp");
		if(!isset($orderCart->demandes)) {
			$orderCart->demandes                             = array();
		}
		if( intval($reservationProductId) ){
			$productRow                                      = $modelProduct->findRow($productid,"productid",null,false);
			if( $productRow && $demande ) {
				$productCategory                             = (isset($productRow->catid) && intval($productRow->catid))?$modelProductCategory->findRow(intval($productRow->catid),"catid",null,false) :  null;
				$productData                                 = $productRow->toArray(); 
				$productData["productid"]                    = $productid;
				$productData["demandeid"]                    = $demandeid;
				$productData["libelle"]                      = (isset($demande->libelle)       && !empty($demande->libelle      ))?$demande->libelle : $productData["libelle"];
			    $productDemande                              = (isset($productData["libelle"]) && !empty($productData["libelle"]))?$modelProduct->findRow($productData["libelle"],"libelle",null,false) : null;
				//On vérifie pour voir si le produit n'existe pas déjà
				if(!$productDemande) {
				    $emptyProductData                        = (isset($productData["productid"]))?$productData : $modelProduct->getEmptyData();
				    $new_product_data                        = array_merge($emptyProductData, array_intersect_key($productData, $emptyProductData));
					if( isset($new_product_data["productid"])) {
						unset($new_product_data["productid"]);
					}
					if( $productCategory ) {
						$new_product_data["cout_ttc"]        = $productData["cout_ttc"] = $productCategory->cout_ttc;
						$new_product_data["cout_ht"]         = $productData["cout_ht"]  = $productCategory->cout_ht;
					}
					$new_product_data["code"]                = $productData["code"]     = sprintf("%s:Reservation%s",$productData["code"],$demande->numero);
					$new_product_data["creationdate"]        = time();
					$new_product_data["creatorid"]           = $me->userid;
					$new_product_data["updateduserid"]       = $new_product_data["updatedate"] = 0;
					if( $foundProduct = $modelProduct->findRow( $new_product_data["code"],"code",null,false)) {
						$new_product_data["code"]            = sprintf("%s:Reservation%s/%d",$productData["code"],$demande->numero,$demandeid);
					}
					try {
						if( $dbAdapter->insert( $prefixName."erccm_vente_products", $new_product_data) ) {
							$new_product_data["productid"]   = $productid = $reservationProductId = $dbAdapter->lastInsertId();
						}
					} catch(Exception $e) {
					}					
				}else {
					$productid                               = $productData["productid"] = $productDemande->productid;
				}
			} else {
				    $errorMessages[]                         = "Les informations de la demande n'ont pas pu être rattachées à votre panier";
			}
		} 
		if( intval($productid)) {
			if(!isset($orderCart->articles[$productid])  ) {
				$orderCart->articles[$productid]             = $productData;
				$orderCart->articles[$productid]["quantite"] = 0;
				$orderCart->counter                          = $orderCart->counter+1;
			}
			if( $demandeRow) {
				$orderCart->demandes[$demandeid]             = $demandeRow->toArray();
			}			
			$orderCart->articles[$productid]["demandeid"]    = $demandeid;
			$orderCart->articles[$productid]["quantite"]     = $orderCart->articles[$productid]["quantite"] + 1;
			$orderCart->outputString                         = "";
			$this->_cart                                     = $orderCart;
		}
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>implode(" ; ",$errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
					 $this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()){
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success"=>"La demande de réservation a été ajoutée au panier avec succès","counter"=>$orderCart->counter,"articles"=>$orderCart->articles));
				exit;
			}
			$this->setRedirect("La demande de réservation a été ajoutée au panier avec succès","success");
		}
		if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
			$returnToUrl              = $application->returnToUrl;
			$application->returnToUrl = "";
			$this->redirect($returnToUrl);
		} else {
			$view                  = &$this->view;
			$orderCart             = new Zend_Session_Namespace("ordercart");
			$application           = new Zend_Session_Namespace("erccmapp");
			
			$member                = $this->_member;
			
			$view->member          = $this->_member;
			$view->articles        = $orderCart->articles;
			$view->registres       = $orderCart->registres;
			$view->documents       = $orderCart->documents;
			$view->requests        = $orderCart->requests;
			$view->returnToUrl     = $application->returnToUrl;
			$view->title           = "Les informations de votre panier";
			$this->render("requestdetails");
		}
	}
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                  = &$this->view;
		$orderCart             = new Zend_Session_Namespace("ordercart");
		$application           = new Zend_Session_Namespace("erccmapp");
		
		$member                = $this->_member;
		
		$view->member          = $this->_member;
		$view->articles        = $orderCart->articles;
		$view->registres       = $orderCart->registres;
		$view->documents       = $orderCart->documents;
		$view->requests        = $orderCart->requests;
		$view->returnToUrl     = $application->returnToUrl;
		$view->title           = "Les informations de votre panier";
		$this->render("details");
	}
	
	public function resetAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		$orderCart              = new Zend_Session_Namespace("ordercart");
		$orderCart->articles    = array();
		$orderCart->registres   = array();
		$orderCart->demandes    = array();
		$orderCart->documents   = array();
		$orderCart->requests    = array();
		$orderCart->counter     = 0;
		$orderCart->initialised = true;
		$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
		if( $this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout(true);
			$this->_helper->viewRenderer->setNoRender(true);
			echo ZendX_JQuery::encodeJson(array("success"=>"Le panier a été vidé avec succès","counter"=>$orderCart->counter,"articles"=>$orderCart->articles));
			exit;
		}
		$this->setRedirect("Le panier a été vidé avec succès","success");
		if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
			$returnToUrl  = $application->returnToUrl;
			$application->returnToUrl = "";
			$this->redirect($returnToUrl);
		} else {
			$this->redirect("public/ordercart/infos");
		}		
	}
	
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		
		$productid             = intval($this->_getParam("productid" , $this->_getParam("id",0)));
		$documentid            = intval($this->_getParam("documentid", 0));
		$registreid            = intval($this->_getParam("registreid", 0));
		$demandeid             = intval($this->_getParam("demandeid", 0));
		$modelProduct          = $model = $this->getModel("product");
		$modelProductCategory  = $this->getModel("productcategorie");
		$modelDemandeur        = $this->getModel("demandeur");
		$modelDocument         = $this->getModel("document");
		$modelDemande          = $this->getModel("demande");
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$tablePrefix           = $prefixName = $modelTable->info("namePrefix");
		$orderCart             = new Zend_Session_Namespace("ordercart");
		$application           = new Zend_Session_Namespace("erccmapp");
		if( intval($productid)) {
			$productRow        = $model->findRow($productid,"productid",null,false);
			if( $productRow ) {
				$registreid    = $productRow->registreid;
				$documentid    = $productRow->documentid;
				$demandeid     = $productRow->demandeid;
			}			
		} elseif( intval($documentid) ) {
			$productRow        = $model->findRow(intval($documentid),"documentid",null,false);
			if( $productRow ) {
				$productid     = $productRow->productid;
				$registreid    = $productRow->registreid;
				$documentid    = $productRow->documentid;
				$demandeid     = $productRow->demandeid;
			}
		}
		if( $orderCart->isLocked()) {
			$orderCart->unlock();
		}	
		$orderCart->unlock();
         	
		$articles  = &$orderCart->articles;
        $documents = &$orderCart->documents;
		$registres = &$orderCart->registres;
		$demandes  = &$orderCart->demandes;
      
		if( isset($articles[$productid]) ) {
			unset($articles[$productid]);
			unset($_SESSION['__ZF']["ordercart"]["articles"][$productid]);
			$orderCart->articles[$productid] = $articles[$productid] = null;
			$orderCart->counter  = $orderCart->counter-1;
		}  
		if( isset($documents[$documentid]) ) {
			unset($documents[$documentid]);
			unset($_SESSION['__ZF']["ordercart"]["documents"][$documentid]);
			$orderCart->documents[$documentid] = null;
		} else {
			unset($orderCart->documents[$documentid]);
		}
		if( isset($registres[$registreid]) ) {
			unset($orderCart->registres[$registreid]);
			$orderCart->registres[$registreid] = null;
			unset($_SESSION['__ZF']["ordercart"]["registres"][$registreid]);
		}
		if( isset($demandes[$demandeid]) ) {
			unset($demandes[$demandeid]);
			unset($_SESSION['__ZF']["ordercart"]["demandes"][$demandeid]);
			$orderCart->demandes[$demandeid] = null;
		}
		$returnToUrl = $application->returnToUrl;
		if( $this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout(true);
			$this->_helper->viewRenderer->setNoRender(true);
			echo ZendX_JQuery::encodeJson(array("success"=>"Votre panier a été mis à jour avec succès","counter"=>$orderCart->counter,"productid"=>$productid,"documentid"=>$documentid,"registreid"=>$registreid,"returnToUrl"=>$returnToUrl));
			exit;
		}
		$this->setRedirect("Votre panier a été mis à jour avec succès","success");
		if(!empty($returnToUrl)) {
			$application->returnToUrl = "";
			$this->redirect($returnToUrl);
		}else {
			$this->redirect("public/ordercart/infos");
		}
		 
	}
	
	 
	 
	
}


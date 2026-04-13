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

class OrdercartController extends Sirah_Controller_Default
{
	
	protected $_member  = null;
	
	protected $_cart    = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		$me             = $loggedInUser = Sirah_Fabric::getUser();
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
        $orderCart      = new Zend_Session_Namespace("ordercart");
		if(!isset($orderCart->initialised) || !$orderCart->initialised ) {
			$orderCart->articles    = array();
			$orderCart->registres   = array();
			$orderCart->documents   = array();
			$orderCart->counter     = 0;
			$orderCart->initialised = true;
			$orderCart->setExpirationSeconds(7200);
		}
		$this->_cart  = &$orderCart;
		parent::init();
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
			$this->redirect("public/registres/search");
		}
		if( count(   $registreDocuments)) {
			foreach( $registreDocuments as  $registreDocument ) {
				     $documentid         =  $registreDocument["documentid"];
					 $documentCatid      = (isset($registreDocument["catid"]) && intval($registreDocument["catid"]))?intval($registreDocument["catid"]):0;
					 
					 $documentProduct    =  $modelProduct->registredoc($documentid,$registreid);
					 $productCategorie   = (intval($documentCatid))?$modelProductCategory->findRow($documentCatid,"catid",null,false) : null;
					 if(!isset($documentProduct["documentid"])) {						 			
						 $productData                  = $modelProduct->getEmptyData();
						 $productData["catid"]         = ($productCategorie)?$productCategorie->catid : 0;
						 $productData["documentcatid"] = $documentCatid;
						 $productData["documentid"]    = $documentid;
						 $productData["registreid"]    = $registreid;
						 $productData["code"]          = sprintf("Prod-%06d/%06d:%s",$registreid,$documentid,$registre->numero);
						 $productData["libelle"]       = sprintf("%s du RCCM n° %s" ,$registreDocument[0]["categorie"],$registre->numero);
						 $productData["description"]   = sprintf("%s du RCCM n° %s.\nNom Commercial:%s,\nN° CNSS :%s,\nN° IFU : %s",
																$registreDocument[0]["categorie"],$registre->numero,$registre->libelle,$registre->numcnss,$registre->numifu);
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
							 if( $productRow = $modelProduct->findRow($productid,"productid",null,false) ) {
								 $productData          = $productRow->toArray();
							 }
					 }
					 if( intval($productid)) {
						 $orderCart->articles[$productid]   = $productData;
						 $orderCart->registres[$registreid] = $registre->toArray();
						 $orderCart->documents[$documentid] = $registreDocument[0];
						 $orderCart->counter                = $orderCart->counter+1;
						 $this->_cart                       = $orderCart;
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
				echo ZendX_JQuery::encodeJson(array("success"=>"Le document a été ajouté au panier avec succès","counter"=>$orderCart->counter,"articles"=>$orderCart->articles));
				exit;
			}
			$this->setRedirect("Le document a été ajouté au panier avec succès","success");
		}
		if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
			$this->redirect($application->returnToUrl);
		} else {
			$this->redirect("public/registres/search");
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
		$documentCatid                    = (isset($registreDocument[0]["catid"]) && intval($registreDocument[0]["catid"]))?intval($registreDocument[0]["catid"]):0;
		$documentProduct                  = $modelProduct->registredoc($documentid,$registreid);
		$productCategorie                 = (intval($documentCatid))?$modelProductCategory->findRow($documentCatid,"catid",null,false) : null;
		if(!isset($documentProduct["documentid"])) {						
			$productData                  = $modelProduct->getEmptyData();
			$productData["catid"]         = ($productCategorie)?$productCategorie->catid : 0;
			$productData["documentcatid"] = $documentCatid;
			$productData["documentid"]    = $documentid;
			$productData["registreid"]    = $registreid;
			$productData["code"]          = sprintf("Prod-%06d/%06d:%s",$registreid,$documentid,$registre->numero);
			$productData["libelle"]       = sprintf("%s du RCCM n° %s", $registreDocument[0]["categorie"],$registre->numero);
			$productData["description"]   = sprintf("%s du RCCM n° %s.\nNom Commercial:%s,\nN° CNSS :%s,\nN° IFU : %s",
			                                        $registreDocument[0]["categorie"],$registre->numero,$registre->libelle,$registre->numcnss,$registre->numifu);
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
		}  else {
		    $productid       = $documentProduct["productid"];
			if( $productRow  = $modelProduct->findRow($productid,"productid",null,false) ) {
				$productData = $productRow->toArray();
				$productData["productid"]      = $productid;
		    }
		}
		if( intval($productid) && isset($productData["productid"])) {
			$orderCart->articles[$productid]   = $productData;
			$orderCart->registres[$registreid] = $registre->toArray();
			$orderCart->documents[$documentid] = $registreDocument[0];
			$orderCart->counter                = $orderCart->counter+1;
			$this->_cart                       = $orderCart;
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
			$this->redirect($application->returnToUrl);
		} else {
			$this->redirect("public/registres/search");
		}
	}
	
	
	public function addrequestAction()
	{
		$demandeid             = intval($this->_getParam("demandeid" , $this->_getParam("id",0)));
		
		$modelProduct          = $this->getModel("product");
		$modelProductCategory  = $this->getModel("productcategorie");
		$modelDemandeur        = $this->getModel("demandeur");
		$modelDocument         = $this->getModel("document");
		$modelDemande          = $model = $this->getModel("demande");
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
		$reservationProductId  = $modelProduct->reservationid();
		if(intval($reservationProductId)) {
		}
	}
	
	public function infosAction()
	{
	}
	
	 
	public function listAction()
	{		
	    $this->_helper->layout->setLayout("home")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                  = &$this->view;
				
		$modelProjet           = new Model_Project();
		$modelDocumentCategory = new Model_Documentcategorie();
		$project               = $modelProjet->findRow("1" , "projectid", null, false );
		$layout                = $this->_helper->layout();
		$viewBasePath          = APPLICATION_TEMPLATES."/public";
		$layoutContent         = "";
		$linkPresentation      = "#";
		if(!$project ) {
			$layoutContent     =" <div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/company.png\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1 class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div  class=\"section-body\"> 
											<p> Le ERCCM est une plateforme de services en ligne du <a title=\"A propos de cette structure\" href=\"#\"> Fichier National du Registre du Commerce et du Crédit Mobilier (FNRCCM) </a> créée dans le but d'archiver, de centraliser et de rendre accessibles au public les du Registres du Commerce et du Crédit Mobilier (RCCM) immatriculés au Burkina Faso. </p>
											<p> Cette plateforme permet la recherche et la consultation en ligne des <a title=\"Consulter les types de documents officiels\" href=\"#\"> documents officiels </a>  des entreprises immatriculées à la <a title=\"En ssavoir plus sur la maison de l'entreprise\" href=\"#\"> Maison de l'Entreprise du Burkina Faso </a>. </p>										
										</div>                           
									</div>                      
								</div>";	             
		} else {
			$layoutContent    = "<div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/company.png\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1 class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div  class=\"section-body\">".$project->introduction."</div>                           
									</div>                      
								</div>";
		    $view->headMeta()->appendName("description", htmlentities(strip_tags($project->introduction)) );
		}		
		$view->headMeta()->appendName("keywords", "burkina,faso,afrique,travel,voyage,courrier,colis,," );
		$view->modules              = array("content-top-mod","search-mod","rightmenu-mod","content-bottom-mod","slideshow-mod");
		$view->documentTypes        = $modelDocumentCategory->getList(array("public"=>1));	

		$view->title                = "BIENVENUE SUR LA PLATEFORME ERCCM";
		$view->columns              = array("content");
        $view->modules              = array("appfeatures","slideshow");
			 
						
	}
	
}


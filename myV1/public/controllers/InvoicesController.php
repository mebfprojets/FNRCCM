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
	
class InvoicesController extends Sirah_Controller_Default
{
	
	protected $_member  = null;
 
	
	public function init()
	{
		
		$actionName      = $this->getRequest()->getActionName();
		$me              = $loggedInUser = Sirah_Fabric::getUser();
		$application     = new Zend_Session_Namespace("erccmapp");
 	 
		$model           = $this->getModel("member");
		$accountMember   = $model->fromuser($me->userid);
		
		if(!$accountMember ) {
			$returnToUrl = (!empty($actionName))?sprintf("public/invoices/%s", $actionName) : "public/invoices/list";
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
			$returnToUrl = (!empty($actionName))?sprintf("public/invoices/%s", $actionName) : "public/invoices/create";
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
		 
		parent::init();
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
		$model                    = $this->getModel("commandefacture");
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
		$pageNum                  = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                  = (isset($params["searchq"] ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		
		$filters                  = array("searchQ"=>$searchQ,"numero"=>null,"name"=>null,"memberid"=>null,"invoiceid"=>null,"commandeid"=>null,"productid"=>null,"registreid"=> null,"catid"=>null,"statutid"=>null);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$filters["creatorid"]     = $me->userid;
		$filters["memberid"]      = $this->_member->memberid;
		$filters["accountid"]     = $this->_member->accountid;
		$invoices                 = $model->getList($filters,$pageNum, $pageSize);
		$paginator                = $model->getListPaginator($filters );
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->invoices     = $this->view->factures = $invoices;
 
        $this->view->title        = "Historique de mes factures";
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
		$model                   = $this->getModel("commandefacture");
		$modelCommande           = $this->getModel("commande");
		$modelLigne              = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $this->getModel("member");
        $modelPaiement           = $this->getModel("commandepaiement");	
 			
		$invoiceid              = intval( $this->_getParam("invoiceid", $this->_getParam("id", 0 )));
		$invoice                = $model->findRow( $invoiceid , "invoiceid", null, false );
		$commandeid             = ($invoice   )? $invoice->commandeid : 0;
		$commande               = ($commandeid)? $modelCommande->findRow($commandeid,"commandeid",null,false) : null;
		$memberid               = ($commande  )? $commande->memberid  : 0;
		//var_dump($invoice);var_dump($commande);die();
		if(!$invoice || !$commandeid || !$commande) {
            
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de cette facture. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette facture. Paramètres invalides", "error");
			$this->redirect("public/invoices/list");
		}	
		if( $memberid != $this->_member->memberid) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Vous n'êtes pas autorisé à consulter les informations de cette invoice. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à consulter les informations de cette invoice. Paramètres invalides", "error");
			$this->redirect("public/invoices/list");
		}
		$this->view->invoice        = $this->view->facture = $invoice;
		$this->view->invoiceid      = $invoiceid;
		$this->view->commandeid     = $commandeid;
		$this->view->commande       = $commande;
		$this->view->member         = $this->view->client    = $this->_member;
		$this->view->billing_address= $invoice->billing_address($invoiceid,"object");
		$this->view->statut         = $invoice->getStatut( $invoiceid);
        $this->view->documents      = $commande->documents($invoiceid);
		$this->view->products       = $this->view->lignes    = $products  = $commande->listproducts($commandeid);
		$this->view->reglements     = $this->view->paiements = $paiements = $modelPaiement->getList(array("commandeid"=> $commandeid), 0, 0, array("P.date ASC"));
		$this->view->title          = sprintf("Les informations de la facture numéro %s", $invoice->numero);
	}
	
	
	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$invoiceid             = intval($this->_getParam("invoiceid", $this->_getParam("id" , 0)));
		if(!$invoiceid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		$model                   = $this->getModel("commandefacture");
		$modelCommande           = $this->getModel("commande");
		$modelLigne              = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $this->getModel("member");
        $modelPaiement           = $this->getModel("commandepaiement");	
 			
		$invoiceid              = intval( $this->_getParam("invoiceid", $this->_getParam("id", 0 )));
		$invoice                = $model->findRow( $invoiceid , "invoiceid", null, false );
		$commandeid             = ( $invoice   )?$invoice->commandeid : 0;
		$commande               = ( $commandeid)?$modelCommande->findRow($commandeid,"commandeid",null,false) : null;
		if(!$invoice || !$commandeid || !$commande) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de cette facture. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette facture. Paramètres invalides", "error");
			$this->redirect("public/invoices/list");
		}	
		if( $invoice->memberid != $this->_member->memberid) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Vous n'êtes pas autorisé à consulter les informations de cette invoice. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à consulter les informations de cette invoice. Paramètres invalides", "error");
			$this->redirect("public/invoices/list");
		}
		$documents               = $invoice->documents($invoiceid);
		//var_dump($documents); die();
		if(!count( $documents )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Le document auquel vous souhaitez accéder n'est pas encore prêt."));
				exit;
			}
			$this->setRedirect("Le document auquel vous souhaitez accéder n'est pas encore prêt.", "error");
			$this->redirect("public/invoices/infos/invoiceid/".$invoiceid."/id/".$invoiceid);
		}
		$filename                = $documents[0]["filepath"];
		if(!file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'est plus disponible") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'est plus disponible" , "error");
			$this->redirect("public/invoices/infos/invoiceid/".$invoiceid."/id/".$invoiceid);
		}
		$documentExtension        = strtolower( Sirah_Filesystem::getFilextension( $filename ) );
		$contentType              = "application/octet-stream";
		switch( $documentExtension ) {
			case "doc" :
			case "docx":
				$contentType      = "application/msword";
				break;
			case "pdf" :
				$contentType      = "application/pdf";
				break;
			case "xls":
			case "xlsx":
				$contentType      = "application/excel";
				break;
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
			case "bmp":
				$contentType      = "image/*";
				break;
			default:
				$contentType      = "application/octet-stream";
		}
		header('Content-Description:File Transfer');
		header('Content-Type: '.$contentType );
		header('Content-Disposition:attachment;filename='.basename( $filename ) );
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize( $filename ) );
		
		if( $content = ob_get_clean() ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Des entetes HTTP ont déjà été transmises" ) );
				exit;
			}
			echo "Des entetes HTTP ont déjà été transmises";
			exit;
		}
		flush();
		@readfile( $filename );
		exit;
	}
	
	 
 
}


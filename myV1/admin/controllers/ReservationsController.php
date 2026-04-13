<?php

class Admin_ReservationsController extends Sirah_Controller_Default
{

	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title       = "Gestion des demandes de réservation de noms commerciaux"  ;
		
		$me                      = Sirah_Fabric::getUser();
		$model                   = $this->getModel("demandereservation");
        $modelLocalite           = $this->getModel("localite");		
 	
		$demandes                = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 0));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_demandes" => array()
			                       );
		}
		if(!isset( $stateStore->filters["_demandes"]["maxitems"])) {
			$stateStore->filters["_demandes"] = array("page"=>1,"maxitems"=> NB_ELEMENTS_PAGE,"libelle"=>null,"numero"=>null,"localiteid"=>0,"searchQ"=>null,"expired"=>4,"disponible"=>4,"date"=>null,"lastname"=>null,"firstname"=>null,"name"=>null,"demandeurname"=>null,"promoteurname"=>null,"demandeurid"=>0,"promoteurid"=>0,"nomcommercial"=>null,
                                              );			
		}
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator                = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                              = $this->_request->getParams();
		$pageNum                             = (isset($params["page"]))    ? intval($params["page"])     : $stateStore->filters["_demandes"]["page"];
		$pageSize                            = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_demandes"]["maxitems"];		
		$searchQ                             = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                             = $stateStore->filters["_demandes"];
        $params                              = array_merge($stateStore->filters["_demandes"], $params);			
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]    = $stringFilter->filter($filterValue);
			}
		}	
 			
		$myLocaliteId                        = $me->localiteid;
		if( $me->isOPS() || $me->isGREFFIERS()) {
			$filters["creatorid"]            = $me->userid;
			if(!intval($filters["localiteid"]) || intval($filters["localiteid"])==$myLocaliteId) {
				$filters["localiteid"]       = $myLocaliteId;
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray              = preg_split("/[\s]+/", $filters["name"]);
			if( count($nameToArray) > 2) {
				$filters["lastname"]  = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["firstname"] = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["lastname"]  = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["firstname"] = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]      = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}
		  
		//print_r($params); die();
		$stateStore->filters["_demandes"]    = $filters;
		$demandes                            = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                           = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns                 = array("left");
		$this->view->demandes                = $demandes;
		$this->view->filters                 = $filters;
		$this->view->params                  = $params;
		$this->view->paginator               = $paginator;
		$this->view->pageNum                 = $pageNum;
		$this->view->pageSize                = $pageSize;		
		$this->view->localites               = $modelLocalite->getSelectListe("Sélectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users                   = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
	}
	
	public function exportAction()
	{
		@ini_set('memory_limit', '512M');
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
				
		$me                      = Sirah_Fabric::getUser();
		$model                   = $this->getModel("demandereservation");
        $modelLocalite           = $this->getModel("localite");		
 	
		$demandes                = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 0));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_demandes" => array()
			                       );
		}
		if(!isset( $stateStore->filters["_demandes"]["maxitems"])) {
			$stateStore->filters["_demandes"] = array("page"=>1,"maxitems"=> NB_ELEMENTS_PAGE,"libelle"=>null,"numero"=>null,"localiteid"=>0,"searchQ"=>null,"expired"=>4,"disponible"=>4,"date"=>null,"lastname"=>null,"firstname"=>null,"name"=>null,"demandeurname"=>null,"promoteurname"=>null,"demandeurid"=>0,"promoteurid"=>0,"nomcommercial"=>null,
                                              );			
		}
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator                = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                              = $this->_request->getParams();
		$pageNum                             = (isset($params["page"]))    ? intval($params["page"])     : $stateStore->filters["_demandes"]["page"];
		$pageSize                            = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_demandes"]["maxitems"];		
		$searchQ                             = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                             = $stateStore->filters["_demandes"];
        $params                              = array_merge($stateStore->filters["_demandes"], $params);			
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]    = $stringFilter->filter($filterValue);
			}
		}	
 			
		$myLocaliteId                        = $me->localiteid;
		if( $me->isOPS() || $me->isGREFFIERS()) {
			$filters["creatorid"]            = $me->userid;
			if(!intval($filters["localiteid"]) || intval($filters["localiteid"])==$myLocaliteId) {
				$filters["localiteid"]       = $myLocaliteId;
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray              = preg_split("/[\s]+/", $filters["name"]);
			if( count($nameToArray) > 2) {
				$filters["lastname"]  = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["firstname"] = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["lastname"]  = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["firstname"] = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]      = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}
		  
		//print_r($params); die();
		$stateStore->filters["_demandes"]        = $filters;
		$demandeids                              = (isset($params["demandeids"]))? $params["demandeids"]: array();
        if( count( $demandeids ) ) {
			$selectedFilters                     = array("demandeids"=> $demandeids );
			if(!$me->isAdmin() && !$me->isAllowed("demandes", "listall")) {
				$selectedFilters["creatorid"]    = $me->userid;
			}
			$demandes                            = $model->getList($selectedFilters);
		} else {
			$demandes                            = $model->getList($filters, $pageNum, $pageSize);
		}			
		if( count(   $demandes ) ) {			
			$myStoreDataPath                     = $me->getDatapath(); 
            if( !is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(   $myStoreDataPath , 0777);
				@mkdir(  $myStoreDataPath . DS . "ARCHIVES");
			}				
			$demandesListDest                     = str_replace("\\\\","\\",$myStoreDataPath . DS . "ARCHIVES" . DS .  "ERCCM_ListReservations.pdf");
			if(!is_dir($myStoreDataPath. DS . "ARCHIVES" ) ) {
				@mkdir($myStoreDataPath. DS . "ARCHIVES", 0777 );
			}
			if( file_exists( $demandesListDest )) {
				@unlink($demandesListDest);
			}
            $PDF           = Sirah_Fabric::getPdf();
			$PDF->setTitle("  ERCCM : Liste des réservations" );
			$PDF->setSubject("ERCCM : Liste des réservations");

			$margins       = $PDF->getMargins();
			$contenuWidth  = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
			$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			$PDF->AddPage();

			$PDF->Ln(10);
			$PDF->SetFont("helvetica","B",9);
			$PDF->setFillColor( 222 , 222 , 222 );
			$PDF->setTextColor( 0 , 0 , 0 );
			$PDF->Cell( $contenuWidth , 8 , "ERCCM : LISTE DES RESERVATIONS DE NOM COMMERCIAL", "B" , 0 , "C" , 1 );
			$PDF->Ln(10);
			
			$PDF->MultiCell( 20 , 10 , "Numéros", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 25 , 10 , "Dates"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 40 , 10 , "Mandataires"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 35 , 10 , "Objets", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 40 , 10 , "Promoteurs", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( $contenuWidth - 160, 10 , "Dates d'expirations", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );

			$PDF->Ln(10);
			$PDF->setTextColor( 0 , 0 , 0 );
			$PDF->SetFont("helvetica" , "" , 9 );
			
			foreach( $demandes as $demande ) {
					 $demandeid          = $demande["demandeid"];
					 $demandeTypeId      = $demande["typeid"];
					 $demandeNumero      = $demande["code"];
					 $demandeLibelle     = $demande["libelle"];
					 $demandeurName      = (isset($demande["demandeur"]) && !empty($demande["demandeur"]))?$demande["demandeur"] : ((isset($demande["demandeurName"]))?$demande["demandeurName"] : "");
					 $promoteurName      = (isset($demande["promoteur"]) && !empty($demande["promoteur"]))?$demande["promoteur"] : ((isset($demande["promoteurName"]))?$demande["promoteurName"] : "");
					 $demandeType        = $demande["type"];
					 $demandeStatut      = $demande["statut"];
					 $zendDate           = (intval($demande['date']))?new Zend_Date($demande['date'],Zend_Date::TIMESTAMP) : null;
					 $dateDemande        = ( $zendDate )? $zendDate->toString("dd MMM YYYY") : null;
					 $zendDateExpiration = (intval($demande['expirationdate']))?new Zend_Date($demande['expirationdate'],Zend_Date::TIMESTAMP) : null;
					 $dateExpiration     = ($zendDateExpiration)? $zendDateExpiration->toString("dd MMM YYYY") : null;
					 
					 $pdfY               = $PDF->GetY();
					 if( $pdfY > 240 ) {
						 $PDF->AddPage();
					 }
					 $PDF->MultiCell( 20 , 20 , $demandeNumero , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M" );
					 $PDF->MultiCell( 25 , 20 , $dateDemande   , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M" );	
                     $PDF->MultiCell( 40 , 20 , $demandeurName , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M" );					 
					 $PDF->MultiCell( 35 , 20 , $demandeLibelle, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M" );
                     $PDF->MultiCell( 40 , 20 , $promoteurName , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M" );					 
					 $PDF->MultiCell($contenuWidth - 160, 20   ,$dateExpiration, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 20 , "M"  );
					
					 $PDF->Ln(20);
			}
            $PDF->Output($demandesListDest,"F");			
		} else {
			         $errorMessages[] =  "Aucune réservation n'a été trouvée dans la base de données";			 
		}	
        if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}	else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"=>"La liste des réservations selectionnées a été exportée avec succès","tmpDocument"=>$demandeDocumentDest));
				exit;
			}			
			exit;
		}					
	}	
	
	
 		
		
	public function infosAction()
	{
		$demandeid                 = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}				
		$model                         = $this->getModel("demande");
        $modelType                     = $this->getModel("demandetype");	
		$modelEntreprise               = $this->getModel("demandentreprise");
		$modelEntrepriseForme          = $this->getModel("entrepriseforme");
		$modelDomaine                  = $this->getModel("domaine");
        $modelDemandeur                = $this->getModel("demandeur");
		$modelPromoteur                = $this->getModel("promoteur");
		$modelIdentite                 = $this->getModel("usageridentite");
		$modelIdentiteType             = $this->getModel("usageridentitetype"); 
		
		$demande                       = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}
 		
		$demandeurid                   = $demande->demandeurid;
		$entrepriseid                  = $demande->entrepriseid;
		$promoteurid                   = $demande->promoteurid;		
		$demandeurRow                  = $demande->demandeur();
		$promoteurRow                  = $demande->promoteur();
		$entrepriseRow                 = $demande->entreprise();
		
        $this->view->demande           = $demande;
        $this->view->demandeid         = $demandeid;
        $this->view->demandeurid       = $demandeurid;
		$this->view->entrepriseid      = $entrepriseid;
        $this->view->demandeurIdentite = ( $demandeurRow )?$modelDemandeur->identite($demandeurRow->identityid) : null;
        $this->view->promoteurIdentite = ( $promoteurRow )?$modelPromoteur->identite($promoteurRow->identityid) : null;		
        $this->view->demandeur         = $demandeurRow;	
        $this->view->promoteur         = $promoteurRow;	
        $this->view->entreprise        = $entrepriseRow;
		$this->view->domaineActivite   = ($entrepriseRow)?$modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
		$this->view->formeJuridique    = ($entrepriseRow)?$modelEntrepriseForme->findRow($entrepriseRow->formid,"formid", null, false) : null;
		$this->view->statut            = $demande->findParentRow("Table_Demandestatuts");
		$this->view->localite          = $demande->findParentRow("Table_Localites");
		$this->view->documents         = $demande->documents();
		$typeOfDocument                = "default";
		$demandeState                  = "default";
		switch(intval($demande->statutid)) {
			case 0:
			case 1:
			default: 
			    $typeOfDocument        = "default";
		        $demandeState          = "default";
			break;
			case 2:
			    $typeOfDocument        = "disponibilite";
		        $demandeState          = "verified";
			break;
			case 3:
			    $typeOfDocument        = "indisponibilite";
				$demandeState          = "indisponiblite";
				break;
			case 4:
			    $typeOfDocument        = "indisponibilite";
				$demandeState          = "reserved";
				break;
			case 5:
			    $typeOfDocument        = "rejet";
				$demandeState          = "rejected";
				break;
			case 6:
			    $typeOfDocument        = "rejet";
				$demandeState          = "canceled";
				break;
		}
        $this->view->state             = $demandeState;
		$this->view->documentype       = $typeOfDocument;
        $this->view->title             = ( $demande )? sprintf("Les informations de la demande %s ", $demande->libelle)	: "Les informations d'une demande";	
	} 	
	
	
	 
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$me                  = Sirah_Fabric::getUser();
		$model               = $this->getModel("demande");
		$dbAdapter           = $model->getTable()->getAdapter();
		$tablePrefix         = $model->getTable()->info("namePrefix");
		$ids                 = $this->_getParam("demandeids", $this->_getParam("ids",array()));
 
		$errorMessages       = array();
		if( is_string($ids) ) {
			$ids             = explode("," , $ids );
		}
		$ids                 = (array)$ids;
		$deleteFilters       =  array();
		if(!$me->isAllowed("demandes", "deleteall") && !$me->isAdmin()) {
			$deleteFilters[] = "creatorid='".$me->userid."'";
		}     		
		if( count(   $ids)) {
			foreach( $ids as $id) {
					 $deleteFilters[] = "demandeid='".$id."'";
 					 
					 if( $dbAdapter->delete($tablePrefix."reservation_demandes_reservations", $deleteFilters) ) {
						 $dbAdapter->update($tablePrefix."reservation_demandes", array("statutid"=>1,"expired"=>0), array("demandeid=?" =>$id));
					 } else {
						$demandeRow          = $model->findRow($id,"demandeid", null, false);
                        if( $demandeRow ) {
							$errorMessages[] = sprintf("La demande de réservation de <b> %s </b> n'a pas pu être annulée, certainement pour des droits d'accès", $demandeRow->objet);
						}													
					 }					 
			}
		} else {
			                $errorMessages[] = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/reservations/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les demandes de réservation selectionnées ont été annulées avec succès"));
				exit;
			}
			$this->setRedirect("Les demandes de réservation selectionnées ont été annulées avec succès", "success");
			$this->redirect("admin/reservations/list");
		}
	}
	
	
	public function getAction()
	{
		$me                           = Sirah_Fabric::getUser();
		$demandeid                    = intval(    $this->_getParam("demandeid", $this->_getParam("id" ,0)));
        $documentType                 = strip_tags($this->_getParam("type"     , "default"));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}				
		
		$model                            = $this->getModel("demande");
        $modelType                        = $this->getModel("demandetype");	
		$modelEntreprise                  = $this->getModel("demandentreprise");
		$modelEntrepriseForme             = $this->getModel("entrepriseforme");
		$modelDomaine                     = $this->getModel("domaine");
        $modelDemandeur                   = $this->getModel("demandeur");
		$modelPromoteur                   = $this->getModel("promoteur");
		$modelIdentite                    = $this->getModel("usageridentite");
		$modelIdentiteType                = $this->getModel("usageridentitetype");
        $modelDocument                    = $this->getModel("document"); 		
		
		$demande                          = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}
        $this->view->identiteTypes        = $identiteTypes  = $modelIdentiteType->getSelectListe(   "Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );		
		$demandeurid                      = $demande->demandeurid;
		$entrepriseid                     = $demande->entrepriseid;
		$promoteurid                      = $demande->promoteurid;		
		$demandeurRow                     = ($demandeurid )?$modelDemandeur->findRow( $demandeurid ,"demandeurid" , null, false ) : null;
		$entrepriseRow                    = ($entrepriseid)?$modelEntreprise->findRow($entrepriseid,"entrepriseid", null, false ) : null;
		$promoteurRow                     = ($promoteurid )?$modelPromoteur->findRow( $promoteurid ,"promoteurid" , null, false ) : null;
		
		$contentData                      = array("demande"=>$demande,"demandeid"=>$demandeid,"demandeur"=>$demandeurRow,"promoteur"=>$promoteurRow,"entreprise"=>$entrepriseRow,"me"=>$me);
        $contentData["demandeurIdentite"] = ($demandeurRow            )? $demandeurRow->identite() : null;
		$contentData["promoteurIdentite"] = ($promoteurRow            )? $promoteurRow->identite() : null;
		$contentData["domaine"]           = ($entrepriseRow->domaineid)? $modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
		$contentData["forme"]             = ($entrepriseRow->formid   )? $modelEntrepriseForme->findRow($entrepriseRow->formid,"formid", null, false) : null;
		$contentData["statut"]            = $demande->findParentRow("Table_Demandestatuts");
		$contentData["identitetypes"]     = $identiteTypes;
		$documentTpl                      = "fiche";
		$documentName                     = "DemandeVerification";
		$documentTitle                    = "Demande de vérification de la disponibilité d'un nom commercial";
		
		switch( strtolower($documentType)) {
			case "default":
			default:
			   $documentTpl            = "default";
			   $documentName           = "DemandeVerification";
			   $documentTitle          = sprintf("FICHE DE RECHERCHE DE DISPONIBILITE ET DE RESERVATION N° %s", $demande->numero);
			   break;
			case "disponibilite":
			   $documentTpl            = "attestation.disponibilite";
			   $documentName           = "AttestationDeDisponibilite";
			   $documentTitle          = sprintf("Attestation de disponibilité du nom commercial %s", $demande->objet);
			   break;
			case "reservation":
			   $documentTpl            = "attestation.reservation";
			   $documentName           = "AttestationDeReservation";
			   $documentTitle          = sprintf("Attestation de réservation du nom commercial %s", $demande->objet);
			   break;
			case "rejet":
			   $documentTpl            = "attestation.rejet";
			   $documentName           = "AttestationDeRejet";
			   $documentTitle          = sprintf("Attestation de rejet du nom commercial %s", $demande->objet);
			   break;			
		}
		$contenu                       = $this->view->partial("reservations/fiches/{$documentTpl}.phtml", $contentData);
        $params                        = array("show_header"=>0,"font"=>12,"show_footer"=>0,"document_output"=>"print");
		if( $this->_request->isPost() ) { 
		    $this->_helper->layout->disableLayout(true);
			$postData                  = $this->_request->getPost();
            						
			$modelTable                = $model->getTable();
			$dbAdapter                 = $modelTable->getAdapter();
			$prefixName                = $modelTable->info("namePrefix");
			$demandesPathRoot          = APPLICATION_DATA_PATH . DS . "demandes";
			
			$stringFilter              = new Zend_Filter();
			$stringFilter->addFilter(    new Zend_Filter_StringTrim());
			$stringFilter->addFilter(    new Zend_Filter_StripTags());

            $libelle                   = $documentTitle;
			$contenu                   = (isset($postData["contenu"]        ))?$postData["contenu"]                                : $contenu;
			$date                      = (isset($postData["date"]           ))?$stringFilter->filter($postData["date"])            : $demande->date;
            $documentOutput            = (isset($postData["document_output"]))?$stringFilter->filter($postData["document_output"]) : "download";
            $documentShowHeader        = (isset($postData["show_header"]    ))?intval($postData["show_header"])	                  : 1;	
			$demandeFilename           = $demandesPathRoot.DS. sprintf("%s_Numero_%s.pdf", $documentName , preg_replace("/[^A-Za-z0-9]/","_",$demande->numero));
			
			if( Zend_Date::isDate( $date, "dd/MM/YYYY")) {
				$zendDate              = new Zend_Date($date,"dd/MM/YYYY" );
				$date                  = $zendDate->get(Zend_Date::TIMESTAMP);
			} else {
				$date                  = $demande->date;
			}
			if( empty( $errorMessages )) {
				if(!is_dir(  $demandesPathRoot ) ) {
					@chmod( APPLICATION_DATA_PATH , 0777);
					@mkdir( APPLICATION_DATA_PATH .DS."demandes");
					@chmod( APPLICATION_DATA_PATH .DS."demandes", 0777);
				 }				 
                 $showHeader           =   $showFooter = ($documentShowHeader == 1)?true:false;	
                 $pageHeaderMargin     = ( $showHeader )? 50 : 5;				 
                 $demandePDF           = Sirah_Fabric::getPdf();
                 $demandePDF->SetCreator("ERCCM");
			     $demandePDF->SetTitle($documentTitle);
			     $demandePDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			     $demandePDF->SetMargins(5,$pageHeaderMargin ,5);
                 $demandePDF->SetPrintHeader($showHeader);
		         $demandePDF->SetPrintFooter($showHeader);				 
			     $margins            = $demandePDF->getMargins();
			     $contenuWidth       = $demandePDF->getPageWidth()-$margins["left"]-$margins["right"];
				 $demandePDF->SetFont("helvetica", "" , 12);
				 $demandePDF->AddPage();
				 $demandePDF->writeHTML( $contenu, true , false , true , false , '' );
				 $demandePDF->Output(    $demandeFilename, "F");
				 
				 if(!file_exists( $demandeFilename )) {
					 $errorMessages[]  = sprintf("La fiche %s n'a pas pu être produite ", $documentName );
				 } else {
					$filename                      = $modelDocument->rename($documentTitle, $me->userid);
					$documentData                  = array("userid"=>$me->userid,"category"=>15,"filename"=>$filename,"filepath"=>$demandeFilename ,"filextension"=>"pdf","filesize"=>filesize($attestationFilename),
														   "resourceid"=>50,"resource"=>"demandes","filedescription"=>$demande->libelle,"filemetadata"=>sprintf("%s,%s,%d,demande", $demande->numero,$demande->objet,$demandeid));
					$documentData["creatoruserid"] = $me->userid;
					$documentData["creationdate"]  = time();
					if( $dbAdapter->insert( $prefixName."system_users_documents", $documentData)) {
						$documentid                = $dbAdapter->lastInsertId();
						$document                  = $modelDocument->findRow($documentid, "documentid", null, false ); 
						$demandeDocumentRow        = array("documentid"=>$documentid,"demandeid"=>$demandeid,"demandeurid"=>$demandeurid,"contenu"=>$contenu,"libelle"=>$documentTitle,
						                                   "document_type"=>strtolower($documentType),"creationdate"=>time(),"creatorid"=>$me->userid,"updatedate"=>0,"updateduserid"=>0);
						$dbAdapter->insert( $prefixName."reservation_demandes_documents", $demandeDocumentRow);						
					}
				 }
				 if( empty( $errorMessages )) {
					 if( $documentOutput == "download" ) {
						 $this->_helper->viewRenderer->setNoRender(true);
						 $this->_helper->layout->disableLayout(true);
						 $demandePDF->Output(preg_replace("/\s+/","_",$documentName).".pdf", "D");
						 exit;
					 } elseif( $documentOutput == "iframe" ) {
						 $filePath   = "http://".APPLICATION_HOST. BASE_PATH. "myV1/documents/privatedata/sirahbf2546155aoo/reservations/". sprintf("%s_Numero_%s.pdf", $documentName , preg_replace("/\s+/","_",$demande->numero));
			             $pageOutput = "<div class=\"pdfFrameWrapper\"> 
			                                <div style=\"display:block;width:100%;margin:0;padding:0;-ms-zoom:1;-moz-transform:scale(1);-moz-transform-origin:0 0;-o-transform:scale(1);-o-transform-origin:0 0;-webkit-transform:scale(1);-webkit-transform-origin: 0 0;\">
							                   <object type=\"application/pdf\" width=\"100%\" height=\"100%\" data=\"".$filePath."#zoom=75\"> <embed width=\'800\' height=\"360\" src=\"".$filePath."?zoom=75\"> type=\"application/pdf\" /></object>
							                </div>
							            </div>";
			             echo $pageOutput;
                         exit;
					 } elseif($documentOutput == "print") {
						 $this->_helper->viewRenderer->setNoRender(true);
						 $this->_helper->layout->disableLayout(true);
						 
						 /*$myDataPath          = $me->getDatapath();
						 $inscriptionFilename = "ficheInscription".sprintf("%06d" , $inscriptionid).".pdf";	
                         $fileTmpPath         = $myDataPath .$inscriptionFilename;
						 if( file_exists($fileTmpPath) ) {
							 @unlink($fileTmpPath);
						 }
						 $fichePDF->Output($fileTmpPath, "F");*/
						 echo ZendX_JQuery::encodeJson(array("success"=>sprintf("La %s  a été produite avec succès",$documentTitle),"tmpDocument"=>$demandeFilename,"demandeid"=>$demandeid,"numero"=>$demande->numero));
                         exit;
				     } else {
						 if( $this->_request->isXmlHttpRequest() ) {
							 $this->_helper->viewRenderer->setNoRender(true);
							 echo ZendX_JQuery::encodeJson(array("success"=> sprintf("La  %s  a été produite avec succès",$documentTitle) ));
							 exit;
						 }
						 $this->setRedirect(sprintf("La %s  a été produite avec succès",$documentTitle),"success");
						 $this->redirect("reservations/infos/demandeid/".$demandeid);
					 }
				 }
			}
			if( count( $errorMessage )) {
				if( $this->_request->isXmlHttpRequest() ) {
					 $this->_helper->viewRenderer->setNoRender(true);
					 echo ZendX_JQuery::encodeJson(array("error" => implode("" , $errorMessages )));
					 exit;
				 }
				 foreach( $errorMessages as $message) {
					      $this->_helper->Message->addMessage( $message , "error" ) ;
				 }
			}		
		}				
		$this->view->demandeid         = $demandeid;
		$this->view->demande           = $demande;
		$this->view->data              = $contentData;
		$this->view->contenu           = $contenu;
        $this->view->contenu           = $contenu;
        $this->view->params            = $params;		
        $this->view->title             = sprintf("GENERER LA %s ", $documentTitle);
        $this->render("fiche");		
	}
	
		
	public function acceptAction()
	{
		$me                           = Sirah_Fabric::getUser();
		$demandeid                    = intval(    $this->_getParam("demandeid", $this->_getParam("id" ,0)));
        $documentType                 = strip_tags($this->_getParam("type"     , "default"));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}						
		$model                        = $this->getModel("demande");	
		$modelTable                   = $model->getTable();
		$dbAdapter                    = $modelTable->getAdapter();
		$prefixName                   = $modelTable->info("namePrefix");
		$tableName                    = $modelTable->info("name");
		$demande                      = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}		
		$verificationStateStore       = new Zend_Session_Namespace("Statestore");
		if(!isset( $verificationStateStore->verificationstate[$demandeid]["completed"]) || !$verificationStateStore->verificationstate[$demandeid]["completed"]) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Vous devrez d'abord vérifier la disponibilité du nom commercial"));
				exit;
			}
			$this->setRedirect("Vous devrez d'abord vérifier la disponibilité du nom commercial" , "error");
			$this->redirect("admin/reservations/verify/id/".$demandeid);
		}
		//On met à jour les informations de la vérification
		$verification                               = $demande->verification($demandeid);
		$verificationSources                        = "";
		if( count(   $verificationStateStore->verificationSource)) {
			foreach( $verificationStateStore->verificationSource as $sourceid => $source ) {
				     $verificationSources           = $verificationSources." - ".$source["libelle"];
					 $sourceCode                    = $source["code"];
					 if( isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode])) {
						 if($demande->verified($demandeid,$sourceCode)) {
							$verificationSourceData                 = array(); 
							$verificationSourceData["updatedate"]   = time();
							$verificationSourceData["updateduserid"]= $me->userid;
							$verificationSourceData["poids"]        = (isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"]))?$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"] : 0;
							$verificationSourceData["failed"]       = (isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]   ))?$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]    : 0;
						    $dbAdapter->update( $prefixName."reservation_demandes_verifications_sources",$verificationSourceData,array("verificationid=?"=>$demandeid,"sourceid=?"=>$sourceid));
						 } else {
							$verificationSourceData                 = array("verificationid"=>$demandeid,"sourceid"=>$sourceid,"updatedate"=>0,"updateduserid"=>0); 
							$verificationSourceData["creationdate"] = time();
							$verificationSourceData["creatorid"]    = $me->userid;
							$verificationSourceData["poids"]        = (isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"]))?$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"] : 0;
							$verificationSourceData["failed"]       = (isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]   ))?$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]    : 0;
						    
							$dbAdapter->delete($prefixName."reservation_demandes_verifications_sources",array("verificationid=?"=>$demandeid,"sourceid=?"=>$sourceid));
							$dbAdapter->insert($prefixName."reservation_demandes_verifications_sources",$verificationSourceData);
						 }						 
					 }
			}
		}
		if( isset( $verification["verificationid"])) {
			$verificationData                       = array();
			$verificationData["disponible"]         = 1;
			$verificationData["sources"]            = $verificationSources;
			$verificationData["taux_disponibilite"] = $verificationStateStore->verificationstate[$demandeid]["successed"];
			$verificationData["updatedate"]         = time();
			$verificationData["updateduserid"]      = $me->userid;
			
			$dbAdapter->update( $prefixName."reservation_demandes_verifications",$verificationData,array("verificationid=?"=>$demandeid));
		} else {
			$verificationData                       = array("verificationid"=>$demandeid,"updateduserid"=>0,"updatedate"=>0);
			$verificationData["demandeurid"]        = $demande->demandeurid;
			$verificationData["disponible"]         = 1;
			$verificationData["sources"]            = $verificationSources;
			$verificationData["taux_disponibilite"] = $verificationStateStore->verificationstate[$demandeid]["successed"];
			$verificationData["creationdate"]       = time();
			$verificationData["creatorid"]          = $me->userid;
			$dbAdapter->delete($prefixName."reservation_demandes_verifications",array("verificationid=?"=>$demandeid));
			$dbAdapter->insert($prefixName."reservation_demandes_verifications",$verificationSourceData);
		}
		$demande->statutid                          = 2;
		$demande->updateduserid                     = $me->userid;
		$demande->updatedate                        = time();
		$demande->save();
		
				
		$this->view->demande                        = $demande;
		$this->view->demandeid                      = $demandeid;
		$this->view->title                          = "Valider la disponibilité du nom commercial ".$demande->objet;
		$this->render("acceptation");
	}
	
	
	public function rejectAction()
	{
		$me                           = Sirah_Fabric::getUser();
		$demandeid                    = intval(    $this->_getParam("demandeid", $this->_getParam("id" ,0)));
        $documentType                 = strip_tags($this->_getParam("type"     , "default"));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}				
		
		$model                            = $this->getModel("demande");
        $modelType                        = $this->getModel("demandetype");	
		$modelEntreprise                  = $this->getModel("demandentreprise");
		$modelEntrepriseForme             = $this->getModel("entrepriseforme");
		$modelDomaine                     = $this->getModel("domaine");
        $modelDemandeur                   = $this->getModel("demandeur");
		$modelPromoteur                   = $this->getModel("promoteur");
		$modelIdentite                    = $this->getModel("usageridentite");
		$modelIdentiteType                = $this->getModel("usageridentitetype");
        $modelDocument                    = $this->getModel("document"); 		
		
		$demande                          = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/reservations/list");
		}
		$demande->statutid            = 3;
		$demande->updateduserid       = $me->userid;
		$demande->updatedate          = time();
		$demande->save();
		
		$this->view->demande          = $demande;
		$this->view->demandeid        = $demandeid;
		$this->view->title            = "Valider la disponibilité du nom commercial ".$demande->objet;
		$this->render("acceptation");
	}
}
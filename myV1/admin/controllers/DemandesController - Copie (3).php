<?php

class Admin_DemandesController extends Sirah_Controller_Default
{
	
	public function requestAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$me                      = Sirah_Fabric::getUser();
		$model                   = $this->getModel("demande");	
		$modelRequest            = $this->getModel("demanderequest");
		$params                  = $this->_request->getParams();
		$requestid               = (isset($params["requestid"]))?intval($params["requestid"]) : 0;
		$demandeid               = (isset($params["demandeid"]))?intval($params["demandeid"]) : 0;
		
		$request                 = $modelRequest->findRow($requestid,"requestid",null,false);
		$demandeRow              = ($request)?$model->findRow($request->demandeid,"demandeid",null,false) : null;
		if(!$request || !$demandeRow) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}
		if( $request->demandeid != $demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"La demande que vous souhaitez traiter est invalide.Veuillez informer votre administrateur."));
				exit;
			}
			$this->setRedirect("La demande que vous souhaitez traiter est invalide.Veuillez informer votre administrateur." , "error");
			$this->redirect("admin/demandes/list");
		}
		$operator           = $request->operator($me->userid,$requestid);
		if( $request->processed && $operator) {			
		    $warningMessage = sprintf("La demande que vous souhaitez traiter, semble avoir été déjà traitée par %s.",$operator->name);
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>$warningMessage));
				exit;
			}
			$this->setRedirect($warningMessage, "error");
			$this->redirect("admin/demandes/infos/demandeid/".$demandeid);
		}
		
		$demandeRow->creatorid = $me->userid;
		$demandeRow->save();
		$this->redirect("admin/demandes/verify/demandeid/".$demandeid);
	}

	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title       = "Gestion des demandes de vérification ou de réservation de disponibilité"  ;
		
		$me                      = Sirah_Fabric::getUser();
		$model                   = $this->getModel("demande");
        $modelType               = $this->getModel("demandetype");	
        $modelStatut             = $this->getModel("demandestatut");
        $modelLocalite           = $this->getModel("localite");		
 	
		$demandes                = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 0));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_demandes" => array());
		}
		if(!isset( $stateStore->filters["_demandes"]["maxitems"])) {
			$stateStore->filters["_demandes"] = array("page"=>1,"maxitems"=>NB_ELEMENTS_PAGE,"libelle"=>null,"numero"=>null,"localiteid"=>0,"searchQ"=>null,
													  "typeid"=>0,"statutid"=>0,"expired"=>4,"disponible"=>4,"processed"=>4,"validated"=>4,"webrequests"=>4,"date"=>null,"lastname"=>null,"firstname"=>null,"name"=>null,
													  "demandeurname"=>null,"promoteurname"=>null,"demandeurid"=>0,"promoteurid"=>0,"nomcommercial"=>null,"creatorid"=>0,
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
		$myLocaliteId                        = LOCALITEID;
		if(!$me->isAdmin()) {
			$filters["creatorid"]            = $filters["operatorid"] = $me->userid;
			if(!intval($filters["localiteid"]) || intval($filters["localiteid"])==$myLocaliteId) {
				$filters["localiteid"]       = $myLocaliteId;
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray                     = preg_split("/[\s]+/", $filters["name"]);
			if( count($nameToArray) > 2) {
				$filters["lastname"]         = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["firstname"]        = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2){
				$filters["lastname"]         = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["firstname"]        = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]             = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
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
        $this->view->demandeurName           = (isset($filters["name"]) && !empty($filters["name"]))?$filters["name"] : $filters["demandeurname"];		
		$this->view->users                   = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->statuts                 = $modelStatut->getSelectListe(  "Sélectionnez un statut"          , array("statutid"  , "libelle"), array(),null,null,false );
        $this->view->types                   = $modelType->getSelectListe(    "Sélectionnez un type de demandes", array("typeid"    , "libelle"), array(),null,null,false );		
		$this->view->localites               = $modelLocalite->getSelectListe("Sélectionnez une localité"       , array("localiteid", "libelle"), array() , null , null , false );
	}
	
	public function exportAction()
	{
		@ini_set('memory_limit', '512M');
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
				
		$me                      = Sirah_Fabric::getUser();
		$model                   = $this->getModel("demande");
        $modelType               = $this->getModel("demandetype");	
        $modelStatut             = $this->getModel("demandestatut");
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
			$stateStore->filters["_demandes"] = array("page"=>1,"maxitems"=> NB_ELEMENTS_PAGE,"libelle"=>null,"numero"=>null,"localiteid"=>LOCALITEID,"searchQ"=>null,
													  "typeid"=>0,"statutid"=>0,"expired"=>4,"disponible"=>4,"date"=>null,"lastname"=>null,"firstname"=>null,"name"=>null,
													  "demandeurname"=>null,"promoteurname"=>null,"demandeurid"=>0,"promoteurid"=>0,"nomcommercial"=>null,
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
		$myLocaliteId                        = LOCALITEID;
		if(!$me->isAllowed("demandes","listall")) {
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
			$demandes                            = $model->basicList($selectedFilters,0,0);
		} else {
			$demandes                            = $model->basicList($filters, $pageNum, $pageSize);
		}			
		if( count(   $demandes ) ) {			
			$myStoreDataPath                     = $me->getDatapath(); 
            if( !is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(   $myStoreDataPath , 0777);
				@mkdir(  $myStoreDataPath . DS . "ARCHIVES");
			}				
			$demandesListDest                     = str_replace("\\\\","\\",$myStoreDataPath . DS . "ARCHIVES" . DS .  "ERCCM_ListDemandes.pdf");
			if(!is_dir($myStoreDataPath. DS . "ARCHIVES" ) ) {
				@mkdir($myStoreDataPath. DS . "ARCHIVES", 0777 );
			}
			if( file_exists( $demandesListDest )) {
				@unlink($demandesListDest);
			}
            $PDF           = Sirah_Fabric::getPdf();
			$PDF->setTitle("  ERCCM : Liste des demandes de vérifications et/ou de réservations" );
			$PDF->setSubject("ERCCM : Liste des demandes de vérifications et/ou de réservations");

			$margins       = $PDF->getMargins();
			$contenuWidth  = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
			$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			
			
			$PDF->setFillColor( 222 , 222 , 222 );
			$PDF->SetMargins(15,20,15);
			$PDF->SetPrintHeader( false);
			$PDF->SetPrintFooter( false);
			$PDF->setTextColor( 0 , 0 , 0 );
			
			$PDF->AddPage();
			$PDF->SetFont("helvetica","B",9);
			$PDF->Cell( $contenuWidth , 8 , "ERCCM : DEMANDES DE VERIFICATION ET/OU RESERVATIONS DE NOM COMMERCIAL", "B" , 0 , "C" , 1 );
			$PDF->Ln(10);
			
			$PDF->MultiCell( 25 , 10 , "Numéros", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 20 , 10 , "Dates"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 35 , 10 , "Mandataires"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 45 , 10 , "Objets", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( 35 , 10 , "Promoteurs", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell( $contenuWidth-160, 10 , "Etats", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );

			$PDF->Ln(10);
			$PDF->setTextColor( 0 , 0 , 0 );
			$PDF->SetFont("helvetica" , "" ,8);
			
			foreach( $demandes as $demande ) {
					 $demandeid        = $demande["demandeid"];
					 $demandeTypeId    = $demande["typeid"];
					 $demandeNumero    = $demande["numero"];
					 $demandeLibelle   = $demande["libelle"];
					 $demandeurName    = (isset($demande["demandeur"]) && !empty($demande["demandeur"]))?$demande["demandeur"] : ((isset($demande["demandeurName"]))?$demande["demandeurName"] : "");
					 $promoteurName    = (isset($demande["promoteur"]) && !empty($demande["promoteur"]))?$demande["promoteur"] : ((isset($demande["promoteurName"]))?$demande["promoteurName"] : "");
					 $demandeType      = $demande["type"];
					 $demandeStatut    = $demande["statut"];
					 $zendDate         = (intval($demande['date']))?new Zend_Date($demande['date'],Zend_Date::TIMESTAMP) : null;
					 $dateDemande      = ( $zendDate )? $zendDate->toString("dd MMM YYYY") : null;
					 
					 $pdfY             = $PDF->GetY();
					 if( $pdfY > 260 ) {
						 $PDF->AddPage();
					 }
					 $PDF->SetFont("helvetica" , "" ,8 );
					 $PDF->MultiCell(25, 10 , $demandeNumero , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(20, 10 , $dateDemande   , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M" );	
                     $PDF->MultiCell(35, 10 , $demandeurName , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M" );					 
					 $PDF->MultiCell(45, 10 , $demandeLibelle, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M" );
                     $PDF->MultiCell(35, 10 , $promoteurName , 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M" );					 
					 $PDF->MultiCell($contenuWidth- 160, 10   ,$demandeStatut, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M"  );
					
					 $PDF->Ln(10);
			}
            $PDF->Output($demandesListDest,"F");			
		} else {
			         $errorMessages[] =  "Aucune n'a été trouvée dans la base de données";			 
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
				echo ZendX_JQuery::encodeJson(array("success"=>"La liste des demandes selectionnées a été exportée avec succès","tmpDocument"=>$demandeDocumentDest));
				exit;
			}	
            $this->_helper->viewRenderer->setNoRender(true);
			$this->_helper->layout->disableLayout(true);
			$PDF->Output($demandesListDest, "D");			
			exit;
		}					
	}	
	
	
	
	public function verifyAction()
	{
		$this->view->title                   = "Vérifier la disponibilité du nom commercial";
		
		$demandeid                           = $id = intval($this->_getParam("demandeid", $this->_getParam("id" ,$this->_getParam("demandeid", 0))));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}		
		
		$model                               = $this->getModel("demande");
		$modelRegistre                       = $this->getModel("registre");
		$modelEntreprise                     = $this->getModel("demandentreprise");
		$modelSource                         = $this->getModel("demandeverificationsource");
		$modelBlacklist                      = $this->getModel("demandeblacklist");
		$modelEntrepriseForme                = $this->getModel("entrepriseforme");
		$modelDomaine                        = $this->getModel("domaine");
        $modelDemandeur                      = $this->getModel("demandeur");
		$modelPromoteur                      = $this->getModel("promoteur");
		$modelIdentite                       = $this->getModel("usageridentite");
		$modelIdentiteType                   = $this->getModel("usageridentitetype");
		$demande                             = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}
		$sourcesList                        = $modelSource->getSelectListe("Selectionnez une source de vérifciation", array("code", "sourceid") , array() , null , null , false );
		$verificationSource                 = $this->_getParam("source", "");
		$verificationStateStore             = new Zend_Session_Namespace("Statestore");
		if(!$this->_request->isXmlHttpRequest() ) {
			unset($verificationStateStore->verificationstate);
		}
		$demandeVerifications               = $demande->verifications($demandeid);
		$verificationList                   = array();
		if(!isset($verificationStateStore->verificationstate[$demandeid])) {
			$verificationStateStore->verificationstate[$demandeid] = array("completed"=>0,"successed"=>0,"failed"=>0,"sources"=>array(),"totalSources"=>0,"next"=>"");
		    $sources                        = $modelSource->getList(array(), 0, 0, array("S.sourceid ASC"));
			$totalSources                   = count($sources);
			$i                              = 0;
			if( count(   $sources )) {
				foreach( $sources as $source ) {
					     $sourceCode        = $source["code"];
						 $j                 = $i+ 1;
						 $nextId            = (isset($sources[$j]))?$sources[$j]["code"] : "";
					     $verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode] = array("successed"=>0,"failed"=>1,"id"=>$source["sourceid"],"next"=>$nextId);
				         $verificationStateStore->verificationSource[$source["sourceid"]]               = $source;
						 $i++;
				}
			}			
			$verificationStateStore->verificationTotalSources = $totalSources;			
		}		
		if(($verificationCompleted || $verificationStateStore->verificationstate[$demandeid]["completed"]) && ($verificationStateStore->verificationstate[$demandeid]["successed"] >= 100)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				$successData                = $verificationStateStore->verificationstate[$demandeid];
				$successData["success"]     = sprintf("La demande a été vérifiée et a produit une moyenne de succès de %d%% ", $verificationStateStore->verificationstate[$demandeid]["total"]);
				echo ZendX_JQuery::encodeJson($successData);
				exit;
			}
			$this->setRedirect(sprintf("La demande a été vérifiée et a produit une moyenne de succès de %d%% ", $verificationStateStore->verificationstate[$demandeid]["total"]), "success");
			$this->redirect("admin/demandes/accept/demandeid/".$demandeid);
		} elseif(($verificationCompleted || $verificationStateStore->verificationstate[$demandeid]["completed"]) && ($verificationStateStore->verificationstate[$demandeid]["successed"] < 100)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);

				echo ZendX_JQuery::encodeJson(array("error" => "La demande a été vérifiée et a indiqué que le nom commercial/dénomination sociale est indisponible"));
				exit;
			}
			$this->setRedirect("La demande a été vérifiée et a indiqué que le nom commercial/dénomination sociale est indisponible", "error");
			$this->redirect("admin/demandes/infos/demandeid/".$demandeid);
		}
        if(!isset($verificationStateStore->verificationstate[$demandeid]["next"]) || empty($verificationStateStore->verificationstate[$demandeid]["next"])) {
			$nextId                         = (isset($verificationStateStore->verificationstate[$demandeid]["sources"][$verificationSource]["next"]))?$verificationStateStore->verificationstate[$demandeid]["sources"][$verificationSource]["next"] : 0;
		    $next                           = (isset($verificationStateStore->verificationSource[$nextId]["code"]))?$verificationStateStore->verificationSource[$nextId]["code"] : "";
		    $verificationStateStore->verificationstate[$demandeid]["next"] = $next;
		} else {
			$next                           = $verificationStateStore->verificationstate[$demandeid]["next"];
		}	
        if( empty($verificationSource) && !empty($next)) {
			$verificationSource             = $next;
		} else if( empty($verificationSource) && empty($next)) {
			$verificationSource             = "erccm";
		}
		
		$sourceRow                          = (!empty($verificationSource))?$modelSource->findRow($verificationSource,"code", null, false ) : null;
		if(!$sourceRow ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer le processus de vérification, la source indiquée semble invalide"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer le processus de vérification, la source indiquée semble invalide", "error");
			$this->redirect("admin/demandes/infos/id/".$demandeid);
		}
        $totalSourcesChecked                = $currentStep  = $verificationStateStore->verificationstate[$demandeid]["totalSources"]+1;	
        $totalSuccessed                     = $verificationStateStore->verificationstate[$demandeid]["successed"];	
		
		//$this->getHelper("Message")->addMessage("ATTENTION!: Les similarités proposées par le FNRCCM ne prennent pas en compte les données de Juillet à Décembre de l'année 2021 de Bobo Dioulasso", "message");
		
		if( $this->_request->isPost() ) {
			$postData                       = $this->_request->getPost();			
			$completed                      = (isset($postData["completed"]))?intval($postData["completed"])     : 0;
			$successed                      = (isset($postData["successed"]))?intval($postData["successed"])     : 0;
			$sourceCode                     = (isset($postData["sourceid"] ))?strip_tags($postData["sourceid"])  : "";
			$failed                         = 1;			
			if(!empty($sourceCode)  &&  isset($verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode])) {
				$sourceId                   = $verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["id"]; 
				$verificationStateStore->verificationstate[$demandeid]["totalSources"]                          = $verificationStateStore->verificationstate[$demandeid]["totalSources"] + 1;
				if( isset($verificationStateStore->verificationSource[$sourceId]) && $successed ) {
					$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"] = $successed = $verificationStateStore->verificationSource[$sourceId]["poids"];
				    $verificationStateStore->verificationstate[$demandeid]["successed"]                         = $verificationStateStore->verificationstate[$demandeid]["successed"] + $verificationStateStore->verificationSource[$sourceId]["poids"];
				    $verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]    = $failed    = 0;					
				} else {
					$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["failed"]    = $failed    = 1;
					$verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["successed"] = $successed = 0;					
				}
				$verificationStateStore->verificationstate[$demandeid]["next"]                                  = $verificationStateStore->verificationstate[$demandeid]["sources"][$sourceCode]["next"];
			    $sourceLibelle              = $verificationStateStore->verificationSource[$sourceId]["libelle"];
				$totalSources               = $verificationStateStore->verificationTotalSources;
				$verificationSource         = (!empty($verificationStateStore->verificationstate[$demandeid]["next"]))?$verificationStateStore->verificationstate[$demandeid]["next"] : 0;
				if( $totalSourcesChecked   >= $totalSources ) {
					$verificationStateStore->verificationstate[$demandeid]["completed"]= 1;
				}
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					
					if( $failed ) {
						$jsonData           = array("error"  => sprintf("La vérification dans la base de données <b> `%s`</b> a indiqué que le nom commercial recherché est indisponible", $sourceLibelle));
					} else {
						$jsonData           = array("success"=> sprintf("La vérification dans la base de données <b> `%s`</b> a indiqué que le nom commercial recherché est disponible", $sourceLibelle));
					}
					$jsonData["next"]       = $verificationSource;
					$jsonData["nextid"]     = (isset($sourcesList[$verificationSource]))?$sourcesList[$verificationSource] : 0;
					$jsonData["successed"]  = (isset($verificationStateStore->verificationstate[$demandeid]["successed"]))?$verificationStateStore->verificationstate[$demandeid]["successed"] : $successed;
					$jsonData["completed"]  = $verificationStateStore->verificationstate[$demandeid]["completed"];
					
					echo ZendX_JQuery::encodeJson($jsonData);
				    exit;
				}
				if( $failed ) {
					$this->setRedirect(sprintf("La vérification dans la base de données <b> `%s`</b> a indiqué que le nom commercial recherché est indisponible", $sourceLibelle), "error");
				} else {
					$this->setRedirect(sprintf("La vérification dans la base de données <b> `%s`</b> a indiqué que le nom commercial recherché est disponible", $sourceLibelle)  , "success");
				}
				$this->redirect("admin/demandes/verify/demandeid/".$demandeid);
			} else {
				$errorMessages[]            = "Veuillez renseigner les bons paramètres de validation";
			}			
		}		
		$entrepriseid                       = $demande->entrepriseid;
		$demandeEntreprise                  = ($entrepriseid)?$modelEntreprise->findRow($entrepriseid,"entrepriseid", null, false ) : null;
		$similarites                        = $similariteActivities = array();
		$totalResults                       = 100;
		$query                              = $keywords = (!empty($demande->keywords))?$modelRegistre->cleanName(preg_replace("/[^A-Za-z0-9_]/","",$demande->keywords)) : $demandeEntreprise->nomcommercial;
		$sigleEntreprise                    = (isset($demandeEntreprise->sigle) && !empty($demandeEntreprise->sigle))?preg_replace("/[^A-Za-z0-9_]/","",$demandeEntreprise->sigle) : "";
		$renderingView                      = "verification";
		$viewTitle                          = sprintf("Vérification de la disponibilité du nom commercial %s", $query);
		$rejected                           = 0;
		if(!empty($sigleEntreprise)) {
			$query                          = $keywords = sprintf("%s;%s", $query, $sigleEntreprise);
		}
		
		//print_r($query); die();
		switch(strtolower($verificationSource)) {
			case "erccm":
			default:
			    $registres                  = $modelRegistre->getList(array("keywords"=>$query,"types" => array(1,2,3,4)), 1, $totalResults);
				if( count(   $registres ) ) {
					$i                      = 0;
					foreach( $registres as $registre ) {
						     $foundRegistreLib         = $modelRegistre->cleanName($registre["libelle"],$registre["numero"]);
						     if( $foundRegistreLib==$query) {
								 $rejected  = 1;
							 }
						     $similarites[$i]          = sprintf("%s : %s", $registre["numero"],$foundRegistreLib);
							 $similariteActivities[$i] = $registre["description"];
							 $i++;
					}
				}
				$renderingView              = "erccm-verification";
				$viewTitle                  = sprintf("Vérification du nom commercial dans le Fichier National...", $query);
			    break;
			case "sigu":
			     if(!empty($sigleEntreprise)) {
					 $query                 = sprintf("%s,%s",$keyword, $sigleEntreprise);
				}
			    $keywords                   = preg_split("/[\s;,\-\@]+/",trim(strip_tags($query)));
				if(!count($keywords)) {
					$keywords               = array(0=>trim(strip_tags($query)));
				}
				$i                          = 0;
				$similarites                = $similariteActivities = array();
				if( count($keywords)) {
					foreach( $keywords as $keyword ) {
						     $keyword       = preg_replace("/\s+/", "",$keyword);
						     if( empty($keyword) ) {
								 continue;
							 }
						     try {
								$siguSearchClient       = new  Zend_Http_Client(VIEW_BASE_URI."/ajaxres/apisearch", array('keepalive' => true));
								$siguSearchClient->setMethod(  Zend_Http_Client::GET);
								$siguSearchClient->setParameterGet("repository", "sigu");
								$siguSearchClient->setParameterGet("keywords"  , $keyword);
								$siguSearchClient->setParameterGet("limit"     , $totalResults);
								$siguSearchClient->setCookieJar();
								$siguSearchClient->setHeaders(array("Cookie"=>sprintf("%s=%s",session_name(),session_id()),"Accept"=>"application/json","Content-type"=>"application/json"));
								
								$siguSearchResponse     = $siguSearchClient->request();
								if( $siguSearchResponse ) {
									$registres          = json_decode($siguSearchResponse->getBody(), true);
									//var_dump($registres); var_dump($keyword); die();
									if( count(   $registres )) {
										foreach( $registres as $NumeroRCCM => $registre ) {
												 $similarites[$i]          = sprintf("%s : %s", $registre["label"], $registre["value"]);
												 $similariteActivities[$i] = $registre["activite"];
												 if( $registre["value"] == $query ) {
													 //$rejected  = 1;
												 }	
												 $i++;									 
										}
									}
								}
							 } catch( Exception $e ) {
								$errorMessages[] = sprintf("Une erreur s'est produite dans la communication avec l'API SIGU/ERP NAV : %s", $e->getMessage());
							 }
					}
				}			    
                $renderingView              = "sigu-verification";	
                $viewTitle                  = sprintf("Vérification du nom commercial dans la base de données de l'ERP NAV/SIGU...", $query);				
			    break;
			case "reservation":
			    $entreprises                = $modelEntreprise->getList(array("libelle"=>$query,"reserved"=>1), 1, $totalResults);
				if( count(   $entreprises ) ) {
					$i                      = 0;
					foreach( $entreprises as $entreprise ) {
						     $similarites[] = (!empty($entreprise["sigle"]))?sprintf("%s %s (%s)", $entreprise["numrccm"], $entreprise["nomcommercial"], $entreprise["sigle"]) : sprintf("%s %s", $entreprise["numrccm"], $entreprise["nomcommercial"]);
					         $similariteActivities[$i] = (isset($entreprise["description"]))?$entreprise["description"] : "";
							 if( $entreprise["nomcommercial"] == $query || $entreprise["sigle"] == $query ) {
								 $rejected  = 1;
							 }
							 $i++;
					}
				}
				$renderingView              = "reservation-verification";
				$viewTitle                  = sprintf("Vérification du nom commercial dans la liste des noms déjà réservés...", $query);
			    break;
			case "blacklist":
			    $blacklisted                = $modelBlacklist->getList(array("searchQ"=>$query), 1, $totalResults);
				if( count(   $blacklisted)) {
					$i                      = 0;
					foreach( $blacklisted as $item ) {
						     $similarites[]           = $item["libelle"];
							 $similariteActivities[$i] = (isset($item["description"]))?$item["description"] : "";
							 if( $registre["value"] == $query ) {
								 $rejected  = 1;
							 }
					}
				}
				//print_r($blacklisted); die();
				$renderingView              = "blacklist-verification";
				$viewTitle                  = sprintf("Vérification du nom commercial dans la liste des Noms commerciaux non Autorisés", $query);
			    break;
			case "apiods":
			case "dgi":
			    if(!empty($sigleEntreprise)) {
					$query                  = sprintf("%s,%s",$keyword, $sigleEntreprise);
				}
			    $keywords                   = preg_split("/[\s;,\-\@]+/",trim(strip_tags($query)));
				if( !count($keywords)) {
					 $keywords              = array(0=>trim(strip_tags($query)));
				}
				$i                          = 0;
				$similarites                = $similariteActivities = array();
				if( count(   $keywords)) {
					foreach( $keywords as $keyword ) {
						     try {								
								$odsSearchClient = new Zend_Http_Client(VIEW_BASE_URI."/ajaxres/apisearch", array('keepalive'=> true));
								$odsSearchClient->setMethod(  Zend_Http_Client::GET);
								$odsSearchClient->setParameterGet("repository", "apiods");
								$odsSearchClient->setParameterGet("nom_raison_sociale"   , strtoupper($keyword));
								$odsSearchClient->setParameterGet("keywords"  , strtoupper($keyword));
								$odsSearchClient->setCookieJar();
								$odsSearchClient->setHeaders(array("Cookie"=>sprintf("%s=%s",session_name(),session_id()),"Accept" => "application/json","Content-type"=>"application/json"));
								
								$odsSearchResponse      = $odsSearchClient->request();

								if( $odsSearchResponse ) {
									$registres          = json_decode($odsSearchResponse->getBody(), true);
									//var_dump($registres); die();
									if( count(   $registres )) {
										foreach( $registres as $NumeroRCCM => $registre ) {
												 $similarites[$i]          = sprintf("%s : %s", $registre["label"], $registre["value"]);
												 $similariteActivities[$i] = $registre["activite"];
												 if( $registre["value"] == $query ) {
													 $rejected  = 1;
												 }
												 $i++;									 
										}
									}
								}
							 } catch( Exception $e ) {
								$errorMessages[]        = sprintf("Une erreur s'est produite dans la communication avec l'API ODS : %s", $e->getMessage());
							 }
					}
				}			    
			    $renderingView              = "apiods-verification";
				$viewTitle                  = sprintf("Vérification du nom commercial dans la base de données des Impôts", $query);
			    break;			
		} 
		$this->view->query                  = $query;
        $this->view->similarites            = $similarites;
		$this->view->similariteActivities   = $similariteActivities;
        $this->view->totalresults           = $totalResults;
        $this->view->currentStep            = $currentStep;
		$this->view->sources                = $sourcesList;
        $this->view->title                  = $this->view->viewTitle = $viewTitle;
		$this->view->next                   = $next;
		$this->view->source                 = $sourceRow;
		$this->view->sourceid               = $sourceRow->sourceid;
		$this->view->sourceCode             = $verificationSource;
		$this->view->renderingView          = $renderingView;
        $this->view->successedPercent       = $totalSuccessed;	
        $this->view->demande                = $demande;
        $this->view->demandeid              = $demandeid;
		$this->view->rejected               = $rejected;
        $this->view->entreprise             = $demandeEntreprise;
        $this->view->domaineActivite        = ($demandeEntreprise->domaineid)? $modelDomaine->findRow($demandeEntreprise->domaineid,"domaineid", null, false) : null;
		$this->view->formeJuridique         = ($demandeEntreprise->formid   )? $modelEntrepriseForme->findRow($demandeEntreprise->formid,"formid", null, false) : null;		
		$this->view->demandeur              = ($demande->demandeurid        )? $modelDemandeur->findRow($demande->demandeurid,"demandeurid" , null, false ) : null;
		$this->view->promoteur              = ($demande->promoteurid        )? $modelPromoteur->findRow($demande->promoteurid,"promoteurid" , null, false ) : null;
		$this->view->statut                 =  $demande->findParentRow("Table_Demandestatuts");
		$this->view->localite               =  $demande->findParentRow("Table_Localites");
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);			
			$this->render($renderingView);
		} else {
			$this->view->title              = sprintf("Vérification de la disponibilité du nom commercial %s", $query);
			$this->render("verification");
		}				
	}

 
		
	public function createAction()
	{
		$this->view->title               = "Créer une demande de vérification et de réservation de noms commerciaux";
		
		$me                              = Sirah_Fabric::getUser();                 
		
		$model                           = $this->getModel("demande");
        $modelType                       = $this->getModel("demandetype");	
		$modelStatut                     = $this->getModel("demandestatut");	
		$modelEntreprise                 = $this->getModel("demandentreprise");
		$modelEntrepriseForme            = $this->getModel("entrepriseforme");
		$modelDomaine                    = $this->getModel("domaine");
        $modelDemandeur                  = $this->getModel("demandeur");
		$modelPromoteur                  = $this->getModel("promoteur");
		$modelIdentite                   = $this->getModel("usageridentite");
		$modelIdentiteType               = $this->getModel("usageridentitetype"); 
        $modelCountry                    = $this->getModel("country");
		$modelLocalite                   = $this->getModel("localite");
		$modelRegistre                   = $this->getModel("registre");
		
		$defaultData                     = $model->getEmptyData();
		$defaultData["numero"]           = $model->reference();
		   
		$defaultData["demandeur_equal_promoteur"]          = $demandeur_equal_promoteur = intval($this->_getParam("demandeur_equal_promoteur", 0));
		$defaultData["localiteid"]       = intval($this->_getParam("localiteid" , LOCALITEID));
		$defaultData["demandeurid"]      = $demandeurid    = intval($this->_getParam("demandeurid", 0));
		$defaultData["promoteurid"]      = $promoteurid    = intval($this->_getParam("promoteurid", 0));
		$defaultData["entrepriseid"]     = $entrepriseid   = intval($this->_getParam("entrepriseid",0));
		$defaultData["disponible"]       = 0;
		$defaultData["expired"]          = 0;
		$defaultData["date_year"]        = date("Y");
		$defaultData["date_month"]       = date("m");
		$defaultData["date_day"]         = date("d");
		$errorMessages                   = array();
		
		$this->view->formes              = $formes         = $modelEntrepriseForme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines            = $domaines       = $modelDomaine->getSelectListe(        "Secteur d'activité"              , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->statuts             = $demandeStatuts = $modelStatut->getSelectListe(         "Sélectionnez un statut"          , array("statutid","libelle"), array(),null,null,false );
        $this->view->types               = $demandeTypes   = $modelType->getSelectListe(           "Sélectionnez un type de demandes", array("typeid","libelle")  , array(),null,null,false );		
		$this->view->localites           = $localites      = $modelLocalite->getSelectListe(       "Sélectionnez une localité"       , array("localiteid", "libelle") , array() , null , null , false );
		$this->view->countries           = $countries      = $modelCountry->getSelectListe(        "Selectionnez un pays"            , array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes       = $identiteTypes  = $modelIdentiteType->getSelectListe(   "Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		
		$demandeurRow                    = $entrepriseRow  = $promoteurRow  = null;
		$demandeurRow                    = ( $demandeurid  )?$modelDemandeur->findRow( $demandeurid ,"demandeurid" , null, false ) : null;
		$promoteurRow                    = ( $promoteurid  )?$modelPromoteur->findRow( $promoteurid ,"promoteurid" , null, false ) : null;
		$entrepriseRow                   = ( $entrepriseid )?$modelEntreprise->findRow($entrepriseid,"entrepriseid", null, false ) : null;
		$demandeurIdentityRow            = (isset($demandeurRow->identityid))?$modelIdentite->findRow($demandeurRow->identityid,"identityid", null, false ) : null;
		
		if( $this->_request->isPost() ) {
			$postData                    = $this->_request->getPost();			
			$modelTable                  = $model->getTable();
			$dbAdapter                   = $modelTable->getAdapter();
			$tableName                   = $modelTable->info("name");
			$prefixName                  = $modelTable->info("namePrefix");
			
			$defaultData                 = $model->getEmptyData();
			$formData                    = array_intersect_key($postData ,   $defaultData);
			$insert_data                 = $demandeData = array_merge($defaultData, $formData);
			$defaultIdentityData         = ($demandeurIdentityRow)?$demandeurIdentityRow->toArray() : $modelIdentite->getEmptyData();
			$defaultDemandeurData        = ($demandeurRow        )?$demandeurRow->toArray()         : $modelDemandeur->getEmptyData();
			$defaultPromoteurData        = ($promoteurRow        )?$promoteurRow->toArray()         : $modelPromoteur->getEmptyData();
			$defaultEntrepriseData       = ($entrepriseRow       )?$entrepriseRow->toArray()        : $modelEntreprise->getEmptyData();
			
			$pieceIdentityData           = array_merge($defaultIdentityData  ,array_intersect_key($postData,$defaultIdentityData ));
			$demandeurData               = array_merge($defaultDemandeurData ,array_intersect_key($postData,$defaultDemandeurData), $pieceIdentityData);
			$promoteurData               = array_merge($defaultPromoteurData ,array_intersect_key($postData,$defaultPromoteurData));
			$entrepriseData              = array_merge($defaultEntrepriseData,array_intersect_key($postData,$defaultEntrepriseData));
 			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
						
			$insert_data["numero"]       = $model->reference();
			$insert_data["demandeurid"]  = (isset( $postData["demandeurid"] ))?intval($postData["demandeurid"])  : $demandeurid;
			$insert_data["promoteurid"]  = (isset( $postData["promoteurid"] ))?intval($postData["promoteurid"])  : $promoteurid;
			$insert_data["entrepriseid"] = (isset( $postData["entrepriseid"]))?intval($postData["entrepriseid"]) : $entrepriseid;
			$insert_data["localiteid"]   = (isset( $postData["localiteid"]  ))?intval($postData["localiteid"])   : LOCALITEID;
			$insert_data["keywords"]	 = (isset( $postData["keywords"]    ))? $stringFilter->filter($postData["keywords"])	 : "";
			$insert_data["registreid"]   = 0;
			$insert_data["periodid"]     = 0;			
			$insert_data["libelle"]      = "";			
			$insert_data["objet"]        = "";
			$insert_data["typeid"]       = (isset($postData["typeid"])   && isset($demandeTypes[$postData["typeid"]]    ))?intval($postData["typeid"])   : 1;
			$insert_data["statutid"]     = (isset($postData["statutid"]) && isset($demandeStatuts[$postData["statutid"]]))?intval($postData["statutid"]) : 1;
			
			if( empty( $insert_data["keywords"])) {
				$errorMessages[]         = "Veuillez renseigner les mots clés de recherche des noms similaires";
			} 
            if(!intval($insert_data["typeid"]) || !isset($demandeTypes[$insert_data["typeid"]])) {
				$errorMessages[]         = "Veuillez sélectionner un type de demande(vérification ou réservation)";
			}
            if(!intval($insert_data["statutid"]) || !isset($demandeStatuts[$insert_data["statutid"]])) {
				$errorMessages[]         = "Le statut de la demande est invalide";
			}	
            if(!isset($domaines[$postData["domaineid"]]) || !intval($postData["domaineid"])) {
				$errorMessages[]         = "Veuillez préciser le secteur d'activité de l'entreprise à créer";
			}
            if( !isset($formes[$postData["formid"]]) || !intval($postData["formid"])) {
				$errorMessages[]         = "Veuillez préciser la forme juridique de l'entreprise à créer";
			}			
			if( $strNotEmptyValidator->isValid( $insert_data["date"]) && Zend_Date::isDate( $insert_data["date"],"YYYY-MM-dd")) {
				$zendDate                = new Zend_Date( $insert_data["date"],"YYYY-MM-dd");
				if( $zendDate ) {
					$zendDate->set(date("h:i:s"),Zend_Date::TIMES);
				}
				$insert_data["date"]     = $zendDate->get( Zend_Date::TIMESTAMP);
			} elseif($strNotEmptyValidator->isValid( $insert_data["date"]) && Zend_Date::isDate( $insert_data["date"],"dd/MM/YYYY")) {
				$zendDate                = new Zend_Date( $insert_data["date"],"dd/MM/YYYY");
				if( $zendDate ) {
					$zendDate->set(date("h:i:s"),Zend_Date::TIMES);
				}
				$insert_data["date"]     = $zendDate->get( Zend_Date::TIMESTAMP);
			} else {
				$insert_data["date"]     = time();
				$zendDate                = Zend_Date::now();;
			}
			if( isset($postData["date_day"]) && isset($postData["date_month"]) && isset($postData["date_year"])) {
				$dateYear                = (isset($postData["date_year"] ))?$stringFilter->filter($postData["date_year"])  : "0000";
			    $dateMonth               = (isset($postData["date_month"]))?$stringFilter->filter($postData["date_month"]) : "00";
			    $dateDay                 = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			    $zendDate                = new Zend_Date(array("year"=> $dateYear,"month"=> $dateMonth,"day"=>$dateDay ));
				if( $zendDate ) {
					$zendDate->set(date('h:i:s'),Zend_Date::TIMES);
				}
				$insert_data["date"]            = (null!= $zendDate) ?$zendDate->get(Zend_Date::TIMESTAMP) : $insert_data["date"];
			}
			if( $insert_data["date"] > (time() +86400) || $insert_data["date"] <=0) {
				$errorMessages[]                = "Veuillez indiquer une date valide";
			}
			if(!intval($insert_data["demandeurid"])) {				
				$errorMessages[]                = "Veuillez sélectionner ou enregistrer le mandataire de cette demande";
			} 
			if( isset($postData["date_etablissement_year"]) && isset($postData["date_etablissement_month"])) {
				$zendIdentityDate               = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
					                                                  "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
					                                                  "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
			    $postData["date_etablissement"] = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
			}
			$promoteurData["name"]              = (isset( $postData["name"]      ))? $stringFilter->filter($postData["name"])       : "";
            $promoteurData["lastname"]          = (isset( $postData["lastname"]  ) && !empty($postData["lastname"]  ))? $stringFilter->filter($postData["lastname"]) : "";			
			$promoteurData["firstname"]         = (isset( $postData["firstname"] ) && !empty($postData["firstname"] ))? $stringFilter->filter($postData["firstname"]) : "";
			$promoteurData["telephone"]         = (isset( $postData["telephone"] ) && !empty($postData["telephone"] ))? $stringFilter->filter($postData["telephone"]) : $demandeurData["telephone"];
			$promoteurData["profession"]        = (isset( $postData["profession"]) && !empty($postData["profession"]))? $stringFilter->filter($postData["profession"]) : $demandeurData["profession"];
			$promoteurData["adresse"]           = (isset( $postData["adresse"])    && !empty($postData["adresse"]   ))? $stringFilter->filter($postData["adresse"]) : $demandeurData["adresse"];
			$promoteurData["identityid"]        = (isset( $postData["identityid"]))? $stringFilter->filter($postData["identityid"]) : 0;
			if( $strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$promoteurData["telephone"]     = $stringFilter->filter($promoteurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($promoteurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"] = $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s) du promoteur";
			} else {
				$promoteurData["firstname"]     = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille du promoteur";
			} else {
				$promoteurData["lastname"]      = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || (($promoteurData["sexe"] != "M" )  && ($promoteurData["sexe"] != "F" ) ) ) {
				$errorMessages[]                = "Veuillez sélectionner le sexe du promoteur , doit etre égal à M ou F";
			} else {
				$promoteurData["sexe"]          = $stringFilter->filter( $promoteurData["sexe"] );
			}
			$promoteurData["name"]              = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
				
            	
            if( $demandeur_equal_promoteur==1) {
				$promoteurData                  = array_merge($defaultPromoteurData ,array_intersect_key($demandeurData,$defaultPromoteurData));
				$promoteurData["identityid"]    = $demandeurData["identityid"];
			} else {
				$promoteurData["identityid"]    = 0;
            }
            if( isset($postData["identite_numero"]) && isset($postData["identitetype"]) && isset($identiteTypes[$postData["identitetype"]])) {
				$promoteurIdentiteData["numero"]= $identiteNumero = $stringFilter->filter($postData["identite_numero"]);
			    $promoteurIdentiteData["typeid"]= $identiteTypeId = intval($postData["identitetype"]);
				$demandeurPromoteurIdentite     = $modelDemandeur->getList(array("identitetypeid"=>$identiteTypeId,"numero"=>$identiteNumero));
			    if( isset($demandeurPromoteurIdentite[0]["identityid"])) {
					$promoteurData["identityid"]= $demandeurPromoteurIdentite[0]["identityid"];
				}
			}			
			$promoteurData["numidentite"]       = "";
			$promoteurData["nationalite"]       = (isset($postData["country"]))? $stringFilter->filter($postData["country"]) : "BF";
			$promoteurData["creatorid"]         = $me->userid;
			$promoteurData["creationdate"]      = time();	
			$promoteurData["updatedate"]        = $promoteurData["updateduserid"] = 0;			
			if(!intval($promoteurData["identityid"])) {
				$promoteurIdentiteData                            = array();
				$promoteurIdentiteData["typeid"]                  = ( isset($postData["identitetype"]) && isset($identiteTypes[$postData["identitetype"]]))?intval($postData["identitetype"]) : 0;
				$promoteurIdentiteData["numero"]                  = ( isset($postData["identite_numero"]        ))?$stringFilter->filter($postData["identite_numero"])         : "";
				$promoteurIdentiteData["organisme_etablissement"] = ( isset($postData["organisme_etablissement"]))?$stringFilter->filter($postData["organisme_etablissement"]) : "";
			    $promoteurIdentiteData["lieu_etablissement"]      = ( isset($postData["lieu_etablissement"]     ))?$stringFilter->filter($postData["lieu_etablissement"])      : "";
			    $promoteurIdentiteData["date_etablissement"]      = ( isset($postData["date_etablissement"]     ))?$stringFilter->filter($postData["date_etablissement"])      : "";
			    $promoteurIdentiteData["creationdate"]            = time();
				$promoteurIdentiteData["creatorid"]               = $me->userid;
				$promoteurIdentiteData["updatedate"]              = $promoteurIdentiteData["updateduserid"] = 0;
				if( $strNotEmptyValidator->isValid($promoteurIdentiteData["date_etablissement"]) && Zend_Date::isDate($promoteurIdentiteData["date_etablissement"],"YYYY-MM-dd")) {
					$zendDateEtablissement                        = new Zend_Date($promoteurIdentiteData["date_etablissement"],"YYYY-MM-dd");

					$promoteurIdentiteData["date_etablissement"]  = ($zendDateEtablissement)?$zendDateEtablissement->toString("YYYY-MM-dd") : "";
				} elseif($strNotEmptyValidator->isValid($promoteurIdentiteData["date_etablissement"]) && Zend_Date::isDate($promoteurIdentiteData["date_etablissement"],"dd/MM/YYYY")) {
					$zendDate                                     = new Zend_Date( $insert_data["date"],"dd/MM/YYYY");
					$promoteurIdentiteData["date_etablissement"]  = ($zendDateEtablissement)?$zendDateEtablissement->toString("YYYY-MM-dd") : "";
				} else {
					$promoteurIdentiteData["date_etablissement"]  = "";
				}
			    if( intval($promoteurIdentiteData["typeid"]) && !empty($promoteurIdentiteData["numero"]) && !empty($promoteurIdentiteData["lieu_etablissement"]) && !empty($promoteurIdentiteData["date_etablissement"])) {
					$dbAdapter->delete(     $prefixName."reservation_demandeurs_identite", array("numero=?"=>$promoteurIdentiteData["numero"],"typeid=?"=>$promoteurIdentiteData["typeid"]));
					if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $promoteurIdentiteData)) {
						$promoteurData["identityid"]              = $dbAdapter->lastInsertId();
						$NumIdentite                              = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identite_typeid"]],$postData["identite_numero"], $postData["date_etablissement"],$postData["organisme_etablissement"], $postData["lieu_etablissement"]);
					    $promoteurData["numidentite"]             = $NumIdentite;
					}
				}
			}			
			if( empty($errorMessages) ) {
				if( intval($promoteurid)) {
					if( isset($promoteurData["promoteurid"])) {
						unset($promoteurData["promoteurid"]);
					}
					$dbAdapter->update($prefixName."reservation_promoteurs", $promoteurData, array("promoteurid=?"=>intval($promoteurid)));
				} else {
					if( $dbAdapter->insert($prefixName."reservation_promoteurs", $promoteurData)) {
						$insert_data["promoteurid"] = $dbAdapter->lastInsertId();
					} else {
						$errorMessages[]            = "Veuillez saisir les informations du promoteur";
					}
				}				
			}
			$entrepriseRow                      = null;
            $entrepriseData["demandeid"]        = 0;			
            $entrepriseData["demandeurid"]      = $insert_data["demandeurid"];
			$entrepriseData["promoteurid"]      = $insert_data["promoteurid"];
			$entrepriseData["responsable"]      = $promoteurData["name"];
			$entrepriseData["catid"]            = 0;
			$entrepriseData["localiteid"]       = $insert_data["localiteid"];
			$entrepriseData["domaineid"]        = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : 0;
			$entrepriseData["formid"]           = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : 0;
			$entrepriseData["country"]          = (isset($postData["country"]  ))? $stringFilter->filter($postData["country"])  : "BF";
			$entrepriseData["city"]             = (isset($postData["city"]     ))? $stringFilter->filter($postData["city"])     : "OUA";
			$entrepriseData["address"]          = (isset($postData["address"]  ))? $stringFilter->filter($postData["address"])  : $promoteurData["adresse"];
			$entrepriseData["activite"]         = (isset($postData["activite"] ))? $stringFilter->filter($postData["activite"]) : "";
			$entrepriseData["numrccm"]          = (isset($postData["numrccm"]  ))? $stringFilter->filter($postData["numrccm"])  : "";
			$entrepriseData["numcnss"]          = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"])  : "";
			$entrepriseData["numifu"]           = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"])   : "";
			$entrepriseData["telephone"]        = (isset($postData["telephone"]))? $stringFilter->filter($postData["telephone"]): "";
			$entrepriseData["email"]            = (isset($postData["email"]    ))? $stringFilter->filter($postData["email"])    : "";
			$entrepriseData["reserved"]         = 0;
			$entrepriseData["blacklisted"]      = 0;
			$entrepriseData["datecreation"]     =  $entrepriseData["datefermeture"] = "";
			$entrepriseData["creationdate"]     =  time();
			$entrepriseData["creatorid"]        =  $me->userid;
			$entrepriseData["updateduserid"]    =  $entrepriseData["updatedate"]    = 0;
			if( isset($postData["nomcommercial"]) && $strNotEmptyValidator->isValid($postData["nomcommercial"])) {
				$entrepriseData["nomcommercial"]= $insert_data["objet"] = $stringFilter->filter($postData["nomcommercial"]);
			} else {
				$errorMessages[]                = "Veuillez saisir le nom commercial de l'entreprise à créer";
			}
			if( isset($postData["sigle"]) &&  $strNotEmptyValidator->isValid($postData["sigle"])) {
				$entrepriseData["sigle"]        = $stringFilter->filter($postData["sigle"]);
				$insert_data["objet"]           = $insert_data["objet"]."(".$entrepriseData["sigle"].")";
			}
            /*if(!isset($postData["denomination"]) || !$strNotEmptyValidator->isValid($postData["denomination"])) {
				$entrepriseData["denomination"] = $entrepriseData["nomcommercial"];
			}*/
			if( empty( $errorMessages )) {
				if( intval($entrepriseid)) {
					if( isset($entrepriseData["entrepriseid"]) ) {
						unset($entrepriseData["entrepriseid"]);
					}
					$dbAdapter->update($prefixName."reservation_demandes_entreprises", $entrepriseData, array("entrepriseid=?"=>intval($entrepriseid)));
				} else {
					if( $dbAdapter->insert($prefixName."reservation_demandes_entreprises", $entrepriseData)) {
						$insert_data["entrepriseid"] = $dbAdapter->lastInsertId();
						$entrepriseRow               = $modelEntreprise->findRow($insert_data["entrepriseid"],"entrepriseid", null, false );
					} else {
						$errorMessages[]             = "Veuillez saisir les informations de l'entreprise à créer";
					}
				}				
			}
			$insert_data["periodstart"]         = $insert_data["date"];			
			$insert_data["periodend"]           = $insert_data["periodstart"] + (3*24*3600);		
            $insert_data["personne_morale"]     = (isset($postData["personne_morale"]))? intval($postData["personne_morale"])             : 0;
			$insert_data["observations"]        = (isset($postData["observations"]   ))? $stringFilter->filter($postData["observations"]): "";
			$insert_data["expired"]             = 0;
			$insert_data["rejected"]            = 0;
			$insert_data["disponible"]          = 0;
			$insert_data["reject"]              = 0;
			$insert_data["motif_rejet"]         = "";
			$insert_data["creatorid"]           = $me->userid;
			$insert_data["creationdate"]        = time();	
			$insert_data["updatedate"]          = $insert_data["updateduserid"] = 0;	
			          				
			if( empty($errorMessages)) {
				$insert_data["libelle"]         = sprintf("%s de l'entreprise %s"  , $demandeTypes[$insert_data["typeid"]], $insert_data["objet"] );
				$emptyData                      = $model->getEmptyData();
				$clean_insert_data              = array_intersect_key( $insert_data, $emptyData);
				if( $dbAdapter->insert($tableName, $clean_insert_data) ) {
					$demandeid                  = $dbAdapter->lastInsertId();		
 					
					if( $entrepriseRow ) {
						$entrepriseRow->demandeid      = $demandeid;
						$entrepriseRow->save();
					}
					$verification_data                 = array("verificationid"=>$demandeid,"demandeurid"=>$insert_data["demandeurid"],"disponible"=>0,"sources"=>"","taux_disponibilite"=>0);
 				    $verification_data["creatorid"]    = $me->userid;
					$verification_data["creationdate"] = time();
					$verification_data["updatedate"]   = $verification_data["updateduserid"] = 0;
					$dbAdapter->delete(    $prefixName."reservation_demandes_verifications", array("verificationid=?"=>$demandeid));
					if( $dbAdapter->insert($prefixName."reservation_demandes_verifications", $verification_data )) {
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							echo ZendX_JQuery::encodeJson(array("success" => "La demande de vérification a été enregistrée avec succès"));
							exit;
						}
						$this->setRedirect("La demande de vérification a été enregistrée avec succès", "success" );
						$this->redirect("admin/demandes/get/id/".$demandeid);
					}										
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement de la demande a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la demande a echoué", "error");
					$this->redirect("admin/demandes/list")	;
				}
			}			
			if( count($errorMessages)) {
				$defaultData  = array_merge( $defaultData, $demandeData, $demandeurData, $promoteurData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data                = $defaultData;
		$this->view->demandeurid         = $demandeurid;
		$this->view->demandeurName       = ( $demandeurRow )?sprintf("%s %s", $demandeurRow->lastname, $demandeurRow->firstname ) : "";
		$this->view->promoteurName       = ( $promoteurRow )?sprintf("%s %s", $promoteurRow->lastname, $promoteurRow->firstname ) : "";
	}
	
	
	public function editAction()
	{		
	    $this->view->title               = "Mettre à jour les informations d'une demande";
		$demandeid                       = $demandeid = intval($this->_getParam("demandeid", $this->_getParam("id" ,$this->_getParam("demandeid", 0))));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}		
		
		$model                           = $this->getModel("demande");
        $modelType                       = $this->getModel("demandetype");
        $modelStatut                     = $this->getModel("demandestatut");		
		$modelEntreprise                 = $this->getModel("demandentreprise");
		$modelEntrepriseForme            = $this->getModel("entrepriseforme");
		$modelDomaine                    = $this->getModel("domaine");
        $modelDemandeur                  = $this->getModel("demandeur");
		$modelPromoteur                  = $this->getModel("promoteur");
		$modelIdentite                   = $this->getModel("usageridentite");
		$modelIdentiteType               = $this->getModel("usageridentitetype"); 
        $modelCountry                    = $this->getModel("country");
		$modelLocalite                   = $this->getModel("localite");
		$modelRegistre                   = $this->getModel("registre");
		
		$demande                         = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}
		
		$defaultData                     = $demande->toArray();
		$defaultData["date_year"]        = date("Y", $defaultData["date"]);
		$defaultData["date_month"]       = date("m", $defaultData["date"]);
		$defaultData["date_day"]         = date("d", $defaultData["date"]);
		$errorMessages                   = array();
		
		$this->view->formes              = $formes         = $modelEntrepriseForme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines            = $domaines       = $modelDomaine->getSelectListe(        "Secteur d'activité"              , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->statuts             = $demandeStatuts = $modelStatut->getSelectListe(         "Sélectionnez un statut"          , array("statutid","libelle"), array(),null,null,false );
        $this->view->types               = $demandeTypes   = $modelType->getSelectListe(           "Sélectionnez un type de demandes", array("typeid","libelle")  , array(),null,null,false );		
		$this->view->localites           = $localites      = $modelLocalite->getSelectListe(       "Sélectionnez une juridiction"    , array("localiteid", "libelle") , array() , null , null , false );
		$this->view->countries           = $countries      = $modelCountry->getSelectListe(        "Selectionnez un pays"            , array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes       = $identiteTypes  = $modelIdentiteType->getSelectListe(   "Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		
		$demandeurid                                 = intval($this->_getParam("demandeurid", $demande->demandeurid));
		$promoteurid                                 = intval($this->_getParam("promoteurid", $demande->promoteurid));
		$entrepriseid                                = intval($this->_getParam("entrepriseid",$demande->entrepriseid));
		
		$demandeurRow                                = ( $demandeurid      )? $modelDemandeur->findRow( $demandeurid, "demandeurid", null, false )  : null;
		$entrepriseRow                               = ( $entrepriseid     )? $modelEntreprise->findRow($entrepriseid,"entrepriseid", null, false ) : null;
		$promoteurRow                                = ( $promoteurid      )? $modelPromoteur->findRow( $promoteurid, "promoteurid", null, false )  : null;
		$promoteurIdentityId                         = ( $promoteurRow     )? $promoteurRow->identityid   : 0;
		$promoteurIdentite                           = ( $promoteurRow     )? $promoteurRow->identite($promoteurIdentityId,"identityid",null,false) : null;
		
		$defaultData["demandeurid"]                  = ( $demandeurRow     )?$demandeurRow->demandeurid   : 0;
		$defaultData["demandeurname"]                = ( $demandeurRow     )?sprintf("%s %s", $demandeurRow->lastname, $demandeurRow->firstname)   : "";
		$defaultData["name"]                         = ( $promoteurRow     )?sprintf("%s %s", $promoteurRow->lastname, $promoteurRow->firstname)   : "";
		$defaultData["country"]                      = $defaultData["nationalite"] = ( $promoteurRow )?$promoteurRow->nationalite   : "";
		$defaultData["telephone"]                    = ( $promoteurRow     )?$promoteurRow->telephone     : "";
		$defaultData["adresse"]                      = ( $promoteurRow     )?$promoteurRow->adresse       : "";
		$defaultData["profession"]                   = ( $promoteurRow     )?$promoteurRow->profession    : "";
		$defaultData["sexe"]                         = ( $promoteurRow     )?$promoteurRow->sexe          : "";
		$defaultData["promoteurid"]                  = ( $promoteurRow     )?$promoteurRow->promoteurid   : 0;
		$defaultData["organisme_etablissement"]      = ( $promoteurIdentite)?$promoteurIdentite->organisme_etablissement : "";
		$defaultData["lieu_etablissement"]           = ( $promoteurIdentite)?$promoteurIdentite->lieu_etablissement      : "";
		$defaultData["identite_numero"]              =   $defaultData["numidentite"] = ( $promoteurIdentite)?$promoteurIdentite->numero   : "";
		$defaultData["identitetype"]                 = ( $promoteurIdentite)?$promoteurIdentite->typeid   : 1;
		if( $promoteurIdentite->date_etablissement && Zend_Date::isDate($promoteurIdentite->date_etablissement,"YYYY-MM-dd")) {
			$zendDateEtablissement                   = new Zend_Date($promoteurIdentite->date_etablissement,"YYYY-MM-dd");
			$defaultData["date_etablissement_year"]  = ( $zendDateEtablissement )?$zendDateEtablissement->get(Zend_Date::YEAR) : 0;
			$defaultData["date_etablissement_month"] = ( $zendDateEtablissement )?$zendDateEtablissement->toString("MM") : 0;
			$defaultData["date_etablissement_day"]   = ( $zendDateEtablissement )?$zendDateEtablissement->toString("dd") : 0;
		}
		//var_dump($defaultData);die();
		$defaultData["nomcommercial"]                = ( $entrepriseRow    )?$entrepriseRow->nomcommercial: "";
		$defaultData["denomination"]                 = ( $entrepriseRow    )?$entrepriseRow->denomination : "";
		$defaultData["activite"]                     = ( $entrepriseRow    )?$entrepriseRow->activite     : "";
		$defaultData["domaineid"]                    = ( $entrepriseRow    )?$entrepriseRow->domaineid    : 0;
		$defaultData["formid"]                       = ( $entrepriseRow    )?$entrepriseRow->formid       : 0;
		
		if( $this->_request->isPost() ) {
			$postData                    = $this->_request->getPost();
			$me                          = Sirah_Fabric::getUser();
			$modelTable                  = $model->getTable();
			$dbAdapter                   = $modelTable->getAdapter();
			$tableName                   = $modelTable->info("name");
			$prefixName                  = $modelTable->info("namePrefix");
			
			$defaultData                 = $demande->toArray();
			$formData                    = array_intersect_key($postData ,   $defaultData);
			$update_data                 = $demandeData = array_merge($defaultData, $formData);
			$defaultIdentityData         = $modelIdentite->getEmptyData();
			$defaultDemandeurData        = ($demandeurRow )?$demandeurRow->toArray()  : $modelDemandeur->getEmptyData();
			$defaultPromoteurData        = ($promoteurRow )?$promoteurRow->toArray()  : $modelPromoteur->getEmptyData();
			$defaultEntrepriseData       = ($entrepriseRow)?$entrepriseRow->toArray() : $modelEntreprise->getEmptyData();
			
			$pieceIdentityData           = array_merge($defaultIdentityData  ,array_intersect_key($postData,$defaultIdentityData ));
			$demandeurData               = array_merge($defaultDemandeurData ,array_intersect_key($postData,$defaultDemandeurData), $pieceIdentityData);
			$promoteurData               = array_merge($defaultPromoteurData ,array_intersect_key($postData,$defaultPromoteurData));
			$entrepriseData              = array_merge($defaultEntrepriseData,array_intersect_key($postData,$defaultEntrepriseData));
 			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
						
			$update_data["numero"]       = $model->reference();
			$update_data["demandeurid"]  = (isset($postData["demandeurid"] ))?intval($postData["demandeurid"])  : $demande->demandeid;
			$update_data["promoteurid"]  = (isset($postData["promoteurid"] ))?intval($postData["promoteurid"])  : $demande->promoteurid;
			$update_data["entrepriseid"] = (isset($postData["entrepriseid"]))?intval($postData["entrepriseid"]) : $demande->entrepriseid;
			$update_data["localiteid"]   = (isset($postData["localiteid"]  ))?intval($postData["localiteid"])   : LOCALITEID;
			$update_data["registreid"]   = (isset($postData["registreid"]  ))?intval($postData["registreid"])   : $demande->registreid;
			$update_data["periodid"]     = (isset($postData["periodid"]    ))?intval($postData["periodid"])     : $demande->periodid;		
			$update_data["keywords"]	 = (isset($postData["keywords"]    ))? $stringFilter->filter($postData["keywords"])	 : $demande->keywords;
			$update_data["objet"]        = "";
			$update_data["typeid"]       = (isset($postData["typeid"])   && isset($demandeTypes[$postData["typeid"]]    ))?intval($postData["typeid"])   : $demande->typeid;
			$update_data["statutid"]     = (isset($postData["statutid"]) && isset($demandeStatuts[$postData["statutid"]]))?intval($postData["statutid"]) : $demande->statutid;
			$update_data["personne_morale"]  = (isset($postData["personne_morale"]))? intval($postData["personne_morale"]) : $demande->personne_morale;	
			
			if( empty( $update_data["keywords"] )) {
				$errorMessages[]         = "Veuillez saisir les mots clés de recherche des noms similaires";
			} else {
				$emptyWords              = $modelRegistre->emptywords();
				//$update_data["keywords"] = str_replace($emptyWords,"", $update_data["keywords"]);
			}
            if(!isset($demandeTypes[$update_data["typeid"]]) || !intval($update_data["typeid"])) {
				$errorMessages[]         = "Veuillez sélectionner un type de demande";
			}
            if(!isset($demandeStatuts[$update_data["statutid"]]) || !intval($update_data["statutid"])) {
				$errorMessages[]         = "Veuillez sélectionner un type de demande";
			}			
			if( $strNotEmptyValidator->isValid( $postData["date"]) && Zend_Date::isDate( $postData["date"],"YYYY-MM-dd")) {
				$zendDate                = new Zend_Date( $postData["date"],"YYYY-MM-dd");
				$update_data["date"]     = ($zendDate)?$zendDate->get( Zend_Date::TIMESTAMP) : $demande->date;
			} elseif($strNotEmptyValidator->isValid( $update_data["date"]) && Zend_Date::isDate( $update_data["date"],"dd/MM/YYYY")) {
				$zendDate                = new Zend_Date( $update_data["date"],"dd/MM/YYYY");
				if( $zendDate ) {
					$zendDate->set(date("h:i:s"),Zend_Date::TIMES);
				}
				$update_data["date"]            = ($zendDate)?$zendDate->get( Zend_Date::TIMESTAMP) : $demande->date;
			} else {
				$update_data["date"]            = time();
				$zendDate                       = Zend_Date::now();;
			}
			if( isset($postData["date_day"]) && isset($postData["date_month"]) && isset($postData["date_year"])) {
				$dateYear                       = (isset($postData["date_year"] ))?$stringFilter->filter($postData["date_year"])  : "0000";
			    $dateMonth                      = (isset($postData["date_month"]))?$stringFilter->filter($postData["date_month"]) : "00";
			    $dateDay                        = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			    $zendDate                       = new Zend_Date(array("year"=> $dateYear,"month"=> $dateMonth,"day"=>$dateDay ));
				if( $zendDate ) {
					$zendDate->set(date('h:i:s'),Zend_Date::TIMES);
				}
				$update_data["date"]            = (null!= $zendDate) ?$zendDate->get(Zend_Date::TIMESTAMP) : $update_data["date"];
			}
			if( $update_data["date"] > (time() +86400) || $update_data["date"] <=0) {
				$errorMessages[]                = "Veuillez indiquer une date valide";
			}
			if(!intval($update_data["demandeurid"])) {				
				$errorMessages[]                = "Veuillez sélectionner ou enregistrer le mandataire";
			} else {
				$demandeurRow                   = ($update_data["demandeurid"])?$modelDemandeur->findRow($update_data["demandeurid"],"demandeurid", null, false ) : null;
			}
			$promoteurData["name"]              = (isset( $postData["name"]      ))? $stringFilter->filter($postData["name"])                                          : $promoteurData["name"];
            $promoteurData["lastname"]          = (isset( $postData["lastname"]  ) && !empty($postData["lastname"]  ))? $stringFilter->filter($postData["lastname"])   : $promoteurData["lastname"];			
			$promoteurData["firstname"]         = (isset( $postData["firstname"] ) && !empty($postData["firstname"] ))? $stringFilter->filter($postData["firstname"])  : $promoteurData["firstname"];
			$promoteurData["telephone"]         = (isset( $postData["telephone"] ) && !empty($postData["telephone"] ))? $stringFilter->filter($postData["telephone"])  : $promoteurData["telephone"];
			$promoteurData["profession"]        = (isset( $postData["profession"]) && !empty($postData["profession"]))? $stringFilter->filter($postData["profession"]) : $promoteurData["profession"];
			$promoteurData["adresse"]           = (isset( $postData["adresse"])    && !empty($postData["adresse"]   ))? $stringFilter->filter($postData["adresse"])    : $promoteurData["adresse"];
			$promoteurData["identityid"]        = (isset( $postData["identityid"]))? $stringFilter->filter($postData["identityid"]) : 0;
			if( $strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$promoteurData["telephone"]     = $stringFilter->filter($promoteurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($promoteurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"] = $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s) du promoteur";
			} else {
				$promoteurData["firstname"]     = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille du promoteur";
			} else {
				$promoteurData["lastname"]      = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || (($promoteurData["sexe"] != "M" )  && ($promoteurData["sexe"] != "F" ))) {
				$errorMessages[]                = "Veuillez sélectionner le sexe du promoteur , doit etre égal à M ou F";
			} else {
				$promoteurData["sexe"]          = $stringFilter->filter( $promoteurData["sexe"] );
			}
			$promoteurData["name"]              = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
			if(($promoteurData["lastname"]==$demandeurData["lastname"] && $promoteurData["firstname"]== $demandeurData["firstname"]) || ($promoteurData["name"]==$demandeurData["name"])) {
				$promoteurData["identityid"]    = $demandeurData["identityid"];
			}	
            if( isset($postData["identite_numero"]) && isset($postData["identitetype"]) && isset($identiteTypes[$postData["identitetype"]])) {
				$promoteurIdentiteData["numero"]= $identiteNumero = $stringFilter->filter($postData["identite_numero"]);
			    $promoteurIdentiteData["typeid"]= $identiteTypeId = intval($postData["identitetype"]);
				$demandeurPromoteurIdentite     = $modelDemandeur->getList(array("identitetypeid"=>$identiteTypeId,"numero"=>$identiteNumero));
			    if( isset($demandeurPromoteurIdentite[0]["identityid"])) {
					$promoteurData["identityid"]= $demandeurPromoteurIdentite[0]["identityid"];
				}
			}
			$promoteurData["identityid"]        = (isset($postData["identityid"]))? intval($postData["identityid"])                 : intval($promoteurData["identityid"]);
			$promoteurData["profession"]        = (isset($postData["profession"]))? $stringFilter->filter($postData["profession"])  : $promoteurData["profession"];
			$promoteurData["adresse"]           = (isset($postData["adresse"]   ))? $stringFilter->filter($postData["adresse"])     : $promoteurData["adresse"];
			$promoteurData["nationalite"]       = (isset($postData["country"]   ))? $stringFilter->filter($postData["country"])     : $promoteurData["nationalite"];
			$promoteurData["updateduserid"]     = $me->userid;
			$promoteurData["updatedate"]        = time();	
			
			if( empty($errorMessages) ) {
				if(!intval($promoteurData["identityid"]) || !intval($promoteurIdentityId)) {
					$promoteurIdentiteData                            = array();
					if( isset($postData["date_etablissement_day"]) && isset($postData["date_etablissement_month"]) && isset($postData["date_etablissement_year"])) {
						$dateYear                                     = (isset($postData["date_etablissement_year"] ))?$stringFilter->filter($postData["date_etablissement_year"])  : "0000";
						$dateMonth                                    = (isset($postData["date_etablissement_month"]))?$stringFilter->filter($postData["date_etablissement_month"]) : "00";
						$dateDay                                      = (isset($postData["date_etablissement_day"]) && ($postData["date_etablissement_day"] != "00" ))? $stringFilter->filter($postData["date_etablissement_day"]) : "05";										
						$zendDateEtablissement                        = new Zend_Date(array("year"=> $dateYear,"month"=> $dateMonth,"day"=>$dateDay ));
						if( $zendDateEtablissement ) {
							$zendDateEtablissement->set(date('h:i:s'),Zend_Date::TIMES);
						}
						$promoteurIdentiteData["date_etablissement"]  = $postData["date_etablissement"] = ( $zendDateEtablissement) ?$zendDateEtablissement->get("YYYY-MM-dd") : $update_etablissement_data["date"];
					}
					$promoteurIdentiteData["typeid"]                  = (isset($postData["identitetype"])            && isset($identiteTypes[$postData["identitetype"]] ))? intval($postData["identitetype"])                           : 1;
					$promoteurIdentiteData["numero"]                  = (isset($postData["identite_numero"]        ) && !empty($postData["identite_numero"]             ))? $stringFilter->filter($postData["identite_numero"])         : "";
					$promoteurIdentiteData["organisme_etablissement"] = (isset($postData["organisme_etablissement"]) && !empty($postData["organisme_etablissement"]     ))? $stringFilter->filter($postData["organisme_etablissement"]) : "";
					$promoteurIdentiteData["lieu_etablissement"]      = (isset($postData["lieu_etablissement"]     ) && !empty($postData["lieu_etablissement"]          ))? $stringFilter->filter($postData["lieu_etablissement"])      : "";
                    $promoteurIdentiteData["date_etablissement"]      = (isset($postData["date_etablissement"]     ) && !empty($postData["date_etablissement"]          ))? $stringFilter->filter($postData["date_etablissement"])      : "";
					$promoteurIdentiteData["creationdate"]            = time();
					$promoteurIdentiteData["creatorid"]               = $me->userid;
					$promoteurIdentiteData["updateduserid"]           = $promoteurIdentiteData["updatedate"]   = 0;
					if( intval($promoteurIdentiteData["typeid"]) && !empty($promoteurIdentiteData["numero"]) && !empty($promoteurIdentiteData["lieu_etablissement"]) && !empty($promoteurIdentiteData["date_etablissement"])) {
						$dbAdapter->delete(     $prefixName."reservation_demandeurs_identite", array("numero=?"=>$promoteurIdentiteData["numero"],"typeid=?"=>$promoteurIdentiteData["typeid"]));
						if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $promoteurIdentiteData)) {
							$promoteurData["identityid"]              = $promoteurIdentityId = $dbAdapter->lastInsertId();
							$NumIdentite                              = sprintf("%s n° %s du %s par %s", $identiteTypes[intval($postData["identitetype"])],$postData["identite_numero"], $postData["date_etablissement"],$postData["organisme_etablissement"], $postData["lieu_etablissement"]);
							$promoteurData["numidentite"]             = $NumIdentite;
						}
					}
				}
				if( $promoteurRow ) {
					if( isset($promoteurData["promoteurid"])) {
						unset($promoteurData["promoteurid"]);
					}
					$promoteurRow->setFromArray($promoteurData);
					$promoteurRow->save();
				} else {
					if( $dbAdapter->insert($prefixName."reservation_promoteurs", $promoteurData)) {
						$update_data["promoteurid"] = $dbAdapter->lastInsertId();
					} else {
						$errorMessages[]            = "Veuillez saisir les informations du promoteur";
					}
				}	
 
                if( $promoteurRow && $promoteurIdentityId ) {
					$promoteurIdentiteData                           = array();
					if( isset($postData["date_etablissement_day"]) && isset(  $postData["date_etablissement_month"]) && isset($postData["date_etablissement_year"])) {
						$dateYear                                    = (isset($postData["date_etablissement_year"] ))?$stringFilter->filter($postData["date_etablissement_year"])  : "0000";
						$dateMonth                                   = (isset($postData["date_etablissement_month"]))?$stringFilter->filter($postData["date_etablissement_month"]) : "00";
						$dateDay                                     = (isset($postData["date_etablissement_day"]) && ($postData["date_etablissement_day"] != "00" ))? $stringFilter->filter($postData["date_etablissement_day"]) : "05";										
						$zendDateEtablissement                       = new Zend_Date(array("year"=> $dateYear,"month"=> $dateMonth,"day"=>$dateDay ));
						if( $zendDateEtablissement ) {
							$zendDateEtablissement->set(date('h:i:s'),Zend_Date::TIMES);
						}
						$promoteurIdentiteData["date_etablissement"]  = $postData["date_etablissement"] = (null!= $zendDateEtablissement) ?$zendDateEtablissement->get("YYYY-MM-dd") : $update_etablissement_data["date"];
					}
					
					$promoteurIdentiteData["typeid"]                  = (isset($postData["identitetype"]) && isset($identiteTypes[$postData["identitetype"]]))?intval($postData["identitetype"]) : $promoteurIdentite->typeid;
					$promoteurIdentiteData["numero"]                  = (isset($postData["identite_numero"]        ) && !empty($postData["identite_numero"]         ))? $stringFilter->filter($postData["identite_numero"])         : $promoteurIdentite->numero;
					$promoteurIdentiteData["organisme_etablissement"] = (isset($postData["organisme_etablissement"]) && !empty($postData["organisme_etablissement"] ))? $stringFilter->filter($postData["organisme_etablissement"]) : $promoteurIdentite->organisme_etablissement;
					$promoteurIdentiteData["lieu_etablissement"]      = (isset($postData["lieu_etablissement"]     ) && !empty($postData["lieu_etablissement"]      ))? $stringFilter->filter($postData["lieu_etablissement"])      : $promoteurIdentite->lieu_etablissement;
                    $promoteurIdentiteData["date_etablissement"]      = (isset($postData["date_etablissement"]     ) && !empty($postData["date_etablissement"]      ))? $stringFilter->filter($postData["date_etablissement"])      : $promoteurIdentite->date_etablissement;
					
					 
					try {
 
						$dbAdapter->update($prefixName."reservation_demandeurs_identite", $promoteurIdentiteData, array("identityid=?"=>$promoteurIdentityId));
					} catch( Exception $e ) {
						$errorMessages[]                               = sprintf("Les informations de la pièce d'identité n'ont pas été mises à jour");
					}
				}					
			}
            $entrepriseData["demandeid"]        = 0;			
            $entrepriseData["demandeurid"]      = $update_data["demandeurid"];
			$entrepriseData["promoteurid"]      = $update_data["promoteurid"];
			$entrepriseData["responsable"]      = $promoteurData["name"];
			$entrepriseData["catid"]            = 0;
			$entrepriseData["domaineid"]        = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : $entrepriseData["domaineid"];
			$entrepriseData["formid"]           = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : $entrepriseData["formid"];
			$entrepriseData["country"]          = (isset($postData["country"]  ))? $stringFilter->filter($postData["country"])   : $entrepriseData["country"];
			$entrepriseData["city"]             = (isset($postData["city"]     ))? $stringFilter->filter($postData["city"])      : $entrepriseData["city"];
			$entrepriseData["address"]          = (isset($postData["address"]  ))? $stringFilter->filter($postData["address"])   : $promoteurData["adresse"];
			$entrepriseData["activite"]         = (isset($postData["activite"] ))? $stringFilter->filter($postData["activite"])  : $entrepriseData["activite"];
			$entrepriseData["numrccm"]          = (isset($postData["numrccm"]  ))? $stringFilter->filter($postData["numrccm"])   : $entrepriseData["numrccm"];
			$entrepriseData["numcnss"]          = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"])   : $entrepriseData["numcnss"];
			$entrepriseData["numifu"]           = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"])    : $entrepriseData["numifu"];
			$entrepriseData["telephone"]        = (isset($postData["telephone"]))? $stringFilter->filter($postData["telephone"]) : $entrepriseData["telephone"];
			$entrepriseData["email"]            = (isset($postData["email"]    ))? $stringFilter->filter($postData["email"])     : $entrepriseData["email"];
			$entrepriseData["updateduserid"]    = $me->userid;
			$entrepriseData["updatedate"]       = time();
			if( isset($postData["nomcommercial"]) && $strNotEmptyValidator->isValid($postData["nomcommercial"])) {
				$entrepriseData["nomcommercial"]= $update_data["objet"] = $stringFilter->filter($postData["nomcommercial"]);
			} else {
				$errorMessages[]                = "Veuillez saisir le nom commercial de l'entreprise";
			}
			if( isset($postData["sigle"]) &&  $strNotEmptyValidator->isValid($postData["sigle"])) {
				$entrepriseData["sigle"]        = $stringFilter->filter($postData["sigle"]);
				$update_data["objet"]           = $update_data["objet"]."(".$entrepriseData["sigle"].")";
			}
            if(!isset($postData["denomination"]) || !$strNotEmptyValidator->isValid($postData["denomination"])) {
				$entrepriseData["denomination"] = $entrepriseData["nomcommercial"];
			}
			if( empty( $errorMessages )) {
				if( $entrepriseRow ) {
					if( isset($entrepriseData["entrepriseid"])) {
						unset($entrepriseData["entrepriseid"]);
					}
					$entrepriseRow->setFromArray($entrepriseData);
					$entrepriseRow->save();
				} else {
					if( $dbAdapter->insert($prefixName."reservation_demandes_entreprises", $entrepriseData)) {
						$update_data["entrepriseid"] = $dbAdapter->lastInsertId();
						$entrepriseRow               = $modelEntreprise->findRow($update_data["entrepriseid"],"entrepriseid", null, false );
					} else {
						$errorMessages[]             = "Veuillez saisir les informations de l'entreprise à créer";
					}
				}				
			}
			$update_data["periodstart"]         = $update_data["date"];			
			$update_data["periodend"]           = $update_data["periodstart"] + (3*24*3600);			
			$update_data["observations"]        = (isset($postData["observations"]))? $stringFilter->filter($postData["observations"]) : $demande->observations;
			$update_data["updateduserid"]       = $me->userid;
			$update_data["updatedate"]          = time();	
            				
			if( empty($errorMessages)) {
				$update_data["libelle"]         = sprintf("%s de l'entreprise %s", $demandeStatuts[$update_data["statutid"]], $update_data["objet"] );
				$emptyData                      = $model->getEmptyData();
				$clean_update_data              = array_intersect_key( $update_data, $emptyData);
				if( isset($clean_update_data["demandeid"])) {
					unset($clean_update_data["demandeid"]);
				}
				if( $dbAdapter->update($tableName, $clean_update_data, array("demandeid=?"=>$demandeid)) ) { 					
					if( $entrepriseRow ) {
						$entrepriseRow->demandeid      = $demandeid;
						$entrepriseRow->save();
					}
					$verification_data                 = array("verificationid"=>$demandeid,"demandeurid"=>$update_data["demandeurid"],"disponible"=>0,"sources"=>"","taux_disponibilite"=>0);
 				    $verification_data["creatorid"]    = $me->userid;
					$verification_data["creationdate"] = time();
					$verification_data["updatedate"]   = $verification_data["updateduserid"] = 0;
					$dbAdapter->delete(    $prefixName."reservation_demandes_verifications", array("verificationid=?"=>$demandeid));
					if( $dbAdapter->insert($prefixName."reservation_demandes_verifications", $verification_data )) {
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							echo ZendX_JQuery::encodeJson(array("success" => "La demande de vérification a été enregistrée avec succès"));
							exit;
						}
						$this->setRedirect("La demande de vérification a été enregistrée avec succès", "success" );
						$this->redirect("admin/demandes/infos/id/".$demandeid);
					}										
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement de la demande a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la demande a echoué", "error");
					$this->redirect("admin/demandes/list")	;
				}
			}			
			if( count($errorMessages)) {
				$defaultData  = array_merge( $defaultData, $demandeData, $demandeurData, $promoteurData, $entrepriseData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data           = $defaultData;
		$this->view->demandeid      = $demandeid;
		$this->view->demandeurid    = $demandeurid;
		$this->view->promoteurid    = $promoteurid;
		$this->view->entrepriseid   = $entrepriseid;
		$this->view->demandeurName  = ( $demandeurRow )?sprintf("%s %s", $demandeurRow->lastname, $demandeurRow->firstname ) : "";
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
			$this->redirect("admin/demandes/list");
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
			$this->redirect("admin/demandes/list");
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
        $this->view->demandeurIdentite = ( $demandeurRow->identityid)?$modelDemandeur->identite($demandeurRow->identityid) : null;
        $this->view->promoteurIdentite = ( $promoteurRow->identityid)?$modelPromoteur->identite($promoteurRow->identityid) : null;		
        $this->view->demandeur         = $demandeurRow;	
        $this->view->promoteur         = $promoteurRow;	
        $this->view->entreprise        = $entrepriseRow;
		$this->view->domaineActivite    = ($entrepriseRow)?$modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
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
			    $typeOfDocument        = "reservation";
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
	
	
	public function reserveAction()
	{
		$me                            = Sirah_Fabric::getUser();
		$demandeid                     = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}				
		$model                         = $this->getModel("demande");
        $modelType                     = $this->getModel("demandetype");	
		$modelEntreprise               = $this->getModel("demandentreprise");
        $modelTable                    = $model->getTable();
		$dbAdapter                     = $modelTable->getAdapter();
		$prefixName                    = $modelTable->info("namePrefix");
		$tableName                     = $modelTable->info("name");
		
		$demande                       = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}
		
		$entrepriseRow                         = $demande->entreprise();
		$reservationRow                        = $demande->reservation();
		
		if( isset( $reservationRow["reservationid"])) {
			$reservationData                   = array();
			$reservationData["demandeurid"]    = $reservationRow["demandeurid"];
			$reservationData["entrepriseid"]   = $reservationRow["entrepriseid"];
			$reservationData["code"]           = $reservationRow["code"];
			$reservationData["nomcommercial"]  = $reservationRow["nomcommercial"];
			$reservationData["denomination"]   = $reservationRow["denomination"];
			$reservationData["sigle"]          = $reservationRow["sigle"];
			$reservationData["updatedate"]     = time();
			$reservationData["updateduserid"]  = $me->userid;
			$dbAdapter->update( $prefixName."reservation_demandes_reservations", $reservationData, array("reservationid=?"=>$demandeid));
		} else {
			$reservationData                   = array("reservationid"=>$demandeid);
			$reservationData["demandeurid"]    = $demande->demandeurid;
			$reservationData["entrepriseid"]   = $demande->entrepriseid;
			$reservationData["code"]           = $demande->reservationkey();
			$reservationData["nomcommercial"]  = $entrepriseRow->nomcommercial;
			$reservationData["sigle"]          = (isset($entrepriseRow->sigle))?$entrepriseRow->sigle : "";
			$reservationData["denomination"]   = $entrepriseRow->denomination;
			$reservationData["expired"]        = 0;
			$reservationData["expirationdate"] = ($demande->date)?($demande->date+(3*30*24*3600)) : (time()+(3*30*24*3600));
			$reservationData["creationdate"]   = ($demande->date)? $demande->date                 :  time();
			$reservationData["creatorid"]      = $me->userid;
			$reservationData["updateduserid"]  = $reservationData["updatedate"] = 0;
			$dbAdapter->insert( $prefixName."reservation_demandes_reservations", $reservationData);
		}				
		$dbAdapter->update($prefixName."reservation_demandes_entreprises", array("reserved" =>1), array("demandeid=?"=>$demandeid) );
		$dbAdapter->update($prefixName."reservation_demandes_requests"   , array("processed"=>1,"validated"=>1,"updatedate"=>time(),"updateduserid"=>$me->userid), array("demandeid=?"=>$demandeid) );
		$demande->disponible                   = 1;
		$demande->statutid                     = 4;
		$demande->updateduserid                = $me->userid;
		$demande->save();
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->viewRenderer->setNoRender(true);
			$this->_helper->layout->disableLayout(true);
			echo ZendX_JQuery::encodeJson(array("success" => sprintf("La réservation du nom commercial `%s` a été enregistrée avec succès", $demande->objet)));
			exit;
		}
		$this->setRedirect(sprintf("La réservation du nom commercial `%s` a été enregistrée avec succès", $demande->objet), "success");
		$this->redirect("admin/demandes/get/demandeid/".$demandeid."/type/reservation");
	}
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$me                  = Sirah_Fabric::getUser();
		$model               = $this->getModel("demande");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$tablePrefix         = $modelTable->info("namePrefix");
		$ids                 = $this->_getParam("demandeids", $this->_getParam("ids",array()));
 
		$errorMessages       = array();
		if( is_string($ids) ) {
			$ids             = explode("," , $ids );
		}
		$ids                 = (array)$ids;
		$deleteFilters       =  array();
		if(!$me->isAdmin()) {
			$deleteFilters[] = "creatorid='".$me->userid."'";
		}     		
		if( count(   $ids)) {
			foreach( $ids as $id) {
					 $deleteFilters[] = "demandeid='".$id."'";
 					 
					 if( $dbAdapter->delete($tablePrefix."reservation_demandes"              , $deleteFilters) ) {
                         $dbAdapter->delete($tablePrefix."reservation_demandes_verifications", array("verificationid=?"=>$id));
						 $dbAdapter->delete($tablePrefix."reservation_demandes_reservations" , array("reservationid=?" =>$id));
						 $dbAdapter->delete($tablePrefix."reservation_demandes_requests"     , array("demandeid=?" =>$id));
					 } else {
						 $demandeRow          = $model->findRow($id,"demandeid", null, false);
                         if( $demandeRow ) {
							 $errorMessages[] = sprintf("La demande de vérification/réservation de <b> %s </b> n'a pas pu être supprimée, certainement pour des autorisations manquantes", $demandeRow->objet);
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
			$this->redirect("admin/demandes/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"=> "Les demandes selectionnées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les demandes selectionnées ont été supprimées avec succès", "success");
			$this->redirect("admin/demandes/list");
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
			$this->redirect("admin/demandes/list");
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
        $modelLocalite		               = $this->getModel("localite");
		$demande                          = $model->findRow( $demandeid, "demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandes/list");
		}
        $this->view->identiteTypes        = $identiteTypes  = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );		
		$demandeurid                      = $demande->demandeurid;
		$entrepriseid                     = $demande->entrepriseid;
		$promoteurid                      = $demande->promoteurid;	
        $localiteid                       = $demande->localiteid;		
		$demandeurRow                     = ($demandeurid )?$modelDemandeur->findRow( $demandeurid ,"demandeurid" ,null, false) : null;
		$entrepriseRow                    = ($entrepriseid)?$modelEntreprise->findRow($entrepriseid,"entrepriseid",null, false) : null;
		$promoteurRow                     = ($promoteurid )?$modelPromoteur->findRow( $promoteurid ,"promoteurid" ,null, false) : null;
		
		$localiteRow                      = ($localiteid) ? $modelLocalite->findRow($localiteid,"localiteid",null,false) : null;
		$contentData                      = array("demande"=>$demande,"demandeid"=> $demandeid ,"demandeur"=>$demandeurRow,"promoteur"=>$promoteurRow,"entreprise"=>$entrepriseRow,"me"=>$me,"localite"=>$localiteRow);
        $contentData["demandeurIdentite"] = ($demandeurRow            )? $demandeurRow->identite($demandeurRow->identityid) : null;
		$contentData["promoteurIdentite"] = ($promoteurRow            )? $promoteurRow->identite($promoteurRow->identityid) : null;
		$contentData["domaine"]           = ($entrepriseRow->domaineid)? $modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
		$contentData["forme"]             = ($entrepriseRow->formid   )? $modelEntrepriseForme->findRow($entrepriseRow->formid,"formid", null, false) : null;
		$contentData["statut"]            = $demande->findParentRow("Table_Demandestatuts");
		$contentData["identitetypes"]     = $identiteTypes;
		$documentTpl                      = "fiche";
		$documentName                     = "DemandeVerification";
		$documentTitle                    = "Demande de verification de la disponibilite d'un nom commercial";
		$documentCategory                 = 10;
		
		switch( strtolower($documentType)) {
			case "default":
			default:
			   $documentTpl            = "default";
			   $documentName           = "DemandeVerification";
			   $documentCategory       = 10;
			   $documentTitle          = sprintf("La FICHE DE RECHERCHE DE DISPONIBILITE ET DE RESERVATION Num %s", $demande->numero);
			   break;
			case "disponibilite":
			   $documentTpl            = "attestation.disponibilite";
			   $documentName           = "AttestationDeDisponibilite";
			   $documentCategory       = 11;
			   $documentTitle          = sprintf("L'Attestation de disponibilite du nom commercial %s", $demande->objet);
			   break;
			case "indisponibilite":
			   $documentTpl            = "attestation.indisponibilite";
			   $documentName           = "AttestationDInDisponibilite";
			   $documentCategory       = 8;
			   $documentTitle          = sprintf("L'Attestation d'indisponibilite du nom commercial %s", $demande->objet);
			   break;
			case "reservation":
			   $documentTpl            = "attestation.reservation";
			   $documentName           = "AttestationDeReservation";
			   $documentCategory       = 7;
			   $documentTitle          = sprintf("L'Attestation de réservation du nom commercial %s", $demande->objet);
			   break;
			case "rejet":
			   $documentTpl            = "attestation.rejet";
			   $documentName           = "AttestationDeRejet";
			   $documentCategory       = 9;
			   $documentTitle          = sprintf("Attestation de rejet du nom commercial %s", $demande->objet);
			   break;			
		}
		$contenu                       = $this->view->partial("demandes/fiches/{$documentTpl}.phtml", $contentData);
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
                 $pageHeaderMargin     = ( $showHeader )? 20 : 1;				 
                 $demandePDF           = Sirah_Fabric::getPdf();
                 $demandePDF->SetCreator("ERCCM");
			     $demandePDF->SetTitle($documentTitle);
			     $demandePDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			     $demandePDF->SetMargins(15,5 ,15);
                 $demandePDF->SetPrintHeader(false);
		         $demandePDF->SetPrintFooter(false);				 
			     $margins              = $demandePDF->getMargins();
			     $contenuWidth         = $demandePDF->getPageWidth()-$margins["left"]-$margins["right"];
				 $demandePDF->setCellHeightRatio(1.30);
				 $demandePDF->SetFont("helvetica", "" , 10);
				 $demandePDF->AddPage();
				 $demandePDF->writeHTML( $contenu, true , false , true , false , '' );
				 $demandePDF->Output(    $demandeFilename, "F");
				 $embedPDF                         = $demandePDF->Output("", "S");
				 if(!file_exists( $demandeFilename )) {
					 $errorMessages[]              = sprintf("La fiche %s n'a pas pu être produite ", $documentName );
				 } else {
					$embedPDF                      = base64_encode($embedPDF);
					$filename                      = $modelDocument->rename($documentTitle, $me->userid);
					$documentData                  = array("userid"=>$me->userid,"category"=>$documentCategory,"filename"=>$filename,"filepath"=>$demandeFilename ,"filextension"=>"pdf","filesize"=>filesize($attestationFilename),"resourceid"=>50,"resource"=>"demandes","filedescription"=>$demande->libelle,"filemetadata"=>sprintf("%s,%s,%d,demande", $demande->numero,$demande->objet,$demandeid));
					$documentData["creatoruserid"] = $me->userid;
					$documentData["creationdate"]  = time();
					if( $dbAdapter->insert( $prefixName."system_users_documents", $documentData)) {
						$documentid                = $dbAdapter->lastInsertId();
						$document                  = $modelDocument->findRow($documentid, "documentid", null, false ); 
						$demandeDocumentRow        = array("documentid"=>$documentid,"demandeid"=>$demandeid,"demandeurid"=>$demandeurid,"contenu"=>$contenu,"libelle"=>$documentTitle,"document_type"=>strtolower($documentType),"creationdate"=>time(),"creatorid"=>$me->userid,"updatedate"=>0,"updateduserid"=>0);
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
						 $filePath   = "http://".APPLICATION_HOST. BASE_PATH. "myV1/documents/privatedata/sirahbf2546155aoo/demandes/". sprintf("%s_Numero_%s.pdf", $documentName , preg_replace("/\s+/","_",$demande->numero));
			             $pageOutput = "<div class=\"pdfFrameWrapper\"> 
			                                <div style=\"display:block;width:100%;margin:0;padding:0;-ms-zoom:1;-moz-transform:scale(1);-moz-transform-origin:0 0;-o-transform:scale(1);-o-transform-origin:0 0;-webkit-transform:scale(1);-webkit-transform-origin: 0 0;\">
							                   <object type=\"application/pdf\" width=\"100%\" height=\"100%\" data=\"data:application/pdf;base64,".$embedPDF."\"> <embed width=\'800\' height=\"360\" src=\"data:application/pdf;base64,".$embedPDF."\"> type=\"application/pdf\" /></object>
							                </div>
							            </div>";
			             echo $pageOutput;
                         exit;
					 } elseif($documentOutput == "print") {
						 $this->_helper->viewRenderer->setNoRender(true);
						 $this->_helper->layout->disableLayout(true);
						 
						 echo ZendX_JQuery::encodeJson(array("success"=>sprintf("%s a été produite avec succès",$documentTitle),"tmpDocument"=>$demandeFilename,"documentid"=>$documentid,"demandeid"=>$demandeid,"numero"=>$demande->numero));
                         exit;
				     } else {
						 if( $this->_request->isXmlHttpRequest() ) {
							 $this->_helper->viewRenderer->setNoRender(true);
							 echo ZendX_JQuery::encodeJson(array("success"=> sprintf("La  %s  a été produite avec succès",$documentTitle) ));
							 exit;
						 }
						 $this->setRedirect(sprintf("La %s  a été produite avec succès",$documentTitle),"success");
						 $this->redirect("demandes/infos/demandeid/".$demandeid);
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
			$this->redirect("admin/demandes/list");
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
			$this->redirect("admin/demandes/list");
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
			$this->redirect("admin/demandes/verify/id/".$demandeid);
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
			$dbAdapter->insert($prefixName."reservation_demandes_verifications",$verificationData);
		}
		$dbAdapter->update($prefixName."reservation_demandes_requests", array("processed"=>1,"validated"=>1,"updatedate"=>time(),"updateduserid"=>$me->userid), array("demandeid=?"=>$demandeid) );
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
			$this->redirect("admin/demandes/list");
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
			$this->redirect("admin/demandes/list");
		}
		$errorMessages                    = array();
		$defaultData                      = array("statutid" => 3);
		if( $this->_request->isPost()) {
			$modelTable                   = $model->getTable();
			$dbAdapter                    = $modelTable->getAdapter();
			$prefixName                   = $modelTable->info("namePrefix");
			$tableName                    = $modelTable->info("name");
			$verificationStateStore       = new Zend_Session_Namespace("Statestore");			
			$postData                     = $this->_request->getPost();
			$statutId                     = (isset($postData["statutid"]))? intval($postData["statutid"])  : 3;
			$motifRejet                   = (isset($postData["motif"]   ))? strip_tags($postData["motif"]) : " ";
			$motifs                       = (isset($postData["motifs"]  ))? $postData["motifs"]            : array();
			if( count( $motifs )) {
				foreach( $motifs as $motifValue ) {
					     $motifRejet     .= $motifValue.";";
				}
			}
			if(!isset($verificationStateStore->verificationSource )) {
				$verificationStateStore->verificationSource = array();
			}
			if(!isset($verificationStateStore->verificationstate)) {
				$verificationStateStore->verificationstate  = array();
			}
			$verification                                   = $demande->verification($demandeid);
			$verificationSources                            = "";
			if( count(   $verificationStateStore->verificationSource)) {
				foreach( $verificationStateStore->verificationSource as $sourceid => $source ) {
						 $verificationSources               = $verificationSources." - ".$source["libelle"];
						 $sourceCode                        = $source["code"];
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
				$verificationData["disponible"]         = 0;
				$verificationData["sources"]            = $verificationSources;
				$verificationData["taux_disponibilite"] = (isset($verificationStateStore->verificationstate[$demandeid]["successed"]))?$verificationStateStore->verificationstate[$demandeid]["successed"] : 0;
				$verificationData["updatedate"]         = time();
				$verificationData["updateduserid"]      = $me->userid;
				
				$dbAdapter->update( $prefixName."reservation_demandes_verifications",$verificationData,array("verificationid=?"=>$demandeid));
			} else {
				$verificationData                       = array("verificationid"=>$demandeid,"updateduserid"=>0,"updatedate"=>0);
				$verificationData["demandeurid"]        = $demande->demandeurid;
				$verificationData["disponible"]         = 0;
				$verificationData["sources"]            = $verificationSources;
				$verificationData["taux_disponibilite"] = (isset($verificationStateStore->verificationstate[$demandeid]["successed"]))?$verificationStateStore->verificationstate[$demandeid]["successed"] : 0;
				$verificationData["creationdate"]       = time();
				$verificationData["creatorid"]          = $me->userid;
				$dbAdapter->delete($prefixName."reservation_demandes_verifications",array("verificationid=?"=>$demandeid));
				$dbAdapter->insert($prefixName."reservation_demandes_verifications",$verificationSourceData);
			}
			if( empty( $errorMessages )) {
				$demande->statutid                      = $statutId;
				$demande->motif_rejet                   = $motifRejet;
				$demande->rejected                      = ($statutId==3)? 0 : 1;
				$demande->updateduserid                 = $me->userid;
				$demande->updatedate                    = time();
				
				if( $demande->save() ) {
					$dbAdapter->update($prefixName."reservation_demandes_requests", array("processed"=>1,"validated"=>0,"updatedate"=>time(),"updateduserid"=>$me->userid), array("demandeid=?"=>$demandeid) );
					if( $statutId == 3 ) {
						$documentType                   = "indisponibilite";
					} else {
						$documentType                   = "rejet";
					}
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("success" =>  "Les informations du rejet du nom commercial ".$demande->objet." ont été mises à jour avec succès"));
						exit;
					}
					$this->setRedirect("Les informations du rejet du nom commercial ".$demande->objet." ont été mises à jour avec succès", "success");
					$this->redirect("admin/demandes/get/demandeid/".$demandeid."/type/".$documentType);
				} else {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" =>  "Les informations du rejet du nom commercial ".$demande->objet."  n'ont pas été mises à jour"));
						exit;
					}
					$this->setRedirect("Les informations du rejet du nom commercial ".$demande->objet." n'ont pas été mises à jour", "error");
					$this->redirect("admin/demandes/infos/demandeid/".$demandeid );
				}
			}
			if( count( $errorMessages )) {
				$defaultData                            = array_merge($defaultData, $postData );
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
		$this->view->demande                            = $demande;
		$this->view->demandeid                          = $demandeid;
		$this->view->data                               = $defaultData;
		$this->view->title                              = "Préciser les causes du rejet du nom commercial ".$demande->objet;
		$this->render("rejet");
	}
}
<?php

class ReservationsController extends Sirah_Controller_Default
{
	public function preDispatch()
	{
		$me                   = Sirah_Fabric::getUser();
		$actionName           = $this->getRequest()->getParam('action');
		if(!$me->userid ) {
			$registreid       = intval($this->_getParam("registreid", $this->_getParam("id", 0)));
			$this->redirect("public/members/login/registreid/".$registreid);
		}
	}	

	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			 $this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
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
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))    ? intval($params["page"])     : $stateStore->filters["_demandes"]["page"];
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_demandes"]["maxitems"];		
		$searchQ                  = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                  = $stateStore->filters["_demandes"];
        $params                   = array_merge($stateStore->filters["_demandes"], $params);			
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  = $stringFilter->filter($filterValue);
			}
		}	
 			
		$myLocaliteId                      = $me->localiteid;
		$filters["creatorid"]              = $me->userid;
		$stateStore->filters["_demandes"]  = $filters;
		$demandes                          = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                         = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns               = array("left");
		$this->view->demandes              = $demandes;
		$this->view->filters               = $filters;
		$this->view->params                = $params;
		$this->view->paginator             = $paginator;
		$this->view->pageNum               = $pageNum;
		$this->view->pageSize              = $pageSize;		
	}
	
	 
		
	public function infosAction()
	{
		$me                           = Sirah_Fabric::getUser();
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$demandeid                    = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/reservations/list");
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
			$this->redirect("public/reservations/list");
		}
 		if($demande->creatorid!= $me->userid) {
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
	
	public function getAction()
	{
		$me                            = Sirah_Fabric::getUser();
		$demandeid                     = intval(    $this->_getParam("demandeid", $this->_getParam("id" ,0)));
        $documentType                  = strip_tags($this->_getParam("type"     , "default"));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/reservations/list");
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
			$this->redirect("public/reservations/list");
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
	
		
	 
}
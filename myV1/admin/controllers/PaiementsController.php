<?php

class Admin_PaiementsController extends Sirah_Controller_Default
{
		
	public function listAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}		
		$this->view->title                 = "Gestion et suivi des paiements des commandes";
		
		$model                             = $this->getModel("commandepaiement");
		$modelCommande                     = $this->getModel("commande");
		$modelMember                       = $this->getModel("member");
		$modelProduit                      = $this->getModel("product");
		$modelCategory                     = $this->getModel("productcategorie");		
		$modelDocumentype                  = $this->getModel("documentcategorie");
		$modelStatut                       = $this->getModel("paiementstatut");
		$articles                          = array();
		$paginator                         = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter                      = new Zend_Filter();
		$stringFilter->addFilter(            new Zend_Filter_StringTrim());
		$stringFilter->addFilter(            new Zend_Filter_StripTags());		
		//On crée un validateur de filtre
		$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));		
		$params                            = $this->_request->getParams();
		$pageNum                           = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize                          = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                           = $stringFilter->filter($this->_getParam("generalfilter" , $this->_getParam("searchq" , null)));
		$filters                           = array("searchQ"=>$searchQ,"commandeid"=>0,"numcommande"=>null,"lastname"=>null,"firstname"=>null,"memberid"=>null,"modepaiement"=>0,"statutid"=>0,
		                                           "date_day"=>0,"date_month"=>0,"date_year"=>0,"date"=>null,"productid"=>null,"documentid"=>null,"registreid"=>null,"productcatid"=>null,
											       "periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate            = new Zend_Date(array("year" => $filters["date_year"] ,"month"=> $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]     = ($zendDate)? $zendDate->get("Y-MM-dd") : "";			   
		}
		if( Zend_Date::isDate($filters["date"], "dd/MM/YYYY")) {
			$zendDate                       = new Zend_Date($filters["date"], Zend_Date::DATES , "fr_FR");
			$filters["date"]                = ($zendDate)? $zendDate->get("Y-MM-dd") : "";	
		}
		if(!empty( $filters["date"] ) && Zend_Date::isDate($filters["date"], "Y-MM-dd")) {
			$filters["periodstart"]         = 0;
			$filters["periodend"]           = 0;
			$filters["periodend_day"]       = $filters["periodend_month"] = $filters["periodstart_day"] = $filters["periodstart_month"] = "00";
			$filters["periodend_year"]      = $filters["periodstart_year"]= 0;
		}
		if((isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_month"]))
				&&
		   (isset($filters["periodend_day"]  ) && intval($filters["periodend_day"]))   && (isset($filters["periodstart_day"])   && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=> $filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=> $filters["periodend_year"]  ,"month"=>$filters["periodend_month"]  ,"day"=> $filters["periodend_day"]   ));
			$filters["periodstart"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periodend"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}
		$paiements                  = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                  = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns        = array("left");
		$this->view->paiements      = $paiements;	
		$this->view->statuts        = $modelStatut->getSelectListe(     "Selectionnez un statut"          , array("statutid"  , "libelle"), array(), 0, null , false);
		$this->view->commandes      = $modelCommande->getSelectListe(   "Selectionnez une commande"       , array("commandeid", "ref"), array(), 0, null , false);
		$this->view->documentypes   = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id","libelle") , array() , null, null , false );
		$this->view->categories     = $this->view->productcategories = $modelCategory->getSelectListe("Selectionnez une catégorie", array("catid","libelle"), array() , null , null , false );	
		$this->view->filters        = $filters;
		$this->view->paginator      = $paginator;						
	}
	
	public function createAction()
	{
		$this->view->title          = " Enregistrer le paiement d'une commande d'achat";
		
		$model                      = $this->getModel("commandepaiement");
		$modelCommande              = $this->getModel("commande");
		$modelMember                = $this->getModel("member");
		$modelProduit               = $this->getModel("product");
		
		$commandeid                 = intval($this->_getParam("commandeid"  , $this->_getParam("commande", 0)));
		$modePaiement               = intval($this->_getParam("modepaiement", $this->_getParam("mode"    , 0)));
		
		$defaultData                = $model->getEmptyData();
		
		$defaultData["date"]        = date("Y-m-d");
		$defaultData["date_day"]    = date("d"); 
		$defaultData["date_month"]  = date("m"); 
		$defaultData["date_year"]   = date("Y"); 
		 
	}
	
	
	public function editAction()
	{
		
	}
	
	public function infosAction()
	{		
		$model                      = $this->getModel("commandepaiement");
		$modelCommande              = $this->getModel("commande");
		$modelMember                = $this->getModel("member");
		$modelProduit               = $this->getModel("product");				
		
		$paiementid                 = intval( $this->_getParam("paiementid", $this->_getParam("id", 0 )));
		$paiement                   = $model->findRow($paiementid,"paiementid", null, false );
		$commandeid                 = ($paiement  )? $paiement->commandeid : 0;
		$commande                   = ($commandeid)? $modelCommande->findRow($commandeid, "commandeid", null, false ) : null;
		if(!$paiement || !$commande) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de ce paiement. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de ce paiement. Paramètres invalides", "error");
			$this->redirect("admin/paiements/list");
		}
 
		$this->view->paiement       = $paiement;
		$this->view->paiementid     = $paiementid;
		$this->view->commande       = $commande;
		$this->view->commandeid     = $commandeid;
		$this->view->memberid       = $memberid                = $paiement->memberid;
		$this->view->member         = $this->view->client      = $modelMember->findRow($memberid,"memberid",null,false);
		$this->view->paiementid     = $paiementid;
	    $this->view->mode           = $paiement->findParentRow("Table_Modepaiements");
		$this->view->transaction    = $this->view->webpaiement = $paiement->webpaiement();
 
        
		$this->view->title          = sprintf("Les informations du paiement %s", $paiement->numero);
	}
	
	
	public function getAction()
	{		
	    $this->_helper->layout->disableLayout(true);
		$model                      = $this->getModel("commandepaiement");
		$modelCommande              = $this->getModel("commande");
		$modelLivraison             = $this->getModel("commandelivraison");
		$modelMember                = $this->getModel("member");				
		
		$paiementid                 = intval( $this->_getParam("paiementid", $this->_getParam("id", 0 )));
		$paiement                   = $model->findRow($paiementid, "paiementid", null, false );
		$commandeid                 = ($paiement  )? $paiement->commandeid : 0;
		$commande                   = ($commandeid)? $modelCommande->findRow($commandeid, "commandeid", null, false ) : null;
		if(!$paiement || !$commande) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de ce paiement. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de ce paiement. Paramètres invalides", "error");
			$this->redirect("admin/paiements/list");
		}
 
		$this->view->paiement       = $paiement;
		$this->view->paiementid     = $paiementid;
		$this->view->commande       = $commande;
		$this->view->commandeid     = $commandeid;
		$this->view->memberid       = $memberid                = $paiement->memberid;
		$this->view->member         = $this->view->client      = $modelMember->findRow($memberid,"memberid",null,false);
		$this->view->paiementid     = $paiementid;
	    $this->view->mode           = $paiement->findParentRow("Table_Modepaiements");
		$this->view->transaction    = $this->view->webpaiement = $paiement->webpaiement();
		$this->view->virement       = $this->view->cheque      = null;
	 
		$this->render("document");
	}
	
	 
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model            = $this->getModel("commandepaiement");
		$modelCommande    = $this->getModel("commande");
		$ids              = $this->_getParam("paiementids", $this->_getParam("ids" , array()));
		$errorMessages    = array();
		if( is_string($ids)) {
			$ids  = explode("," ,$ids);
		}
		$ids      = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $id) {
					 $paiementid               = intval($id);
					 $paiement                 = $model->findRow($paiementid, "paiementid" , null , false );
					 if($paiement ) {
						$commandeid            = $paiement->commandeid;
						$commande              = ( $commandeid)? $modelCommande->findRow($commandeid,"commandeid", null, false ) : null;
						$totalAPayer           = ( $commande  )? $commande->valeur_ttc : 0;
						if(!$paiement->delete()) {
							$errorMessages[]   = " Erreur de la base de donnée le paiement id#$id n'a pas été supprimé ";
						} else {												
							$commandePaiements = $modelCommande->paiements($commandeid);
							if( count($commandePaiements) && $totalAPayer && $commande ) {
								$totalPaiement = $totalPaid = 0;
								foreach( $commandePaiements as $paiement ) {
										 $totalPaiement+= $paiement["montant"];
										 $totalPaid    += $paiement["totalPaid"];
										 $reste         = floatval($totalAPayer- $totalPaiement );
										 $u_paiementid  = $paiement["paiementid"];
										 $dbAdapter->update($prefixName."erccm_vente_commandes_paiements", array("totalAPayer"=>$totalAPayer,"totalPaid"=>$totalPaiement,"reste"=>$reste),array("paiementid=?"=>$u_paiementid));										
								}
                                if( $commande ) {
									$commande->totalPaid     = $totalPaid;
									$commande->updateduserid = $me->userid;
									$commande->updatedate    = time();
									$commande->save();
								}									
							}
						}
					} else {
							$errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le paiement id #$id ";
					}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage ) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/paiements/list");
		} else {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("success"  => "Les paiements selectionnés ont  été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les paiements selectionnés ont  été supprimés avec succès" , "success");
			$this->redirect("admin/paiements/list");
		}
	}
	
	
	
	public function uploadAction()
	{
		$paiementid           = intval($this->_getParam("paiementid" , $this->_getParam("id" , 0 )));
		$model                = $this->getModel("commandepaiement");
		if(!$paiementid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/paiements/list");
		}
		$paiement            = $model->findRow( $paiementid , "paiementid" , null , false );
		if(!$paiement) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/paiements/list");
		}
		$me                                  = Sirah_Fabric::getUser();
		$modelCommande                       = $this->getModel("commande");
		$modelDocument                       = $this->getModel("document");
		$modelCategory                       = $this->getModel("documentcategorie");

		$defaultData                         = $modelDocument->getEmptyData();
		$fileDataPath                        = APPLICATION_DATA_PATH . DS . "paiements" . DS . "documents"   ;
		$errorMessages                       = array();
		$uploadedFiles                       = array();
		$categories                          = $modelCategory->getSelectListe("Selectionnez une catégorie", array("id", "libelle") );
		$commandeid                          = $paiement->commandeid;
		$livraisonid                         = $paiement->livraisonid;
 
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$formData                        = array_intersect_key( $postData ,  $defaultData )	;
			$documentData                    = array_merge( $defaultData ,  $formData );
			$paiementDocument                = array("paiementid"=>$paiementid,"commandeid" =>$commandeid,"updatedate"=>0,"updateduserid"=>0);
			$commandeDocument                = array("commandeid"=>$commandeid,"livraisonid"=>$livraisonid,"creatorid"=>$me->userid);
			$modelTable                      = $model->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
			if( !is_dir( $fileDataPath ) ) {
				$errorMessages[]             = "Le dossier de stockage des documents de l'article n'est pas créé, veuillez informer l'administrateur ";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(          new Zend_Filter_StringTrim());
			$stringFilter->addFilter(          new Zend_Filter_StripTags());
			//On crée un validateur de filtre
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
			$documentData["userid"]          = $me->userid;
			$documentData["category"]        = intval( $documentData["catid"] );
			$documentData["resource"]        =  "paiements" ;
			$documentData["resourceid"]      = 0;
			$documentData["filedescription"] = $stringFilter->filter($documentData["filedescription"] );
			$documentData["filemetadata"]    = (isset($postData["filemetadata"]))?$stringFilter->filter($documentData["filemetadata"]) : "";
	
			$userMaxFileSize                 = 32;
			$userMaxUploadFileSize           = 100;
			$userSingleFileSize              = 100;
			$userTotalFiles                  = 10;
	
			$documentsUpload                 = new Zend_File_Transfer();
			$documentsUpload->addValidator("Count"    , false , 1 );
			$documentsUpload->addValidator("Extension", false , array("csv","xls","xlxs", "pdf","png","gif","jpg","docx", "doc" , "xml"));
			$documentsUpload->addValidator("Size"     , false , array("max"  => "20MB"));
			$documentsUpload->addValidator("FilesSize", false , array("max"  => "20MB"));
	
			$basicFilename                  = $documentsUpload->getFileName('paiementfiles' , false );
			$documentExtension              = Sirah_Filesystem::getFilextension( $basicFilename );
			$tmpFilename                    = Sirah_Filesystem::getName( $basicFilename);
			$filePath                       = $fileDataPath . DS . time(). "_".sprintf("docpaiement%04d", $paiementid ) . "." . $documentExtension;
				
			$documentsUpload->addFilter("Rename", array("target" => $filePath, "overwrite" => true), "paiementfiles");
			//On upload les fichiers du dossier d'paiement
			if( $documentsUpload->isUploaded("paiementfiles")){
				$documentsUpload->receive(   "paiementfiles");
			} else {
				$errorMessages[]                    = " Le document que vous avez chargé n'est pas valide";
			}
			if( $documentsUpload->isReceived("paiementfiles") ) {
				$fileSize                           = $documentsUpload->getFileSize("paiementfiles");
				$myFilename                         = (isset($postData["filename"]) && $strNotEmptyValidator->isValid($postData["filename"])) ? $stringFilter->filter( $postData["filename"] ) : $tmpFilename;
				$documentData["filename"]           = $paiementDocument["libelle"]      = $commandeDocument["libelle"]      = $modelDocument->rename( $myFilename , $me->userid );
				$documentData["filepath"]           = $filePath;
				$documentData["filextension"]       = $documentExtension;
				$documentData["filesize"]           = floatval($fileSize);
				$documentData["creationdate"]       = $paiementDocument["creationdate"] = $commandeDocument["creationdate"] = time();
				$documentData["creatoruserid"]      = $paiementDocument["creatorid"]    = $commandeDocument["creatorid"]    = $me->userid;
				if( $dbAdapter->insert( $prefixName ."system_users_documents", $documentData ) ) {
					$documentid                     = $dbAdapter->lastInsertId();
					$paiementDocument["documentid"] = $commandeDocument["documentid"]    = $documentid;
					if( $dbAdapter->insert( $prefixName ."gestapp_achat_paiements_documents", $paiementDocument)) {
						$dbAdapter->insert( $prefixName ."gestapp_achat_commandes_documents", $commandeDocument);
					}
					$uploadedFiles[$documentid]     = $documentData;
				} else {
					$errorMessages[]                = "Les informations du document n'ont pas été enregistrées dans la base de données";
				}
			} else {
				$errorMessages[]                    = "Le document n'a pas été chargé correctement sur le serveur";
			}
			if( empty( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonArray                      = array();
					$jsonArray["success"]           = "Le document a été enregistré avec succès";
					$jsonArray["document"]          = $documentData ;
					echo ZendX_JQuery::encodeJson( $jsonArray );
					exit;
				}
				$this->_helper->Message->addMessage("Le document a été enregistré avec succès" , "success");
				$this->redirect("admin/paiements/infos/paiementid/".$paiementid);
			} else {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
					exit;
				}
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage , "error");
				}
			}
		}
		$this->view->paiementid    = $paiementid;
		$this->view->categories    = $categories;
		$this->view->data          = $defaultData;
	}
}
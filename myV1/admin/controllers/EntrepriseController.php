<?php

class Admin_EntrepriseController extends Sirah_Controller_Default
{
 
	
	public function infosAction()
	{		
		$id                      = intval($this->_getParam("id" , $this->_getParam("entrepriseid" , ENTREPRISEID )));		
		$model                   = $this->getModel("entreprise");		
		$entreprise              = $model->findRow($id, "entrepriseid", null, false);		
		if( !$entreprise ) {
			$this->setRedirect("Aucune information n'a été retrouvée pour l'entreprise don vous souhaitez visualiser les informations");
			$this->redirect("index/index");
		}
		
		$this->view->title       = "Les informations de l'entreprise";
		$this->view->entreprise  = $entreprise;	
		$this->view->domaine     = $entreprise->findParentRow("Table_Domaines");
		$this->view->forme       = $entreprise->findParentRow("Table_Entrepriseformes");
		$this->view->projects    = $entreprise->projects();
		$this->render("infos");
	}
	
	
	public function editAction()
	{
		$this->view->title       = "Mettre à jour les informations de l'entreprise";
		
		$id                      = intval($this->_getParam("id" , $this->_getParam("entrepriseid" , ENTREPRISEID)));		
		$model                   = $this->getModel("entreprise");
		$modelEntreprisegroup    = $this->getModel("entreprisegroup");
		$modelEntrepriseforme    = $this->getModel("entrepriseforme");
		$modelDomaine            = $this->getModel("domaine");
		$modelCity               = $this->getModel("countrycity");
		$entreprise              = $entrepriseRow = $model->findRow($id , "entrepriseid" , null , false );
		if(!$entreprise ) {
			$pagekey             = $stringFilter->filter( $this->_getParam("pagekey", $this->_getParam("key", "" ) ) );
			$entreprise          = $model->findRow( $pagekey, "pagekey", null , false );
			if( !$entreprise) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender( true );
					$this->_helper->layout->disableLayout( true );
					echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour cette requete sont invalides"));
					exit;
				}
				$this->setRedirect("Les paramètres fournis pour cette requete sont invalides", "error");
				$this->redirect("admin/entreprise/infos");
			}
		}
		$defaultData             = $entrepriseRow->toArray();
		$defaultData["datecreation_day"]  = date("d", $entrepriseRow->datecreation);
		$defaultData["datecreation_month"]= date("n", $entrepriseRow->datecreation);
		$defaultData["datecreation_year"] = date("Y", $entrepriseRow->datecreation);
		$errorMessages           = array();
		if($this->_request->isPost()) {
			$postData            = $this->_request->getPost();	
			$updated_data        = array();
			$me                  = Sirah_Fabric::getUser();
			$userTable           = $me->getTable();
			$prefixName          = $userTable->info("namePrefix");
			$dbAdapter           = $userTable->getAdapter();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter        = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			 
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator              = new Sirah_Validateur_Email();
			$floatFiltre                 = new Sirah_Filtre_Float();
			
			//On crée les validateurs nécessaires
			$updated_data["num_securite_social"]= ( isset( $postData["num_securite_social"] )) ? $stringFilter->filter( $postData["num_securite_social"] ) : $entreprise->num_securite_social;
			$updated_data["num_rc"]             = ( isset( $postData["num_rc"] )) ? $stringFilter->filter( $postData["num_rc"] ) : $entreprise->num_rc;
			$updated_data["libelle"]        = ( isset( $postData["libelle"] )) ? $stringFilter->filter( $postData["libelle"] ) : $entreprise->libelle;
			$referenceKey                   = ( isset( $postData["reference"])) ? $stringFilter->filter( $postData["reference"] ) : $entreprise->reference;
			$updated_data["presentation"]   = ( isset( $postData["presentation"] )) ? $stringFilter->filter( $postData["presentation"] ) : $entreprise->presentation;
			$updated_data["address"]        = ( isset( $postData["address"] )) ? $stringFilter->filter( $postData["address"] ) : $entreprise->address;
			$updated_data["email"]          = ( isset( $postData["email"]   )) ? $stringFilter->filter( $postData["email"] )   : $entreprise->email;
			$updated_data["phone1"]         = ( isset( $postData["phone1"]  )) ? $stringFilter->filter( $postData["phone1"]  ) : $entreprise->phone1;
			$updated_data["phone2"]         = ( isset( $postData["phone2"]  )) ? $stringFilter->filter( $postData["phone2"])   : $entreprise->phone2;
			$updated_data["siteweb"]        = ( isset( $postData["siteweb"] )) ? $stringFilter->filter( $postData["siteweb"])  : $entreprise->siteweb;
			$updated_data["groupid"]        = ( isset( $postData["groupid"] )) ? intval( $postData["groupid"])  : $entreprise->groupid;
			$updated_data["country"]        = ( isset( $postData["country"] )) ? $stringFilter->filter( $postData["country"])  : $entreprise->country;
			$updated_data["zip"]            = ( isset( $postData["zip"] ) )     ? $stringFilter->filter( $postData["zip"])      : $entreprise->zip;
			$postData["ville"]              = ( isset( $postData["ville"] ) )   ? $stringFilter->filter( $postData["ville"])    : "";
			$updated_data["city"]           = ( isset( $postData["city"] ) )    ? intval( $postData["city"] )     : $entreprise->city;
			$updated_data["formid"]         = ( isset( $postData["formid"] ) )  ? intval( $postData["formid"] )   : $entreprise->formid;
			$updated_data["domaineid"]      = ( isset( $postData["domaineid"])) ? intval( $postData["domaineid"]) : $entreprise->domaineid;
			$updated_data["region"]         = ( isset( $postData["region"] ) )  ? $stringFilter->filter( $postData["region"])   : "";
			$postData["regionlib"]          = ( isset( $postData["regionlib"] ) )         ? $stringFilter->filter( $postData["regionlib"])   : "";
			$updated_data["responsable_email"]  = ( isset( $postData["responsable_email"] ) ) ? $stringFilter->filter( $postData["responsable_email"]) : $entreprise->responsable_email;
			$updated_data["responsable"]    = ( isset( $postData["responsable"] ) )       ? $stringFilter->filter( $postData["responsable"]) : $entreprise->responsable;
			$updated_data["capital"]        = ( isset( $postData["capital"] ) )           ? $floatFiltre->filter(  $postData["capital"] ) :$entreprise->capital;
			$updated_data["nbemployes_min"] = ( isset( $postData["nbemployes_min"] ) )    ? intval( $postData["nbemployes_min"] ) : $entreprise->nbemployes_min;
			$updated_data["nbemployes_max"] = ( isset( $postData["nbemployes_max"] ) )    ? intval( $postData["nbemployes_max"] ) : $entreprise->nbemployes_max;
			$postData["datecreation_day"]   = ( isset( $postData["datecreation_day"] ) )  ? sprintf("%02d", $postData["datecreation_day"] )   : "00";
			$postData["datecreation_month"] = ( isset( $postData["datecreation_month"] ) )? sprintf("%02d", $postData["datecreation_month"] ) : "00";
			$postData["datecreation_year"]  = ( isset( $postData["datecreation_year"] ) ) ? sprintf("%04d", $postData["datecreation_year"] )  : "0000";
			
			if( !$updated_data["city"] && isset( $postData["ville"] ) ) {
				if( $strNotEmptyValidator->isValid( $postData["ville"] ) ) {
					$libelleVille  = $stringFilter->filter( $postData["ville"] );
					$rowCity       = $modelCity->findRow( $libelleVille , "libelle" , null , false);
					if( $rowCity ) {
						$updated_data["city"]     = $rowCity->id;
					} else {
						$libelleVille = $stringFilter->filter( $postData["ville"] );
						if( $dbAdapter->insert( $prefixName . "system_countries_cities", array("libelle" => $libelleVille, "creatorid" => $me->userid , "creationdate" => time() ) ) ) {
							$updated_data["city"] = $dbAdapter->lastInsertId();
						}
					}
				}
			}
			if( !$strNotEmptyValidator->isValid( $updated_data["libelle"] ) ) {
				$errorMessages[]         = "Veuillez saisir la désignation/dénomination de l'entreprise";
			} elseif( (strcasecmp($entreprise->libelle, $updated_data["libelle"]) != 0  )) {
				$pageKey                 = preg_replace('/\s+/', '-', strtolower( $updated_data["libelle"] ) );
				$countPageKeySelect      = $dbAdapter->select()->from( $prefixName . "gestoptic_clients_entreprises")->where("pagekey = ?", $pageKey );
				$countPageKey            = intval( count( $dbAdapter->fetchAll( $countPageKeySelect ) ) );
				$countVal                = $countPageKey + 1;
				$updated_data["pagekey"] = ( $countPageKey ) ? $pageKey."-".$countVal : $pageKey;
			}
			if( $strNotEmptyValidator->isValid( $updated_data["email"] ) && !$emailValidator->isValid( $updated_data["email"] ) ) {
				$errorMessages[]         = "Veuillez entrer une adresse email valide pour cette entreprise";
			} else {
				$countEmailSelect        = $dbAdapter->select()->from( $prefixName . "gestoptic_clients_entreprises")->where("email = ?", $updated_data["email"] )->where("email != ?", $entrepriseRow->email);
				$countEmail              = intval( count( $dbAdapter->fetchAll( $countEmailSelect ) ) );
				if( $countEmail ) {
						$errorMessages[] = "L'adresse email que vous avez enregistrée, est déjà rattachée à une entreprise, veuillez saisir une adresse email différente";
				}
			}
			$zendDatecreation        = new Zend_Date(array("year" => $postData["datecreation_year"] ,
						                                   "month"=> $postData["datecreation_month"],
						                                   "day"  => $postData["datecreation_day"]  ));
			$updated_data["responsableid"]= 0;
			if( $strNotEmptyValidator->isValid( $updated_data["email"] )  ) {
				$responsableRow = $userTable->find( array("email" => $postData["responsable_email"] ) );
				if( $responsableRow ) {
					$updated_data["responsableid"]  = $responsableRow["userid"];
				}
			}
			if( empty( $referenceKey)) {
				$referenceKey         = "Eo-".sprintf("%08d", Sirah_Functions_Generator::getInteger( 8 ));
				while( $model->findRow( $referenceKey , "reference" , null , false ) ) {
			    	   $referenceKey  = "Eo-".sprintf("%08d", Sirah_Functions_Generator::getInteger( 8 ));
			    }
			}
			$updated_data["datecreation"] = $zendDatecreation->getTimestamp();
			$updated_data["presentation"] = substr( $updated_data["presentation"] , 0 , 2000 );
			$updated_data["reference"]    = $referenceKey;
			$updated_data["updateduserid"]= $me->userid;
			$updated_data["updatedate"]   = time();
			    
			if(empty($errorMessages)) {
				//On stocke le logo de l'entreprise
					$logoImgPath       = APPLICATION_DATA_PATH . DS . "entreprises" . DS ."logos";
					$originalFilename  = "";
					$photoUpload       = new Zend_File_Transfer();
					$photoUpload->addValidator('Count' , false , 2 );
					$photoUpload->addValidator("Extension", false, array("png","jpg","jpeg", "gif","bmp","PNG","JPEG","JPG"));
					$photoUpload->addValidator("FilesSize", false, array("max" => "5MB"));
					if( $photoUpload->isUploaded("logo") ) {
						$photoExtension     = Sirah_Filesystem::getFilextension( $photoUpload->getFileName('logo') );
						$originalFilename   = $logoImgPath . DS . "original" . DS . $entreprise->reference ."Img.".$photoExtension;
						$photoUpload->addFilter("Rename", array("target" => $originalFilename , "overwrite" => true) , "logo");
						$photoUpload->receive("logo") ;
					}
					if( $photoUpload->isReceived("logo") ) {
						//On redimensionne  la photo en faisant des copies dans le  dossier thumb
						$photoImage  = Sirah_Filesystem_File::fabric("Image" , $originalFilename , "rb+");
						$photoImage->resize("90"  , null , true , $logoImgPath . DS . "thumbs" );
						$updated_data["logo"]  =  $originalFilename;
					} else {
						$updated_data["logo"]  = $entreprise->logo;
					}
				$entreprise->setFromArray( $updated_data );
			    if(!$entreprise->save()) {
			    	if( $this->_request->isXmlHttpRequest()) {
			    	    $this->_helper->viewRenderer->setNoRender(true);
			    		$this->_helper->layout->disableLayout(true);
			    		echo ZendX_JQuery::encodeJson(array("error"  => "Aucune mise à jour réelle n'a été appliquée sur les informations de l'entreprise"));
			    		exit;
			    	}
			    	$this->setRedirect("Aucune mise à jour réelle n'a été appliquée sur les informations de l'entreprise" , "message");
			    	$this->redirect("admin/entreprise/infos");
			    } else {
			    	if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender( true );
							$this->_helper->layout->disableLayout( true );
							$this->getResponse()->setHeader("Content-type", "application/json");
							$jsonReturnArray                 = $updated_data;
							$jsonReturnArray["entreprise"]   = $entreprise->libelle;
							$jsonReturnArray["entrepriseid"] = $entrepriseid;
							$jsonReturnArray["ville"]        = $postData["ville"];
							$jsonReturnArray["success"]      = "Les informations de votre société ont été mises à jour avec succès";
							$jsonReturnArray["error"]        = "";
							echo ZendX_JQuery::encodeJson( $jsonReturnArray );
							exit;
					}
					$this->setRedirect("Les informations de votre société ont été mises à jour avec succès", "success");
					$this->redirect("admin/entreprise/infos" );
			    }			    	
			} else {
			   $defaultData   = $postData;
			   if( $this->_request->isXmlHttpRequest()) {
			       $this->_helper->viewRenderer->setNoRender(true);
			       $this->_helper->layout->disableLayout(true);
			       echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages)));
			       exit;
			    }
			    foreach($errorMessages as $errorMessage) {
			    		$this->_helper->Message->addMessage($errorMessage , "error");
			    }
			}
		}		
		$this->view->entreprise       = $entreprise;		
		$this->view->data             = $defaultData;
		$this->view->groupes          = $modelEntreprisegroup->getSelectListe("Selectionnez un groupe", array("groupid", "libelle"),array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines         = $modelDomaine->getSelectListe("Selectionnez un domaine", array("domaineid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->formes           = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->entrepriseid     = $id;
	}
		
}
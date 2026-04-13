<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client as GuzzleHttpClient;

class Admin_CountriesController extends Sirah_Controller_Default
{
	
	public function updatecallingcodesAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		
		$modelCountry        = $this->getModel("country");
		$modelTable          = $modelCountry->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$prefixName          = $modelTable->info("namePrefix");
		$countries           = $modelCountry->getList();	
        $countryApiEndPoint  = "https://restcountries.eu/rest/v2/"; 		
		$restCountriesClient = new GuzzleHttpClient(["base_uri"=>$countryApiEndPoint]);
		$countriesData       = $errorMessages = array();
			
		if( count(   $countries ) )  {
			foreach( $countries  as $country ) {
				     $countryCode = $country["code"];					 
					 if(!empty($countryCode)  && ( count($countryCode) <=3 ) ) {
						try {
							 $restCountryResponse = $restCountriesClient->get("alpha/".$countryCode)->getBody();
							 $updatedCountryData  = $country;
							 if(!empty($restCountryResponse) && $updatedCountryData ) {
								 $decodedDataRest                     = json_decode($restCountryResponse);
 								 
								 $updatedCountryData["code_iso"]      = $decodedDataRest->alpha3Code;
								 $updatedCountryData["capital"]       = $decodedDataRest->capital;
								 $updatedCountryData["flag"]          = $decodedDataRest->flag;
								 $updatedCountryData["code_calling"]  = $decodedDataRest->callingCodes[0];
								 $updatedCountryData["code_language"] = $decodedDataRest->languages[0]->iso639_1;
								 $updatedCountryData["code_currency"] = $decodedDataRest->currencies[0]->code;	   
								 $updatedCountryData["updatedate"]    = time(); 
								 $updatedCountryData["updateduserid"] = 1; 		
                                 							 
								 if( $dbAdapter->update( $prefixName. "envoitout_localites_countries", $updatedCountryData, array("code=?" => $countryCode ))) {
									 $countriesData[$countryCode]     = $updatedCountryData;
								 } else {
									 $errorMessages[]                 = sprintf("Le pays %s n'a pas été mis à jour", $countryCode );
								 }								 
							 }
						} catch( Exception $e ) {
							         $errorMessages[]                 = sprintf("Le pays %s n'a pas été mis à jour : %s, Erreur : %s ", $countryCode , $e->getMessage() );
						}						 
					 }
			}
		}		
		if( count(   $errorMessages ) ) {
			foreach( $errorMessages as $errorMessage ) {
				     if( $this->_request->isXmlHttpRequest() ) {						
						 $this->_helper->viewRenderer->setNoRender(true);
						 $this->_helper->layout->disableLayout(true);
						 echo ZendX_JQuery::encodeJson(array("error" => "Erreur produite : ".$errorMessage ));
						 exit;
					 }
					 $this->setRedirect("Erreur produite : ".$errorMessage, "error");					 
			}			
		} else {
			         $this->setRedirect(sprintf("Les informations de %d pays ont été enregistrées", count($countriesData)), "success");
		}
		$this->redirect("admin/countries/list")	;
		
	}
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Gestion de la liste des  pays"  ;
        $modelCountry       = $model = $this->getModel("country");
	
		$countries          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;		
		
		$filters            = array("libelle"=>null);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$countries            = $model->getList( $filters , $pageNum , $pageSize);
		$paginator            = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->countries = $countries;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;	
	}	
		
	public function createAction()
	{
		$this->view->title                        = "Enregistrer un nouveau pays";
		
		$model                                    = $this->getModel("country");	
		$defaultData                              = $model->getEmptyData();
		$errorMessages                            = array();
		
		if( $this->_request->isPost() ) {
			$postData                             = $this->_request->getPost();
			$formData                             = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data                          = array_merge( $model->getEmptyData(), $formData);
			$me                                   = Sirah_Fabric::getUser();
			$userTable                            = $me->getTable();
			$dbAdapter                            = $userTable->getAdapter();
			$prefixName                           = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator                  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$libelle                               = (isset( $postData["libelle"] ))? $stringFilter->filter($postData["libelle"]) : "";
			$insert_data["code"]                   = (isset( $postData["code"]    ))? $stringFilter->filter($postData["code"])    : "AF";
			$insert_data["code_iso"]               = (isset( $postData["code_iso"]))? $stringFilter->filter($postData["code_iso"]): "BF";
			$insert_data["libelle"]                = (isset( $postData["libelle"] ))? $stringFilter->filter($postData["libelle"]) : "";
            if(!$strNotEmptyValidator->isValid($insert_data["code"])) {
				$errorMessages[]                   = " Veuillez entrer une désignation valide pour ce pays";
			} elseif( $model->findRow($insert_data["code"], "code" , null , false )) {
				$errorMessages[]                   = sprintf("Un pays existant porte la même désignation %s , veuillez entrer une désignation différente " ,$insert_data["code"]);
		    }  
            if(!$strNotEmptyValidator->isValid($insert_data["code_iso"])) {
				$errorMessages[]                   = " Veuillez entrer une désignation valide pour ce pays";
			} elseif( $model->findRow($insert_data["code_iso"], "code_iso" , null , false )) {
				$errorMessages[]                   = sprintf("Un pays existant porte la même désignation %s , veuillez entrer une désignation différente " ,$insert_data["code_iso"]);
		    }			
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                   = " Veuillez entrer une désignation valide pour ce pays";
			} elseif( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]                   = sprintf("Un pays existant porte la même désignation %s , veuillez entrer une désignation différente " , $libelle );
		    } else {
				$insert_data["libelle"]            = $libelle;
			}			 
			$insert_data["description"]            = $stringFilter->filter( $insert_data["description"] );
			$insert_data["creatorid"]              = $me->userid;
			$insert_data["creationdate"]           = time();
            $insert_data["updateduserid"]          = 0;
			$insert_data["updatedate"]             = 0;			
            				
			if(empty($errorMessages)) {
				 $emptyData                        = $model->getEmptyData();
				 $clean_insert_data                = array_intersect_key( $insert_data, $emptyData);
				if( $dbAdapter->insert( $prefixName . "envoitout_localites_countries", $clean_insert_data) ) {
					$id                            = $dbAdapter->lastInsertId();											                    					
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "Le pays a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Le pays a été enregistré avec succès", "success" );
					$this->redirect("admin/countries/infos/id/" . $id );					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement du pays a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du pays a echoué" , "error");
					$this->redirect("admin/countries/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
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
		$this->view->data      = $defaultData;
	}
	
	
	public function editAction()
	{
		$this->view->title     = " Mettre à jour les informations d'un pays";
		
		$id                    = intval($this->_getParam("id", $this->_getParam("id" , 0)));
		
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/countries/list");
		}		
		$model                = $this->getModel("country");
		$country               = $model->findRow( $id , "id" , null , false);		
		if(!$country) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/countries/list");
		}		
		$defaultData         = $country->toArray();
		$errorMessages       = array();  
		
		if( $this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = $userTable->getAdapter();
			$prefixName      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator                  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));

			$libelle                               = (isset( $postData["libelle"] ))? $stringFilter->filter($postData["libelle"]) : "";
			$update_data["code"]                   = (isset( $postData["code"]    ))? $stringFilter->filter($postData["code"])    : "AF";
			$update_data["code_iso"]               = (isset( $postData["code_iso"]))? $stringFilter->filter($postData["code_iso"]): "BF";
			$update_data["libelle"]                = (isset( $postData["libelle"] ))? $stringFilter->filter($postData["libelle"]) : "";
            if(!$strNotEmptyValidator->isValid($update_data["code"])) {
				$errorMessages[]                   = " Veuillez entrer un code valide pour ce pays";
			} elseif( $model->findRow($update_data["code"], "code" , null , false ) && ($update_data["code"]!=$country->code)) {
				$errorMessages[]                   = sprintf("Un pays existant porte le même code %s , veuillez entrer une désignation différente " ,$update_data["code"]);
		    }  
            if(!$strNotEmptyValidator->isValid($update_data["code_iso"]) ) {
				$errorMessages[]                   = " Veuillez entrer un code ISO valide pour ce pays";
			} elseif( $model->findRow($update_data["code_iso"], "code_iso" , null , false )  && ($update_data["code_iso"]!=$country->code_iso)) {
				$errorMessages[]                   = sprintf("Un pays existant porte le même code ISO %s , veuillez entrer une désignation différente " ,$update_data["code_iso"]);
		    }			
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                   = " Veuillez entrer une désignation valide pour ce pays";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ($update_data["libelle"]!=$country->libelle)) {
				$errorMessages[]                   = sprintf("Un pays existant porte la même désignation %s , veuillez entrer une désignation différente " , $libelle );
		    } else {
				$update_data["libelle"]            = $libelle;
			}			 
			$update_data["description"]            = $stringFilter->filter( $update_data["description"] );			
			$update_data["updateduserid"]          = $me->userid;
			$update_data["updatedate"]             = time();	
			$country->setFromArray( $update_data );				
			if(empty($errorMessages)) {
				if( $country->save()) {		
					 					
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations du pays ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations du pays ont été mises à jour avec succès", "success" );
					$this->redirect("admin/countries/infos/id/".$id);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations du pays"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations du pays" , "message");
					$this->redirect("admin/countries/infos/id/".$id);
				}
			} else {
				$defaultData   = $update_data;
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}					
		}	
		$this->view->data        = $defaultData;
		$this->view->id          = $id;
	}	
 		
		
	public function infosAction()
	{
		$id              = $countryid = intval($this->_getParam("id", $this->_getParam("id"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/countries/list");
		}		
		$model            = $this->getModel("country");
		$country          = $model->findRow( $id , "id" , null , false);		
		if(!$country) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun pays n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucun pays n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/countries/list");
		}
		$this->view->country    = $country;
		$this->view->countryid  = $countryid;
		$this->view->title      = " Les informations du pays";
		$this->view->columns    = array("left");	
	} 		
		
	 
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$model         = $this->getModel("country");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("ids", $this->_getParam("id", $this->_getParam("ids",array())));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}
		$ids           = (array)$ids;
		if( count(  $ids)) {
			foreach($ids as $id) {
					$country       = $model->findRow( $id , "id" , null , false );
					$country_code  = $country->code_iso;
					if( $country) {
						if(!$country->delete()) {
							$errorMessages[]  = " Erreur de la base de donnée la pays id#$id n'a pas été supprimée ";
						} 						
					} else {
							$errorMessages[]  = "Aucune entrée valide n'a été trouvée pour ce pays id #$id ";
					}
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/countries/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les pays indiqués ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les pays indiqués ont été supprimés avec succès", "success");
			$this->redirect("admin/countries/list");
		}
	}
}
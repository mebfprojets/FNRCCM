<?php


class Admin_ProductsController extends Sirah_Controller_Default
{
	
	 
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Gestion des produits et services"  ;
		
		$model              = $this->getModel("product");
		$modelCategory      = $this->getModel("productcategorie");
		$modelDocumentype   = $this->getModel("documentcategorie");
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;		
		
		$filters            = array("libelle"=>null,"catid"=>null,"documentcatid"=>null,"documentid"=>null,"registreid"=>null);		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$produits              = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator             = $model->getListPaginator($filters);
		
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns      = array("left");
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->produits     = $produits;
		$this->view->documentypes = $this->view->documentcategories = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"   , "libelle"), array(), null , null , false );
		$this->view->categories   = $modelCategory->getSelectListe("Selectionnez un type de produits", array("catid", "libelle"), array(), null , null , false );	
	}
	
	public function createAction()
	{
		$this->view->title        = " Nouveau produit ou service";
		
		$model                    = $modelProduit = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");		
		$modelDocumentype         = $this->getModel("documentcategorie");
		
		$defaultData              = $model->getEmptyData();
		$defaultParams            = array();
		$errorMessages            = array();
		
		$this->view->documentypes = $documentypes  = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"   , "libelle") , array() , null , null , false );
		$this->view->categories   = $categories    = $modelCategory->getSelectListe(   "Selectionnez une catégorie"      , array("catid", "libelle") , array() , null , null , false );	
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();
			$formData             = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data          = array_merge( $model->getEmptyData() , $formData);
			$me                   = Sirah_Fabric::getUser();
			$modelTable           = $model->getTable();
			$dbAdapter            = $modelTable->getAdapter();
			$prefixName           = $modelTable->info("namePrefix");
			$tableName            = $modelTable->info("name");
			$nextProductKey       = $model->count() + 1;
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter         =  new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$insert_data["libelle"]            = $libelle       = (isset($postData["libelle"]      ))?$stringFilter->filter($postData["libelle"])    : "";
			$insert_data["code"]               = $code          = (isset($postData["code"]         ))?$stringFilter->filter($postData["code"])    : "";
			$insert_data["documentid"]         = $documentid    = (isset($postData["documentid"]   ))?intval($postData["documentid"])    : 0;
			$insert_data["catid"]              = $catid         = (isset($postData["catid"]        ))?intval($postData["catid"])         : 0;
			$insert_data["documentcatid"]      = $documentcatid = (isset($postData["documentcatid"]))?intval($postData["documentcatid"]) : 0;
			$insert_data["registreid"]         = $registreid    = (isset($postData["registreid"]   ))?intval($postData["registreid"])    : 0;
			$insert_data["cout_ht"]            = (isset($postData["cout_ht"]    ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ht"] ))) : 0;			
		    $insert_data["cout_ttc"]           = (isset($postData["cout_ttc"]   ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ttc"]))) : 0;
			$insert_data["description"]        = (isset($postData["description"]))? $stringFilter->filter($postData["description"]) : "";
			$insert_data["params"]             = (isset($postData["params"]     ))? $stringFilter->filter($postData["params"])      : "";
			if(!$strNotEmptyValidator->isValid($insert_data["libelle"])) {
				$errorMessages[]               = " Veuillez entrer une désignation valide pour le produit";
			} elseif($model->findRow($libelle,"libelle", null , false )) {
				$errorMessages[]               = sprintf(" Un produit existant porte la même désignation %s " , $libelle );
		    } else {
				$insert_data["libelle"]        = $libelle;
			}						
			if(!$strNotEmptyValidator->isValid($insert_data["cout_ttc"]) ) {
				$insert_data["cout_ttc"]       = ( $insert_data["cout_ht"] + (( $insert_data["cout_ht"]*18) / 100) );
			} else {
				$insert_data["cout_ttc"]       = floatval($insert_data["cout_ttc"]);
			}
			if(!intval( $insert_data["catid"] ) || !isset($categories[$insert_data["catid"]])) {
				$errorMessages[]               = "Veuillez selectionner une catégorie de produits";
			} else {
				$insert_data["catid"]          = intval( $insert_data["catid"] );
				$categorieRow                  = $modelCategory->findRow($insert_data["catid"],"catid",null,false);
				if( $categorieRow ) {
					$insert_data["documentcatid"] = (isset($postData["documentcatid"]) && intval($postData["documentcatid"]))?intval($postData["documentcatid"]) : $categorieRow->documentcatid;
				    $insert_data["cout_ttc"]      = (!floatval($insert_data["cout_ttc"]))?$categorieRow->cout_ttc : floatval($insert_data["cout_ttc"]);
					$insert_data["cout_ht"]       = (!floatval($insert_data["cout_ht"] ))?$categorieRow->cout_ht  : floatval($insert_data["cout_ht"]);
				}
			}
			$insert_data["creatorid"]          = $me->userid;
			$insert_data["creationdate"]       = time();	
			$insert_data["updateduserid"]      = 0;
			$insert_data["updatedate"]         = 0;
			if(!$strNotEmptyValidator->isValid( $insert_data["code"] ) ) {
				$insert_data["code"]           = $modelProduit->createCode();
			}
			if( empty($errorMessages)) {
				if( $dbAdapter->insert( $tableName, $insert_data) ) {
					$productid                 = $dbAdapter->lastInsertId();	
					$produit                   = $modelProduit->findRow( $productid, "productid", null , false );
				 				
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"=>"Le produit a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Le produit a été enregistré avec succès", "success" );
					$this->redirect("admin/products/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement du produit a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du produit a echoué" , "error");
					$this->redirect("admin/products/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
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
		$this->view->data         = $defaultData;
				
	}
	
	
	public function editAction()
	{
		$this->view->title        = " Mettre à jour les informations d'un produit  ";
		
		$productid                = intval($this->_getParam("id" , $this->_getParam("productid" , 0)));
		
		if(!$productid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/products/list");
		}		
		$model              = $this->getModel("product");
		$modelCategory      = $this->getModel("productcategorie");
        $modelRegistre      = $this->getModel("registre");		
		$modelDocumentype   = $this->getModel("documentcategorie");
		$produit            = $model->findRow( $productid ,  "productid" , null , false);	
			
		if(!$produit) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/products/list");
		}
		$defaultData              = $produit->toArray();
		$errorMessages            = array();
		//$this->view->registres    = $registres     = $modelRegistre->getSelectListe(   "Selectionnez un registre", array("registreid", "libelle") , array() , null , null , false );
		$this->view->documentypes = $documentypes  = $modelDocumentype->getSelectListe("Selectionnez un type de document"      , array("id"   ,"libelle"), array(),null,null,false);
		$this->view->categories   = $categories    = $modelCategory->getSelectListe(   "Selectionnez une catégorie de produits", array("catid","libelle"), array(),null,null,false);	
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();
			$update_data          = array_merge( $defaultData , $postData);
			$me                   = Sirah_Fabric::getUser();
			$modelTable           = $model->getTable();
			$dbAdapter            = $modelTable->getAdapter();
			$prefixName           = $modelTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter         =  new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
	
			$update_data["libelle"]            = $libelle       = (isset($postData["libelle"]      ))?$stringFilter->filter($postData["libelle"]) : $defaultData["libelle"];
			$update_data["code"]               = $code          = (isset($postData["code"]         ))?$stringFilter->filter($postData["code"])    : $defaultData["code"];
			$update_data["documentid"]         = $documentid    = (isset($postData["documentid"]   ))?intval($postData["documentid"])    : $defaultData["documentid"];
			$update_data["catid"]              = $catid         = (isset($postData["catid"]        ))?intval($postData["catid"])         : $defaultData["catid"];
			$update_data["documentcatid"]      = $documentcatid = (isset($postData["documentcatid"]))?intval($postData["documentcatid"]) : $defaultData["documentcatid"];
			$update_data["registreid"]         = $registreid    = (isset($postData["registreid"]   ))?intval($postData["registreid"])    : $defaultData["registreid"];
			$update_data["cout_ht"]            = (isset($postData["cout_ht"]    ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ht"] ))) : $defaultData["cout_ht"];			
		    $update_data["cout_ttc"]           = (isset($postData["cout_ttc"]   ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ttc"]))) : $defaultData["cout_ttc"];	
			$update_data["description"]        = (isset($postData["description"]))? $stringFilter->filter($postData["description"]) : $defaultData["description"];
			$update_data["params"]             = (isset($postData["params"]     ))? $stringFilter->filter($postData["params"])      : $defaultData["params"];
			if(!$strNotEmptyValidator->isValid($update_data["libelle"])) {
				$errorMessages[]               = " Veuillez entrer une désignation valide pour le produit";
			} elseif($model->findRow($libelle,"libelle",null,false) && strtolower($update_data["libelle"])!= strtolower($produit->libelle)) {
				$errorMessages[]               = sprintf(" Un produit existant porte la même désignation %s " , $libelle );
		    } else {
				$update_data["libelle"]        = $libelle;
			}			
            if(!$strNotEmptyValidator->isValid($update_data["code"])) {
				$errorMessages[]               = " Veuillez entrer un code valide pour le produit";
			} elseif($model->findRow($code,"code", null , false ) && strtolower($update_data["code"])!= strtolower($produit->code)) {
				$errorMessages[]               = sprintf(" Un produit existant porte le code %s " , $code);
		    } else {
				$update_data["code"]           = $code;
			}			
			if(!floatval($update_data["cout_ttc"]) ) {
				$update_data["cout_ttc"]       = ( $update_data["cout_ht"] + (( $update_data["cout_ht"]*18) / 100) );
			} else {
				$update_data["cout_ttc"]       = floatval($update_data["cout_ttc"]);
			}
			if(!intval( $update_data["catid"] ) || !isset($categories[$update_data["catid"]])) {
				$errorMessages[]               = "Veuillez selectionner une catégorie de produits";
			} else {
				$update_data["catid"]          = intval( $update_data["catid"] );
				$categorieRow                  = $modelCategory->findRow($update_data["catid"],"catid",null,false);
				if( $categorieRow ) {
					$update_data["documentcatid"] = (isset($postData["documentcatid"]) && intval($postData["documentcatid"]))?intval($postData["documentcatid"]) : $categorieRow->documentcatid;
				    $update_data["cout_ttc"]   = (!floatval($update_data["cout_ttc"]))?$categorieRow->cout_ttc : floatval($update_data["cout_ttc"]);
					$update_data["cout_ht"]    = (!floatval($update_data["cout_ht"] ))?$categorieRow->cout_ht  : floatval($update_data["cout_ht"]);
				}
			}
			$update_data["updateduserid"]      = $me->userid;
			$update_data["updatedate"]         = time();	
			$produit->setFromArray($update_data);				
			if( empty($errorMessages)) {
				if( $produit->save()) {					
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations du produit ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations du produit ont été mises à jour avec succès", "success" );
					$this->redirect("admin/products/list");
	
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"=>"Aucune modifiation n'a été apportée sur les informations du produit "));
						exit;
					}
					$this->setRedirect(" Aucune modifiation n'a été apportée sur les informations du produit " , "message");
					$this->redirect("admin/products/list");
				}
			} else {
				$defaultData   = $update_data;
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}					
		}	
		$this->view->data         = $defaultData;			
	}
		
		
	public function infosAction()
	{
		$id              = $productid = intval($this->_getParam("id", $this->_getParam("productid", $this->_getParam("produitid", 0))));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/products/list");
		}		
		$model         = $this->getModel("product");
		$produit       = $model->findRow($id , "productid" , null , false);		
		if(!$produit) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>" Aucune entrée de produit n'a été retrouvée avec cet identifiant "));
				exit;
			}
			$this->setRedirect("Aucune entrée de produit n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/products/list");
		}
		$this->view->produit     = $this->view->product           = $produit;
		$this->view->categorie   = $produit->findParentRow("Table_Productcategories");
		$this->view->documentype = $this->view->documentcategorie = $produit->findParentRow("Table_Documentcategories");
		$this->view->registre    = $produit->findParentRow("Table_Registres");
		$this->view->document    = $produit->findParentRow("Table_Documents");
		$this->view->produitid   = $this->view->productid         = $id;
		$this->view->title       = sprintf(" Les informations du produit reference %s", $produit->code );
	} 	
	
	 
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$model         = $this->getModel("product");
		$modelTable    = $model->getTable();
		$dbAdapter     = $modelTable->getAdapter();
		$prefixName    = $tablePrefix = $modelTable->info("namePrefix");
		$ids           = $this->_getParam("productids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}
		$ids           = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $productid  ) {
				     if(!$dbAdapter->delete( $tablePrefix."erccm_vente_products", "productid=".$productid )) {
						 $errorMessages[]  = sprintf("Le produit ref#%d n'a pas pu être supprimé", $productid);
					 }	else {
						 $dbAdapter->delete( $tablePrefix."erccm_vente_commandes_ligne", "productid=".$productid );
					 }			 
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/products/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"=> "Les produits selectionnés ont  été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les produits selectionnés ont  été supprimés avec succès" , "success");
			$this->redirect("admin/products/list");
		}
	}	 	
}
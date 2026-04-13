<?php

class RegistreController extends Sirah_Controller_Default
{	
      public function listAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  		
		$this->view->title  = "ERCCM : Recherche de personnes physiques ou de personnes morales"  ;
		
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		
		$registres          = array();
		$paginator          = null;
		$me                 = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10;	
		$adavancedSearch      = (isset($params["advanced"]))? intval($params["advanced"]) : 0;	
		$searchQ              = (isset($params["q"]       ))? $stringFilter->filter($params["q"]) : null;
		$filters              = array("libelle"=>null,"numero"=>null,"domaineid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>$searchQ,"date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"type"=>0,"keywords"=>null,
		                              "periode_start_day"=>0,"periode_start_month"=>0,"periode_start_year"=>0,"periode_end_day" =>0,"periode_end_month"=>0,"periode_end_year"=>0,"advanced"=>1);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"])  : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray           = preg_split("/[\s]+/", $filters["name"]);
			if( count($nameToArray) > 2) {
				$filters["nom"]    = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["prenom"] = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["nom"]    = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["prenom"] = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]   = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate               = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]        = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if((isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_day"]))
				&&
		   (isset($filters["periode_end_day"])   && intval($filters["periode_end_day"] ))  && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$params["searchQ"]            = (isset( $filters["searchQ"] ))? $filters["searchQ"] : "";
		$filters["types"]             = array(1,2);
		$registres                    = $model->basicList( $filters , $pageNum , $pageSize);
		$paginator                    = $model->getListPaginator($filters);				
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Secteur d'activité" , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites        = $modelLocalite->getSelectListe("Localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types            = array(0 => "Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->advanced         = $adavancedSearch;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;	
        $this->view->title            = ( !empty($params["searchQ"]) ) ? sprintf("ERCCM : les résultats de la recherche '%s' ", $params["searchQ"]) : "ERCCM : Faites vos recherches";
	}
     
	
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/registres/list");
		}
		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelMorale           = $this->getModel("registremorale");
		$modelDirigeant        = $this->getModel("registredirigeant");
		$modelRepresentant     = $this->getModel("representant");
		$modelEntreprise       = $this->getModel("entreprise");		
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$renderView            = "infos";		
		if(!$registre ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/registres/list");
		}
 
		if( $registre->type == 1 ) {
			$dirigeant                 = $modelDirigeant->findRow( $registreid, "registreid", null , false )	;
			$representant              = $modelRepresentant->findRow( $dirigeant->representantid , "representantid", null , false );		
		    $registreData              = $registre->toArray();
		    $dirigeantData             = ( $dirigeant    ) ? $dirigeant->toArray() : array();
		    $representantData          = ( $representant ) ? $representant->toArray() : array();
		    $defaultData               = array_merge( $representantData, $dirigeantData, $registreData );
			$this->view->representant  = $representant;
			$renderView                = "physique";
		} elseif( $registre->type == 2 ) {
			$modelDomaine              = $this->getModel("domaine");
		    $modelLocalite             = $this->getModel("localite");
		    $modelDocument             = $this->getModel("document");
		    $modelEntrepriseforme      = $this->getModel("entrepriseforme");
		    $modelCity                 = $this->getModel("countrycity");
			$entreprise                = $modelEntreprise->findRow(  $registreid, "registreid", null , false  );
		    $dirigeants                = ( $registre ) ? $registre->dirigeants() : array();
			$registreData              = $registre->toArray();
		    $entrepriseData            = $entreprise->toArray();
		    $defaultData               = array_merge( $entrepriseData, $registreData );
			$this->view->entreprise    = $entreprise;
			$this->view->dirigeants    = $dirigeants;
			$this->view->forme         = ( $entreprise)? $entreprise->findParentRow("Table_Entrepriseformes") : null;
			$renderView                = "morale";
		}								
		$this->view->data              = $defaultData;
		$this->view->registre          = $registre;
		$this->view->numero            = $registre->numero;
		$this->view->registreid        = $registreid;
		$this->view->domaine           = $registre->findParentRow("Table_Domaines");
		$this->view->localite          = $registre->findParentRow("Table_Localites");
		$this->view->documents         = $registre->documents();
		$this->view->modifications     = $registre->modifications();
		$this->view->suretes           = $registre->suretes();
		$this->view->title             = sprintf("Les informations du registre numero %s", $registre->numero);

		$this->render( $renderView );		 
	}
	
}
<?php

class ActivitesController extends Sirah_Controller_Default
{
	
	public function indexAction()
	{
	   
		$this->view->title   = " Bienvenue sur l'interface d'administration";
		$this->_helper->viewRenderer->setNoRender( true );
		
		echo "Juste un tests";
	}
	
	public function statistiquesAction()
	{
		$this->view->title             = "Bilan statistique des activités";
		
		$model                         = $this->getModel("activite");
		$modelRegion                   = $this->getModel("region");
		$modelIndicateur               = $this->getModel("indicateur");
		
	    $regionid                      = intval( $this->_getParam("regionid" , 0));
	    $domaineid                     = intval( $this->_getParam("domaineid", 0));
	    
	    $statRegions                   = $model->statregions( $domaineid, $regionid );
	    $statDomaines                  = $model->statdomaines($domaineid );
	    $statBeneficiaires             = $model->statbenefciaires();
	    $statPartenaires               = $model->statpartenaires();
	    
	    
	    $this->view->statDomaines      = $statDomaines;
	    $this->view->statRegions       = $statRegions;
	    $this->view->statPartenaires   = $statPartenaires;
	    $this->view->statBeneficiaires = $statBeneficiaires;
	    $this->view->regions           = $modelRegion->getSelectListe(    null, array("regionid", "libelle") , array() , null , null , false );
	    $this->view->indicateurs       = $modelIndicateur->getSelectListe(null, array("indicatid", "libelle") , array() , null , null , false );
	}
	
    public function listAction()
	{	
		$this->view->title    = "Liste des activités du projet ";
		
		$model                = $this->getModel("activite");
		$modelCategory        = $this->getModel("categorie");
		$modelProduit         = $this->getModel("produit");
		$modelRegion          = $this->getModel("region");
		$modelProvince        = $this->getModel("province");
		$modelVille           = $this->getModel("ville");
		$modelCommune         = $this->getModel("commune");
		$modelBeneficiaire    = $this->getModel("beneficiaire");
		$modelPartenaire      = $this->getModel("partenaire");
		$activites            = array();
		$paginator            = null;
		
				
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         = new Zend_Filter();
		$cleanUrlFilter       =  new Zend_Filter_Callback(array('Sirah_Functions_String', 'removeUrl'));
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$stringFilter->addFilter($cleanUrlFilter);
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$beneficiaireids      = array();
		$domaineids           = array();
		$regionids            = array();
		$partenaireids        = array();
		$provinceids          = array();
		
		$dateFinMonth         = ( isset( $params["datefin_month"]) ) ? $stringFilter->filter( $params["datefin_month"])   : "00";
		$dateFinYear          = ( isset( $params["datefin_year"] ) ) ? $stringFilter->filter( $params["datefin_year"])    : "00";
			
		$dateDebutYear        = ( isset( $params["datedebut_year"] ))? $stringFilter->filter( $params["datedebut_year"])  : "0000";
		$dateDebutMonth       = ( isset( $params["datedebut_month"]))? $stringFilter->filter( $params["datedebut_month"]) : "00";
			
		$date_debut           = sprintf("%d-%02d-%s", $dateDebutYear, $dateDebutMonth, "01");
		$date_fin             = sprintf("%d-%02d-%s", $dateFinYear  , $dateFinMonth  , "01");
		
		$filterDateDebut      = "";
		$filterDateFin        = "";
			
		if( Zend_Date::isDate( $date_debut , "YYYY-MM-dd" ) ) {
			$filterDateDebut  = $date_debut;
		}  
		if( Zend_Date::isDate(  $date_debut , "YYYY-MM-dd" ) ) {
			$filterDateFin    = $date_fin;
		}				
		$defaultFilter        = $stringFilter->filter($this->_getParam("generalfilter", null));
		$filters              = array("libelle"=> $defaultFilter, "produitid"=> 0, "beneficiaireid"=> 0, "partenaireid" => 0,
				                      "provinceid" => 0, "communeid"=> 0, "villeid" => 0, "category"=> 0,"regionid" => 0,
				                      "datedebut"  => $filterDateDebut, "published" => 1, "datefin" => $filterDateFin  );	
		$cleanParams          = array_intersect_key( $params, $filters );
		if(!empty(   $cleanParams )) {
			foreach( $cleanParams as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$filters["access"]                 = 1;
		$filters["published"]              = 1;
		if( isset( $params["regionids"] ) && !empty( $params["regionids"] ) ) {
			$regionids           = (array)$params["regionids"];
			if( is_string( $regionids ) ) {
				$regionids       = array( $params["regionids"] );
			}
			foreach( $regionids as $key => $val ) {
				     $regionids[$key]   = $stringFilter->filter( $val );
			}
			$filters["regionids"]=  $regionids;
		}
		if( isset( $params["provinceids"] ) && !empty( $params["provinceids"] ) ) {
			$provinceids         = (array)$params["provinceids"];
			if( is_string( $provinceids ) ) {
				$provinceids     = array( $params["provinceids"] );
			}
			foreach( $provinceids as $pKey => $pVal ) {
				     $provinceids[$pKey]   = $stringFilter->filter( $pVal );
			}
			$filters["provinceids"]= $provinceids;
		}
		if( isset( $params["domaineids"] ) && !empty( $params["domaineids"] ) ) {
			$domaineids          = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids      = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey]   = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]= $domaineids;
		}
		if( isset( $params["beneficiaireids"] ) && !empty( $params["beneficiaireids"] ) ) {
			$beneficiaireids      = (array)$params["beneficiaireids"];
			if( is_string( $beneficiaireids) ) {
				$beneficiaireids  = array( $params["beneficiaireids"] );
			}
			foreach( $beneficiaireids as $bKey => $bVal ) {
				     $beneficiaireids[$bKey]    = $stringFilter->filter( $bVal );
			}
			$filters["beneficiaireids"]= $beneficiaireids;
		}
		if( isset( $params["partenaireids"] ) && !empty( $params["partenaireids"] ) ) {
			$partenaireids      = (array)$params["partenaireids"];
			if( is_string( $partenaireids) ) {
				$partenaireids  = array( $params["partenaireids"] );
			}
			foreach( $partenaireids as $paKey => $paVal ) {
				     $partenaireids[$paKey]   = $stringFilter->filter($paVal );
			}
			$filters["partenaireids"]         = $partenaireids;
		}
		$activites               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
				
		$this->view->headMeta()->appendName("description", "Publication des activités du projet OSRO/BKF/203/SWI");
		$this->view->headMeta()->appendName("keywords"   , implode(", ", $filters). ", pfnl,burkina,faso,ouagadougou,fao,Fao,FAO,projet,osro,bkf203,BKF,203,swiss,suisse,BF,SWI,swi,PFNL,agriculture,ongone,obame,food,organization,united,nation" );		
		
		$this->view->generalfilter        = $defaultFilter;
		$this->view->activites            = $activites;
		$this->view->categories           = $modelCategory->getSelectListe(    "Tous les domaines d'activités" , array("catid" , "libelle") , array() , null , null , false );
		$this->view->regions              = $modelRegion->getSelectListe(      "Toutes les régions" , array("regionid", "libelle") , array() , null , null , false );
		$this->view->provinces            = $modelProvince->getSelectListe(    "Toutes les provinces" , array("provinceid", "libelle") , array() , null , null , false );
		$this->view->villes               = $modelVille->getSelectListe(       null , array("villeid", "libelle") , array() , null , null , false );
		$this->view->communes             = $modelCommune->getSelectListe(     null , array("communeid", "libelle") , array() , null , null , false);
		$this->view->beneficiaires        = $modelBeneficiaire->getSelectListe("Tous les bénéficiaires", array("beneficiaireid", "libelle") , array() , null , null , false );
		$this->view->partenaires          = $modelPartenaire->getSelectListe(  null  , array("partenaireid", "libelle") , array() , null , null , false );
		$this->view->produits             = $modelProduit->getSelectListe(     null  , array("produitid", "libelle") , array() , null , null , false );
		$this->view->filters              = $filters;
		$this->view->paginator            = $paginator;
		$this->view->checkedDomaines      = $domaineids;
		$this->view->checkedBeneficiaires = $beneficiaireids;
		$this->view->checkedRegions       = $regionids;
		$this->view->checkedProvinces     = $provinceids;
	}
	
	public function infosAction()
	{		
		$activityid      = intval($this->_getParam("id", $this->_getParam("activityid" , 0 )));
		if(!$activityid) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("public/activites/list");
		}
		$model                      = $this->getModel("activite");
		$modelGallery               = $this->getModel("gallerie");
		$activite                   = $model->findRow( $activityid, "activityid", null , false );
		if(!$activite) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("public/activites/list");
		}
		
		$this->view->headMeta()->appendName("description", $activite->libelle );
		$this->view->headMeta()->appendName("keywords"   , $activite->keywords. ", pfnl,burkina,faso,ouagadougou,fao,Fao,FAO,projet,osro,bkf203,BKF,203,swiss,suisse,BF,SWI,swi,PFNL,agriculture,ongone,obame,food,organization,united,nation" );
		
		$this->view->activite        = $activite;
		$this->view->localisation    = $activite->localisation();
		$this->view->partenaires     = $activite->partenairesList();
		$this->view->beneficiaires   = $activite->beneficiairesList();
		$this->view->resultats       = $activite->resultats();
		$this->view->documents       = $activite->documents();
		$this->view->parent          = ( $activite->parentid ) ? $model->findRow( $activite->parentid, "activityid", null , false ) : null;
		$this->view->categorie       = $activite->findParentRow("Table_Categories");
		$this->view->produit         = $activite->findParentRow("Table_Produits");
		$this->view->gallerie        = $gallerie = $modelGallery->findRow( $activityid , "activityid", null , false );
		$this->view->photos          = ( $gallerie ) ? $gallerie->photos() : array();
	
		$this->_helper->layout->setLayout("activitelayout");
		$this->view->title           = sprintf(" %s", $activite->libelle);
		$this->view->columns         = array("left");
		$this->view->showLayoutTitle = false;
	}
}
<?php

class Admin_FacturesController extends Sirah_Controller_Default
{
	
	 
	public function listAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title        = "Gestion des factures de la plateforme en ligne";
		
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
		
		$filters                  = array("searchQ"=>$searchQ,"numero"=>null,"name"=>null,"lastname"=>null,"firstname"=>null,"commandeid"=>null,"memberid"=>null,"productid"=> null,"registreid"=> null,"catid"=>null,"documentcatid"=> null,"statutid"=>null,
		                                  "date_day"=>null,"date_month"=>null,"date_year"=>null,"date"=>null,"periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year"  => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if((isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
		   (isset( $filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"],"month"=> $filters["periode_start_month"],"day" => $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month"=> $filters["periode_end_month"]  ,"day"   => $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ( $zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]   = ( $zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}
		$invoices                     = $factures = $model->getList( $filters, $pageNum, $pageSize);
		$paginator                    = $model->getListPaginator(  $filters );
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->invoices     = $invoices;
 
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->categories   = $modelCategory->getSelectListe("Selectionnez un type de produits"  , array("catid"   ,"libelle"), array() , null , null , false );
		$this->view->documentypes = $this->view->documentcategories = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"   , "libelle"), array(), null , null , false );
        $this->view->statuts      = $modelStatut->getSelectListe(  "Selectionnez un état de traitement", array("statutid","libelle"), array() , null , null , false );
	}
	
	public function exportAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		
		$me                       = Sirah_Fabric::getUser();
		$model                    = $this->getModel("commandefacture");
		$modelProduit             = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$modelStatut              = $this->getModel("commandestatut");	
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                  = (isset($params["searchq"] ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		$filters                  = array("searchQ"=>$searchQ,"numcommande"=>null,"lastname"=>null,"firstname"=>null,"commandeid"=> null,"memberid"=> null,"productid"=> null,"registreid"=> null,"catid"=>null,"documentcatid"=> null,"statutid"=>null,
		                                  "date_day"=>null,"date_month"=>null,"date_year"=>null,"date"=>null,"periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year"  => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"],"month" => $filters["periode_start_month"],"day" => $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month" => $filters["periode_end_month"]  ,"day"   => $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ( $zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]   = ( $zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}
		 
		$invoiceids                  = (isset($params["invoiceids"]))? $params["invoiceids"]: array();
		$factures                    = (count($invoiceids))?$model->getList(array("invoiceids"=>$invoiceids)) : $model->getList( $filters);
		$statuts                      = $modelStatut->getSelectListe("Sélectionnez un état de traitement"  , array("statutid", "libelle"), array() , null , null , false );
		$PDF                          = new ProjectPdf_Default();
		$PDF->setPrintHeader(true);
		$PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
		$PDF->SetTitle(  "Répertoire des factures");		
		$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$PDF->SetMargins(15, 40, 15);
		$PDF->SetHeaderMargin(5);
		$PDF->SetFooterMargin(30);
		$PDF->SetPrintHeader(true);
		
		$margins       = $PDF->getMargins();
		$contenuWidth  = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$PDF->AddPage();

		$PDF->SetFont("helvetica","B",9);
		$PDF->setFillColor( 222 , 222 , 222 );
		$PDF->setTextColor( 0 , 0 , 0 );
		$PDF->Cell( $contenuWidth , 8 , "LISTE DES FACTURES", "B" , 0 , "C" , 1 );
		$PDF->Ln(10);
		
		$PDF->MultiCell(20, 10 , "Références" , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(20, 10 , "Commandes"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Dates"      , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(40, 10 , "Clients"    , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Statuts"    , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Montants HT", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell($contenuWidth-155,10  , "Valeurs TTC", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );

		$PDF->Ln(10);
		$PDF->setTextColor( 0 , 0 , 0 );
		$PDF->SetFont("helvetica" , "" , 9 );
		$coutTotalTTC = $coutTotalHT = 0;
 		
		if( count(   $factures)) {
			foreach( $invoices     as $invoice ) {
				     $invoiceid      = $invoice["invoiceid"];
					 $clientName     = $invoice["client"];
					 $invoiceNumero  = $invoice["numero"];
					 $commandeNumero = $invoice["numcommande"];
 
					 $produits       = array();
					 $montantHT      = (isset( $invoice["valeur_ht"] ))?number_format($invoice["valeur_ht"], 0 , ", "," ") : "";
					 $montantTTC     = (isset( $invoice["valeur_ttc"]))?number_format($invoice["valeur_ttc"], 0 , ", "," ") : "";
					 $invoiceDate    = (intval($invoice["date"]      ))?date("d/m/Y",$invoice["date"]) : ""; 
                     $invoiceStatut  = (isset( $statuts[$invoice["statutid"]]) && intval($invoice["statutid"])) ? $statuts[$invoice["statutid"]] : "";
                     $coutTotalTTC   = $coutTotalTTC + $invoice["valeur_ttc"];
					 $coutTotalHT    = $coutTotalHT  + $invoice["valeur_ht"];
					 $pdfY           = $PDF->GetY();
					 if( $pdfY > 240 ) {
						 $PDF->AddPage();
					 }
					 $PDF->MultiCell(20,10, $invoiceNumero , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(20,10, $commandeNumero, 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(25,10, $invoiceDate   , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(40,10, $clientName    , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(25,10, $invoiceStatut , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );		
                     $PDF->MultiCell(25,10, $coutTotalHT   , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );						 
					 $PDF->MultiCell($contenuWidth - 155,10,$montantTTC, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M"  );
					
					 $PDF->Ln(10);					 				 
			}
			$PDF->MultiCell(130, 10 ,"TOTAL", 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell(25 , 10 ,number_format($coutTotalHT ,0," "," "), 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell(155, 10 ,number_format($coutTotalTTC,0," "," "), 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		} else {
			$this->setRedirect("Aucune facture n'a été trouvée avec les critères selectionnés","error");
			$this->redirect("admin/factures/list");
		}
		if( $this->_request->isXmlHttpRequest()) {
			$myStoreDataPath             = $me->getDatapath(); 
            if(!is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(  $myStoreDataPath , 0777);
				@mkdir( $myStoreDataPath . DS . "ARCHIVES");
			}				
			$invoiceDocumentDest         = $myStoreDataPath . "ARCHIVES" .DS.  "ERCCM_ListFactures.pdf" ;
			if( file_exists($invoiceDocumentDest)) {
				@unlink($invoiceDocumentDest);
			}
			$PDF->Output($invoiceDocumentDest, "F");
			echo ZendX_JQuery::encodeJson(array("success"=>"La liste des factures a été produite avec succès", "tmpDocument"=>$invoiceDocumentDest));
		    exit;
		}
		echo $PDF->Output("listeDesFactures.pdf", "D");
	}	
	
	public function exportpdfAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		
		$me                       = Sirah_Fabric::getUser();
		$model                    = $this->getModel("commandefacture");
		$modelProduit             = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$modelStatut              = $this->getModel("commandestatut");	
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                  = (isset($params["searchq"] ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		$filters                  = array("searchQ"=>$searchQ,"numcommande"=>null,"lastname"=>null,"firstname"=>null,"commandeid"=> null,"memberid"=> null,"productid"=> null,"registreid"=> null,"catid"=>null,"documentcatid"=> null,"statutid"=>null,
		                                  "date_day"=>null,"date_month"=>null,"date_year"=>null,"date"=>null,"periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year"  => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"],"month" => $filters["periode_start_month"],"day" => $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month" => $filters["periode_end_month"]  ,"day"   => $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ( $zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]   = ( $zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}
		 
		$invoiceids                  = (isset($params["invoiceids"]))? $params["invoiceids"]: array();
		$factures                    = (count($invoiceids))?$model->getList(array("invoiceids"=>$invoiceids)) : $model->getList( $filters);
		
		$statuts                      = $modelStatut->getSelectListe("Sélectionnez un état de traitement"  , array("statutid", "libelle"), array() , null , null , false );
		$PDF                          = new ProjectPdf_Default();
		$PDF->setPrintHeader(true);
		$PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
		$PDF->SetTitle(  "Répertoire des factures");		
		$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$PDF->SetMargins(15, 40, 15);
		$PDF->SetHeaderMargin(5);
		$PDF->SetFooterMargin(30);
		$PDF->SetPrintHeader(true);
		
		$margins       = $PDF->getMargins();
		$contenuWidth  = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$PDF->AddPage();

		$PDF->SetFont("helvetica","B",9);
		$PDF->setFillColor( 222 , 222 , 222 );
		$PDF->setTextColor( 0 , 0 , 0 );
		$PDF->Cell( $contenuWidth , 8 , "LISTE DES FACTURES", "B" , 0 , "C" , 1 );
		$PDF->Ln(10);
		
		$PDF->MultiCell(20, 10 , "Références" , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(20, 10 , "Commandes"  , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Dates"      , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(40, 10 , "Clients"    , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Statuts"    , 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell(25, 10 , "Montants HT", 1 , 'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		$PDF->MultiCell($contenuWidth-155,10  , "Valeurs TTC", 1 ,'C', true , 0 , "" , "" , true, 0 , false, true, 10 , "M" );

		$PDF->Ln(10);
		$PDF->setTextColor( 0 , 0 , 0 );
		$PDF->SetFont("helvetica" , "" , 9 );
		$coutTotalTTC = $coutTotalHT = 0;
 		
		if( count(   $factures)) {
			foreach( $invoices     as  $invoice ) {
				     $invoiceid      = $invoice["invoiceid"];
					 $clientName     = $invoice["client"];
					 $invoiceNumero  = $invoice["numero"];
					 $commandeNumero = $invoice["numcommande"];
 
					 $produits       = array();
					 $montantHT      = (isset( $invoice["valeur_ht"] ))?number_format($invoice["valeur_ht"], 0 , ", "," ") : "";
					 $montantTTC     = (isset( $invoice["valeur_ttc"]))?number_format($invoice["valeur_ttc"], 0 , ", "," ") : "";
					 $invoiceDate    = (intval($invoice["date"]      ))?date("d/m/Y",$invoice["date"]) : ""; 
                     $invoiceStatut  = (isset( $statuts[$invoice["statutid"]]) && intval($invoice["statutid"])) ? $statuts[$invoice["statutid"]] : "";
                     $coutTotalTTC   = $coutTotalTTC + $invoice["valeur_ttc"];
					 $coutTotalHT    = $coutTotalHT  + $invoice["valeur_ht"];
					 $pdfY           = $PDF->GetY();
					 if( $pdfY > 240 ) {
						 $PDF->AddPage();
					 }
					 $PDF->MultiCell(20,10, $invoiceNumero , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(20,10, $commandeNumero, 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(25,10, $invoiceDate   , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(40,10, $clientName    , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
					 $PDF->MultiCell(25,10, $invoiceStatut , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );		
                     $PDF->MultiCell(25,10, $coutTotalHT   , 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );						 
					 $PDF->MultiCell($contenuWidth - 155,10,$montantTTC, 1 ,'C', false, 0, "" , "" , true, 0 , false, true, 10 , "M"  );
					
					 $PDF->Ln(10);					 				 
			}
			$PDF->MultiCell(130, 10 ,"TOTAL", 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell(25 , 10 ,number_format($coutTotalHT ,0," "," "), 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
			$PDF->MultiCell(155, 10 ,number_format($coutTotalTTC,0," "," "), 1 ,'C', false, 0 , "" , "" , true, 0 , false, true, 10 , "M" );
		} else {
			$this->setRedirect("Aucune facture n'a été trouvée avec les critères selectionnés","error");
			$this->redirect("admin/factures/list");
		}
		if( $this->_request->isXmlHttpRequest()) {
			$myStoreDataPath             = $me->getDatapath(); 
            if(!is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(  $myStoreDataPath , 0777);
				@mkdir( $myStoreDataPath . DS . "ARCHIVES");
			}				
			$invoiceDocumentDest         = $myStoreDataPath . "ARCHIVES" .DS.  "ERCCM_ListFactures.pdf" ;
			if( file_exists($invoiceDocumentDest)) {
				@unlink($invoiceDocumentDest);
			}
			$PDF->Output($invoiceDocumentDest, "F");
			echo ZendX_JQuery::encodeJson(array("success"=>"La liste des factures a été produite avec succès", "tmpDocument"=>$invoiceDocumentDest));
		    exit;
		}
		echo $PDF->Output("listeDesFactures.pdf", "D");
	}	
	
	public function exportcsvAction()
	{
		
		$me                       = Sirah_Fabric::getUser();
		$model                    = $this->getModel("commandefacture");
		$modelProduit             = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$modelStatut              = $this->getModel("commandestatut");	
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                  = (isset($params["searchq"] ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		$filters                  = array("searchQ"=>$searchQ,"numcommande"=>null,"lastname"=>null,"firstname"=>null,"commandeid"=> null,"memberid"=> null,"productid"=> null,"registreid"=> null,"catid"=>null,"documentcatid"=> null,"statutid"=>null,
		                                  "date_day"=>null,"date_month"=>null,"date_year"=>null,"date"=>null,"periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year"  => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"],"month" => $filters["periode_start_month"],"day" => $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month" => $filters["periode_end_month"]  ,"day"   => $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ( $zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]   = ( $zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}
		 
		$invoiceids                   = (isset($params["invoiceids"]))? $params["invoiceids"]: array();
		$factures                     = (count($invoiceids))?$model->getList(array("invoiceids"=>$invoiceids)) : $model->getList( $filters);
		
		$statuts                      = $modelStatut->getSelectListe("Sélectionnez un état de traitement", array("statutid", "libelle"), array() , null , null , false );
		if( count(   $invoices )) {
			$csvRows                  = array();
			$myStoreDataPath          = $me->getDatapath(); 
            if(!is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(  $myStoreDataPath , 0777);
				@mkdir( $myStoreDataPath . DS . "ARCHIVES");
			}				
			$invoiceDocumentDest         = $myStoreDataPath . "ARCHIVES" .DS.  "ERCCM_ListFactures.csv" ;
			if( file_exists($invoiceDocumentDest)) {
				@unlink($invoiceDocumentDest);
			}
			$coutTotalTTC                    = $coutTotalHT = 0;
			foreach( $invoices        as $invoice ) {
				
				     $invoiceid              = $invoice["invoiceid"];
					 $clientName             = $invoice["client"];
					 $invoiceNumero          = $invoice["numero"];
					 $commandeNumero         = $invoice["numcommande"];
 
					 $produits               = array();
					 $montantHT              = (isset( $invoice["valeur_ht"] ))?number_format($invoice["valeur_ht"], 0 , ", "," ") : "";
					 $montantTTC             = (isset( $invoice["valeur_ttc"]))?number_format($invoice["valeur_ttc"], 0 , ", "," ") : "";
					 $invoiceDate            = (intval($invoice["date"]      ))?date("d/m/Y",$invoice["date"]) : ""; 
                     $invoiceStatut          = (isset( $statuts[$invoice["statutid"]]) && intval($invoice["statutid"])) ? $statuts[$invoice["statutid"]] : "";
                     $coutTotalTTC           = $coutTotalTTC + $invoice["valeur_ttc"];
					 $coutTotalHT            = $coutTotalHT  + $invoice["valeur_ht"];
				      
					 $csvRowData             = array();
					 $csvRowData["NUMEROS"]  = $invoiceNumero;
					 $csvRowData["COMMANDES"]= $commandeNumero;
					 $csvRowData["DATES"]    = $invoiceDate;
					 $csvRowData["CLIENTS"]  = $clientName;
                     $csvRowData["MONTANTS"] = $montantTTC;	
                     $csvRowData["STATUTS"]  = $invoiceStatut;						 
			         $csvRows[$invoiceid]    = $csvRowData;
				 				 
			}
		} else {
			$this->setRedirect("Aucune facture n'a été trouvée avec les critères selectionnés","error");
			$this->redirect("admin/factures/list");
		}
		if( count( $csvRows )) {
			$csvHeader   = array("NUMEROS","DATES","CLIENTS","STATUTS","MONTANTS");
			$csvAdapter  = Sirah_Filesystem_File::fabric("Csv", array("filename"=>$invoiceDocumentDest,"has_header"=>true, "header"=>$csvHeader ) , "wb+" );
			if( $csvAdapter->save( $csvRows ) ) {
				$this->_helper->Message->addMessage( sprintf("Votre opération de création du fichier CSV s'est produite avec succès"), "success");
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				//$this->getResponse()->setHeader("Content-Type" , "text/csv");					
				echo $csvAdapter->Output($invoiceDocumentDest);
				@unlink($invoiceDocumentDest);
				exit;
			} else {
				$errorMessages[]  = " Aucune facture n'a pu être exportée ";
			}
		}
	}	
	
	 
	
	public function infosAction()
	{		
		$model                   = $this->getModel("commandefacture");
		$modelCommande           = $this->getModel("commande");
		$modelLigne              = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $modelMember  = $this->getModel("member");
        $modelPaiement           = $this->getModel("commandepaiement");	
 	
		
		$invoiceid              = intval( $this->_getParam("invoiceid", $this->_getParam("id", 0 )));
		$invoice                = $model->findRow( $invoiceid , "invoiceid", null, false );
		$commandeid             = ( $invoice   )?$invoice->commandeid : 0;
		$commande               = ( $commandeid)?$modelCommande->findRow($commandeid,"commandeid",null,false) : null;
		if(!$invoice || !$commandeid || !$commande) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'afficher les informations de cette invoice. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette invoice. Paramètres invalides", "error");
			$this->redirect("admin/factures/list");
		}		
		$this->view->invoice        = $this->view->facture   = $invoice;
		$this->view->invoiceid      = $this->view->factureid = $invoiceid;
		$this->view->commandeid     = $commandeid;
		$this->view->commande       = $commande;
		$this->view->member         = $this->view->client = ($commande->memberid)? $modelMember->findRow($commande->memberid,"memberid",null,false) : null;
		$this->view->billing_address= $invoice->billing_address($invoiceid,"object");
		$this->view->statut         = $invoice->getStatut( $invoiceid);
        $this->view->documents      = $invoice->documents($invoiceid);
		$this->view->products       = $this->view->lignes    = $products  = $commande->listproducts($commandeid);
		$this->view->reglements     = $this->view->paiements = $paiements = $modelPaiement->getList(array("commandeid"=> $commandeid), 0, 0, array("P.date ASC"));
		$this->view->title          = sprintf("Les informations de la facture numéro %s", $invoice->numero);
		$this->view->lastReglement  = $modelPaiement->getLast($invoiceid);
	}
	
	
	public function getAction()
	{
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
        $modelClient	         = $modelMember = $this->getModel("member");
        $modelPaiement           = $this->getModel("commandepaiement");	
 			
		$invoiceid               = intval( $this->_getParam("invoiceid", $this->_getParam("id", 0 )));
		$invoice                 = $model->findRow( $invoiceid , "invoiceid", null, false );
		$commandeid              = ($invoice   )?$invoice->commandeid : 0;
		$commande                = ($commandeid)?$modelCommande->findRow($commandeid,"commandeid",null,false) : null;
		if(!$invoice || !$commandeid || !$commande) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=>"Impossible d'afficher les informations de cette facture. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette facture. Paramètres invalides", "error");
			$this->redirect("admin/factures/list");
		}	
        $this->view->invoice      	= $this->view->facture= $invoice;	
		$this->view->commande       = $commande;
		$this->view->invoiceid      = $invoiceid;
		$this->view->commandeid     = $commandeid;
		$this->view->billing_address= $invoice->billing_address($invoiceid,"object");
		$this->view->member         = $this->view->client = ($commande->memberid)? $modelMember->findRow($commande->memberid, "memberid", null, false) : null;
		$this->view->statut         = $commande->getStatut(); 
		$this->view->products       = $this->view->lignes = $products = $commande->listproducts($commandeid);
		$this->render("document");		
	}
	
	public function deleteAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$model                      = $this->getModel("commandefacture");
		$modelLigne                 = $this->getModel("commandeligne");
		$modelProduit               = $this->getModel("product");
		
		$modelTable                 = $model->getTable();
		$dbAdapter                  = $modelTable->getAdapter();
		$tablePrefix                = $modelTable->info("namePrefix");
		$errorMessages              = array();
		
		$invoiceid                  = intval( $this->_getParam("invoiceid", $this->_getParam("id", 0 )));
		$invoice                    = $model->findRow( $invoiceid , "invoiceid", null, false );
		if(!$invoice ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Impossible d'afficher les informations de cette invoice. Paramètres invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'afficher les informations de cette invoice. Paramètres invalides", "error");
			$this->redirect("admin/factures/list");
		}
		 			 
		if(!$invoice->delete()) {
			$errorMessages[]       = "La suppression de la facture a echoué";
		} else {
			$dbAdapter->delete($tablePrefix."erccm_vente_commandes_invoices_addresses",array("invoiceid=?"=>$invoiceid));
		}
		if( count($errorMessages)) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"=> implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/factures/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Cette facture a été supprimée avec succès"));
				exit;
			}
			$this->setRedirect("Cette facture a été supprimée avec succès", "success");
			$this->redirect("admin/factures/list");
		}
	}
	
	 
	
	public function uploadAction()
	{
		$invoiceid           = intval($this->_getParam("invoiceid", $this->_getParam("id" , 0 )));
		$category             = intval($this->_getParam("category"  , $this->_getParam("categorie" , 0 )));
		$model                = $this->getModel("commande");
		if(!$invoiceid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/factures/list");
		}
		$commande            = $model->findRow( $invoiceid , "invoiceid" , null , false );
		if(!$commande) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/factures/list");
		}
		if( $commande->isClosed($invoiceid) ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Cette commande semble clôturée ou est rattachée à un projet clôturé"));
				exit;
			}
			$this->setRedirect("Cette commande semble clôturée ou est rattachée à un projet clôturé", "error");
			$this->redirect("admin/factures/infos/invoiceid/".$invoiceid);
		}
		$me                     = $user = Sirah_Fabric::getUser();
		$modelDocument          = $this->getModel("document");
		$modelCategory          = $this->getModel("documentcategorie");
		$modelProfile           = $this->getModel("profile");
		$defaultData            = $modelDocument->getEmptyData();
		$userDataPath           = APPLICATION_DATA_PATH . DS . "commandes". DS;
		$errorMessages          = array();
		$uploadedFiles          = array();
		$categories             = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
	
		if( $this->_request->isPost() ) {
			$postData           = $this->_request->getPost();
			$formData           = array_intersect_key( $postData ,  $defaultData )	;
			$documentData       = array_merge( $defaultData ,  $formData );
			$modelTable         = $me->getTable();
			$dbAdapter          = $modelTable->getAdapter();
			$prefixName         = $modelTable->info("namePrefix");
			if( !is_dir( $userDataPath ) ) {
				$errorMessages[]   = "Le dossier de stockage des documents de l'utilisateur n'est pas créé, veuillez l'indiquer à l'administrateur ";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			//On crée un validateur de filtre
			$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
			$documentData["userid"]         = $me->userid;
			$documentData["category"]       = intval( $documentData["category"] );
			$documentData["resource"]       = ( isset( $postData["resource"] ) )   ? $stringFilter->filter($postData["resource"]) : "" ;
			$documentData["resourceid"]     = ( isset( $postData["resourceid"] ) ) ? intval($postData["resourceid"]) : 0 ;
			$documentData["filedescription"]= $stringFilter->filter( $documentData["filedescription"] );
			$documentData["filemetadata"]   = $stringFilter->filter( $documentData["filemetadata"] );
	
			$userMaxFileSize                = 32;
			$userMaxUploadFileSize          = 100;
			$userSingleFileSize             = 100;
			$userTotalFiles                 = 10;
	
			$documentsUpload                = new Zend_File_Transfer();
			$documentsUpload->addValidator("Count"    , false , 1 );
			$documentsUpload->addValidator("Extension", false , array("csv" , "xls" , "xlxs" , "pdf" , "png" , "gif" , "jpg" , "docx" , "doc" , "xml"));
			$documentsUpload->addValidator("Size"     , false , array("max"  => $userSingleFileSize."MB"));
			$documentsUpload->addValidator("FilesSize", false , array("max"  => $userSingleFileSize."MB"));
	
			$basicFilename                 = $documentsUpload->getFileName('commandefiles', false);
			$documentExtension             = Sirah_Filesystem::getFilextension( $basicFilename );
			$tmpFilename                   = Sirah_Filesystem::getName( $basicFilename);
			$userFilePath                  = $userDataPath . time()  . "_" . $basicFilename;
				
	
			$documentsUpload->addFilter("Rename", array("target" => $userFilePath , "overwrite" => true) , "commandefiles");
			//On upload les fichiers du dossier d'commande
			if( $documentsUpload->isUploaded("commandefiles")){
				$documentsUpload->receive("commandefiles");
			} else {
				$errorMessages[]  = " Le document que vous avez chargé n'est pas valide";
			}
			if( $documentsUpload->isReceived("commandefiles") ) {
				$fileSize                       = $documentsUpload->getFileSize('commandefiles');
				$myFilename                     = ( isset( $postData["filename"] ) && $strNotEmptyValidator->isValid( $postData["filename"] ) ) ? $stringFilter->filter( $postData["filename"] ) : $tmpFilename;
				$documentData["filename"]       = $modelDocument->rename( $myFilename , $user->userid );
				$documentData["filepath"]       = $userFilePath ;
				$documentData["filextension"]   = $documentExtension;
				$documentData["filesize"]       = floatval($fileSize);
				$documentData["creationdate"]   = time();
				$documentData["creatoruserid"]  = $me->userid;
				if( $dbAdapter->insert( $prefixName . "system_users_documents", $documentData ) ) {
					$documentid                 = $dbAdapter->lastInsertId();
					$commandeDocumentData       = array("documentid" => $documentid, "invoiceid" => $invoiceid, "creatorid" => $me->userid, "creationdate" => time());
					if( $dbAdapter->insert( $prefixName ."gestapp_achat_commandes_documents", $commandeDocumentData )) {
						$uploadedFiles[$documentid] = $documentData;
					}										 					
				} else {
					$errorMessages[]            = "Les informations du document n'ont pas été enregistrées dans la base de données";
				}
			} else {
				$errorMessages[]                = "Le document n'a pas été chargé correctement sur le serveur";
			}
			if( empty( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonArray             = array();
					$jsonArray["success"]  = "Le document a été enregistré avec succès";
					$jsonArray["document"] = $documentData ;
					echo ZendX_JQuery::encodeJson( $jsonArray );
					exit;
				}
				$this->_helper->Message->addMessage("Le document a été enregistré avec succès" , "success");
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
		$this->view->invoiceid = $invoiceid;
		$this->view->categories = $categories;
		$this->view->data       = $defaultData;
		$this->view->category   = $category;
	}
}

<?php

class ProductsController extends Sirah_Controller_Default
{	
      public function listAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  		
		$modelProductCategory = new Model_Productcategorie();
		
		$this->view->title    = " Plan de tarification ";
		$this->view->products = $modelProductCategory->getList();
	}
     
	
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  
		
		$productid        = intval($this->_getParam("productid", $this->_getParam("id", $this->_getParam("catid",0))));
		if(!$productid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/products/list");
		}
		
		$model                 = $this->getModel("productcategorie");	
		$product               = $model->findRow( $productid, "catid" , null , false);
 	
		if(!$product ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/products/list");
		}
        $this->view->product   = $product;
		$this->view->title     = sprintf("Les informations du produit %s", $product->libelle);	 
	}
	
}
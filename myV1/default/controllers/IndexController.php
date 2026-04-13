<?php

class IndexController extends Sirah_Controller_Default
{
	
	public function indexAction()
	{
	   
		$this->view->title     = " Bienvenue sur l'interface d'administration de votre systeme ";
		$this->view->subtitle  = " Veuillez vous connecter pour accéder aux fonctionnalités ";
		
		$this->view->toolsbar  = "";
		
	 
		 
	}
	
	public function errorAction()
	{
		echo "error";
		echo "Nous sommes sur la page d'accueil";
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	public function denyAction()
	{
		$loger     = $this->getHelper("Log")->getLoger();
		$resource  = $this->_request->getParam("_precController");
		if($this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRender->setNoRender(true);
			$error=array("error"=>" Désolé ! Vous n'etes pas autorisé à effectuer cette action sur la ressource  {$resource}... ");
			echo ZendX_JQuery::encodeJson($error);
		}
		else{
			$this->view->message=" Désolé ! Vous n'etes pas autorisé à effectuer cette action sur la ressource {$resource}...";
			$this->render();
		}
		$author=null;
		$writer=& $loger->getWriter("fichier");
		$formater=new Zend_Log_Formatter_Simple(" %user% %message% à la date du %timestamp% \n" );
		$writer->setFormatter( $formater);
		$loger->autorisation(" il a tente d'acceder à la ressource {$resource} à laquelle il n'etait pas autorisé ");
		
	}


}







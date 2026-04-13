<?php

class SystemController extends Sirah_Controller_Default
{
	
	public function paramsAction()
	{
		$this->view->title    = "Les paramètres système de l'application";		
		$configFile           = APPLICATION_PATH . DS . "cfg" . DS . "system.ini";		
		if( !file_exists($configFile ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => "Aucun fichier de configuration système n'a été créé, vérifiez les dossiers"));
				exit;
			}
			$this->setRedirect("Aucun fichier de configuration système n'a été créé, vérifiez les dossiers" , "error");
			$this->redirect("index/index");
		}
		$fileContent   = new Zend_Config_Ini( $configFile , null ,array("allowModifications" => true ,
				                                                        "skipExtends"        => true));
		$configuration = $fileContent->configuration;
		if( $this->_request->isPost()) {
			$postData  = $this->_request->getPost();			
			$configuration->inscription->periode->start = ( isset( $postData["inscription_periode_start"])) ? $postData["inscription_periode_start"] : ""; 
			$configuration->inscription->periode->end   = ( isset( $postData["inscription_periode_end"])  ) ? $postData["inscription_periode_end"] : "";
			$configuration->inscription->periodicite    = ( isset( $postData["inscription_periodicite"])  ) ? $postData["inscription_periodicite"] : "";
			$configuration->inscription->cout->initial  = ( isset( $postData["inscription_cout_initial"]) ) ? $postData["inscription_cout_initial"] : "";	
			$configuration->candidature->cout->total    = ( isset( $postData["candidature_cout_total"])   ) ? $postData["candidature_cout_total"] : "";	
			$fileContent->configuration   = $configuration;
			$writer    = new Zend_Config_Writer_Ini(array("config" => $fileContent , "filename"  => $configFile));
			$writer->setRenderWithoutSections();
			$writer->write();			
		}		
		$this->view->configuration   = $configuration;
	}
		
	
	


}







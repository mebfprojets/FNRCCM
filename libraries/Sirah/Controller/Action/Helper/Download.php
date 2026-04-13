<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Cette classe représente une aide d'action
 * 
 * qui permet d'enregistrer les messages d'erreurs
 * 
 * générés par l'application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

  class Sirah_Controller_Action_Helper_Download extends Zend_Controller_Action_Helper_Abstract
  {
  	
  	
  	public function direct($filepath,$filetype)
  	{
  		return $this->_download($filepath,$filetype);
  	}
  	
  	
  	/**
  	 * Permet de lancer le téléchargement du fichier
  	 *
  	 * @param   string   $filepath  le chemin du fichier
  	 *
  	 * @since
  	 */
  	protected function _download($filepath,$filetype=null)
  	{
  		$filesize = Sirah_Filesystem::getSize($filepath);
  		$filemd5  = md5_file($filepath);
  		$filename = Sirah_Filesystem::basename($filepath);
  		
  		if(!headers_sent()){
  			// Gestion du cache
  			header('Pragma: public');
  			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  			header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');
 		
  		   // Informations sur le contenu à envoyer
  		   // header('Content-Tranfer-Encoding: ' . $type . "\n");
  		    header('Content-Length: '. $filesize);
  		    header('Content-MD5: '. base64_encode($filemd5));
  		    header('Content-Type: application/force-download; name="' . $filename . '"');
  		    header('Content-Disposition: attachement; filename="' . $filename . '"');
  		   // Informations sur la réponse HTTP elle-même
  		    header('Date: '.gmdate('D, d M Y H:i:s', time()) . ' GMT');
  		    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 1) . ' GMT');
  		    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
  		    readfile($filepath);
  		    exit;
  		}
  	}

  	
  	

   }


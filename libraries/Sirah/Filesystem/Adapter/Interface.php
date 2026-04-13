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
 * Cette classe correspond à l'interface utilisée par les classes utilisées pour la copie des fichiers
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

interface Sirah_Filesystem_Adapter_Interface
{

	public function copy( $destination ,$overwrite , $newname );
	
	public function setOptions($options);
	
	public function read($size , $offset);
	
	public function write($data , $length);
	
	public function reset();
	
	public function close();


  }

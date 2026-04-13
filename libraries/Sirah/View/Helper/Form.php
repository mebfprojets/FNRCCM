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
 * Cette classe représente une aide de vue
 * 
 * qui permet de générer la pagination
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
/** Sirah_View_Helper_FormElement */
require_once 'Sirah/View/Helper/FormElement.php';

class Sirah_View_Helper_Form extends Sirah_View_Helper_FormElement
{
    /**
     *  Permet de rendre le code HTML du formulaire
     *
     * @param  string $name Form name
     * @param  null|array $attribs HTML form attributes
     * @param  false|string $content Form content
     * @return string
     */
    public function form($name, $attribs = null, $content = false)
    {
        $info = $this->_getInfo($name, $content, $attribs);
        extract($info);

        if (!empty($id)) {
            $id = ' id="' . $this->view->escape($id) . '"';
        } else {
            $id = '';
        }

        if (array_key_exists('id', $attribs) && empty($attribs['id'])) {
            unset($attribs['id']);
        }

        $xhtml = '<form'
               . $id
               . $this->_htmlAttribs($attribs)
               . '>';

        if (false !== $content) {
            $xhtml .= $content
                   .  '</form>';
        }

        return $xhtml;
    }
}

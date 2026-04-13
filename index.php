<?php
 
try {
    require_once("myV1/cfg/application.php");
    ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . getcwd() . '/libraries/');

    require_once  'Zend/Application.php'; 
    require_once  'Smarty/libs/Smarty.class.php';
    require 'vendor/autoload.php';

     

    // Création de l'application

    $application = new Zend_Application(APPLICATION_ENV ,  'myV1/cfg/application.ini');
    $application->bootstrap()->run();
} catch(Exception $e ) {
    
    echo "<div style=\"position:relative;width:100%;height:30px;background-color:#f8d7da;border:#f5c6cb;color:#721c24;padding:.75rem 1.25rem\"><font style=\"color:#721c24\"><i>UN DYSFONCTIONNEMENT TECHNIQUE A ETE RENCONTRE. VOUS POUVEZ REESSAYER EN ACTUALISANT LA PAGE ! ET SI L'ERREUR PERSISTE, UNE MAINTENANCE EST CERTAINEMENT EN COURS, ET DES SOLUTIONS SONT EN COURS DE MISE EN OEUVRE. NOUS VOUS PROPOSONS DE REVENIR SOUS PEU. MERCI DE LA COMPREHENSION</i> </font></div>";

   die();
}

//echo "<div style=\"position:relative;width:100%;height:30px;background-color:#d4edda;border:#c3e6cb;color:#155724;padding:.75rem 1.25rem\"><font style=\"color:#155724\"> <i> MAINTENANCE DE LA PLATEFORME !. NOUS VOUS PROPOSONS DE REVENIR SOUS PEU. MERCI</i> </font></div>";


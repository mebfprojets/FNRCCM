<?php

class Sira_Validateur_Telephone extends Zend_Validate_Abstract{


const TROP_COURT='short';

const NO_MODEL='sans-model';

const NUMERO_INVALID='invalid' ;

var $taille=8;


protected $_models=array();



protected $_messageVariables = array(
        'taille' => 'taille'
    );
 

protected $_messageTemplates=array(
      
         self::TROP_COURT=>" '%value%' est trop court, il doit contenir au minimum '%taille%' caracteres ",

         self::NO_MODEL=>" Aucun format/modele de numero telephonique n'est defini  ",

         self::NUMERO_INVALID=>" Le numero de telephone  '%value%' que vous avez saisi  est invalide "

   );



protected function _setDefaultModels(){

     $this->_models = array(

           'burkina'=>'/226-\d{2}-{1}\d{2}-{1}\d{2}-{1}\d{2}/',
           'mali'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'cote'=>'/225-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'guinee'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'dakar'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'niger'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'ghana'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'togo'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'tchad'=>'/\d{3}-{1}\d{2}-{1}\d{2}-{1}-\d{2}-{1}\d{2}/',
           'france'=>'//'
     );

}

function setModel($pays,$model){

    $this->_models[$pays]=$model;
}

function setModels($models){

   $this->_models=$models;

}

function getModels(){

   return $this->_models;

}

function getModel($pays){

    return $this->_models[$pays];

  }



public function isValid($value,$pays=null){

  $this->_setValue($value);

  $this->_setDefaultModels();

  $isValid=true;

  if(!count($this->_models)){

        $this->_error(self::NO_MODEL);

       return false;
   }

   else{

   $isValid=false;

   if($pays===null){

     foreach($this->_models as $pays=>$model){

    
         if(preg_match($model,$value)){
   
           $isValid=true;  
           break; 

          }
       }
     }else{

          if(preg_match($this->_models[$pays],$value)){
               
             $isValid=true;

          }
      }

    if(!$isValid){
    
     $this->_error(self::NUMERO_INVALID);

     }
   }

  return $isValid;
}





}

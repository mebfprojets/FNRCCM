<?php

class Sira_Repertoire{



 function create($dir,$createMod="0777"){

      if(!is_dir($dir))
      {    
         @chmod($createMod);     
         @mkdir($dir,$createMod);
       }

}


function copier($src,$dest,$createreps=true){

$resultat=true;
$src=Sira_Fichier_Chemin::cleanRep($src);
$dest=Sira_Fichier_Chemin::cleanRep($dest);
if(!is_dir($src)){
  throw new Sira_Fichier_Exception(" Repertoire source invalide {$src} ");
 }

if(!is_dir($dest) && ($createreps) ){
   $dest=Sira_Repertoire::create($dest);
  } 
     $handle=opendir($src);
     while(($file=readdir($handle)) !==false){
        $srcPath=$src.DIRECTORY_SEPARATOR.$file;
        $destPath=$dest.DIRECTORY_SEPARATOR.$file;
       switch(filetype($srcPath)){ 
            case "dir":
                        $resultat=Sira_Repertoire::copier($destPath);
                        break;
            case "file":
                       if(!@copy($srcPath,$destPath)){
                          $resultat=false;
                        }
                        break;
                        
         }
    
     }
      

  return $resultat;
}



function explorerDossiers($path,$filtre=".",$cheminComplet=false){
  $output="";
  $chemin=Sira_Fichier_Chemin::cleanRep($path);  
  if(!is_dir($chemin)){

     throw new Sira_Fichier_Exception(" Le dossier que vous voulez explorer est invalide ");

   }
  $array=array();
  $handler=opendir($chemin);
		while (($fichier = readdir($handler)) !== false)
		{
			if (($fichier != '.') && ($fichier != '..') ) {
				$dossier = $chemin.DIRECTORY_SEPARATOR.$fichier;
				if (is_dir($dossier)) {
					if (preg_match("/$filtre/", $fichier)) {
						if ($cheminComplet) {
							$array[] = $dossier;
						} else {
							$array[] = $fichier;
						}
					}

                                 }
                    }
          }
     closedir($handler);
     return $array;
}


function explorerFichiers($path,$filtre=".")
{

$chemin=Sira_Fichier_Chemin::cleanRep($path);  
  if(!is_dir($chemin)){

     throw new Sira_Fichier_Exception(" Le dossier que vous voulez explorer est invalide {$path} ");

   }
   if(!is_readable($chemin)){

            throw new Sira_Fichier_Exception(" Vous n'avez pas de permissions sur le dossier photos ");
   }
  $array=array();
  $handler=opendir($chemin);
		while (($fichier = readdir($handler)) !== false)
		{
			if (($fichier != '.') && ($fichier != '..')) {
				$document = $chemin.DIRECTORY_SEPARATOR.$fichier;

					if (preg_match("/$filtre/", $fichier)) {
						if(!is_dir($document)){

                                                        $array[]=new Sira_Fichier($document);
                                           }
					}

                                
                    }
          }
     closedir($handler);
     return $array;

}



function makePathTree($path,$level=0,$maxLevel=4,$parent=0)
 {

   $chemins=array();
   if($level==0){

      $GLOBALS['_FOLDER_INDEX_'] = 0;

     }

   if($level < $maxLevel)
    {

          $dossiers=Sira_Repertoire::explorerDossiers();

          foreach($dossiers as $dossier){

               $id = ++$GLOBALS['_FOLDER_INDEX_'];
	       $cheminComplet = Sira_Fichier_Chemin::cleanRep($path . DIRECTORY_SEPARATOR . $dossier);
               


          }

     }
  }


function supprimer($chemin){

  $resultat=true;
  $chemin=Sira_Fichier_Chemin::cleanRep($chemin);
  $handler=opendir($chemin);
 
    while(($file=readdir($handler))!==false)
   {

       $filePath=$chemin.DIRECTORY_SEPARATOR.$file;

       switch(filetype($filePath))
       {

         case "dir":
                     $resultat=Sira_Repertoire::supprimer($filePath);
                     break;
       
        case "file": $resultat=@unlink($filePath);
                     break;
       }
     }

return $resultat;

}


function deplacer($src,$dest,$createReps=true){

$resultat=true;
$src=Sira_Fichier_Chemin::cleanRep($src);
$dest=Sira_Fichier_Chemin::cleanRep($dest);
if(!is_dir($src)){
  throw new Sira_Fichier_Exception(" Repertoire source invalide ");
 }
//On copie les donnees du repertoire source vers le repertoire de destination
Sira_Repertoire::copier($src,$dest);


//Puis on supprime les fichiers du repertoire source
Sira_Repertoire::supprimer($src);
}


   


}

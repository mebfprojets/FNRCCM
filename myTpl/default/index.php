
<?php 
      $app         = JFactory::getApplication();
      $menu        = & JSite::getMenu();
      $isFrontpage = (($menu->getActive() == $menu->getDefault()) || (JRequest::getVar('view') == 'frontpage'));
      $tPath       = $this->baseurl.'/templates/'.$this->template;
      $doc         = JFactory::getDocument();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <script type="text/javascript" src="<?php echo $tPath; ?>/js/jquery-1.10.2.min.js"> </script>
    <script type="text/javascript" src="<?php echo $tPath; ?>/js/jquery-ui-1.10.3.custom.min.js"> </script>
    <script type="text/javascript" src="<?php echo $tPath; ?>/js/bootstrap/bootstrap.min.js"> </script>

    <jdoc:include type="head" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../ico/favicon.png">
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/bootstrap/css/bootstrap.min.css"    />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/bootstrap/css/bootstrap-theme.min.css"  />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/bootstrap/css/bootstrap-modal.css"  />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/bootstrap/css/bootstrap-responsive.css"  />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/bootstrap/css/prettify.css"  />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/uxsfbtfg/jquery-ui-1.10.0.custom.css"  />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/contenu.css"    />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/template.css"    />
    <link rel="stylesheet" href="<?php echo $tPath; ?>/css/sirahviews.css"  />
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
  <body class="sr_page_body">
  
   <!-- L'entete fixe de la page -->
  <div id="headNavigation" class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div id="sirah-page-head" class="container col-md-12" >  
        <div id="logoCol" class="col-md-3" >
           <a title="<?php echo 'Le logo de l\'entreprise' ?>" href="index.php"> 
             <img align="center" src="<?php echo $tPath; ?>/images/logo.png" /> 
           </a>
        </div>
        <!-- La colonne du module de recherche -->
        <div id="searchCol" class="searchbox col-md-5"> 
           <form action="#" id="defaultSearchForm" method="post" class="form-horizontal">        
              <div id="searchMod" class="input-group pull-left">
                       <input id="defaultSearchInput" name="defaultsearchkeys" style="display: inline-block; height:34px;" 
                              class="headInputs form-control input-medium" placeholder="Saisissez vos mots clés de recherche, ou des noms d'entreprise ..." type="text">
                     <span class="input-group-btn">
                        <button id="defaultSearchBtn" class="btn btn-default defaultSearchBtn" type="button"> 
                             <span class="glyphicon glyphicon-search icon-white"> </span> 
                        </button>
                        <a href="#"> Recherche avancée </a>
                     </span>
             </div> 
          </form>         
        </div>
        <!-- Fin de la colonne du module de recherche -->
        <div id="navCol" class="col-md-4 pull-right">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#"> <i class="glyphicon glyphicon-home "></i> Accueil</a></li>
            <li class="dropdown">
                 <a data-toggle="dropdown" class="dropdown-toggle" href="#"> <i class="glyphicon glyphicon-cog"></i> Mon profil <b class="caret"></b></a>
                   <ul class="dropdown-menu">                   
                      <li class="dropdown-header"> Administrateur </li>
                      <li><a href="#"><i class="glyphicon glyphicon-dashboard"></i> Tableau de bord</a></li>
                      <li><a href="#"><i class="glyphicon glyphicon-user "></i> Mon profil</a></li>
                      <li><a href="#"><i class="glyphicon glyphicon-info-sign "></i> Mes paramètres</a></li>
                      <li><a href="#"><i class="glyphicon glyphicon-log-out "></i> Me déconnecter</a></li>                      
                      <li class="divider"></li>                      
                      <li><a href="#"> <i class="glyphicon glyphicon-question-sign"></i> Besoin d'aide</a></li>
                      <li><a href="#"> <i class="glyphicon glyphicon-envelope"></i> Contacter l'administrateur </a></li>                                                                                    
              </ul>
            </li>            
          </ul>
        </div>
      </div>
    </div>
  <!-- Fin de l'entete fixe de la page -->
  <!--Le corps du site-->
   <div id="sirah-page-body" class="colmask wrapper three-columns container" >  
     <div id="region-main-box" class="sirah-side-middle" > 
        <div id="region-post-box" class="sirah-side-left" >
                  
          <!--Bloc central de la page du site-->
            <div id="sirah-page-mainblock-wrapper" class="col1">                         
                          <!--Le contenu principal la page du site-->
                           <div id="sirah-page-mainblock"  class="container sr_bx" >                            
                           
                             <!--Le bloc du contenu de la page-->
                             <div id="sirah-page-content" class="sirah-page-content" >
                                 <div id="maincontent" class="container maincontent">
                                        <jdoc:include type="component" />
                                 </div>
                             </div>
                             <!--Fin du bloc du contenu de la page-->                           
                           </div>
                         <!--Fin du contenu principal de la page du site-->                                           
         </div>
         <!--Fin du bloc principal de la page du site-->  
     
        <!--Le bloc gauche du corps du site-->
         <div id="sirah-page-leftblock-wrapper" class="col2" >
            <jdoc:include type="modules" name="left"  />
            <jdoc:include type="modules" name="leftmod" style="sirahmodule" />
         </div>  
        <!--Fin du bloc de gauche du site--> 
     
        <!--Le bloc droite du corps du site-->
         <div id="sirah-page-rightblock-wrapper" class="col3" >
            <jdoc:include type="modules" name="right"  />
            <jdoc:include type="modules" name="rightmod" style="sirahmodule"  />
         </div>  
        <!--Fin du bloc de droite du corps du site-->     
        
       </div>
    </div>   
  </div> 
  <!--Fin corps du site-->        
 
  <!--Pied de page du site-->
  <div id="sirah-page-footer" >
    <div id="footerNavigation" class="container" >
      <ul class="navButtons nav navbar nav-pills">
        <li class="dropdown"> 
         <a data-toggle="dropdown" class="linkButton dropdown-toggle" href="#"> <i class="glyphicon glyphicon-globe"></i> Pays : Burkina Faso <b class="caret"></b></a>
                   <ul class="dropdown-menu">
                      <li class="dropdown-header"> Liste des pays </li>                   
                      <li><a href="#"> Burkina Faso  </a></li>
                      <li><a href="#"> Cote d'Ivoire </a></li>
                      <li><a href="#"> Mali </a></li>  
                   </ul>
           </li>
           <li class="dropdown"> 
<a data-toggle="dropdown" class="linkButton dropdown-toggle" href="#"> <i class="glyphicon glyphicon-tasks"></i> Secteur d'activité : Informatique <b class="caret"></b></a>
                   <ul class="dropdown-menu">                   
                      <li><a href="#">Burkina Faso  </a></li>
                      <li><a href="#">Cote d'Ivoire </a></li>
                      <li><a href="#">Mali </a></li>  
                   </ul>
           </li>
           <li class="dropdown"> 
               <a data-toggle="dropdown" class="linkButton dropdown-toggle" href="#"> 
                   <i class="glyphicon glyphicon-comment"></i> Langues : Français <b class="caret"></b></a>
                   <ul class="dropdown-menu">                   
                      <li><a href="#"> Burkina Faso  </a></li>
                      <li><a href="#"> Cote d'Ivoire </a></li>
                      <li><a href="#"> Mali </a></li>  
                   </ul>
           </li>
           <li> <a class="linkButton" href="#"> <i class="glyphicon glyphicon-question-sign "></i> Besoin d'aide ? </a> </a> </li>
        </ul>
        <ul  class="nav nav-pills">
           <li> <a href="#"> A propos </a> </li>
           <li> <a href="#"> Presse et Blogs </a> </li>
           <li> <a href="#"> Conditions d'utilisation    </a> </li>
           <li> <a href="#"> Confidentialité et sécurité </a> </li>
           <li> <a href="#"> Business  </a> </li>
           <li> <a href="#"> Publicité </a> </li>
        </ul>         
    </div>
    <div class="container">
         <p class="text-muted credit">©2014 Copyright SIRAH, Ouagadougou Burkina Faso </p>
    </div>
  </div>
  <!--Fin pied de page du site-->        
  </body>
</html>

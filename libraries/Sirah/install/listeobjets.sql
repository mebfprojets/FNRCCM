VALUES ('list',"lister tous les comptes des utilisateurs",1,1),
       ('create',"Creer le compte dun utilisateur",1,1),
       ('edit',"Mettre a jour le compte dun utilisateur",1,1),
       ('delete',"Supprimer le compte dun utilisateur",1,1),
       ('block',"Bloquer le compte dun utilisateur",1,1),
       ('disblock',"Debloquer le compte dun utilisateur",1,1),
       ('lock',"Verrouiller le compte dun utilisateur",1,1),
       ('unlock',"Deverrouiller le compte dun utilisateur",1,1),
       ('enable',"Activer le compte dun utilisateur",1,1),
       ('disable',"Desactiver le compte dun utilisateur",1,1),
       ('assignroles',"Assigner des roles a un utilisateur",1,1),
       ('infos',"Afficher les informations du compte dun utilisateur",1,1),
       ('changeavatar',"Changer lavatar dun utilisateur",1,1),
       ('disconnect',"Deconnecter un utilisateur de sa session",1,1),
       ('rights',"Affiche les permissions associees au compte dun utilisateur",1,1),
       ('assignrights',"Definir des permissions pour un utilisateur",1,1),
       ('settings',"Acceder aux param�tres de mon compte",1,2),
       ('login',"Ouvrir une session de connexion",1,2),
       ('logout',"Fermer ma session",1,2),
       ('register',"Creer mon compte",1,2),
       ('edit',"Mettre a jour les informations de mon compte",1,2),
       ('changeavatar',"Mettre a jour mon avatar",1,2),
       ('forgotpassword',"Lancer le processus de rappel de mon mot de passe",1,2),
       ('error',"Visualiser les erreurs associees aux operations sur mon compte",1,2),
       ('activate',"Activer mon compte",1,2),
       ('create',"Enregistrer un nouveau role",1,3),
       ('edit',"Mettre a jour les informations dun role",1,3),
       ('delete',"Supprimer un role utilisateur",1,3),
       ('infos',"Acceder aux informations dun role",1,3),
       ('rights',"Afficher les permissions dun role",1,3),
       ('updaterights',"Mettre � jour les permissions d'un role utilisateur",1,3),
       ('list',"Lister les roles de lapplication",1,3),
       ('list',"Lister toutes les permissions du systeme",1,4),
       ('remove',"Supprimer une/des permissions du syst�me",1,4),
       ('infos',"Acceder aux informations dune permission",1,4),
       ('list',"Acceder a toutes mes notifications",1,5),
       ('listall',"Acceder � toutes les notifications du syst�me",1,5),
       ('solve',"Resoudre le probl�me li� � une notification",1,5),
       ('read',"Acceder aux informations dune notification",1,5),
       ('remove',"Supprimer une notification du syst�me",1,5),
       ('infos',"Afficher les informations des caracteristiques du syst�me",1,6),
       ('features',"Acc�der aux ressources du syst�me",1,6),
       ('enablefeature',"Activer une ressource du syst�me",1,6),
       ('disablefeature',"Desactiver une ressource du syst�me",1,6),
       ('featurerights',"Afficher les permissions appliquees a une ressource",1,6),
       ('featureobjects',"Afficher les objets dune ressource",1,6),
       ('featureobjectright',"Afficher les permissions dacc�s a l'objet dune ressource",1,6),
       ('removefeature',"Supprimer une ressource du syst�me",1,6),
       ('removefeatureobject',"Supprimer un objet dune ressource du syst�me",1,6),
       ('infos',"Afficher les informations du profil dun utilisateur",1,7),
       ('edit',"Mettre a jour les donnees du profil dun utilisateur",1,7),
	   ('all',"Tous les privill�ges de la ressource de gestion des comptes des utilisateurs (useraccount)",1,1),
	   ('all',"Tous les privill�ges de la ressource de gestion de mon compte(myaccount)",1,2),
	   ('all',"Tous les privill�ges de la ressource de gestion des roles (useroles)",1,3),
	   ('all',"Tous les privill�ges de la ressource de gestion des permissions (userights) ",1,4),
	   ('all',"Tous les privill�ges de la ressource de gestion des notifisations(usernotification) ",1,5),
	   ('all',"Tous les privill�ges de la ressource de gestion des param�tres syst�me ",1,6),
	   ('all',"Tous les privill�ges de la ressource de gestion du profil dun utilisateur(profile) ",1,7),
	   ('all',"Tous les privill�ges de la ressource de gestion de mon profil (myprofile) ",1,8),
	   ('all',"Tous les privill�ges de la ressource de gestion de mes contacts (contacts) ",1,9);
	   
	   
	   /* ******* On insère les ressources de la plateforme ******** */
INSERT INTO `system_acl_resources` (`resourceid` , `resourcename` , `description` , `parentid` , `creatoruserid` , `moduleid` , `enabled`)
VALUES (11 , 'schoolcontacts' , 'Gestion des contacts de luniversité' , 0 , 1 , 2 , 1) , 
       (12 , 'candidats' , 'Gestion des candidats de luniversité' , 0 , 1 , 2 , 1) , 
       (13 , 'candidatinscription' , 'Gestion des roles des pré-inscriptions' , 0 , 1 , 2 , 1) , 
       (14 , 'schoolemailing' , 'Gestion du système demailing ' , 0 , 1 , 2 , 1) ,        
       /* ******* Module de gestion de la comptabilité ******** */
       (15 , 'schoolfees' , 'Suivi des paiements de frais de scolarité' , 0 , 1 , 3 , 1) , 
       (16 , 'schoolfeesinscription' , 'Gestion des frais dinscription' , 0 , 1 , 3 , 1) , 
       (17 , 'honorairefees' , 'Gestion des paiements honoraires' , 0 , 1 , 3 , 1) , 
       (18 , 'schoolorders' , 'Gestion des commandes' , 0 , 1 , 3 , 1) , 
       (19 , 'achats' , 'Gestion des achats' , 0 , 1 , 3 , 1) ,
       (20 , 'reglements' , 'Gestion des paiements de prestataires' , 0 , 1 , 3 , 1) , 
       (21 , 'prestataires' , 'Gestion des fournisseurs/prestataires' , 0 , 1 , 3 , 1),
       (22 , 'bilancomptable' , 'Bilan des paiements' , 0 , 1 , 3 , 1),
        /* ******* Module de gestion de la scolarité ******** */
       (23 , 'inscriptions' , 'Gestion des inscriptions' , 0 , 1 , 5 , 1) , 
       (24 , 'honoraires' , 'Gestion des honoraires' , 0 , 1 , 5 , 1) , 
       (25 , 'cohortes' , 'Gestion des cohortes' , 0 , 1 , 5 , 1) , 
       (26 , 'statistiques' , 'Statistiques des inscriptions' , 0 , 1 , 5 , 1) , 
        /* ******* Module de gestion des modules de cours ******** */
       (27 , 'coursemodule' , 'Gestion des modules de cours' , 0 , 1 , 6 , 1) , 
       (28 , 'courseinstance' , 'Gestion des instances des cours' , 0 , 1 , 6 , 1) , 
       (29 , 'courseschedule' , 'Programme des cours' , 0 , 1 , 6 , 1) , 
       (30 , 'coursesubscription' , 'Répartition des cours' , 0 , 1 , 6 , 1) ,
       (31 , 'coursecategories' , 'Gestion des catégories des cours' , 0 , 1 , 6 , 1) , 
       (32 , 'courseabsence' , 'Gestion des absences' , 0 , 1 , 6 , 1) ,
       (33 , 'coursedelays' , 'Gestion des retards' , 0 , 1 , 6 , 1) ,
       (34 , 'courselevels' , 'Les niveaux détudes' , 0 , 1 , 6 , 1) ,
       (35 , 'coursefilieres' , 'Les filières des cours' , 0 , 1 , 6 , 1) ,
       /* ******* Module de gestion des ressources pédagogiques ******** */
       (36 , 'classrooms' , 'Gestion des salles de cours' , 0 , 1 , 7 , 1) , 
       (37 , 'classtools' , 'Gestion du matériel de cours' , 0 , 1 , 7 , 1) , 
       (38 , 'classlibrary' , 'Bibliothèque numérique' , 0 , 1 , 7 , 1) , 
       (39 , 'classmemories' , 'Mémoires détudes' , 0 , 1 , 7 , 1) , 
       (40 , 'classdocuments' , 'Gestion des documents' , 0 , 1 , 7 , 1) , 
        /* ******* Module de gestion des examens ******** */
       (41 , 'classexams' , 'Gestion des examens' , 0 , 1 , 7 , 1) , 
       (42 , 'classprep' , 'Gestion des devoirs' , 0 , 1 , 7 , 1) , 
       (43 , 'classprepresult' , 'Gestion des notes' , 0 , 1 , 7 , 1) , 
       (44 , 'classexamresult' , 'Gestion des résultats' , 0 , 1 , 7 , 1) ,       
        /* ******* Module de gestion des examens ******** */
       (45 , 'entreprise' , 'Gestion de la structure' , 0 , 1 , 8 , 1) , 
       (46 , 'entreprisemembers' , 'Gestion du personnel' , 0 , 1 , 8 , 1) , 
       (47 , 'entrepriseservices' , 'Gestion des services' , 0 , 1 , 8 , 1) , 
       (48 , 'entreprisemissions' , 'Gestion des missions' , 0 , 1 , 8 , 1) ;
	   	   
	   
	   INSERT INTO `system_acl_rights` (`objectid` , `roleid` , `userid` , `allow` , `creatoruserid`)
	   VALUES (267 , 10 , 0 , 1 , 1), /** Les commerciaux accèdent à toutes les fonctionnalités de gestion des contacts **/
	          (268 , 10 , 0 , 1 , 1), /** Les commerciaux accèdent à toutes les fonctionnalités de gestion des candidats **/
			  (269 , 10 , 0 , 1 , 1), /** Les commerciaux accèdent à toutes les fonctionnalités de gestion des candidatures **/
			  (270 , 10 , 0 , 1 , 1), /** Les commerciaux accèdent à toutes les fonctionnalités de gestion du système d'emailing **/
			  (271 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des frais de scolarité **/
			  (272 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des frais d'inscriptions **/
			  (273 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des frais d'honoraires **/
			  (274 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des commandes initiées avec les fournisseurs **/
			  (275 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des achats **/
			  (276 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des règlements des prestataires **/
			  (277 , 8 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités de gestion des commandes initiées avec les fournisseurs **/
			  (278 , 7 , 0 , 1 , 1), /** Les comptables accèdent à toutes les fonctionnalités du bilan comptable **/
			  (279 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des inscriptions **/
			  (280 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des honoraires **/
			  (281 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des cohortes **/
			  (282 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des statistiques **/
			  (283 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des modules de cours **/
			  (284 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des instances des cours **/
			  (285 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion de la programmation des cours **/
			  (286 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de l'inscription des cohortes aux cours **/
			  (287 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des catégories de cours **/
			  (288 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des absences aux cours **/
			  (289 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des retards **/
			  (290 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des niveaux d'études **/
			  (291 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des filières **/
			  (292 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des salles de cours **/
			  (293 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion du matériel pédagogique **/
			  (294 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion de la bibliothèque numérique **/
			  (295 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des mémoires d'études **/
			  (296 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des documents des cohortes **/
			  (297 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des examens **/
			  (298 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des devoirs **/
			  (299 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des notes de devoirs **/
			  (300 , 7 , 0 , 1 , 1), /** Les gestionnaires accèdent à toutes les fonctionnalités de gestion des résultats d'examens **/
			  (301 , 2 , 0 , 1 , 1), /** Les administrateurs accèdent à toutes les fonctionnalités de gestion des entreprises **/
			  (302 , 2 , 0 , 1 , 1), /** Les administrateurs accèdent à toutes les fonctionnalités de gestion du personnel de l'entreprise **/
			  (303 , 2 , 0 , 1 , 1), /** Les administrateurs accèdent à toutes les fonctionnalités de gestion des prestations de l'entreprise **/
			  (304 , 2 , 0 , 1 , 1), /** Les administrateurs accèdent à toutes les fonctionnalités de gestion des missions **/
			  (269 , 6 , 0 , 1 , 1) /** Les candidats accèdent à toutes les fonctionnalités de candidatures **/ ;
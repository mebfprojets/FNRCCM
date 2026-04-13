/* Structure de la table des utilisateurs _system_users_account */
DROP TABLE IF EXISTS `system_users_account`;
CREATE TABLE `system_users_account`(
  `userid` int(10) NOT NULL AUTO_INCREMENT , 
  `firstname` varchar(30) NOT NULL , 
  `lastname` varchar(50) NOT NULL , 
  `email` varchar(30) NOT NULL , 
  `username` varchar(150) NOT NULL , 
  `password` varchar(150) CHARACTER SET macce NOT NULL , 
  `phone1`  varchar(30) NOT NULL , 
  `phone2` varchar(30) , 
  `address` varchar(100) , 
  `zipaddress` varchar(100) , 
  `city` varchar(80) , 
  `country` varchar(4) NOT NULL , 
  `language` varchar(20) NOT NULL , 
  `facebookid` varchar(80) , 
  `skypeid` varchar(80) , 
  `sexe` varchar(2) , 
  `expired` tinyint(1) , 
  `connected` tinyint(1) , 
  `activated` tinyint(1) , 
  `blocked` tinyint(1) , 
  `locked` tinyint(1) , 
  `admin` tinyint(1) , 
  `statut` tinyint(1) , 
  `accesstoken` varchar(150) , 
  `logintoken` varchar(150) , 
  `params` mediumtext NOT NULL , 
  `accountlife` bigint(10) , 
  `registeredDate` bigint(10) , 
  `lastConnectedDate` bigint(10) , 
  `lastUpdatedDate` bigint(10) , 
  `lastIpAddress`  varchar(100) , 
  `lastHttpClient` varchar(100) , 
  `lastSessionId` varchar(32) , 
  `creatoruserid`  int(7) , 
  `updateduserid`  int(7) , 
  `nb_connections` tinyint(4) , 
  `avatar` varchar(150) NOT NULL , 
  `areakey` varchar(150) , 
  PRIMARY KEY (`userid`) , 
  UNIQUE  KEY `email` (`email`) , 
  UNIQUE  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stocke les informations des comptes des utilisateurs de lapplication'  AUTO_INCREMENT=1;

/* *******On insère les informations du premier utilisateur qui correspond a l'administrateur******** */
INSERT INTO `system_users_account` (`userid` ,  `firstname` ,  `lastname` ,  `email` ,  `username` ,  `password` ,  `phone1` ,  `phone2` ,  `address` ,  `zipaddress` ,  `city` ,  `country` ,  `language` ,  `facebookid` ,  `skypeid` ,  `sexe` ,  `expired` ,  `connected` ,  `activated` ,  `blocked` ,  `locked` ,  `admin` ,  `statut` ,  `accesstoken` ,  `logintoken` ,  `params` ,  `accountlife` ,  `registeredDate` ,  `lastConnectedDate` ,  `lastUpdatedDate` ,  `lastIpAddress` ,  `lastHttpClient` ,  `lastSessionId` ,  `creatoruserid` ,  `updateduserid` ,  `nb_connections` ,  `avatar` ,  `areakey`) VALUES
(1 ,  'Administrateur' ,  '' ,  'banaohamed@gmail.com' ,  'useradmin' ,  'de5838fcaa61322501ccced938d30d27:vc9rnHqZvnRR' ,  '+22676580423' ,  NULL ,  NULL ,  NULL ,  NULL ,  'BF' ,  'FR' ,  NULL ,  NULL ,  NULL ,  0 ,  1 ,  1 ,  0 ,  0 ,  1 ,  2 ,  NULL ,  '5e2297b62c1' ,  '{''send_notifications'':1;''remember_me'':1}' ,  '2013-11-08 00:03:52' ,  '0000-00-00 00:00:00' ,  '0000-00-00 00:00:00' ,  '0000-00-00 00:00:00' ,  '127.0.0.1' ,  'Google Chrome 30.0.1599.101' ,  'u34u67nenhj2pet2gj965hbndlvf8jms' ,  NULL ,  NULL ,  27 ,  '' ,  NULL) , 
(2 ,  'Mohamed' ,  'Banao' ,  'banaohamed@yahoo.fr' ,  'banaohamed' ,  'de5838fcaa61322501ccced938d30d27:vc9rnHqZvnRR' ,  '+226 76 58 04 23' ,  '5345454564654' ,  '' ,  '' ,  '' ,  'BF' ,  'FR' ,  '' ,  '' ,  'M' ,  0 ,  1 ,  1 ,  0 ,  0 ,  0 ,  1 ,  '' ,  '56b9c455423' ,  '' ,  '2013-11-08 23:26:55' ,  '0000-00-00 00:00:00' ,  '0000-00-00 00:00:00' ,  '0000-00-00 00:00:00' ,  '127.0.0.1' ,  'Mozilla Firefox 25.0' ,  's8s3tkpcpnvbmtqfe57s3iidctmr8r3t' ,  1 ,  3 ,  4 ,  '' ,  NULL);


/* ******* La table des donnees de securite des comptes des utilisateurs ******** */
DROP TABLE IF EXISTS `system_users_account_security`;
CREATE TABLE `system_users_account_security` (
`userid` int(10) NOT NULL , 
`totalcheckauth` int(2) , 
`totalfailedauth` int(2) , 
`lastnbcheckauth` int(2) , 
`password_salt` varchar(100) , 
  PRIMARY KEY (`userid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ******* La table des informations de notifications des utilisateurs ******** */
DROP TABLE IF EXISTS `system_users_account_notification`;
CREATE TABLE `system_users_account_notification` (
`userid` int(10) NOT NULL , 
`notificationid` int(10) NOT NULL , 
`read` tinyint(1) , 
`solved` tinyint(1) , 
  PRIMARY KEY (`userid` , `notificationid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Structure de la table des notifications systèmes */
DROP TABLE IF EXISTS `system_notifications`;
CREATE TABLE `system_notifications` (
`notificationid` int(10) NOT NULL AUTO_INCREMENT  , 
`libelle` varchar(80) NOT NULL , 
`description` varchar(200) NOT NULL , 
`linksolve` varchar(80) NOT NULL , 
`creatoruserid` int(7) , 
`creationdate`  bigint(10) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`notificationid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

/* Structure de la table des utilisateurs assignes a des roles _system_acl_useroles */
DROP TABLE IF EXISTS `system_acl_useroles`;
CREATE TABLE `system_acl_useroles` (
`roleid` int(3) NOT NULL  , 
`userid` int(3) NOT NULL  , 
`creatoruserid` int(7) , 
`creationdate`  bigint(10) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`roleid` , `userid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ******* On assigne le role Superviseur au premier utilisateur enregistre ci-dessus ******** */
INSERT INTO `system_acl_useroles` (`roleid` , `userid` , `creatoruserid`)
VALUES (1 , 1 , 1)  ,  (3 , 2 , 1);

/* Structure de la table des roles du système _system_acl_roles */
DROP TABLE IF EXISTS `system_acl_roles`;
CREATE TABLE `system_acl_roles` (
`roleid` int(3) NOT NULL AUTO_INCREMENT , 
`rolename` varchar(80) NOT NULL , 
`description` varchar(220) , 
`accesslevel` int(2) NOT NULL , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`roleid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stocke les roles des utilisateurs'  AUTO_INCREMENT=1;

/* ******* On insère les roles par defaut de l'application ******** */
INSERT INTO `system_acl_roles` (`roleid` , `rolename` , `description` , `accesslevel` , `creatoruserid`)
VALUES (1 , 'Superviseur' , 'Le role associe aux utilisateurs devant administrer et acceder a toutes le fonctionnalites' , 1 , 1) , 
       (2 , 'Administrateur'  ,  'Correspond aux utilisateurs devant administrer la plateforme ,  mais ne peuvent pas acceder a toutes les fonctionnalites' , 2 , 1) , 
       (3 , 'Utilisateur'  ,  'Correspond a lutilisateur de base de la plateforme'  ,  10  , 1) , 
       (4 , 'Guest'  ,  'Ce role est associe a tous les utilisateurs anonymes nayant pas de compte sur la plateforme' , 100 , 1);

/* Structure de la table des roles parents _system_acl_parentroles */
DROP TABLE IF EXISTS `system_acl_parentroles`;
CREATE TABLE `system_acl_parentroles` (
`childroleid` int(3) NOT NULL , 
`parentroleid` int(3) NOT NULL , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`childroleid` , `parentroleid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ******* On insère les roles parents par defaut de l'application ******** */
INSERT INTO `system_acl_parentroles` (`childroleid` , `parentroleid` , `creatoruserid`)
VALUES (3 , 4 , 1) , (2 , 3 , 1) , (1 , 2 , 1);

/* Structure de la table des modules des ressources système*/
DROP   TABLE IF EXISTS `system_acl_resource_modules`;
CREATE TABLE `system_acl_resource_modules` (
`moduleid` int(3) NOT NULL AUTO_INCREMENT , 
`modulename` varchar(80) NOT NULL , 
`description` varchar(200) NOT NULL , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
`applicationid` int(2) , 
 PRIMARY KEY (`moduleid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

/* ******* On insère les modules par defaut ******** */
INSERT INTO `system_acl_resource_modules` (`moduleid` , `modulename` , `description` , `creatoruserid`)
VALUES (1 , 'usermanagement' , 'Gestion des comptes' , 1);

/* Structure de la table des ressources système*/
DROP   TABLE IF EXISTS `system_acl_resources`;
CREATE TABLE `system_acl_resources` (
`resourceid` int(3) NOT NULL AUTO_INCREMENT , 
`resourcename` varchar(80) NOT NULL , 
`description` varchar(200) NOT NULL , 
`parentid` int(3) , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
`moduleid` int(2) , 
`enabled` int(2) , 
 PRIMARY KEY (`resourceid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

/* ******* On insère les ressources par defaut de l'application******** */
INSERT INTO `system_acl_resources` (`resourceid` , `resourcename` , `description` , `parentid` , `creatoruserid` , `moduleid` , `enabled`)
VALUES (1 , 'useraccount' , 'Gestion des comptes des utilisateurs' , 2 , 1 , 1 , 1) , 
       (2 , 'myaccount' , 'Gestion de mon compte dans le système' , 0 , 1 , 1 , 1) , 
       (3 , 'useroles' , 'Gestion des roles des utilisateurs' , 0 , 1 , 1 , 1) , 
       (4 , 'userights' , 'Gestion des permissions des utilisateurs' , 0 , 1 , 1 , 1) , 
       (5 , 'usernotification' , 'Gestion des notifications' , 0 , 1 , 1 , 1) , 
       (6 , 'system' , 'Gestion des paramètres du système'  ,  0  ,  1  ,  1  ,  1 ) , 
       (7 , 'profile' , 'Gestion des profils des utilisateurs'  ,  0  ,  1  ,  1  ,  1 ) , 
       (8 , 'myprofile' , 'Gestion de mon profil utilisateur'  ,  0  ,  1  ,  1  ,  1 ) , 
       (9 , 'mycontacts' , 'Gestion des contacts associes a mon compte'  ,  0  ,  1  ,  1  ,  1 ),
       (10 , 'cron' , 'Gestion des taches crons de la plateforme'  ,  0  ,  1  ,  1  ,  1);
	   
/* Structure de la table des objets d'acl */
DROP TABLE IF EXISTS `system_acl_objects`;
CREATE TABLE `system_acl_objects` (
`objectid` int(3) NOT NULL AUTO_INCREMENT , 
`objectname` varchar(100) NOT NULL , 
`description`  varchar(200) NOT NULL , 
`resourceid` int(3) NOT NULL , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`objectid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

/* ******* On insère les objets ACL par defaut de l'application*********/
INSERT INTO `system_acl_objects` (`objectid` , `objectname` , `description` , `creatoruserid` , `resourceid`)
VALUES (1 , 'list' , "lister tous les comptes des utilisateurs" , 1 , 1) , 
       (2 ,'create' , "Creer le compte dun utilisateur" , 1 , 1) , 
       (3 , 'edit' , "Mettre a jour le compte dun utilisateur" , 1 , 1) , 
       (4 ,'delete' , "Supprimer le compte dun utilisateur" , 1 , 1) , 
       (5 , 'block' , "Bloquer le compte dun utilisateur" , 1 , 1) , 
       (6 , 'disblock' , "Debloquer le compte dun utilisateur" , 1 , 1) , 
       (7 , 'lock' , "Verrouiller le compte dun utilisateur" , 1 , 1) , 
       (8 , 'unlock' , "Deverrouiller le compte dun utilisateur" , 1 , 1) , 
       (9 , 'enable' , "Activer le compte dun utilisateur" , 1 , 1) , 
       (10 , 'disable' , "Desactiver le compte dun utilisateur" , 1 , 1) , 
       (11 , 'assignroles' , "Assigner des roles a un utilisateur" , 1 , 1) , 
       (12 , 'infos' , "Afficher les informations du compte dun utilisateur" , 1 , 1) , 
       (13 , 'changeavatar' , "Changer lavatar dun utilisateur" , 1 , 1) , 
       (14 , 'disconnect' , "Deconnecter un utilisateur de sa session" , 1 , 1) , 
       (15 , 'rights' , "Affiche les permissions associées au compte dun utilisateur" , 1 , 1) , 
       (16 , 'assignrights' , "Definir des permissions pour un utilisateur" , 1 , 1) , 
       (17 , 'settings' , "Acceder aux paramètres de mon compte" , 1 , 2) , 
       (18 , 'login' , "Ouvrir une session de connexion" , 1 , 2) , 
       (19 , 'logout' , "Fermer ma session" , 1 , 2) , 
       (20 , 'register' , "Creer mon compte" , 1 , 2) , 
       (21 , 'edit' , "Mettre a jour les informations de mon compte" , 1 , 2) , 
       (22 , 'changeavatar' , "Mettre a jour mon avatar" , 1 , 2) , 
       (23 , 'forgotpassword' , "Lancer le processus de rappel de mon mot de passe" , 1 , 2) , 
       (24 ,'error' , "Visualiser les erreurs associees aux operations sur mon compte" , 1 , 2) , 
       (25 , 'activate' , "Activer mon compte" , 1 , 2) , 
       (26 , 'create' , "Enregistrer un nouveau role" , 1 , 3) , 
       (27 , 'edit' , "Mettre a jour les informations dun role" , 1 , 3) , 
       (28 , 'delete' , "Supprimer un role utilisateur" , 1 , 3) , 
       (29 ,'infos' , "Acceder aux informations dun role" , 1 , 3) , 
       (30 ,'rights' , "Afficher les permissions dun role" , 1 , 3) , 
       (31 , 'updaterights' , "Mettre à jour les permissions d'un role utilisateur" , 1 , 3) , 
       (32 , 'list' , "Lister les roles de lapplication" , 1 , 3) , 
       (33 , 'list' , "Lister toutes les permissions du systeme" , 1 , 4) , 
       (34 , 'remove' , "Supprimer une/des permissions du système" , 1 , 4) , 
       (35 , 'infos' , "Acceder aux informations dune permission" , 1 , 4) , 
       (36 , 'list' , "Acceder a toutes mes notifications" , 1 , 5) , 
       (37 , 'listall' , "Acceder à toutes les notifications du système" , 1 , 5) , 
       (38 , 'solve' , "Resoudre le problème lié à une notification" , 1 , 5) , 
       (39 , 'read' , "Acceder aux informations dune notification" , 1 , 5) , 
       (40 , 'remove' , "Supprimer une notification du système" , 1 , 5) , 
       (41 , 'infos' , "Afficher les informations des caracteristiques du système" , 1 , 6) , 
       (42 , 'features' , "Accéder aux ressources du système" , 1 , 6) , 
       (43 , 'enablefeature' , "Activer une ressource du système" , 1 , 6) , 
       (44 , 'disablefeature' , "Desactiver une ressource du système" , 1 , 6) , 
       (45 , 'featurerights' , "Afficher les permissions appliquees a une ressource" , 1 , 6) , 
       (46 , 'featureobjects' , "Afficher les objets dune ressource" , 1 , 6) , 
       (47 , 'featureobjectright' , "Afficher les permissions daccès a l'objet dune ressource" , 1 , 6) , 
       (48 , 'removefeature' , "Supprimer une ressource du système" , 1 , 6) , 
       (49 , 'removefeatureobject' , "Supprimer un objet dune ressource du système" , 1 , 6) , 
       (50 , 'infos', "Afficher les informations du profil dun utilisateur" , 1 , 7) , 
       (51 , 'edit', "Mettre a jour les donnees du profil dun utilisateur" , 1 , 7) , 
	   (52 , 'all' , "Tous les privillèges de la ressource de gestion des comptes des utilisateurs (useraccount)" , 1 , 1) , 
	   (53 , 'all' , "Tous les privillèges de la ressource de gestion de mon compte(myaccount)" , 1 , 2) , 
	   (54 , 'all' , "Tous les privillèges de la ressource de gestion des roles (useroles)" , 1 , 3) , 
	   (55 , 'all' , "Tous les privillèges de la ressource de gestion des permissions (userights) " , 1 , 4) , 
	   (56 , 'all' , "Tous les privillèges de la ressource de gestion des notifisations(usernotification) " , 1 , 5) , 
	   (57 , 'all' , "Tous les privillèges de la ressource de gestion des paramètres système " , 1 , 6) , 
	   (58 , 'all' , "Tous les privillèges de la ressource de gestion du profil dun utilisateur(profile) " , 1 , 7) , 
	   (59 , 'all' , "Tous les privillèges de la ressource de gestion de mon profil (myprofile) " , 1 , 8) , 
	   (60 , 'all' , "Tous les privillèges de la ressource de gestion de mes contacts (mycontacts) " , 1 , 9) , 
	   (61 , 'all' , "Tous les privillèges de la ressource de gestion des taches crons" , 1 , 10) ,
	   (62 , 'list', "Lister les profils des utilisateurs" , 1 , 7);

/* Structure de la table des permissions associ�es aux differents roles*/
DROP TABLE IF EXISTS `system_acl_rights`;
CREATE TABLE `system_acl_rights` (
`objectid` int(3) NOT NULL , 
`roleid` int(3) , 
`userid` int(7) , 
`allow` tinyint(1) , 
`creationdate`  bigint(10) , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`objectid` , `roleid` , `userid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* On insère les permissions par défaut de l'application */
INSERT INTO `system_acl_rights` (`objectid` , `roleid` , `userid` , `allow` , `creatoruserid`)
      /**** Les permissions du role Guest (Les invités, ils n'ont pas de compte) ****/
VALUES (18 , 4 , 0 , 1 , 1) , /** on autorise les invités à ouvrir une session **/
       (20 , 4 , 0 , 1 , 1) , /** on autorise les invités à créer leur compte **/
       (23 , 4 , 0 , 1 , 1) , /** on autorise les invités à initier le processus de rappel du mot de passe **/
       (24 , 4 , 0 , 1 , 1) , /** on autorise les invités à accéder aux erreurs relatives à la ressource myaccount **/
       (25 , 4 , 0 , 1 , 1) , /** on autorise les invités à activer leur compte **/       
       /**** Les permissions du role Administrateur (Le simple administrateur)  ****/
       (1 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à lister les comptes des utilisateurs **/
       (2 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à créer les comptes des utilisateurs **/
       (3 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à mettre à jour les comptes des utilisateurs **/
       (12 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à visualiser les informations des comptes des utilisateurs **/
       (13 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à changer l'avatar des utilisateurs **/ 
       (14 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à déconnecter un utilisateur de sa session **/ 
       (15 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à accéder aux permissions du compte d'un utilisateur **/ 
       (16 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à définir des permissions pour les comptes des utilisateurs **/ 
       (17 , 2 , 0 , 1 , 1) , /** on autorise l'administrateur à accerder aux parmètres du compte d'un utilisateur **/        
       (54 , 2 , 0 , 1 , 1) , /** all pour useroles 54 **/
       (55 , 2 , 0 , 1 , 1) , /** all pour userights 55 **/
       (56 , 2 , 0 , 1 , 1) , /** all pour usernotification 56 **/       
       (57 , 2 , 0 , 1 , 1) , /** all pour system 57 **/  
       (58 , 2 , 0 , 1 , 1) , /** all pour profile 58 **/ 
       /**** Les permissions du role Superadmin (L'administrateur le plus elevé) ****/
       (4 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à supprimer les comptes des utilisateurs **/
       (5 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à bloquer les comptes des utilisateurs **/
       (6 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à débloquer les comptes des utilisateurs **/
       (7 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à verrouiller les comptes des utilisateurs **/
       (8 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à deverrouiller les comptes des utilisateurs **/
       (9 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à activer les comptes des utilisateurs **/
       (10 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à desactiver les comptes des utilisateurs **/
       (11 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à assigner des roles aux utilisateurs **/
       (61 , 1 , 0 , 1 , 1) , /** on autorise le superadministrateur à exécuter des taches crons **/
       /**** Les permissions du role Utilisateur (Un simple utilisateur) ****/
       (59 , 3 , 0 , 1 , 1) , /** on autorise le simple utilisateur à accéder à son profil **/
       (17 , 3 , 0 , 1 , 1) , /** on autorise le simple utilisateur à accéder aux paramètres de son compte **/
       (19 , 3 , 0 , 1 , 1) , /** on autorise le simple utilisateur à fermer sa session **/
       (21 , 3 , 0 , 1 , 1) , /** on autorise le simple utilisateur à mettre à jour les informations de son compte **/
       (22 , 3 , 0 , 1 , 1) , /** on autorise le simple utilisateur à changer son avatar **/
       (60 , 3 , 0 , 1 , 1)  /** on autorise le simple utilisateur à gérer ses contacts **/;

/* Structure de la table des paramètres d'assertion ou de validation des permissions*/
DROP TABLE IF EXISTS `system_acl_rights_params`;
CREATE TABLE `system_acl_rights_params` (
`paramid` int(3) NOT NULL AUTO_INCREMENT , 
`rightid` int(3) NOT NULL , 
`roleid` int(3) NOT NULL , 
`paramkey` varchar(80) NOT NULL , 
`paramvalue` varchar(200) NOT NULL , 
`creationdate` bigint(10) NOT NULL , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`paramid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


/* Structure de la table des informations sur les differentes applications*/
DROP   TABLE IF EXISTS `system_applications`;
CREATE TABLE `system_applications` (
`applicationid` int(3) NOT NULL AUTO_INCREMENT , 
`applicationame` varchar(80) NOT NULL , 
`creationdate` bigint(10) NOT NULL , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`applicationid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


/* Structure de la table des thèmes de l'application */
DROP TABLE IF EXISTS `system_layout`;
CREATE TABLE `system_layout` (
`layoutid` int(3) NOT NULL AUTO_INCREMENT , 
`layoutname` varchar(100) NOT NULL , 
`layoutpath` varchar(100) NOT NULL , 
`description`  varchar(200) NOT NULL , 
`creationdate` bigint(10) NOT NULL , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
`applicationid` int(2) , 
 PRIMARY KEY (`layoutid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8  AUTO_INCREMENT=1;


/* Structure de la table des menus de thèmes de l'application */
DROP TABLE IF EXISTS `system_layout_menu`;
CREATE TABLE `system_layout_menu` (
`menuid` int(3) NOT NULL AUTO_INCREMENT , 
`menuname` varchar(100) NOT NULL , 
`description`  varchar(200) , 
`position` varchar(100) , 
`layoutid` int(3) , 
`creationdate` bigint(10) NOT NULL , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`menuid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `system_layout_menu` (`menuid` , `menuname` , `description` , `position` , `creatoruserid` )
VALUES (1  ,  "Administration Système"  ,  "Le module dadministration système de l'application"  ,  "right"  ,  1 )  , 
       (2  ,  "Gestion de la structure"  ,  "Permet de gérer les informations de l'instance de l'application"  ,  "right"  ,  1 ) ; 

/* Structure de la table des elements des menus de thèmes de l'application */
DROP TABLE IF EXISTS `system_layout_menu_element`;
CREATE TABLE `system_layout_menu_element` (
`elementid`   int(3) NOT NULL AUTO_INCREMENT , 
`elementname` varchar(100) NOT NULL , 
`elementlink` varchar(100) NOT NULL , 
`description` varchar(200) NOT NULL , 
`elementicon` varchar(10) , 
`aclobjectid` int(3) , 
`menuid` int(3) , 
`elementorder` int(2) , 
`creationdate` bigint(10) NOT NULL , 
`creatoruserid` int(7) , 
`areakey` varchar(150) , 
 PRIMARY KEY (`elementid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `system_layout_menu_element` (`elementid` , `elementname`  ,  `elementlink` , `description` , `elementicon` , `aclobjectid` , `menuid`  ,  `elementorder`  , `creatoruserid` )
VALUES (1  ,  "Gestion des comptes"  , ""  ,  "Gérer les comptes des utilisateurs"  , "icon-user"  ,  1  ,  1  ,  1  ,  1 )  , 
       (2  ,  "Gestion des roles"  , ""  ,  "Gérer les roles associés aux comptes des utilisateurs"  , "icon-user"  ,  32  ,  1  ,  2  ,  1 )  , 
	   (3  ,  "Gestion des permissions"  , ""  ,  "Gérer les permissions attribuées aux utilisateurs"  , "icon-user"  ,  33  ,  1  ,  3  ,  1 );

/* ******* La table de stockage des donnees de session ******** */
DROP TABLE IF EXISTS `system_users_session`;
CREATE TABLE `system_users_session` (
  `session_id` varchar(32) NOT NULL , 
  `modified` int(11) NOT NULL , 
  `vie` int(11) NOT NULL , 
  `donnees` text NOT NULL , 
  `session_name` varchar(64) NOT NULL , 
  `save_path` varchar(64) NOT NULL , 
  `areakey` varchar(150) , 
  PRIMARY KEY (`session_id` , `session_name` , `save_path`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ******* La table de stockage des donnees de journalisation ******** */
DROP TABLE IF EXISTS `system_journal`;
CREATE TABLE `system_journal` (
  `msg` mediumtext CHARACTER SET utf8 NOT NULL , 
  `niveau` int(11) NOT NULL , 
  `priorityName` varchar(100) CHARACTER SET utf8 NOT NULL , 
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP , 
  `pid` int(11) NOT NULL , 
  `user` varchar(100) CHARACTER SET utf8 NOT NULL , 
  `id` int(11) NOT NULL AUTO_INCREMENT , 
  `id_aplication` int(11) , 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `system_parametres`;
CREATE TABLE `system_parametres` (
  `id` int(11) NOT NULL AUTO_INCREMENT , 
  `type` varchar(150) NOT NULL , 
  `paramname` varchar(200) NOT NULL , 
  `paramval` varchar(200) NOT NULL , 
  `paramtypeid` int(11) NOT NULL , 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
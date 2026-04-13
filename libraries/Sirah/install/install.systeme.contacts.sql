
/* Structure de la table des categories des contacts des utilisateurs */
DROP TABLE IF EXISTS `system_users_contact_categories`;
CREATE TABLE `system_users_contact_categories` (
  `categoryid` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des categories des contacts des utilisateurs' AUTO_INCREMENT=1;

/* Structure de la table des contacts des utilisateurs */
DROP TABLE IF EXISTS `system_users_contacts`;
CREATE TABLE `system_users_contacts`(
  `contactid` int(10) NOT NULL AUTO_INCREMENT,
  `categoryid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `firstname` varchar(30) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `phone`  varchar(30) NOT NULL,
  `mobile` varchar(30),
  `address` varchar(100),
  `entreprise` varchar(100),
  `zipaddress` varchar(100),
  `city` varchar(80),
  `country` varchar(4) NOT NULL,
  `creatoruserid` int(7),
  `creationdate`  bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`contactid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des contacts des utilisateurs' AUTO_INCREMENT=1;

/* Structure de la table des coordonnées des profils des utilisateurs */
DROP TABLE IF EXISTS `system_users_contact_address`;
CREATE TABLE `system_users_contact_address` (
  `contactid` int(10) NOT NULL,
  `email` varchar(90) NOT NULL,
  `tel_bureau` varchar(20),
  `tel_mob` varchar(20),
  `tel_dom` varchar(20),
  `code_postal` varchar(60),
  `rue` varchar(60),
  `address` varchar(90),
  `city` varchar(80),
  `country` varchar(10),
  `department` varchar(90),
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
 PRIMARY KEY (`contactid`) ,
 UNIQUE  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des coordonnées associées aux profils des utilisateurs';


/* Structure de la table des carrieres des profils des utilisateurs */
DROP TABLE IF EXISTS `system_users_contact_carreer`;
CREATE TABLE `system_users_contact_carreer` (
  `carreerid` int(10) NOT NULL AUTO_INCREMENT,
  `contactid` int(10) NOT NULL,
  `fk_id_entreprise` int(5),
  `fk_id_profession` int(5),
  `description` varchar(200) NOT NULL,
  `current` tinyint(1),
  `secteur` varchar(150) NOT NULL,
  `departement` varchar(150) NOT NULL,
  `email` varchar(20),
  `ville` varchar(80),
  `country` varchar(20),
  `date_debut` bigint(10),
  `date_fin` bigint(10),
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
 PRIMARY KEY (`carreerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations sur la carrière des profils' AUTO_INCREMENT=1;

/* Structure de la table des entreprises associées aux carrières */
DROP TABLE IF EXISTS `system_users_contact_carreer_employers`;
CREATE TABLE `system_users_contact_carreer_employers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150) NOT NULL,
  `description` varchar(200),
  `effectif` varchar(100),
  `address` varchar(200),
  `telephone` varchar(20),
  `email` varchar(80),
  `code_postal` varchar(80),
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des entreprises employeurs' AUTO_INCREMENT=1;


/* Structure de la table des professions associées aux carrières */
DROP TABLE IF EXISTS `system_users_contact_carreer_profession`;
CREATE TABLE `system_users_contact_carreer_profession` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150) NOT NULL,
  `description` varchar(200),
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des professions associées aux carrières' AUTO_INCREMENT=1;

/* Structure de la table des carrieres des profils des utilisateurs */
DROP TABLE IF EXISTS `system_users_contact_formation`;
CREATE TABLE `system_users_contact_formation` (
  `formationid` int(10) NOT NULL AUTO_INCREMENT,
  `contactid` int(10) NOT NULL,
  `annee` int(4),
  `universite` varchar(150) NOT NULL,
  `intitule` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `ville` varchar(80),
  `country` varchar(20),
  `diplome` varchar(80),
  `documentid` int(4),
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
 PRIMARY KEY (`formationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations sur le parcours de formation des utilisateurs' AUTO_INCREMENT=1;

/* Structure de la table des documents des utilisateurs */
DROP TABLE IF EXISTS `system_users_contact_documents`;
CREATE TABLE `system_users_contact_documents` (
  `documentid` int(5) NOT NULL AUTO_INCREMENT,
  `contactid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `type` varchar(150) NOT NULL,
  `keys` varchar(150) NOT NULL,
  `filename` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creatoruserid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`documentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Stocke les informations des documents associés au profil des utilisateurs' AUTO_INCREMENT=1;



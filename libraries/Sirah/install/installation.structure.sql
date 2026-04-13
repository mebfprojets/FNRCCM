DROP TABLE IF EXISTS `system_structure_users_poste`;
CREATE TABLE `system_structure_users_poste` (
  `posteid` int(11) NOT NULL,
  `personnelid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  PRIMARY KEY (`posteid`,`personnelid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `system_structure_infos`;
CREATE TABLE `system_structure_infos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(200) NOT NULL,
  `telephone` varchar(200) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `slogan` varchar(200) NOT NULL,
  `capital` float NOT NULL,
  `objectif` mediumtext NOT NULL,
  `mission` mediumtext NOT NULL,
  `country` int(11) NOT NULL,
  `responsable` int(11) NOT NULL,
  `codepostal` varchar(10) NOT NULL,
  `siteweb` varchar(200) NOT NULL,
  `fax` varchar(200) NOT NULL,
  `ville` varchar(200) NOT NULL,
  `devise` varchar(10) NOT NULL,
  `note` mediumtext NOT NULL,
  `reference` varchar(100) NOT NULL,
  `ifu` varchar(200) NOT NULL,
  `ass_tva` tinyint(1) NOT NULL,
  `userid` int(11) NOT NULL,
  `id_aplication` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `system_structure_personnel`;
CREATE TABLE `system_structure_personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricule` varchar(80) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `telephone` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `cnib` varchar(200) NOT NULL,
  `typecontrat` int(11) NOT NULL,
  `firstname` varchar(150) NOT NULL,
  `lastname` varchar(150) NOT NULL,
  `birthday` bigint(10),
  `birthaddress` varchar(150),
  `presentation` varchar(210),
  `language` varchar(40),
  `socialstate` varchar(120),
  `professionalstate` varchar(120),
  `sexe` varchar(2) ,
  `creatorid` int(7),
  `updateduserid` int(7),
  `creationdate`  bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`,`matricule`) 
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `system_structure_poste`;
CREATE TABLE `system_structure_poste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creatorid` int(7),
  `updateduserid` int(7),
  `creationdate`  bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `system_structure_prestation`;
CREATE TABLE `system_structure_prestation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(200) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `description` mediumtext NOT NULL,
  `responsableid` int(11) NOT NULL,
  `objectif` varchar(200) NOT NULL,
  `creatorid` int(7),
  `updateduserid` int(7),
  `creationdate`  bigint(10),
  `updatedate`  bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


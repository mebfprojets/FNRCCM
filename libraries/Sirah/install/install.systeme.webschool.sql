/* Structure de la table des  des cohortes */
DROP TABLE IF EXISTS `webschool_cohorte`;
CREATE TABLE `webschool_cohorte` (
  `id` int(10) NOT NULL AUTO_INCREMENT ,
  `libelle` varchar(100) NOT NULL,
  `secteur` varchar(100) NOT NULL,
  `description` varchar(200) NOT NULL,
  `filiereid` int(5),
  `niveauid` int(5),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des filières associées aux  cohortes */
DROP TABLE IF EXISTS `webschool_cohorte_filiere`;
CREATE TABLE `webschool_cohorte_filiere` (
  `id` int(10) NOT NULL AUTO_INCREMENT ,
  `libelle` varchar(100) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des niveaux d'étude associés aux  cohortes */
DROP TABLE IF EXISTS `webschool_cohorte_level`;
CREATE TABLE `webschool_cohorte_level` (
  `id` int(10) NOT NULL AUTO_INCREMENT ,
  `libelle` varchar(100) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des inscriptions des étudiants */
DROP TABLE IF EXISTS `webschool_users_inscription`;
CREATE TABLE `webschool_users_inscription` (
  `inscriptionid` int(10) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `cohortid` int(10) NOT NULL,
  `groupid` int(10),
  `dateinscription` bigint(10) NOT NULL,
  `annee` tinyint(4) NOT NULL,
  `periode_start` bigint(10) NOT NULL,
  `periode_end` bigint(10) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`inscriptionid` , `cohortid` , `userid` , `groupid` , `annee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci  AUTO_INCREMENT=1;

/* Structure de la table des documents associés aux inscriptions des étudiants */
DROP TABLE IF EXISTS `webschool_users_inscription_documents`;
CREATE TABLE `webschool_users_inscription_documents` (
  `inscriptionid` int(10) NOT NULL,
  `documentid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`inscriptionid` , `documentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

/* Structure de la table des paiements des frais d'inscriptions des étudiants */
DROP TABLE IF EXISTS `webschool_users_inscription_paiements`;
CREATE TABLE `webschool_users_inscription_paiements` (
  `paiementid` int(10) NOT NULL AUTO_INCREMENT,
  `inscriptionid` int(10) NOT NULL,
  `datepaiement` bigint(10) NOT NULL,
  `nextdate` bigint(10) NOT NULL,
  `montant` float(8) NOT NULL,
  `total` float(8) NOT NULL,
  `reste` float(8) NOT NULL,
  `type` varchar(100) NOT NULL,
  `modereglement` tinyint(1),
  `documentid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid` , `inscriptionid` , `datepaiement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci  AUTO_INCREMENT=1;

/* Structure de la table des candidatures */
DROP TABLE IF EXISTS `webschool_users_candidature`;
CREATE TABLE `webschool_users_candidature` (
  `candidatureid` int(10) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `cohortid` int(10) NOT NULL,
  `datecandidature` bigint(10) NOT NULL,
  `annee` tinyint(4) NOT NULL,
  `periode_start` bigint(10) NOT NULL,
  `periode_end` bigint(10) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`candidatureid` , `cohortid` , `userid` , `annee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

/* Structure de la table des documents associés aux candidatures des étudiants */
DROP TABLE IF EXISTS `webschool_users_candidature_documents`;
CREATE TABLE `webschool_users_candidature_documents` (
  `candidatureid` int(10) NOT NULL,
  `documentid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`candidatureid` , `documentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

/* Structure de la table des paiements des frais de candidature des étudiants */
DROP TABLE IF EXISTS `webschool_users_candidature_paiements`;
CREATE TABLE `webschool_users_candidature_paiements` (
  `paiementid` int(10) NOT NULL AUTO_INCREMENT,
  `candidatureid` int(10) NOT NULL,
  `datepaiement` bigint(10) NOT NULL,
  `nextdate` bigint(10) NOT NULL,
  `montant` float(8) NOT NULL,
  `total` float(8) NOT NULL,
  `reste` float(8) NOT NULL,
  `type` varchar(30) NOT NULL,
  `modereglement` tinyint(1),
  `documentid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid` , `candidatureid` , `datepaiement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;

/* Structure de la table des abonnements aux cours */
DROP TABLE IF EXISTS `webschool_course_subscription`;
CREATE TABLE `webschool_course_subscription` (
  `courseid` int(10) NOT NULL ,
  `userid` int(10) NOT NULL,
  `cohortid` int(10) NOT NULL,
  `groupid` int(10) NOT NULL,
  `cout_unit` float(8) NOT NULL,
  `cout_total` float(8) NOT NULL,
  `periodidicty` tinyint(2),
  `periodidicty_format` tinyint(1),
  `duree` tinyint(2),
  `duree_format` tinyint(1),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`courseid` , `cohortid` , `userid` , `groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

/* Structure de la table des  des absences aux cours */
DROP TABLE IF EXISTS `webschool_course_absence`;
CREATE TABLE `webschool_course_absence` (
  `instanceid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `motif` varchar(200) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`userid` , `instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Structure de la table des  des instances de cours (calendrier) */
DROP TABLE IF EXISTS `webschool_course_instance`;
CREATE TABLE `webschool_course_instance` (
  `courseid` int(10) NOT NULL ,
  `userid` int(10) NOT NULL,
  `cohortid` int(10) NOT NULL,
  `groupid` int(10),
  `salleid` int(7),
  `startime` bigint(10) NOT NULL,
  `endtime` bigint(10) NOT NULL,
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`courseid` , `cohortid` , `userid` , `groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Structure de la table des  des ressources associées aux instances de cours */
DROP TABLE IF EXISTS `webschool_course_instance_resources`;
CREATE TABLE `webschool_course_instance_resources` (
  `resourceid` int(10) NOT NULL AUTO_INCREMENT ,
  `instanceid` int(10) NOT NULL,
  `objectif` varchar(100) NOT NULL,
  `commentaire` varchar(100) NOT NULL,
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`resourceid` , `instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des  des ressources associées aux instances de cours */
DROP TABLE IF EXISTS `webschool_course_instance_documents`;
CREATE TABLE `webschool_course_instance_documents` (
  `documentid` int(10) NOT NULL AUTO_INCREMENT ,
  `instanceid` int(10) NOT NULL,
  `teacherid` int(10) NOT NULL,
  `objectif` varchar(100) NOT NULL,
  `commentaire` varchar(100) NOT NULL,
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`documentid` , `instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;


/* Structure de la table des  des modules de cours */
DROP TABLE IF EXISTS `webschool_course_module`;
CREATE TABLE `webschool_course_module` (
  `courseid` int(10) NOT NULL AUTO_INCREMENT ,
  `categoryid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `objectif` varchar(150) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`courseid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des  des catégories de modules de cours */
DROP TABLE IF EXISTS `webschool_course_module_categories`;
CREATE TABLE `webschool_course_module_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT ,
  `libelle` varchar(150) NOT NULL,
  `description` varchar(200) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des  des honoraires des modules de cours */
DROP TABLE IF EXISTS `webschool_course_module_honoraires`;
CREATE TABLE `webschool_course_module_honoraires` (
  `honoraireid` int(10) NOT NULL AUTO_INCREMENT,
  `courseid` int(10) NOT NULL ,
  `teacherid` int(10) NOT NULL,
  `cohortid` int(10) NOT NULL,
  `groupid` int(10) NOT NULL,
  `annee` tinyint(4) NOT NULL,
  `cout` float(8) NOT NULL,
  `duree` tinyint(2),
  `duree_format` tinyint(1),
  `libelle` varchar(150) NOT NULL,
  `teacheralias` varchar(150),
  `params` varchar(150),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`honoraireid` , `courseid` , `cohortid` , `teacherid` , `groupid` , `annee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;

/* Structure de la table des paiements des honoraires des enseignants */
DROP TABLE IF EXISTS `webschool_module_honoraire_paiements`;
CREATE TABLE `webschool_module_honoraire_paiements` (
  `paiementid` int(10) NOT NULL AUTO_INCREMENT,
  `honoraireid` int(10) NOT NULL,
  `teacherid` int(7),
  `datepaiement` bigint(10) NOT NULL,
  `montant` float(8) NOT NULL,
  `total` float(8) NOT NULL,
  `reste` float(8) NOT NULL,
  `type` varchar(100) NOT NULL,
  `modereglement` tinyint(1),
  `documentid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid` , `honoraireid` , `teacherid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci  AUTO_INCREMENT=1;

/* Structure de la table des paiements des honoraires des enseignants */
DROP TABLE IF EXISTS `webschool_module_honoraire_paiements_instance`;
CREATE TABLE `webschool_module_honoraire_paiements_instance` (
  `paiementid` int(10) NOT NULL,
  `instanceid` int(10) NOT NULL,
  `creatorid` int(7),
  `creationdate` bigint(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid` , `instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/* Structure de la table des  des devoirs */
DROP TABLE IF EXISTS `webschool_course_module_devoirs`;
CREATE TABLE `webschool_course_module_devoirs` (
  `devoirid` int(10) NOT NULL  AUTO_INCREMENT ,
  `courseid` int(10) NOT NULL,
  `teacherid` int(10) NOT NULL,
  `examid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `objectif` varchar(150) NOT NULL,
  `description` varchar(200),
  `bareme` float(3) NOT NULL,
  `minpoints` float(3) NOT NULL,
  `startime` bigint(10) NOT NULL,
  `endtime` bigint(10) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`devoirid`,  `courseid` , `teacherid` , `examid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;


/* Structure de la table des  des examens */
DROP TABLE IF EXISTS `webschool_course_module_exam`;
CREATE TABLE `webschool_course_module_exam` (
  `examid` int(10) NOT NULL  AUTO_INCREMENT ,
  `cohortid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `groupid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `description` varchar(200),
  `minpoints` float(3) NOT NULL,
  `totalpoints` float(3) NOT NULL,
  `periode_start` bigint(10) NOT NULL,
  `periode_end` bigint(10) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`examid`,  `cohortid` , `userid` , `groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;


/* Structure de la table des  resultats des devoirs */
DROP TABLE IF EXISTS `webschool_course_module_devoirs_resultats`;
CREATE TABLE `webschool_course_module_devoirs_resultats` (
  `resultatid` int(10) NOT NULL  AUTO_INCREMENT ,
  `devoirid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `teacherid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `observations` varchar(200),
  `appreciations` varchar(200),
  `note` float(3) NOT NULL,
  `viewed` tinyint(1),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`resultatid`,  `devoirid` , `userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci  AUTO_INCREMENT=1 ;


/* Structure de la table des  resultats des devoirs */
DROP TABLE IF EXISTS `webschool_course_module_exam_resultats`;
CREATE TABLE `webschool_course_module_exam_resultats` (
  `resultatid` int(10) NOT NULL  AUTO_INCREMENT ,
  `examid` int(10) NOT NULL,
  `userid` int(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `observations` varchar(200),
  `moyenne` float(3) NOT NULL,
  `bareme` float(3) NOT NULL,
  `validated` tinyint(1),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`resultatid`,  `examid` , `userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci  AUTO_INCREMENT=1 ;

DROP   TABLE IF EXISTS `webschool_users_paiement_cheque`;
CREATE TABLE `webschool_users_paiement_cheque` (
  `paiementid` int(11) NOT NULL,
  `numero` varchar(200) NOT NULL,
  `date` bigint(10) NOT NULL,
  `lieu` varchar(200) NOT NULL,
  `banque` varchar(200),
  `montant` float,
  `numcompte` varchar(200) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `webschool_users_paiement_virement`;
CREATE TABLE `webschool_users_paiement_virement` (
  `paiementid` int(11) NOT NULL,
  `date` bigint(10) NOT NULL,
  `numero` varchar(200) NOT NULL,
  `numcompte_debiteur` varchar(200) NOT NULL,
  `numcompte_reception` varchar(200) NOT NULL,
  `banque` varchar(200),
  `montant` float,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`paiementid`)
  )ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
DROP   TABLE IF EXISTS `webschool_commercial_produit`;
CREATE TABLE `webschool_commercial_produit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150)  NOT NULL,
  `description` mediumtext NOT NULL,
  `prix_vente_ttc` float NOT NULL,
  `prix_vente_ht` float NOT NULL,
  `prix_achat_ht` float NOT NULL,
  `prix_achat_ttc` float NOT NULL,
  `parametres` mediumtext,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `webschool_commercial_commande`;
CREATE TABLE `webschool_commercial_commande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestataireid` int(11) NOT NULL,
  `reference` varchar(200)  NOT NULL,
  `date` bigint(10) NOT NULL,
  `datelivraison` bigint(10),
  `valeur` float NOT NULL,
  `frais` float NOT NULL,
  `note` varchar(200),
  `statut` tinyint(1),
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `webschool_commercial_commande_ligne`;
CREATE TABLE `webschool_commercial_commande_ligne` (
  `produitid` int(11) NOT NULL,
  `commandeid` int(11) NOT NULL,
  `quantite` int(7) NOT NULL,
  `prix_unit` float NOT NULL,
  `valeur` float NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`produitid`,`commandeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `webschool_commercial_prestataire`;
CREATE TABLE `webschool_commercial_prestataire` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150)  NOT NULL,
  `activite` varchar(200) NOT NULL,
  `adresse` varchar(200) NOT NULL,
  `telephone` varchar(28) NOT NULL,
  `email` varchar(68) NOT NULL,
  `fax` varchar(28) NOT NULL,
  `code_postal` varchar(28) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `webschool_commercial_prestataire_produit`;
CREATE TABLE `webschool_commercial_prestataire_produit` (
  `produitid` int(11) NOT NULL,
  `prestataireid` int(11) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`prestataireid`,`produitid`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP   TABLE IF EXISTS `webschool_commercial_reglement`;
CREATE TABLE `webschool_commercial_reglement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commandeid` int(11) NOT NULL,
  `numfacture` varchar(100),
  `reference` varchar(200),
  `date` bigint(10) NOT NULL,
  `montant` float NOT NULL,
  `montant_total` float NOT NULL,
  `solde` float NOT NULL,
  `modereglement` tinyint(1) NOT NULL,
  `creationdate` bigint(10),
  `updatedate` bigint(10),
  `creatorid` int(10),
  `updateduserid` int(10),
  `areakey` varchar(150),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



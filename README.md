# Fichier National RCCM

Application de gestion du Registre du Commerce et du Crédit Mobilier.

## Technologies
- Zend Framework 1.12
- jQuery
- TCPDF
- Client LIGDICASH
- TINYMCE
- SIRAH

## Installation

1. Cloner le projet
2. Rendez-vous dans le fichier myV1/cfg/application.ini pour renseigner:
   2.1. Les informations de la base de données dans la section `resources.multidb`
   2.2. Les Informations du client de messagerie dans la section `resources.mail`
   2.3 Dans la section phpsettings pour définir les options de PHP
3. Allez ensuite dans le fichier myV1/cfg/application.php pour renseigner les chemins des fichiers de config et des dossiers des librairies ainsi que quelques constantes
4. Lancer l'application

## Sécurité
Les fichiers de configuration sensibles ne sont pas inclus.
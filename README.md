# Simple École

Application de gestion scolaire développée avec Laravel (PHP 8.3). Elle est conçue pour des écoles francophones et fonctionne en mode multi-écoles (SaaS) : plusieurs établissements peuvent utiliser la même instance, chacun étant isolé au niveau des données.

## Fonctionnalités principales

- **Élèves et inscriptions** : gestion des élèves, des classes, des inscriptions et des années scolaires.
- **Notes et bulletins** : saisie des notes par matière, calcul des moyennes et des rangs, génération de bulletins PDF (mensuels et trimestriels).
- **Paiements et échéances** : suivi des frais de scolarité, échéanciers, paiements et rapports financiers.
- **Cantine / goûter** : abonnements et paiements liés aux services de cantine et de goûter.
- **Présence** : suivi de l'assiduité des élèves.
- **Notifications SMS** : envoi de SMS aux parents (absences, retards de paiement, etc.) via la passerelle Orange SMS.
- **Multi-écoles** : espace Super Admin pour gérer les établissements, les abonnements et les contrats, avec isolation des données par école.

## Prérequis

- PHP 8.3+
- Composer
- MySQL (ou MariaDB)
- Node.js et npm
- Un environnement local type Laragon, Herd, Valet ou équivalent

## Installation

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Copier le fichier d'environnement et générer la clé d'application
cp .env.example .env
php artisan key:generate

# 3. Configurer la base de données MySQL dans le fichier .env
#    (DB_DATABASE, DB_USERNAME, DB_PASSWORD, ...)

# 4. Exécuter les migrations (et éventuellement les seeders)
php artisan migrate
# php artisan db:seed

# 5. Installer les dépendances front-end et compiler les assets
npm install
npm run build

# 6. Lancer le serveur de développement
php artisan serve
```

## Variables d'environnement spécifiques au projet

En plus des variables standard de Laravel, l'application utilise les variables suivantes pour la passerelle SMS Orange (voir `config/services.php` et `app/Services/Sms/OrangeSmsGateway.php`) :

```
ORANGE_SMS_CLIENT_ID=
ORANGE_SMS_CLIENT_SECRET=
ORANGE_SMS_SENDER_NAME=MIRABELLES
ORANGE_SMS_DEV_MODE=true
```

Ces identifiants peuvent aussi être configurés par école directement depuis l'application (paramètres SMS), auquel cas ils sont stockés chiffrés en base de données et priment sur ces variables globales.

## Licence

Ce projet s'appuie sur le framework [Laravel](https://laravel.com), open-sourcé sous licence [MIT](https://opensource.org/licenses/MIT).

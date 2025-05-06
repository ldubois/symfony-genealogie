# Projet Symfony

## Description
Ce projet est une application web développée avec le framework Symfony.

## Prérequis
- PHP 8.1 ou supérieur
- Composer
- MySQL 5.7 ou supérieur
- Node.js et npm (pour les assets)

## Installation

1. Cloner le repository
```bash
git clone [URL_DU_REPO]
cd [NOM_DU_PROJET]
```

2. Installer les dépendances PHP
```bash
composer install
```

3. Installer les dépendances JavaScript (si nécessaire)
```bash
npm install
```

4. Configurer la base de données
- Créer un fichier `.env.local` à partir du `.env`
- Configurer les variables d'environnement de la base de données :
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name?serverVersion=8.0"
```

5. Créer la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

6. Charger les fixtures (si disponibles)
```bash
php bin/console doctrine:fixtures:load
```

## Démarrage

1. Lancer le serveur Symfony
```bash
symfony server:start
```

2. Lancer le serveur de développement pour les assets (si nécessaire)
```bash
npm run dev-server
```

L'application sera accessible à l'adresse : `http://localhost:8000`

## Commandes utiles

- Créer une migration : `php bin/console make:migration`
- Exécuter les migrations : `php bin/console doctrine:migrations:migrate`
- Vider le cache : `php bin/console cache:clear`
- Lister les routes : `php bin/console debug:router`

## Structure du projet

```
├── bin/            # Fichiers exécutables
├── config/         # Configuration
├── public/         # Point d'entrée public
├── src/            # Code source
│   ├── Controller/ # Contrôleurs
│   ├── Entity/     # Entités
│   ├── Repository/ # Repositories
│   └── ...
├── templates/      # Templates Twig
├── tests/          # Tests
└── var/            # Fichiers générés
```

## Tests

Pour exécuter les tests :
```bash
php bin/phpunit
```

## Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## Licence

Ce projet est sous licence [MIT](LICENSE).

## Contact

Votre Nom - [@votre_twitter](https://twitter.com/votre_twitter) - email@example.com

Lien du projet : [https://github.com/votre_username/votre_repo](https://github.com/votre_username/votre_repo)

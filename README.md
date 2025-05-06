# Application de Généalogie

## Description
Cette application web permet de gérer et visualiser un arbre généalogique. Développée avec Symfony, elle offre une interface intuitive pour gérer les informations familiales et visualiser les relations entre les membres de la famille.

## Fonctionnalités

### Gestion des Personnes
- Création de profils personnels complets
- Informations détaillées :
  - Nom et prénom
  - Dates de naissance et de décès
  - Lieux de naissance et de décès
  - Biographie
  - Photo de profil
- Relations familiales :
  - Parents (père et mère)
  - Enfants
  - Navigation facile entre les membres de la famille

### Visualisation
- Liste des personnes avec cartes détaillées
- Vue détaillée de chaque personne
- Arbre généalogique interactif :
  - Affichage des ancêtres (jusqu'à 4 générations)
  - Affichage des descendants
  - Navigation intuitive entre les membres
  - Mise en évidence de la personne sélectionnée

### Interface Utilisateur
- Design responsive avec Bootstrap 5
- Navigation intuitive
- Cartes interactives avec effets de survol
- Formulaires optimisés pour la saisie des données
- Messages de confirmation pour les actions importantes

### Fonctionnalités Techniques
- Gestion des relations familiales bidirectionnelles
- Validation des données
- Protection CSRF
- Gestion des photos de profil
- Interface responsive pour tous les appareils

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

## Utilisation

### Ajouter une personne
1. Cliquer sur "Nouvelle personne" dans la barre de navigation
2. Remplir le formulaire avec les informations de la personne
3. Sélectionner les parents si connus
4. Ajouter une photo (URL)
5. Cliquer sur "Enregistrer"

### Visualiser l'arbre généalogique
1. Accéder à la page de détails d'une personne
2. Cliquer sur "Voir l'arbre"
3. Naviguer dans l'arbre en cliquant sur les différentes personnes

### Modifier une personne
1. Accéder à la page de détails de la personne
2. Cliquer sur "Modifier"
3. Mettre à jour les informations
4. Cliquer sur "Mettre à jour"

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


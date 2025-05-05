# Slim API

Ce projet est un serveur d'API REST développé avec le framework [Slim](https://www.slimframework.com/) en PHP.


---

## Installation

1. **Cloner le dépôt :**
   ```bash
   git clone https://github.com/lmbexe/API.git
   cd slimApi
   ```

2. **Installer les dépendances via Composer :**
   ```bash
   composer install
   ```


## Structure du projet

```
slimApi/
├── app/              # Routes
├── public/           # Point d'entrée de l'application (index.php)
├── src/Service       # Lien vers la base de données
├── vendor/           # Dépendances Composer
├── composer.json     # Dépendances et scripts
└── README.md         # Documentation
```

## Utilisation de l'API
Lancer Postman, se connecter puis
#### Exemple : Récupérer toutes les visites

- **GET** `http://localhost/slimApi/get/visite`
- **Réponse :**
  ```json
  [
    {
        "id": 1,
        "patient": 5,
        "infirmiere": 3,
        "date_prevue": "2024-05-18 14:00:00",
        "date_reelle": "0000-00-00 00:00:00",
        "duree": 60,
        "compte_rendu_infirmiere": "",
        "compte_rendu_patient": null
    },
    {
        "id": 2,
        "patient": 6,
        "infirmiere": 3,
        "date_prevue": "2024-06-03 09:00:00",
        "date_reelle": "0000-00-00 00:00:00",
        "duree": 30,
        "compte_rendu_infirmiere": "",
        "compte_rendu_patient": null
    }
  ]
  ```

#### Exemple : Créer une visite

- **POST** `/post/visite`
- **Corps de la requête :**
  ```json
  {
   "id" : 0,
   "patient" : 9,
   "infirmiere" : 4,
   "date_prevue" : "",
   "date_relle" : "",
   "duree" : 65,
   "compte_rendu_infirmiere" : "",
   "compte_rendu_patient" : ""
  }
  ```
- **Réponse :**
 Accepted

#### Autres routes

- **GET** `/patient` : Récupérer un patient par ID
- **PUT** `/put/visite` : Mettre à jour une visite par id et les infos que l'on veut modifier
- **DELETE** `/delete/visite` : Supprimer une visite par id

## Tests

- Les tests peuvent être lancés avec PHPUnit (si configuré) :
  ```bash
  ./vendor/bin/phpunit
  ```

## Dépendances

- [Slim Framework](https://www.slimframework.com/)
- [PHP-DI](https://php-di.org/)
- [Monolog](https://seldaek.github.io/monolog/) (pour les logs)
- Autres selon votre `composer.json`






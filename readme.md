# Application de Suggestions d'Activités Adaptées

## Déploiement

Nécessite l'ajout d'un .env à la racine avec un VITE_GOOGLE_MAPS_API_KEY

```
npm i
```

```
npm run dev
```

Attention aux problèmes de CORS en local

Test fonctionnel sur : https://localhost:8000/sep/GPTVite/

## Présentation

### Introduction générale

L'activité physique est primordiale dans la gestion des maladies comme la Sclérose En Plaque ou Charcot. Plus généralement, il est recommandé à tous de pratiquer régulièrement de l'activité physique. Notre application vise à motiver les utilisateurs, en leur suggérant des activités adaptées à leurs capacités.

### Introduction technique

Cette application aide les utilisateurs à trouver des activités sur mesure, en utilisant les deux technologies web parmi les plus puissantes à l'heure actuelle :

- une intelligence artificielle pour générer des suggestions
- Google Maps pour les afficher sur une carte.

### Ouverture

- Recommandations contextuelles dynamiques (branché sur les données de santé, météo, heure, évènements)
- Apprentissage supervisé en temps réel (amélioration à chaque interaction)
- Rnn/transformer (séquence temporelle d’interaction de l’utilisateur avec l’application)
  Défis techniques : volume de donnée

### Synopsis de la vidéo

- Présentation générale (script DONE cf intro générale)
- Présentation de l'équipe (TODO)
- Présentation de l'application (TODO)
- Présentation technique (script DONE cf intro technique)

## Note de version & Work In Progress

### Version 0.3

- Utiliser Node pour l'API en utilisant une structure ViteJS
- Ajout d'un loading
- Affiche par défaut le texte de la première activité
- Markers cliquables
- Appel à l'api au chargement de la page
- 2 pages dont la première est un QCM simple puis renvoie vers la page principale
- rollback: API via PHP
- ajout de Bootstrap & dark mode

### Version 0.2

- Ajout de la geolicalisation de l'utilisateur
- Affichage des infos des activités en dessous de la carte
- Fix des warning relatifs à l'utilisation d'une mauvaise version de map

### TODO

- Porter l'application sur Android avec Expo
- Faire l'appli avec l'IA Gemini (en effectuant au préalable la requête via Gemini)
- Relancer la recherche sur la base de la position courante de la carte et non les coords geolocalisés
- Ajouter des fonctionnalités : persistance de données, personnalisation (budget, durée - ouvert maintenant, type - culturel, social, physique + autres cf questionnaire) avec "Commencez tout de suite - Faire le questionnaire (2 minutes)"

### Question @ Google

- Comment utiliser Gemini
- Comment faire en sorte de rechercher sur la fenêtre courante

© 2024 EPSI Lille

# Guide utilisateur — Plugin GLPI MyDashboard

## 1. Présentation

Le plugin **MyDashboard** remplace ou complète la page d'accueil GLPI par un tableau de bord entièrement personnalisable. Il offre :

- Une grille **drag-and-drop** (GridStack.js) où chaque utilisateur place, redimensionne et réorganise ses widgets
- Plus de **100 widgets** couvrant les tickets, les statistiques, les KPIs, l'inventaire, la planification, les rappels, les flux RSS, la base de connaissance, les contrats et les projets
- Une **personnalisation par utilisateur** : chaque utilisateur sauvegarde sa propre disposition ; les administrateurs définissent des grilles par défaut au niveau du profil
- Des **indicateurs visuels (KPIs)** sous forme de cartes colorées avec seuils d'alerte
- Des graphiques **Apache ECharts** (barres, courbes, secteurs, donut, entonnoir) avec thèmes de couleurs configurables
- Des tableaux interactifs **DataTables** avec export CSV, Excel et PDF
- Un widget cartographique **OpenStreetMap** affichant les tickets ouverts par localisation géographique
- Un **export PDF** du tableau de bord entier (côté client)
- Une intégration avec le plugin **Service Catalog**
- Un mode **"remplacer la page centrale"** redirigeant automatiquement l'utilisateur vers le tableau de bord à la connexion

---

## 2. Gestion des droits

Chemin : `Administration > Profils > onglet My Dashboard`

### 2.1 Droits disponibles

| Droit | Valeur | Description |
|-------|--------|-------------|
| `plugin_mydashboard` | **1 = Personnalisé** | Accès au tableau de bord ; seuls les widgets autorisés par le profil sont visibles |
| `plugin_mydashboard` | **6 = Complet** | Accès complet : tous les widgets sont visibles (sous réserve des droits GLPI individuels) |
| `plugin_mydashboard_config` | CREATE+UPDATE+PURGE | Accès à la page de configuration globale du plugin |
| `plugin_mydashboard_edit` | CREATE+UPDATE | Permission d'entrer en mode édition pour réarranger les widgets |
| `plugin_mydashboard_stockwidget` | READ+CREATE+UPDATE+PURGE | Gérer les widgets Stock d'inventaire |

> **Note :** Avec `plugin_mydashboard = 1 (Personnalisé)`, l'administrateur configure une liste blanche de widgets autorisés pour ce profil (voir section 10).

---

## 3. Configuration globale

Chemin : `Configuration > Plugins > My Dashboard` (requiert le droit `plugin_mydashboard_config`)

### 3.1 Options principales

| Paramètre | Description |
|-----------|-------------|
| Activer le plein écran | Afficher le bouton plein écran dans la barre d'outils |
| Afficher dans le menu | Afficher le lien MyDashboard dans le menu GLPI |
| Remplacer la page centrale | Activer globalement la redirection vers le tableau de bord à la connexion |
| Niveau de catégorie | Profondeur de l'arborescence des catégories ITIL dans les graphiques (défaut : 2) |
| Couleurs d'impact 1 à 5 | Couleurs utilisées pour les 5 niveaux d'impact dans les widgets d'alerte |
| Titre Alertes réseau | Titre personnalisé du widget alertes réseau |
| Titre Maintenances | Titre personnalisé du widget maintenances planifiées |
| Titre Informations | Titre personnalisé du widget informations |

### 3.2 Onglets de la configuration

| Onglet | Contenu |
|--------|---------|
| **Principal** | Options ci-dessus |
| **Traductions** | Traduction des libellés de configuration dans d'autres langues |
| **Vérification du schéma** | Contrôle d'intégrité des tables de la base de données du plugin |

---

## 4. Préférences utilisateur

Chemin : `Préférences > onglet My Dashboard`

| Paramètre | Description |
|-----------|-------------|
| Actualisation automatique | Activer le rafraîchissement automatique des widgets |
| Délai d'actualisation | Intervalle : 1, 2, 5, 10, 30 ou 60 minutes |
| Remplacer la page centrale | Override personnel : rediriger vers le tableau de bord à la connexion |
| Largeur par défaut des widgets | Nombre de colonnes de grille occupées par défaut |
| Groupe(s) technicien préféré(s) | Groupes pré-sélectionnés dans les filtres des widgets graphiques |
| Groupe(s) demandeur préféré(s) | Groupes demandeurs pré-sélectionnés |
| Entité préférée | Entité pré-sélectionnée dans les filtres |
| Mode édition au démarrage | Démarrer en mode édition |
| Activer le glisser-déposer | Glisser-déposer disponible en mode vue |
| Palette de couleurs | Thème ECharts pour les graphiques |
| Type de ticket préféré | Incident / Demande / Tous (filtre pré-sélectionné) |
| Catégorie préférée | Catégorie ITIL pré-sélectionnée |
| Année préférée | Année courante ou précédente |

> Dans les préférences, l'utilisateur peut également **masquer des groupes de widgets** provenant de plugins tiers (liste de plugins intégrés à MyDashboard).

> **Lien avec la barre de filtres globaux :** les préférences *Entité préférée*, *Groupe(s) technicien préféré(s)*, *Type de ticket préféré* et *Année préférée* sont utilisées pour **pré-remplir la barre de filtres globaux** à chaque chargement du tableau de bord (voir section 5.2). Modifier un filtre dans la barre ne modifie pas les préférences enregistrées : les préférences constituent uniquement la valeur initiale.

---

## 5. Interface du tableau de bord

### 5.1 Barre d'outils

La barre d'outils (en haut du tableau de bord) contient :

| Bouton | Action |
|--------|--------|
| Mode édition | Activer/désactiver le glisser-déposer et le redimensionnement |
| Ajouter un widget | Ouvre le catalogue de widgets |
| Actualiser | Rafraîchir tous les widgets |
| Mise en page prédéfinie | Choisir parmi 11 dispositions prédéfinies |
| Exporter en PDF | Exporter le tableau de bord entier en PDF |
| Plein écran | Afficher en plein écran (si activé dans la config) |

### 5.2 Barre de filtres globaux

Directement sous la barre d'outils se trouve la **barre de filtres globaux**. Elle permet d'appliquer simultanément des critères à **tous les widgets graphiques affichés**, sans avoir à configurer chaque widget individuellement.

#### Filtres disponibles

| Filtre | Description |
|--------|-------------|
| **Entité** | Restreindre tous les graphiques à une entité GLPI précise. La valeur `0` correspond à l'entité racine et est un filtre valide. |
| **Groupe technicien** | Un ou plusieurs groupes techniciens assignés (sélection multiple) |
| **Type** | Incident, Demande ou Tous |
| **Année** | Année de référence pour les données |

#### Fonctionnement

- Les sélecteurs sont **pré-remplis** depuis les préférences utilisateur (voir section 4) à chaque chargement du tableau de bord.
- Dès qu'un filtre est modifié, **tous les widgets visibles sont automatiquement rafraîchis** avec les nouvelles valeurs.
- Les filtres globaux s'appliquent comme **valeur par défaut** : si un widget possède son propre formulaire de critères (icône ⚙ dans son en-tête) et que l'utilisateur y a défini des valeurs, **les critères locaux du widget ont la priorité** sur les filtres globaux.
- Le formulaire de critères de chaque widget est **mis à jour** lors du rafraîchissement : les sélecteurs internes du widget reflètent les valeurs en cours (globales ou locales).

> **Exemple :** Si le filtre global est réglé sur l'entité "Site Paris" et l'année 2025, tous les graphiques affichent les données correspondantes. Si l'utilisateur ouvre ensuite le formulaire de critères du widget "Top 10 catégories" et y sélectionne "Site Lyon", ce widget affichera "Site Lyon" tandis que les autres conserveront "Site Paris".

### 5.3 Mode édition

En mode édition (requiert le droit `plugin_mydashboard_edit`) :

- **Glisser-déposer** des widgets pour les repositionner
- **Redimensionner** les widgets en tirant les coins
- **Supprimer** un widget (icône croix sur le widget)
- **Configurer** les critères d'un widget (icône engrenage)
- La disposition est **sauvegardée automatiquement** dans la base de données

### 5.4 Mises en page prédéfinies

11 dispositions prédéfinies sont disponibles comme point de départ :

| Disposition | Contenu |
|-------------|---------|
| Admin GLPI | Vue administrateur complète |
| Admin Inventaire | Widgets inventaire |
| Superviseur Helpdesk | Vue superviseur avec KPIs globaux |
| Superviseur Incidents | Suivi des incidents |
| Superviseur Demandes | Suivi des demandes |
| Technicien Helpdesk | Vue technicien avec files d'attente |
| Tous les secteurs | Tous les graphiques en secteurs |
| Tous les graphiques en barres | Tous les graphiques en barres |
| Tous les graphiques en courbes | Tous les graphiques en courbes |
| Tous les tableaux | Tous les widgets tableau |
| Tous les indicateurs | Tous les KPIs |

---

## 6. Types de widgets

| Type | Rendu | Description |
|------|-------|-------------|
| **KPI / Indicateur** | Carte colorée | Compteur avec seuil d'alerte et couleur configurable |
| **Tableau** | DataTables.js | Tableau interactif avec tri, recherche, export |
| **Secteur** | ECharts | Graphique en secteurs, donut ou aire polaire |
| **Barres** | ECharts | Graphique en barres horizontales ou verticales |
| **Courbes** | ECharts | Graphique en courbes |
| **Carte** | OpenStreetMap | Carte géographique avec marqueurs |
| **Planning** | Vue GLPI | Planning intégré |
| **HTML libre** | Rendu HTML | Contenu HTML personnalisé |

### Fonctionnalités communes aux graphiques ECharts

Chaque graphique dispose d'une barre d'outils intégrée :
- **Vue des données** : affiche les données brutes sous forme de tableau texte
- **Changer le type** : basculer entre barres et courbes
- **Restaurer** : réinitialiser le zoom
- **Enregistrer en image** : télécharger le graphique en PNG

---

## 7. Catalogue des widgets — Vue d'ensemble

### 7.1 Widgets Alertes / Système

| Widget | Catégorie | Type | Contenu |
|--------|-----------|------|---------|
| Alertes réseau | Système | KPI | Alertes réseau actives (depuis les rappels de type 0), colorées par niveau d'impact |
| Maintenances planifiées | Système | KPI | Alertes de maintenance (rappels de type 1) |
| Informations | Système | KPI | Notices d'information (rappels de type 2) |
| Alertes incidents | Helpdesk | KPI | Incidents ouverts critiques/hauts avec seuil coloré |
| Alertes SLA Incidents | Helpdesk | KPI | Incidents en dépassement ou proche du SLA |
| Alertes demandes | Helpdesk | KPI | Demandes ouvertes critiques/hautes |
| Alertes SLA Demandes | Helpdesk | KPI | Demandes en dépassement ou proche du SLA |
| Statut GLPI | Système | KPI | Résultats de la vérification de santé GLPI |
| Alertes tickets par utilisateur | Helpdesk | Tableau | Tickets par utilisateur dépassant un seuil configurable |
| Actions automatiques en erreur | Système | Tableau | Tâches cron GLPI actuellement en erreur |
| Mails non importés | Système | Tableau | Entrées de boîte mail en échec d'import |
| Alertes stock inventaire | Inventaire | KPI | Alertes stock d'actifs (garantie, expiration) |
| Vos équipements | Inventaire | Tableau | Matériels assignés à l'utilisateur courant |
| Indicateurs globaux | Helpdesk | KPI | Compteurs ouverts/en attente/fermés |
| Indicateurs globaux par semaine | Helpdesk | KPI | Mêmes compteurs pour la semaine courante |

### 7.2 Widgets Graphiques en barres (Helpdesk)

| Widget | Contenu |
|--------|---------|
| Backlog de tickets par mois | Barres + courbe mixte ; clic vers liste de tickets |
| Temps moyen de traitement par technicien | Barres horizontales |
| Top 10 catégories | Barres horizontales avec clic vers liste |
| Tickets par technicien | Barres horizontales avec filtre catégorie |
| Durée moyenne des tickets | Double barres/courbe |
| Top 10 techniciens | Barres horizontales |
| Âge des tickets ouverts | Distribution en barres |
| Tickets par priorité | Barres |
| Tickets par statut | Barres |
| Satisfaction par trimestre | Courbe + barres |
| Réactivité 12 derniers mois | Barres multi-séries |
| Évolution sources de demandes | Barres multi-séries (12 mois) |
| Évolution types de solution | Barres multi-séries |
| Délai de résolution / TTO | Double axe Y |
| Satisfaction par année | Barres par mois |
| Dernière synchro ordinateurs par mois | Inventaire ; ordinateurs par date de synchro |
| Évolution respect TTO | Courbe + barres |
| Évolution respect TTR | Courbe + barres |

### 7.3 Widgets Graphiques en secteurs (Helpdesk)

| Widget | Sous-type |
|--------|-----------|
| Tickets par priorité | Secteur |
| Top 10 demandeurs | Secteur |
| Respect TTR | Donut |
| Respect TTO | Donut |
| Incidents par catégorie | Secteur |
| Demandes par catégorie | Secteur |
| Ouverts / fermés / non planifiés | Secteur |
| Types de solution | Donut |
| Groupes demandeurs | Secteur |
| Niveau de satisfaction | Secteur |
| Tickets par localisation | Secteur |
| Sources de demandes | Donut |
| Par localisation (aire polaire) | Aire polaire |
| Par application | Secteur |

### 7.4 Widgets Graphiques en courbes (Helpdesk)

| Widget | Données |
|--------|---------|
| Stock de tickets par mois | Données historiques pré-agrégées |
| Ouverts vs. fermés | Requête directe, 12 mois glissants |
| Ouverts / résolus / fermés | 3 séries sur 12 mois |
| Ouverts / fermés / non planifiés | 3 séries |
| Tickets créés par mois | Par entité/groupe |
| Tickets créés par semaine | Granularité hebdomadaire |
| Refus de validation | Comptage mensuel |
| Tickets liés à des problèmes | Comptage mensuel |
| Backlog par semaine | Stock hebdomadaire |
| En cours mensuel | Requête directe |
| Tickets avec plus d'une solution | Comptage mensuel |

### 7.5 Widgets Tableaux

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Tickets ouverts par technicien et statut | Helpdesk | Tableau croisé : lignes=techniciens, colonnes=statuts |
| Tickets ouverts par groupe et statut | Helpdesk | Tableau croisé : lignes=groupes, colonnes=statuts |
| Unicité des champs / doublons | Inventaire | Détection des valeurs dupliquées dans les actifs |
| Articles KB non publiés | Outils | Articles `is_faq=0` non encore publiés |
| Annuaire interne utilisateurs | Utilisateurs | Liste des utilisateurs internes actifs avec recherche |

### 7.6 Widget Carte

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Tickets ouverts par localisation | Helpdesk | OpenStreetMap ; marqueurs depuis les coordonnées GPS des localisations GLPI ; nombre de tickets ouverts par lieu |

### 7.7 Widget Entonnoir

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Pyramide des âges des ordinateurs | Inventaire | Distribution des ordinateurs par date d'achat (entonnoir ECharts) |

### 7.8 Widgets Files d'attente Tickets

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Tickets en cours | Vue demandeur | Tickets en cours de l'utilisateur courant |
| Tickets observés | Vue demandeur | Tickets où l'utilisateur est observateur |
| Tickets rejetés | Vue demandeur | Tickets refusés |
| Tickets à clôturer | Vue demandeur | Tickets à valider/clôturer |
| Enquêtes de satisfaction | Vue demandeur | Enquêtes en attente |
| Tickets à valider | Vue demandeur | Tickets en attente de validation par l'utilisateur |
| Nouveaux tickets | Vue technicien | Tickets non assignés |
| Tickets à traiter | Vue technicien | Tickets assignés à l'utilisateur |
| Tickets en attente | Vue technicien | Tickets en attente du technicien |
| Tâches à faire | Vue technicien | Tâches à faire de l'utilisateur |
| Tickets groupe à traiter | Vue groupe | Tickets assignés aux groupes de l'utilisateur |
| Tâches groupe à faire | Vue groupe | Tâches pour les groupes de l'utilisateur |
| Compteur tickets | Helpdesk | Compteurs par statut avec liens vers les listes |

### 7.9 Widgets Problèmes

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Problèmes à traiter | Helpdesk | Problèmes assignés à l'utilisateur |
| Problèmes en attente | Helpdesk | Problèmes en attente de l'utilisateur |
| Compteur problèmes | Helpdesk | Comptages par statut |
| Problèmes groupe à traiter | Vue groupe | Problèmes des groupes |
| Problèmes groupe en attente | Vue groupe | Problèmes en attente des groupes |

> Requiert le droit `problem` READALL ou READMY.

### 7.10 Widgets Changements

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Changements à traiter | Helpdesk | Changements de l'utilisateur |
| Changements en attente | Helpdesk | Changements en attente |
| Changements appliqués | Helpdesk | Changements résolus dans les 30 derniers jours |
| Compteur changements | Helpdesk | Comptages par statut |
| Changements groupe à traiter | Vue groupe | Changements des groupes |
| Changements groupe en attente | Vue groupe | Changements en attente des groupes |

> Requiert le droit `change` READALL ou READMY.

### 7.11 Widgets Projets et Tâches

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Tâches de projet à traiter | Outils | Tâches de projet de l'utilisateur (non terminées) |
| Tâches de projet groupe | Vue groupe | Tâches de projet des groupes |
| Projets à traiter | Outils | Projets de l'utilisateur |
| Projets groupe | Vue groupe | Projets des groupes |

### 7.12 Autres widgets

| Widget | Catégorie | Contenu |
|--------|-----------|---------|
| Planning | Outils | Planning GLPI intégré de l'utilisateur |
| Rappels personnels | Outils | Rappels personnels de l'utilisateur |
| Rappels publics | Outils | Rappels publics visibles par l'utilisateur |
| Derniers événements | Système | Derniers événements système GLPI (droit `logs` READ requis) |
| Articles KB populaires | Outils | Articles les plus consultés |
| Articles KB récents | Outils | Articles les plus récemment créés |
| Articles KB mis à jour | Outils | Articles les plus récemment modifiés |
| Contrats | Gestion | Statut des contrats : actifs, expirant bientôt, expirés (droit `contract` READ requis) |
| Flux RSS personnels | Outils | Éléments de flux RSS personnels |
| Flux RSS publics | Outils | Éléments de flux RSS publics |

---

## 8. Widgets Stock (inventaire)

Chemin : `Outils > My Dashboard > Stock Widgets` (requiert le droit `plugin_mydashboard_stockwidget`)

Les widgets Stock sont des KPIs d'inventaire entièrement configurables.

### Champs d'un widget Stock

| Champ | Description |
|-------|-------------|
| Nom | Libellé affiché sur le KPI |
| Type d'objet | Type d'actif GLPI (Ordinateur, Moniteur, Équipement réseau, etc.) |
| États | États des objets à comptabiliser (JSON) |
| Types | Types d'objets à filtrer (JSON) |
| Groupe | Filtre par groupe propriétaire |
| Entité | Périmètre entité |
| Récursif | Inclure les sous-entités |
| Couleur | Couleur de la carte KPI |
| Seuil d'alerte | Seuil en dessous duquel la couleur d'alerte s'affiche |

Chaque widget Stock créé devient automatiquement un widget KPI dans le tableau de bord.

---

## 9. Widgets personnalisés (HTML)

Chemin : `Configuration > Intitulés > My Dashboard > Widgets personnalisés`

Les widgets personnalisés permettent d'afficher du contenu HTML libre dans le tableau de bord.

### Création d'un widget personnalisé

1. Aller dans `Configuration > Intitulés > My Dashboard > Widgets personnalisés`
2. Créer un nouvel élément (nom, commentaire)
3. Ouvrir l'onglet **Contenu** de l'élément créé
4. Saisir le contenu HTML via l'éditeur de texte riche
5. Le widget apparaît automatiquement dans la catégorie **Autres** du catalogue

> Trois widgets personnalisés par défaut sont créés à l'installation : "Incidents", "Demandes", "Problèmes".

---

## 10. Droits par profil et liste blanche de widgets

Chemin : `Administration > Profils > [profil] > onglet My Dashboard`

### Accès "Personnalisé" (droit = 1)

Lorsque le droit `plugin_mydashboard` est défini à **1 (Personnalisé)**, un panneau supplémentaire s'affiche sur la fiche du profil permettant de cocher exactement quels widgets sont accessibles pour ce profil.

Seuls les widgets cochés apparaîtront dans le catalogue de ce profil.

### Groupes techniciens par défaut par profil

Sur la même fiche de profil, une section permet d'associer un ou plusieurs groupes techniciens par défaut. Ces groupes sont automatiquement pré-sélectionnés dans les filtres des widgets graphiques pour les utilisateurs de ce profil.

---

## 11. Alertes tableau de bord

### 11.1 Lier une alerte à un Rappel GLPI

Les alertes affichées dans les widgets Système (Alertes réseau, Maintenances, Informations) sont basées sur des **Rappels GLPI** liés à une alerte de tableau de bord.

Depuis un Rappel, un Problème ou un Changement :
1. Ouvrir la fiche de l'objet
2. Aller dans l'onglet **Alerte Dashboard**
3. Créer une alerte en renseignant :
   - Type (0 = Alerte réseau, 1 = Maintenance, 2 = Information)
   - Impact (1 à 5)
   - Catégorie ITIL (optionnel)
   - Dates de visibilité (début/fin)

### 11.2 Affichage sur la page de connexion

Lorsque des alertes actives existent, un **bandeau défilant** (newsTicker) s'affiche automatiquement en bas de la page de connexion GLPI avec le texte des alertes.

---

## 12. Filtres et critères des widgets

### 12.1 Barre de filtres globaux

La **barre de filtres globaux** (section 5.2) permet de filtrer tous les widgets d'un seul geste. Elle est initialisée depuis les préférences utilisateur et déclenche un rafraîchissement automatique de tous les widgets à chaque modification.

### 12.2 Critères par widget

La plupart des widgets graphiques disposent également d'un formulaire de filtres propre, accessible via l'icône ⚙ dans l'en-tête du widget. Les critères disponibles varient selon le widget :

| Critère | Description |
|---------|-------------|
| Entité | Filtrer par entité GLPI. L'entité racine (id=0) est un filtre valide. |
| Groupe technicien | Un ou plusieurs groupes techniciens assignés |
| Groupe demandeur | Un ou plusieurs groupes demandeurs |
| Technicien | Technicien assigné spécifique |
| Localisation | Localisation unique |
| Localisations multiples | Plusieurs localisations |
| Catégorie ITIL | Catégorie de ticket |
| Type | Incident / Demande / Tous |
| Année | Année de création des tickets |
| Mois | Mois (combiné avec l'année) |
| Mode date | Basculer entre mode Année et plage de dates |
| Limite | Nombre maximum de lignes à retourner (0=Tous) |
| Type d'ordinateur | Pour les widgets inventaire |

### 12.3 Priorité des filtres

Les filtres s'appliquent dans l'ordre de priorité suivant (du plus fort au plus faible) :

1. **Critères locaux du widget** — définis via le formulaire ⚙ de chaque widget
2. **Filtres globaux** — définis dans la barre de filtres globaux (section 5.2)
3. **Préférences utilisateur** — valeurs enregistrées dans les préférences (section 4)

Lorsque le formulaire de critères d'un widget est soumis, les filtres globaux actifs lui sont transmis en base, et les critères locaux écrasent les clés en conflit. Ainsi un widget peut affiner ou contredire le filtre global sans affecter les autres widgets.

> Lorsque la barre de filtres globaux modifie un widget, le formulaire de critères interne (icône ⚙) est régénéré et reflète les valeurs en cours.

---

## 13. Export des données

### 13.1 Export du tableau de bord en PDF

Bouton dans la barre d'outils : **Exporter en PDF**

- Capture tous les widgets visibles en image canvas
- Génère un PDF avec en-tête (titre + date)
- Détection automatique portrait/paysage selon les dimensions de la grille
- Traitement entièrement côté client (aucune donnée envoyée au serveur)

### 13.2 Export depuis les tableaux (DataTables)

Chaque widget tableau dispose de boutons d'export :

| Bouton | Format |
|--------|--------|
| Copier | Presse-papier |
| Excel | .xlsx |
| CSV | .csv |
| PDF | PDF côté client |
| Imprimer | Impression navigateur |

### 13.3 Export des graphiques (ECharts)

La barre d'outils de chaque graphique permet :
- **Vue des données** : données brutes en tableau texte
- **Changer le type** : basculer barres/courbes
- **Enregistrer en image** : télécharger en PNG

---

## 14. Remplacement de la page centrale

Le mode "Remplacer la page centrale" redirige automatiquement l'utilisateur vers le tableau de bord MyDashboard au lieu de la page Accueil GLPI lors de la connexion.

### Activation

| Niveau | Chemin | Description |
|--------|--------|-------------|
| Global | `Configuration > Plugins > My Dashboard` | Active la fonctionnalité pour tous |
| Par utilisateur | `Préférences > My Dashboard` | Chaque utilisateur active pour lui-même |

> Si le plugin Service Catalog est actif, le lien MyDashboard est retiré du menu helpdesk pour ne pas dupliquer la navigation.

---

## 15. Bonnes pratiques

- **Définir des mises en page par profil** : configurer une grille de démarrage adaptée (superviseur, technicien, admin) pour éviter que les utilisateurs commencent avec une page vide
- **Utiliser les groupes par défaut du profil** : pré-sélectionner les groupes techniciens dans les filtres des widgets graphiques pour que chaque profil voie ses propres données
- **Activer l'actualisation automatique** sur les tableaux de bord opérationnels (supervision temps réel) : définir un intervalle de 5 ou 10 minutes dans les préférences
- **Restreindre les droits à "Personnalisé" (1)** pour les profils helpdesk et configurer la liste blanche de widgets pour ne montrer que les widgets pertinents (files d'attente, KPIs)
- **Créer des widgets Stock** pour suivre l'état de l'inventaire critique (PC en garantie, licences expirant bientôt, etc.)
- **Configurer les couleurs d'impact** dans la configuration globale pour que les widgets d'alerte reflètent la charte graphique de l'organisation
- **Alimenter les alertes de connexion** (Maintenances, Alertes réseau) via des Rappels liés pour informer les utilisateurs dès la page de connexion
- **Utiliser les widgets personnalisés** pour afficher des informations contextuelles (contact support, lien portail externe, procédures d'urgence)

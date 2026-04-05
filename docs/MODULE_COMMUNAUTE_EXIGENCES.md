# Module Communauté — couverture des exigences (patrons, entités, saisie, UI, avancé)

Document de traçabilité pour l’incrément **Communauté** (posts, commentaires, votes, tableau de bord).

## 1. Patrons de conception appliqués

| Patron | Où dans le projet | Rôle |
|--------|-------------------|------|
| **MVC** | `CommunityController` + Twig + entités | Pages `/communaute`, `/communaute/tableau-de-bord`, export PDF. |
| **API / Front Controller** | `CommunityApiController`, routes Symfony | Point d’entrée unique `/api/...` pour JSON. |
| **Repository** | `PostRepository`, `CommentRepository`, `UtilisateurRepository`, etc. | Accès données et requêtes métier (fil, stats, filtres). |
| **Service / couche métier** | `CommunityMetrics`, `TunisWeatherService`, `CommunityAssistant` | Logique réutilisable hors contrôleur. |
| **Strategy** | `App\Service\Assistant\*IntentHandler` + `AssistantIntentHandlerInterface` | Une stratégie par famille d’intentions pour l’assistant (météo, stats, guide, profil, défaut). |
| **Chaîne de responsabilité** | `CommunityAssistant::answer()` parcourt les handlers dans l’ordre | Premier `supports()` vrai traite la requête ; le défaut est en dernier. |
| **Dependency Injection** | Constructeurs Symfony, `services.yaml` | Injection des services et de la liste ordonnée des handlers. |
| **Singleton (conteneur)** | Services en scope par défaut | Une instance partagée de `CommunityMetrics`, etc. |

## 2. Fonctionnalités basiques par entité

| Entité (concept) | Opérations | Fichiers / routes principales |
|------------------|------------|-------------------------------|
| **Post** | Liste filtrée, détail, création, mise à jour, suppression (auteur), votes | `Post`, `PostRepository`, `POST/GET/PATCH/DELETE /api/posts` |
| **Comment** | Par post, création, réponse (`parent_comment_id`), édition/suppression auteur, votes | `Comment`, `CommentRepository`, `/api/posts/{id}/comments`, `/api/comments/{id}` |
| **Utilisateur (liaison)** | Session communauté via `ID_UTILISATEUR`, affichage nom | `POST /api/community/session`, `UtilisateurRepository::getDisplayNamesByIds` |
| **Votes** | Post / commentaire | `PostVote`, `CommentVote`, routes `/vote` |

## 3. Contrôles de saisie

- **Côté serveur** (`CommunityApiController`) : longueurs max (titre, description, tag, URL, commentaire, recherche), `FILTER_VALIDATE_URL` pour les images, `LIKE` échappé pour les recherches, validation `user_id` existant en base.
- **Côté client** : attributs `maxlength`, `type="url"`, messages sous formulaires, validation JS avant `fetch` (fil + tableau de bord).

## 4. Interfaces graphiques

- **Twig** : `templates/community/index.html.twig` (fil), `dashboard.html.twig`, `dashboard_pdf.html.twig`, sélecteur de langue.
- **CSS / JS** : `public/topNavbar/communaute-app.css|js`, `community-dashboard.*`, `community-enhancements.css` (widgets météo, chatbot, profil).

## 5. Fonctionnalités avancées (bundles externes, API, métier)

| Élément | Technologie |
|---------|-------------|
| Bundles / libs | `symfony/translation`, `symfony/http-client`, `symfony/validator`, `symfony/serializer`, `dompdf/dompdf`, `nelmio/api-doc-bundle`, Doctrine, Twig |
| API REST | `CommunityApiController` : session, stats, listes dashboard, CRUD posts/comments, votes, météo, assistant |
| Validation structurée | DTO `App\Dto\Community\CreatePostInput` + `ValidatorInterface` (réponses HTTP 422 + détail des erreurs) |
| Pagination métier | `CommunityPostFeedService` + `PostRepository::countFeedOrdered` / `findFeedOrderedPaged` — `GET /api/posts` retourne `posts` + `meta` (`page`, `limit`, `total`, `pages`) |
| Documentation OpenAPI | **Swagger UI** : `GET /api/doc` — spécification JSON : `GET /api/doc.json` |
| Métier | Agrégats stats, assistant par intentions (Strategy), export PDF, i18n FR/EN/AR, `LocaleSubscriber` |

---

*À compléter dans ton rapport : backlog Scrum, estimation (story points), schéma de déploiement deux machines (voir `DATABASE_URL` vers serveur BDD distant).*

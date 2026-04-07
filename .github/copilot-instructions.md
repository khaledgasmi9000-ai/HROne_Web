# Project Guidelines

## Code Style
- Target PHP 8.1+ and Symfony 6.4; follow attribute-based Doctrine mappings.
- Keep entity getters/setters fluent (return $this) and match existing property naming.

## Architecture
- Symfony app: entities in src/Entity, repositories in src/Repository, controllers in src/Controller.
- Services are auto-discovered via config/services.yaml (autowire/autoconfigure); do not register entities as services.

## Build and Test
- composer install
- docker compose up -d
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate (when migrations exist)
- bin/console cache:clear
- No automated tests are configured yet.

## Conventions
- Entity properties and column names use capitalized snake_case with ID_ prefixes (see src/Entity).
- Table names are lower-case via #[ORM\Table] attributes.
- Use explicit JoinColumn names for relations.

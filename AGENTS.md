# Repository Guidelines

## Project Structure & Module Organization
- `app/` — Domain and HTTP layer (Actions, Http/Controllers, Models, Services, Policies, etc.). Routes use attribute routing (`spatie/laravel-route-attributes`), not a `routes/` folder.
- `resources/` — Frontend (React + Inertia) in `js/` with `components/`, `pages/`, `layouts/`; Blade views in `views/`; styles in `css/`.
- `config/`, `database/`, `public/`, `storage/`, `bootstrap/`, `docker/`, `scripts/`, `tests/` (Feature, Unit).
- Use `.env` (copy from `.env.example`); Sail/Docker via `./sail` and `docker-compose.yml`.

## Build, Test, and Development Commands
- Install deps: `composer install` and `npm ci`.
- Configure app: `cp .env.example .env && php artisan key:generate`.
- Run locally (Docker): `make start` (alias for `./sail up`) and `make stop`.
- Assets: `npm run dev` (Vite dev), `npm run build` (production), `npm run build:ssr` (SSR).
- Tests: `php artisan test` (PHPUnit). Filter: `php artisan test --filter ClassOrMethod`.
- Static analysis/formatting: `vendor/bin/phpstan analyze`, `vendor/bin/pint`, `npm run lint`, `npm run format`, `npm run types`.

## Coding Style & Naming Conventions
- PHP: Laravel Pint preset (PSR-12, 4-space indent). Classes `StudlyCaps`, methods/vars `camelCase`. Keep controllers thin; prefer `Actions/` for business logic.
- JS/TS/React: ESLint + Prettier (2-space indent). Components `PascalCase.tsx` in `resources/js/components/`; hooks `useThing.ts` in `hooks/`.
- Tailwind: Prettier plugin organizes classes; prefer `cn(...)`/`clsx(...)` utilities.

## Testing Guidelines
- Framework: PHPUnit. Place tests in `tests/Feature` or `tests/Unit`; name files `*Test.php`.
- Keep tests deterministic; use factories/seeders over fixtures. Aim to cover critical flows (provisioning, deployments, settings) before UI details.

## Commit & Pull Request Guidelines
- Commits: Imperative, concise subject; optional type prefix (`feat:`, `fix:`, `chore:`). Reference issues/PRs (e.g., `(#789)`). Example: `fix: prevent downtime on alias update (#789)`.
- PRs: Clear description, linked issues, reproduction/verification steps, and screenshots for UI changes. Include migration notes when schema changes.
- Pre-submit: Ensure `phpstan`, `pint`, `php artisan test`, and `npm lint/format/types` all pass (mirrors our pre-commit script).

## Security & Configuration
- Do not commit secrets; use `.env` and follow `SECURITY.md`. Review changes to `config/` and `public/` carefully. Prefer environment-driven configuration over hard-coded values.


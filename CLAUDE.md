# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Vito is a self-hosted web application for server management and PHP application deployment. It's built with Laravel (PHP 8.4+) for the backend and React with TypeScript for the frontend, using Inertia.js as the bridge.

## Development Commands

### Frontend Development
- `npm run dev` - Start Vite development server for hot-reloading
- `npm run build` - Production build of frontend assets
- `npm run format` - Format code with Prettier (resources/ directory)
- `npm run format:check` - Check formatting without changes
- `npm run lint` - Run ESLint with auto-fix
- `npm run types` - TypeScript type checking

### Backend Development  
- `php artisan serve` - Start Laravel development server
- `php artisan test` - Run PHPUnit tests
- `php artisan test --filter TestName` - Run specific test
- `./vendor/bin/pint` - Format PHP code with Laravel Pint
- `./vendor/bin/phpstan analyze` - Static analysis with PHPStan
- `php artisan migrate` - Run database migrations
- `php artisan horizon` - Start Horizon queue worker

### Docker/Sail Commands
- `./sail up` or `make start` - Start Docker containers
- `./sail down` or `make stop` - Stop Docker containers
- `./sail artisan` - Run Artisan commands in container
- `./sail npm` - Run npm commands in container
- `./sail composer` - Run Composer commands in container

### Pre-commit Checks
The pre-commit script runs:
1. `./vendor/bin/pint --parallel` - PHP formatting
2. `./vendor/bin/phpstan analyze` - PHP static analysis
3. `npm run format` - JavaScript/TypeScript formatting
4. `npm run lint` - JavaScript/TypeScript linting
5. `php artisan test` - Run tests

## Architecture

### Backend Structure
- **Actions** (`app/Actions/`) - Single-responsibility command classes for business logic
- **Models** (`app/Models/`) - Eloquent models representing database entities
- **Policies** (`app/Policies/`) - Authorization logic for models
- **Services** (`app/Services/`) - Complex business logic and external integrations
- **ServerProviders** (`app/ServerProviders/`) - Integrations with cloud providers (AWS, DigitalOcean, etc.)
- **SiteTypes** (`app/SiteTypes/`) - Different application deployment strategies
- **Plugins** (`app/Plugins/`) - Extensible plugin system for additional features
- **SSH** (`app/SSH/`) - SSH connection and command execution logic
- **Notifications** (`app/Notifications/`) - System notifications and alerts

### Frontend Structure
- **Pages** (`resources/js/pages/`) - React components mapped to Laravel routes via Inertia
- **Components** (`resources/js/components/`) - Reusable React components using Shadcn UI
- **Hooks** (`resources/js/hooks/`) - Custom React hooks for shared logic
- **Stores** (`resources/js/stores/`) - Zustand state management
- **Types** (`resources/js/types/`) - TypeScript type definitions

### Key Patterns
- **Inertia.js**: Server-side routing with client-side rendering. Pages receive props from Laravel controllers
- **Route Attributes**: Uses Spatie's route attributes package for controller routing
- **Fortify**: Handles authentication flows
- **Horizon**: Manages background job queues
- **Plugin System**: Extensible architecture allowing third-party plugins in `storage/plugins/`

## Testing Approach
- **Feature Tests** (`tests/Feature/`) - Integration tests for API endpoints and workflows
- **Unit Tests** (`tests/Unit/`) - Isolated tests for individual classes
- Test database uses SQLite (`database-test.sqlite`)
- Run specific test: `php artisan test --filter MethodName`

## Code Style Guidelines
- Follow existing patterns in neighboring files
- Use Shadcn UI components for React UI elements
- Leverage existing Actions and Services rather than creating new ones
- API responses use Inertia for page components
- Background tasks use Laravel Horizon queues
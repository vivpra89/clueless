# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- **Backend**: Laravel 12.0 (PHP 8.2+)
- **Frontend**: Vue 3.5.13 with TypeScript and Inertia.js
- **CSS**: Tailwind CSS 4.1.1
- **Build**: Vite 6
- **Desktop**: NativePHP/Electron
- **Testing**: Pest PHP
- **Database**: SQLite (default)

## Development Commands

### Running the Application

```bash
# Full development environment (Laravel + Vite + Queue + Logs)
composer dev

# Development with Server-Side Rendering
composer dev:ssr

# Desktop application mode (NativePHP)
composer native:dev

# Frontend only (Vite dev server)
npm run dev
```

### Build Commands

```bash
# Build frontend for production
npm run build

# Build with SSR support
npm run build:ssr
```

### Code Quality

```bash
# Format code with Prettier
npm run format

# Check formatting without fixing
npm run format:check

# Fix ESLint issues
npm run lint

# PHP code style (Laravel Pint)
php artisan pint
```

### Testing

```bash
# Run all tests
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/DashboardTest.php

# Run tests with coverage (if configured)
php artisan test --coverage
```

## Project Architecture

### Directory Structure

- `/app/` - Laravel backend logic
  - `/Http/Controllers/` - Request handlers (Auth, Settings)
  - `/Models/` - Eloquent models
  - `/Providers/` - Service providers
- `/resources/js/` - Vue frontend application
  - `/components/` - Reusable Vue components
  - `/components/ui/` - UI component library (shadcn/ui-inspired)
  - `/pages/` - Inertia.js page components
  - `/layouts/` - Layout components
  - `/composables/` - Vue composables (e.g., useAppearance)
  - `/types/` - TypeScript type definitions
- `/routes/` - Application routing (web.php)
- `/database/` - Migrations, factories, seeders
- `/tests/` - Pest PHP test suites

### Key Patterns

1. **Inertia.js Integration**: Pages are Vue components loaded via Inertia.js, providing SPA-like experience without API endpoints
2. **Component Library**: UI components in `/resources/js/components/ui/` follow Reka UI patterns
3. **TypeScript**: Strict mode enabled, with path alias `@/` for `/resources/js/`
4. **Authentication**: Built-in Laravel auth with custom Vue components
5. **Theme Support**: Dark/light mode via `useAppearance` composable

### Important Files

- `vite.config.ts` - Vite configuration with Laravel plugin
- `tsconfig.json` - TypeScript configuration
- `eslint.config.js` - ESLint rules (ignores UI components)
- `resources/js/app.ts` - Frontend entry point
- `resources/js/ssr.ts` - SSR entry point

## Common Development Tasks

### Creating New Pages

1. Create Vue component in `/resources/js/pages/`
2. Add route in `/routes/web.php` returning Inertia::render()
3. TypeScript types auto-imported from `@/types`

### Adding UI Components

UI components are in `/resources/js/components/ui/` and follow Reka UI patterns. Import and use them directly - they're excluded from ESLint checks.

### Database Operations

```bash
# Run migrations
php artisan migrate

# Create new migration
php artisan make:migration create_example_table

# Seed database
php artisan db:seed
```

### Environment Setup

1. Copy `.env.example` to `.env`
2. Generate app key: `php artisan key:generate`
3. Create SQLite database: `touch database/database.sqlite`
4. Run migrations: `php artisan migrate`

## Database Migrations

**IMPORTANT**: This application uses TWO databases:
1. Default SQLite database: `database/database.sqlite`
2. NativePHP SQLite database: `database/nativephp.sqlite`

When creating or running migrations, you MUST run them on BOTH databases:
```bash
# Run on default database
php artisan migrate

# Run on nativephp database (REQUIRED!)
php artisan migrate --database=nativephp

# For specific migrations:
php artisan migrate --database=nativephp --path=database/migrations/your_migration.php
```

Always check both databases when debugging database-related issues!

## Documentation Access

When you need to access documentation for any library or framework, use the Context7 MCP tool. This is especially important as we're in 2025 and web search results from 2024 may be outdated. Always prefer Context7 for the latest documentation.

Example usage:
- For PrismPHP docs: Use `mcp__context7__get-library-docs` with library ID `/prism-php/prism`
- For Laravel docs: Use Context7 to get the latest Laravel 12 documentation
- For any other library: First use `mcp__context7__resolve-library-id` to find the library

## Notes

- ESLint ignores `/resources/js/components/ui/*` (third-party UI components)
- Prettier formats all files in `/resources/` directory
- NativePHP allows running as desktop application
- Concurrently runs multiple processes in development (server, queue, logs, vite)
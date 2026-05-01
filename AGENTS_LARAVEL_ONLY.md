<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated for this application. These rules should be followed closely when working in this repository.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystem package versions are below.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- laravel/sanctum - SPA cookie-based authentication

## Project Context

This application provides a REST API for a Vue 3 frontend.

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Use it for N+1 and query performance issues, caching, authorization, validation, error handling, queue configuration, route definitions, and architecture decisions.

## Conventions

- Follow existing code conventions used in this application. Check sibling files before creating or editing files.
- Use descriptive names for variables and methods.
- Check for existing components to reuse before writing a new one.
- Prefer consistency over cleverness.
- Stick to the existing directory structure. Do not create new base folders without approval.
- Do not change dependencies without approval.
- Be concise in explanations.

## Architecture

### API-First Design

- All application functionality should be exposed via `/api` routes unless the existing codebase clearly requires otherwise.
- Controllers should return JSON responses for API endpoints.
- Do not return Blade views unless explicitly required.
- Prefer named routes and Laravel conventions.
- Use route groups to share common route attributes such as `middleware`, `prefix`, `name`, and `controller` instead of repeating them on individual routes.
- Prefer nested route groups when they make route files clearer; Laravel merges middleware and `where` constraints, and appends prefixes and route-name prefixes.

### Routing

- Prefer `Route::apiResource()` for standard API CRUD endpoints when it matches the existing route structure.
- Use implicit route model binding instead of manually querying models from route parameters.
- Use scoped bindings for nested resources so child models are resolved within the parent relationship.
- Keep route files declarative. Do not move business logic into route closures when controllers, actions, or services are the established project pattern.

### Authorization

- Every mutating endpoint must authorize explicitly using policies, gates, or Form Request authorization.
- Prefer authorization in Form Requests for write actions when that matches the existing project pattern.
- Do not rely on frontend checks for authorization.

### Controllers

- Keep controllers thin.
- Do not place business logic in controllers.
- Delegate business logic to:
  - `app/Services`
  - `app/Actions`
- Controllers must not contain:
  - loops with business logic
  - condition-heavy workflows
  - response transformation logic
  - manual pagination calculations
  - raw query construction when a scope, action, service, or resource is more appropriate

### Services and Actions

- Business logic should live in `app/Services` and `app/Actions`.
- Actions should be single-purpose.
- Services may coordinate multiple actions or domain operations.
- Do not put HTTP-specific concerns inside services or actions.
- Requests validate and authorize input.
- Actions perform one business operation.
- Services orchestrate actions and domain workflows.
- Resources transform output only.

### Models

- Use models for relationships, casts, accessors, mutators, and simple reusable scopes.
- Keep models lightweight.
- Avoid complex workflow logic inside models.
- Do not expose model internals directly through API responses.

### API Resources

- Always return API Resources for model-based API responses unless the existing codebase uses a different established pattern.
- Do not return raw Eloquent models from API endpoints.
- Keep API response shapes stable.
- Only include relationships when loaded.
- Hide internal and sensitive fields in resources.
- Do not duplicate transformation logic in controllers, services, actions, or macros.

### API Response Shape

Preferred JSON response patterns:

Standard:
```json
{
  "data": {}
}
```

Paginated:
```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

Validation / error:
```json
{
  "message": "Error message",
  "errors": {}
}
```

If the project uses response macros, use them consistently for response wrapping only.

## Pagination

- All list endpoints should be paginated unless there is a strong established reason not to.
- Do not hardcode pagination values.
- Use configuration and validated request input.
- Allow `per_page` when the existing API supports it.
- Clamp `per_page` to the configured maximum.
- Prefer the existing `$request->perPage()` macro if the project already uses it.
- Do not manually compute pagination limits in controllers.
- Return paginated API Resource collections.

Example config:
```php
return [
    'pagination' => [
        'default' => env('DEFAULT_PAGINATION_LIMIT', 15),
        'max' => env('MAX_PAGINATION_LIMIT', 100),
    ],
];
```

## Validation

- Always use Form Request classes for validation when appropriate.
- Do not write inline validation in controllers when a Form Request is the project convention.
- Centralize validation logic.
- Validate query parameters such as `page`, `per_page`, filters, and sorting when relevant.
- Use `$request->validated()` instead of `$request->all()` for validated operations.

## Security

- Use policies or gates for authorization.
- Never trust client input.
- Hide sensitive fields in API Resources.
- Do not expose secrets, tokens, passwords, or internal configuration values.
- Use Sanctum CSRF protection and Laravel defaults for authentication and authorization.

## Performance

- Use eager loading where appropriate.
- Avoid N+1 queries.
- Paginate large datasets.
- Avoid loading unnecessary relationships.
- Select only needed columns when appropriate.
- Cache when appropriate and consistent with existing patterns.

### Query Performance

- Eager load relationships with `with()` when rendering collections or nested API resources.
- Use `withCount()` instead of loading full relationships just to count related records.
- Do not place query logic in API Resources, accessors, or computed attributes that will run per model in large collections.

## Migrations

- Do not edit old migrations that may have already run in production.
- Create a new forward-fix migration instead of rewriting existing production migration history.
- Add indexes in migrations when query patterns require them.

## Verification Scripts

- Do not create ad hoc verification scripts or use tinker when tests already cover the behavior. Unit and feature tests are more important.

## Frontend Bundling

- If a frontend change does not appear in the UI, ask the user to run `npm run build`, `npm run dev`, or `composer run dev` as appropriate.

## Documentation Files

- Only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Prefer Laravel Boost tools over manual alternatives when available.
- Use `database-query` for read-only database queries instead of raw SQL in tinker.
- Use `database-schema` before writing migrations or models.
- Use `get-absolute-url` before sharing project URLs.
- Use `browser-logs` for recent browser logs, errors, and exceptions.

## Searching Documentation

- Always use `search-docs` before making code changes. Do not skip this step.
- Pass a `packages` array when you know which packages are relevant.
- Use broad topic-based queries such as `['rate limiting', 'routing', 'middleware']`.
- Do not include package names in the query text.

### Search Syntax

1. Use words for AND logic: `rate limit`
2. Use quoted phrases for exact matches: `"infinite scroll"`
3. Combine words and phrases: `middleware "rate limit"`
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`

## Artisan

- Run Artisan commands directly via the command line.
- Use `php artisan list` to discover commands.
- Use `php artisan [command] --help` to inspect options.
- Use `php artisan route:list` to inspect routes.
- Use `php artisan config:show` or read config files directly when needed.
- Read `.env` directly if environment values must be checked.

## Tinker

- Use tinker only when necessary.
- Prefer tests, factories, and existing Artisan commands over custom tinker code.
- Do not create models in tinker without approval.
- Always use single quotes around `php artisan tinker --execute`.

=== php rules ===

# PHP

- Always use curly braces for control structures.
- Use PHP 8 constructor property promotion when appropriate.
- Use explicit parameter types and return types.
- Use TitleCase for enum keys.
- Prefer PHPDoc over inline comments except for unusually complex logic.
- Use array shape PHPDoc definitions when helpful.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create Laravel files.
- If creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to Artisan generators.
- Follow existing app conventions before introducing new patterns.

### Model Creation

- When creating new models, also create useful factories and seeders when appropriate.
- Check `php artisan make:model --help` for available options.

## APIs and Eloquent Resources

- For APIs, default to Eloquent API Resources and API versioning unless the existing application uses another pattern consistently.

## URL Generation

- Prefer named routes and the `route()` helper.

## Testing

- This application uses PHPUnit. All tests must be PHPUnit classes.
- If you encounter Pest tests, convert them to PHPUnit only if the task requires updating that area and the team expects PHPUnit consistency.
- Use `php artisan make:test --phpunit {name}` for new tests.
- When creating test models, use factories and existing factory states.
- Most tests should be feature tests.
- Prefer feature tests for API endpoints.
- Every time a test is updated, run that singular test first.
- Tests should cover happy paths, failure paths, and edge cases.
- For API endpoints, test success responses, authorization failures, validation errors, and pagination/filter behavior where applicable.
- Do not remove tests without approval.

## Running Tests

- Run the minimal number of tests needed for the change.
- To run all tests:
  - `php artisan test --compact`
- To run one file:
  - `php artisan test --compact tests/Feature/ExampleTest.php`
- To run one test:
  - `php artisan test --compact --filter=testName`
- When related tests are passing, ask the user if they want the full suite run as well.

## Formatting

- If PHP files were modified, run:
  - `vendor/bin/pint --dirty --format agent`
- Do not run:
  - `vendor/bin/pint --test --format agent`

## Vite Error

- If you see `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## General Rules for AI Agents

- Follow this file over generic Laravel advice.
- Prefer existing project patterns.
- Do not introduce new architecture without approval.
- Do not introduce repositories unless the project already uses them.
- Do not introduce DTO layers, custom response wrappers, or other new abstractions unless the project already uses them or the user asks for them.
- Do not bypass Form Requests, Services, Actions, API Resources, or existing macros when those are the established project pattern.
- Do not return raw models from API endpoints.
- Do not place business logic in controllers.
- Do not hardcode pagination values.
- Use API Resources for transformation.
- Use response macros consistently if the project already uses them.

</laravel-boost-guidelines>

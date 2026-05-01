# AGENTS.md

## Project

This is a Vue 3 + Vite project using TypeScript, backed by a Laravel API using Sanctum authentication.

---

## Stack

* Vue 3
* Vite
* TypeScript
* Vue Router
* Pinia (client state only)
* TanStack Query (server/API state)
* Axios (API client)
* VeeValidate + Zod (forms + validation)
* Vitest (unit/component tests)
* Playwright (E2E tests)
* ESLint + Prettier

---

## Architecture

### State Management

* Use **TanStack Query** for all server/API state.
* Use **Pinia only for client-side/global state** (UI state, theme, local preferences).
* Do NOT duplicate server data in Pinia.

---

### API Layer

* Use **Axios** for all API calls.
* Use a centralized API client:

```
src/lib/api.ts
```

* All requests must go through this client.
* Do NOT use `fetch` directly.
* Services must return `res.data`, not the full Axios response.
* All list endpoints should expect Laravel-style paginated responses.
* Treat Laravel API Resources as the response contract.
* Do NOT depend on raw model fields, hidden attributes, or internal backend columns.
* Only use relationship fields when the API response includes them.
* Expect standard Laravel response shapes such as `data`, `meta`, `links`, `message`, and `errors`.

---

### Auth (Laravel Sanctum)

* Uses cookie-based authentication.
* Always call:

```
GET /sanctum/csrf-cookie
```

before login.

* Axios must be configured with:

    * `withCredentials: true`
    * `withXSRFToken: true`

* Authenticated user endpoint:

```
GET /api/user
```

---

### Query Patterns

* Queries must live in:

```
src/queries/
```

* Mutations must:

    * invalidate relevant queries
    * NOT manually sync state

* Include pagination, filters, and sorting in query keys.

Example:

```ts
queryClient.invalidateQueries({ queryKey: ['user'] })
```

---

### Folder Structure

* API services → `src/services/`
* Query logic → `src/queries/`
* Composables → `src/composables/`
* Views/pages → `src/views/`
* Shared components → `src/components/`

---

## Rules

* Use Vue SFCs with `<script setup lang="ts">`
* Prefer Composition API
* Do NOT use JSX unless explicitly requested
* Keep components small and reusable
* Keep business logic out of components

---

## Forms & Validation

* Use **VeeValidate** for forms
* Use **Zod** for schema validation
* Validation must be schema-driven
* Client-side validation is for UX only
* Laravel Form Request validation is the backend source of truth
* Frontend forms must handle Laravel `422` validation responses even if client-side validation passed

---

## Error Handling

* Do NOT handle API errors directly inside components
* Handle errors inside:

    * query/mutation functions
    * or shared helpers

### Axios Error Format

```ts
error.response?.data
```

### Laravel Validation Error Format

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Rules

* Extract validation errors and pass to forms
* Do NOT expose raw API responses
* Show user-friendly messages


### Helper Example

```ts
export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? 'Something went wrong'
  }
  return 'Unexpected error'
}
```

### Error Display Rules

- All validation errors must be shown at the field level.
- Use VeeValidate to map backend validation errors to form fields.
- Do NOT show validation errors only as global messages.

Example (Laravel response):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Notifications (Toasts)

- Use toast notifications for non-field errors and success messages.
- Do NOT use toasts for field validation errors.

### Use toasts for:

- Successful operations (create, update, delete)
- General API errors (e.g., 500, network errors)
- Auth errors (e.g., session expired)

### Do NOT use toasts for:

- Validation errors (these belong to fields)

### Rules

- Always show a success toast after successful mutations.
- Show error toast when:
    - API request fails without validation errors
    - Unexpected error occurs

### Example

```ts
toast.success('User created successfully')
toast.error('Something went wrong')
```

---

## Pagination

* All list queries must support server-side pagination.
* Use TanStack Query for pagination.
* Do NOT manually compute pagination logic in components.
* Do NOT hardcode pagination defaults.
* Backend controls default and max limits.

### Query Key Pattern

```ts
['users', { page, perPage }]
```

### Example

```ts
useQuery({
  queryKey: ['users', { page, perPage }],
  queryFn: () =>
    api.get('/api/users', {
      params: { page, per_page: perPage },
    }).then(res => res.data),
})
```

### Rules

* Send `page` and `per_page` as query params.
* Respect backend limits for `per_page`.
* Read pagination state from API response (`meta`).
* Use:
  - `meta.current_page`
  - `meta.last_page`
  - `meta.per_page`
  - `meta.total`
* Do NOT calculate pagination from array length.
* Keep pagination state in component, route query, or composable.
* Do NOT store paginated data in Pinia.

---

## Infinite Queries

* Use `useInfiniteQuery` for load-more or infinite scroll

### Example

```ts
useInfiniteQuery({
  queryKey: ['users'],
  queryFn: ({ pageParam = 1 }) =>
    api.get(`/api/users?page=${pageParam}`).then(res => res.data),
  getNextPageParam: (lastPage) =>
    lastPage.meta?.current_page < lastPage.meta?.last_page
      ? lastPage.meta.current_page + 1
      : undefined,
})
```

### Rules

* Do NOT flatten pages manually in components

---

## Mutations

* All mutations must use `useMutation`
* Must invalidate relevant queries after success

### Example

```ts
const mutation = useMutation({
  mutationFn: (payload) => api.post('/users', payload),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['users'] })
  },
})
```

### Rules

* Do NOT manually update cache unless necessary
* Prefer invalidation over manual updates

---

## File Uploads

* Use `FormData`
* Do NOT manually set `Content-Type`

### Example

```ts
const formData = new FormData()
formData.append('file', file)

await api.post('/upload', formData)
```

---

## API Responses

* Standard Laravel format:

```json
{
  "data": ...
}
```

### Paginated

```json
{
  "data": [],
  "meta": {},
  "links": {}
}
```

### Rules

* Always return `res.data`
* Do NOT return full Axios response

---

## Composables

* Use for:
  - reusable logic
  - UI behavior
  - derived state

* Do NOT perform raw API calls inside composables

---

## Loading States

* Always handle:
  - loading
  - error
  - empty state

---

## Commands

```bash
npm run dev
npm run build
npm run lint
npm run format
npm run test:unit
npm run test:e2e
```

---

## Testing (Playwright)

### Selector Priority

1. Prefer:
    * `getByRole`
    * `getByLabel`
    * `getByText`
    * `getByPlaceholder`

2. Fallback:
    * `getByTestId`

---

### Rules

* Prefer user-facing selectors
* Do NOT rely on:
    * CSS classes
    * `nth-child`
    * deep DOM structure
* Do NOT add `id` for testing
* Use `data-testid` only when necessary


### Test ID Convention

```html
<button data-testid="login-submit-button">Login</button>
<input data-testid="email-input" />
```

---

### Test Behavior

* Simulate real user behavior
* Tests must be independent
* Do NOT rely on execution order
* Avoid `waitForTimeout`

Prefer:

```ts
await expect(page.getByText('Dashboard')).toBeVisible()
```

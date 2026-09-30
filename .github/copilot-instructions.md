# Copilot Instructions: Ajaia Collaborative Docs

[Always read and reference this](/IMPLEMENTATION-PLAN.md)

Lightweight Google Docs-style editor built as a timed take-home. Priorities, in order: working end-to-end, deployed, correct authorization, clear code, tests, polish. Do not over-engineer.

## Stack (fixed, do not suggest alternatives)

- Laravel 13, PHP 8.3+, Eloquent, Pest
- Official Laravel React Starter Kit: React 19, TypeScript, Inertia, Tailwind CSS 4, shadcn/ui, Vite
- Tiptap (`@tiptap/react`, `@tiptap/pm`, `@tiptap/starter-kit`)
- SQLite locally, PostgreSQL in production (Laravel Cloud). Code must work on both.
- No separate REST API. Use Inertia pages and Laravel controllers/routes.

## Domain model

- `documents`: `id`, `owner_id` (FK users), `title`, `content` (json, cast to `array`), timestamps
- `document_shares`: `id`, `document_id`, `user_id`, `permission` (`view` | `edit`), timestamps, unique `(document_id, user_id)`
- `Document::owner()` belongsTo User; `Document::sharedWith()` belongsToMany User via `document_shares` with pivot `permission`
- `User::documents()` hasMany; `User::sharedDocuments()` belongsToMany

## Authorization (DocumentPolicy, the only place these rules live)

| Action                      | Owner | Shared `edit` | Shared `view` | Other |
| --------------------------- | ----- | ------------- | ------------- | ----- |
| view                        | yes   | yes           | yes           | no    |
| update (content and rename) | yes   | yes           | no            | no    |
| share / unshare             | yes   | no            | no            | no    |
| delete                      | yes   | no            | no            | no    |

- Call `$this->authorize(...)` in every document controller action.
- Pass `can` flags (`update`, `share`, `delete`) to React so the UI hides forbidden actions. The UI is a convenience; the server is the enforcement.

## Editor rules

- Store Tiptap JSON (`editor.getJSON()`) in `documents.content`. Never store HTML.
- Use `StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false })`. Underline, lists and headings are already in StarterKit. Do not install separate extensions for them.
- Save is explicit (Save button and Ctrl/Cmd+S) via Inertia `router.put`. No autosave, no realtime.
- Save states: `Saved`, `Unsaved changes`, `Saving…`, `Error`.
- `editable` must equal `can.update`. View-only users get a disabled toolbar.
- Tailwind preflight removes list and heading styles, so keep the `.tiptap` styles in `resources/css/app.css`.

## Import rules

- `POST /documents/import`, validate `file` as `required|file|max:1024|extensions:txt,md`.
- Conversion lives in `app/Services/TiptapImporter.php` (pure function, text in, Tiptap JSON array out).
- TXT: blank-line-separated paragraphs. MD: `#`/`##`/`###` headings, `-`/`*` bullets, `1.` ordered, everything else paragraphs. No inline Markdown, no DOCX.
- The uploaded file is read and discarded. Do not store it.

## Code conventions

- Use FormRequest classes for validation. Keep controllers thin.
- Use Eloquent relationships and eager loading (`with`). Avoid N+1 queries.
- Use named routes. Follow the route-helper pattern already used in the starter kit.
- Reuse existing shadcn/ui components and existing layouts. Match the file layout and casing of the starter kit.
- TypeScript: type all page props. No `any` unless unavoidable.
- Inertia: use the installed Inertia version's API (check `package.json`). Use `useForm` or `<Form>` for forms and `router` for actions, and surface `errors` inline.
- Show success and error feedback with the toast pattern already in the kit.
- Do not hand-`json_encode` the `content` column. The `array` cast handles it.
- Migrations must be SQLite and PostgreSQL compatible. No raw engine-specific SQL.

## Do not

- Add dependencies without being asked.
- Add features beyond the request (no comments, versions, realtime, export, teams, roles).
- Modify starter-kit authentication logic.
- Commit secrets, `.env`, or `database.sqlite`.
- Refactor unrelated code or rename existing files.

## Commands

- `composer run dev`: local server, queue and Vite
- `php artisan migrate:fresh --seed`: reset and seed demo data
- `php artisan test`: Pest suite
- `npm run build`: production build (must pass)
- Use the lint and type-check scripts in `package.json` before finishing.

## Demo accounts (seeded, idempotent seeder)

- `owner@example.com` / `password`
- `reviewer@example.com` / `password`

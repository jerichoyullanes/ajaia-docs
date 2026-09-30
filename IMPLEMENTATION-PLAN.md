# Implementation Plan: Ajaia Collaborative Docs

**For AI agents (Codex, Copilot):** this is the source of truth for scope and order of work. Implement only the phase or task you are asked to implement. Do not build anything listed under "Do not build". If this file conflicts with the existing code's conventions, follow the existing code and report the conflict.

## 0. Working rules (apply to every task)

1. Inspect existing code first (routes, controllers, pages, components, `package.json`, `composer.json`).
2. Make only the requested change. No drive-by refactors.
3. Use idiomatic Laravel 13 / React 19 / Inertia patterns already used in the starter kit. Check the installed Inertia version and use its API.
4. No new dependencies unless the task says so.
5. Preserve existing functionality.
6. Run relevant checks: `php artisan test`, `npm run build`, and the lint/type scripts in `package.json`.
7. Report exactly which files changed and what each change does.
8. Stop when the requested task is complete.

## 1. Goal and priorities

Lightweight Google Docs-style editor. Priorities, in order: working end-to-end, deployed, reviewer-friendly UX, clear architecture, correct authorization, basic testing, documentation, visual polish.

## 2. Fixed stack (do not propose alternatives)

- Laravel 13, Eloquent, Pest, Vite
- Official Laravel React Starter Kit: React 19, TypeScript, Inertia, Tailwind CSS 4, shadcn/ui
- Tiptap: `@tiptap/react`, `@tiptap/pm`, `@tiptap/starter-kit` (StarterKit already includes Bold, Italic, Underline, Heading, BulletList, OrderedList, ListItem)
- SQLite locally, PostgreSQL in production
- Deploy: Laravel Cloud. No separate REST API; use Inertia pages and controllers.

## 3. Architecture

| Layer         | Responsibility                                                                                                                |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Laravel       | Routing, FormRequest validation, Policy authorization, Eloquent persistence, TXT/MD to Tiptap JSON conversion, flash messages |
| Inertia/React | Pages, forms, toolbar, editor state, save state. Uses Inertia `router`/`useForm`, no client-side API calls                    |
| Database      | Users, documents, shares. FKs and a unique share row                                                                          |
| Tiptap        | Editing and formatting. Emits JSON, receives JSON                                                                             |
| Auth          | Starter kit auth. Two seeded users, registration left on                                                                      |
| Authorization | One `DocumentPolicy`, called with `$this->authorize()`. `can` flags passed to React                                           |
| Import        | Multipart POST to `TiptapImporter` to new `Document` to redirect to editor                                                    |
| Deploy        | GitHub to Laravel Cloud app to Cloud Serverless Postgres (or external Postgres via env vars)                                  |

## 4. Scope

**Must build:** seeded users and demo docs; dashboard with "My Documents" and "Shared With Me"; create, rename, open, delete; Tiptap editor with explicit Save and dirty/saved indicator and Ctrl/Cmd+S; share by email with view/edit permission and unshare; TXT/MD import; validation, toasts, 403 page; one Pest test; live deploy; docs and video.

**Only if ahead:** delete confirm dialog, `beforeunload` warning, owner name on shared cards, word count, demo-credentials box on login.

**Do not build:** realtime collaboration, autosave, comments, version history, DOCX/PDF/export, roles or teams, email invites, notifications, search, folders, changes to starter-kit auth logic.

## 5. Database

**documents**

- `id` bigint PK
- `owner_id` foreignId to users, cascadeOnDelete
- `title` string(255), default `'Untitled document'`
- `content` json, nullable (model cast `array`; never hand-encode)
- timestamps
- index `(owner_id, updated_at)`

**document_shares**

- `id` bigint PK
- `document_id` foreignId to documents, cascadeOnDelete
- `user_id` foreignId to users, cascadeOnDelete
- `permission` string(10), default `'edit'` (`view` | `edit`)
- timestamps
- unique `(document_id, user_id)`; index `user_id`

**Relationships**

- `Document::owner()` belongsTo User (`owner_id`)
- `Document::sharedWith()` belongsToMany User via `document_shares`, `withPivot('permission')`
- `User::documents()` hasMany Document (`owner_id`)
- `User::sharedDocuments()` belongsToMany Document via `document_shares`, `withPivot('permission')`

Must work on SQLite and PostgreSQL. No engine-specific SQL.

## 6. Authentication and seed data

Use the starter kit auth as-is. Seeder is idempotent (`updateOrCreate`/`firstOrCreate`) because it runs on every deploy.

- `owner@example.com` / `password`, `email_verified_at` set
- `reviewer@example.com` / `password`, `email_verified_at` set
- Owner's "Welcome to the Editor": valid Tiptap JSON using heading, bold, italic, underline, bullet and ordered lists; shared to reviewer with `edit`
- Owner's "Private Notes": not shared (proves 403)
- Reviewer's "Reviewer Notes": shared to owner with `view`

## 7. Authorization (DocumentPolicy)

| Action                      | Owner | Shared `edit` | Shared `view` | Other |
| --------------------------- | ----- | ------------- | ------------- | ----- |
| view                        | yes   | yes           | yes           | no    |
| update (content and rename) | yes   | yes           | no            | no    |
| share / unshare             | yes   | no            | no            | no    |
| delete                      | yes   | no            | no            | no    |

Every document controller action calls `$this->authorize(...)`. Pass `can` flags (`update`, `share`, `delete`) to React. The server is the enforcement; the UI only hides actions.

## 8. Routes and controllers

```
GET    /dashboard                        DocumentController@index
POST   /documents                        DocumentController@store      (creates blank doc, redirects to show)
GET    /documents/{document}             DocumentController@show       (Inertia: documents/edit)
PUT    /documents/{document}             DocumentController@update     (title + content)
DELETE /documents/{document}             DocumentController@destroy
POST   /documents/import                 DocumentController@import
POST   /documents/{document}/shares     DocumentShareController@store
DELETE /documents/{document}/shares/{user}  DocumentShareController@destroy
```

All under the `auth` middleware group. Define `documents/import` before the resource route so it is not captured by `{document}`.

## 9. Editor

- `useEditor({ extensions: [StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false })], content, editable: can.update })`
- Toolbar: Bold, Italic, Underline, H1, H2, Paragraph, Bullet list, Ordered list, Undo, Redo, with `editor.isActive(...)` active states.
- Storage is Tiptap JSON (`editor.getJSON()`) in `documents.content`. Never HTML.
- Save: button plus Ctrl/Cmd+S calls `router.put(...)` with `{ title, content }`, `preserveScroll: true`.
- Save states: `Saved`, `Unsaved changes` (set on editor `update`), `Saving…`, `Error`.
- Null content loads as `{"type":"doc","content":[{"type":"paragraph"}]}`.
- Tailwind 4 preflight removes list/heading styles: add about 25 lines of `.tiptap` CSS in `resources/css/app.css` (ul, ol, h1, h2, h3, p margins, focus outline).
- View-only users: badge "View only", disabled toolbar.

## 10. File import

1. Dashboard "Import file" button opens a dialog with a file input (`useForm`, `forceFormData`).
2. `POST /documents/import` validates `file` as `required|file|max:1024|extensions:txt,md` (confirm the rule name in the Laravel docs).
3. Controller reads contents, rejects non-UTF-8, normalizes line endings, calls `TiptapImporter::fromText($text, $ext)`.
    - TXT: blank-line-separated paragraphs.
    - MD: `#`/`##`/`###` headings; `-`/`*` lines as a bulletList; `1.` lines as an orderedList; everything else paragraphs. No inline Markdown parsing.
4. Create a Document owned by the user, titled with the filename minus extension, redirect to its editor with a success flash. The uploaded file is not stored.

## 11. UX

- **Login:** starter kit page, optional demo-credentials box.
- **Dashboard:** header with "New document" and "Import file". Two sections: "My Documents" and "Shared With Me". Badges: Owner / Can edit / View only. Shared rows show owner name. Independent empty states. Loading state on buttons (`processing`).
- **Editor:** top bar with Back, title input, save-state text, Save button, Share button (owner only). Toolbar, then a centered page-width editor.
- **Share dialog:** email input, view/edit select, Add button, current shares with Remove.
- **Feedback:** inline validation errors, flash toasts for success/error, friendly 403 page.
- **Responsive:** single column below `md`, toolbar wraps. No animations.

## 12. Important files

```
routes/web.php
app/Http/Controllers/DocumentController.php
app/Http/Controllers/DocumentShareController.php
app/Http/Requests/{UpdateDocumentRequest,StoreShareRequest,ImportDocumentRequest}.php
app/Models/Document.php
app/Policies/DocumentPolicy.php
app/Services/TiptapImporter.php
database/migrations/*_create_documents_table.php
database/migrations/*_create_document_shares_table.php
database/factories/DocumentFactory.php
database/seeders/DatabaseSeeder.php
resources/js/pages/dashboard.tsx              (match the starter kit's folder casing)
resources/js/pages/documents/edit.tsx
resources/js/components/documents/{document-list,import-dialog,share-dialog,editor-toolbar,rich-editor,save-status}.tsx
resources/css/app.css
tests/Feature/DocumentAccessTest.php
README.md  ARCHITECTURE.md  AI-WORKFLOW.md  SUBMISSION.md
```

## 13. Implementation phases

Each phase lists objective, work, verification and definition of done. An agent asked to "do Phase N" does only Phase N.

### Phase 1: Setup and first deploy

- **Work:** scaffold with `laravel new ajaia-docs --using=laravel/react-starter-kit` (Pest, SQLite, Laravel auth); `npm i @tiptap/react @tiptap/pm @tiptap/starter-kit`; push to GitHub; create Cloud app and deploy the bare kit.
- **Verify:** `composer run dev` works locally; live URL shows login page.
- **Done:** app runs locally and on the live URL.

### Phase 2: Schema, models, policy, seeder

- **Work:** two migrations; `Document` model with `array` cast and relations; `User` relations; `DocumentPolicy`; idempotent seeder per section 6.
- **Verify:** `php artisan migrate:fresh --seed`; tinker shows relations and seeded data.
- **Done:** seeded data present, relations work.

### Phase 3: Dashboard

- **Work:** `DocumentController@index` returns `owned` and `shared` (with owner name, pivot permission, `can` flags), ordered by `updated_at` desc. Dashboard page with two sections, badges, empty states.
- **Verify:** log in as both users.
- **Done:** both sections render correctly for each user.

### Phase 4: Document CRUD

- **Work:** routes per section 8; `store` creates blank doc and redirects; `update` validates `title` (`required|string|max:255`) and `content` (`required|array`) via `UpdateDocumentRequest`; `destroy`; authorize everywhere; "New document" and owner-only delete on dashboard; edit page with title input and Save button.
- **Verify:** owner creates, renames, deletes; another user's private doc returns 403.
- **Done:** CRUD works and authorization is enforced.

### Phase 5: Tiptap editor

- **Work:** `rich-editor`, `editor-toolbar`, `save-status`, `edit.tsx`, `.tiptap` CSS per section 9.
- **Verify:** apply all six formats, Save, hard refresh, content identical.
- **Done:** all formats persist across refresh.

### Phase 6: Sharing

- **Work:** `DocumentShareController`; `StoreShareRequest` (email `required|email|exists:users,email`, not the owner, permission in `view,edit`; `updateOrCreate`); owner-only authorize; share dialog; view-only editor mode; 403 page.
- **Verify:** share, log in as other user, confirm view vs edit behavior.
- **Done:** sharing and permissions work end to end.

### Phase 7: Import

- **Work:** `TiptapImporter`, import route and request, dialog per section 10.
- **Verify:** import a sample `.txt` and `.md`; try a `.pdf`.
- **Done:** valid files open as editable docs; invalid files show a validation error.

### Phase 8: Validation and errors

- **Work:** inline errors for title, share email (unknown, self, duplicate), import (type, size, empty); toasts on save, share, import, delete; loading states. No new features.
- **Done:** every failure path shows a clear message.

### Phase 9: Automated test

- **Work:** `tests/Feature/DocumentAccessTest.php` as a single Pest test with `RefreshDatabase` and factories. Actors: owner, shared-edit user, shared-view user, stranger. Assert: owner GET 200; stranger GET 403 and PUT 403; view-user GET 200 and PUT 403; edit-user PUT 200 with content persisted; edit-user DELETE 403; owner DELETE succeeds.
- **Verify:** `php artisan test`.
- **Done:** test passes.

### Phase 10: Polish and production readiness

- **Work:** spacing, editor column, empty states, responsive toolbar wrap. Check `.env.example`, `.gitignore` (`.env`, `node_modules`, `vendor`, `database.sqlite`), `npm run build`, Postgres-safe migrations.
- **Done:** build passes, no secrets tracked.

### Phase 11: Documentation

- **Work:** `README.md` (features, local setup, demo accounts, test command, live URL), `ARCHITECTURE.md` (layers, data model, authorization table, storage format, import flow, trade-offs, what was cut), `AI-WORKFLOW.md` (Claude plans/reviews, Codex implements in small prompts, Copilot inline; what was verified by hand; one concrete example where AI output was corrected), `SUBMISSION.md` (live URL, credentials, video link, checklist). Be factual to the actual code.

### Phase 12: Deployment review

- Read-only checklist: migrate and seed on deploy, `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, Postgres env vars, session/cache drivers work, assets built, no dependence on local SQLite or file storage.

## 14. Deployment (Laravel Cloud)

1. Push `main` to GitHub; confirm `.env` is not tracked.
2. Create Cloud account, connect GitHub, create the app (smallest compute).
3. Add Serverless Postgres and attach it, or set external Postgres `DB_*` vars. Verify the DB variables are injected.
4. Env vars: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL` (verify whether Cloud sets it), `DB_CONNECTION=pgsql`, `MAIL_MAILER=log`, `SESSION_DRIVER=database`.
5. Build commands (verify in dashboard): `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`.
6. Deploy command (verify in dashboard): `php artisan migrate --force && php artisan db:seed --force`.
7. Set the PHP version to match `composer.json`.
8. Smoke test on the live URL (section 15).

## 15. Manual test matrix (live URL)

| #   | Check                                            | Expected                                      |
| --- | ------------------------------------------------ | --------------------------------------------- |
| 1   | Login as owner                                   | Dashboard with both sections                  |
| 2   | Create document                                  | Editor opens, title "Untitled document"       |
| 3   | Rename, Save                                     | Saved state; dashboard shows new title        |
| 4   | Bold, italic, underline, H1/H2, bullets, numbers | All render                                    |
| 5   | Save, hard refresh                               | Content and formatting identical              |
| 6   | Go back, reopen                                  | Same content                                  |
| 7   | Import `.txt`                                    | New doc with paragraphs                       |
| 8   | Import `.md`                                     | Headings and lists correct                    |
| 9   | Import `.pdf`                                    | Validation error, no doc created              |
| 10  | Share with reviewer (edit)                       | Appears in share list                         |
| 11  | Log out, log in as reviewer                      | "Shared With Me" shows doc with correct badge |
| 12  | Reviewer edits and saves                         | Persists; owner sees it                       |
| 13  | Reviewer on a view-only doc                      | Toolbar disabled, no Share button             |
| 14  | Reviewer opens owner's private doc URL           | 403 page                                      |
| 15  | Share with unknown or own email                  | Inline validation error                       |
| 16  | Save with empty title                            | Validation error                              |
| 17  | Log out, open a doc URL                          | Redirects to login                            |
| 18  | Log back in                                      | Data still present                            |

## 16. Known risks and fallbacks

1. **Cloud signup, billing or build blocks deploy.** Deploy the bare kit first. Fallback: keep Cloud for the app and point `DB_*` at an external Postgres.
2. **Lists or headings do not render.** Add the `.tiptap` CSS (section 9).
3. **Wrong Inertia or Laravel API used.** Read installed versions; copy working patterns from the kit's own pages.
4. **Works locally, fails in production.** Check `APP_KEY`, `APP_URL`, DB env vars and Cloud logs; temporarily enable `APP_DEBUG` to read the error, then disable.
5. **Saved content lost or double-encoded.** Use the `array` cast and never hand-encode. Fallback: `longText` column with explicit `json_encode`/`json_decode` in the controller.

## 17. Submission package

- `ajaia-docs-source.zip` via `git archive --format=zip -o ajaia-docs-source.zip HEAD` (tracked files only). Verify with `unzip -l ajaia-docs-source.zip | grep -E "\.env$|node_modules|vendor/|\.sqlite"`, which must return nothing. `.env.example` should be present.
- `README.md`, `ARCHITECTURE.md`, `AI-WORKFLOW.md`, `SUBMISSION.md`
- `DEMO-VIDEO.txt` containing the video URL
- Optional screenshots: dashboard, editor, share dialog

## 18. Definition of done

Live URL works for both users; the manual matrix passes on production; the Pest test passes; all docs and the video are in the Drive folder; the source zip is verified clean.

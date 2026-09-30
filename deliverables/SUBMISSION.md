# Submission Contents

This submission contains the Ajaia Collaborative Docs Laravel application and the following implemented functionality:

- **Local development:** Laravel 13 application using the React starter kit, React 19, Inertia 3, TypeScript, Tailwind CSS 4, and Tiptap 3.
- **Demo data:** An idempotent seeder for `owner@example.com` and `reviewer@example.com` (both password `password`), their example documents, and the requested edit/view shares.
- **Document persistence:** SQLite/PostgreSQL-compatible document and share schemas; Eloquent relationships and JSON casting for Tiptap document content.
- **Dashboard:** Separate owned and shared document lists, permission badges, empty states, document creation and owner-only deletion.
- **Editor:** Tiptap formatting toolbar, title editing, explicit save via button or Ctrl/Cmd+S, save status, and view-only presentation.
- **Sharing:** Owner-managed grants for existing users with `edit` or `view` permission, current-share listing, and access removal.
- **Import:** Authenticated TXT and limited Markdown import, with file/type/size/name/content validation and conversion to Tiptap JSON.
- **Authorization:** `DocumentPolicy` enforcement for viewing, updating, sharing, and deleting; UI capability flags supplement but do not replace server checks.
- **Validation and feedback:** Inline field errors, success/error toast feedback, and submit/loading states for relevant actions.
- **Feature tests:** `tests/Feature/DocumentAccessTest.php` covers access behavior and document, share, and import flows, including important validation failures.
- **Documentation:** This README plus the architecture and AI workflow notes in `deliverables/`.

## Verification recorded

The latest implementation checks passed: 118 Laravel tests (507 assertions), PHPStan, Pint, frontend formatting/lint checks, TypeScript, production frontend build, and `git diff --check`. Browser smoke checks confirmed owner login, editor rendering, title validation feedback, duplicate-share validation, and save/share loading feedback.

## Not included

- No claim of a production deployment or production environment verification is made here.
- No realtime co-editing, autosave, comments, version history, export, DOCX import, or document-specific roles/teams.
- No separate REST API or new third-party dependencies were added for these deliverables.

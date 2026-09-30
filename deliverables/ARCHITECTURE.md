# Architecture

## Stack and request flow

The application uses Laravel 13 and Eloquent for routing, validation, authorization, and persistence. React 19 pages are rendered through Inertia 3; the dashboard is `resources/js/pages/dashboard.tsx` and the document editor is `resources/js/pages/documents/edit.tsx`. Tiptap provides the editor UI and stores document bodies as JSON in the `documents.content` column.

Authenticated document routes connect the dashboard and editor to `DocumentController`. `DocumentShareController` handles access grants and removals. Form Requests validate document updates, sharing, and imports; `TiptapImporter` converts supported TXT and limited Markdown input into Tiptap JSON. Inertia flash data is presented as Sonner toasts, with validation errors also shown inline.

## Data and access rules

- `Document` belongs to an owner and may be shared with users through `document_shares`, which records `view` or `edit` permission.
- `DocumentPolicy` is the server-side authority: owners can manage their documents; shared editors can view and update; viewers can only view; other users cannot access them.
- The editor receives `can` flags to hide or disable actions, but the policy remains the security boundary.
- Migrations use portable schema operations intended for SQLite locally and PostgreSQL in production.

## Editing and import behavior

Saving is explicit through the Save button or Ctrl/Cmd+S. The editor reports saved, unsaved, saving, and error states; there is no autosave or realtime synchronization. TXT imports create paragraphs; Markdown imports support basic headings and lists. Uploads are validated and converted without being stored as files.

## Deliberate scope

This is a lightweight editor rather than a realtime collaboration suite. It does not implement live co-editing, autosave, comments, version history, export, DOCX import, or document-specific roles and teams.

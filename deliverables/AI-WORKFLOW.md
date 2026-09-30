# AI-Native Workflow Note

## Tools used

- **GitHub Copilot (Free Plan) in VS Code** as the AI coding assistant for requirements-driven implementation and review.
- **VS Code workspace tools** to inspect source files and package versions, edit code, and run checks.
- **Laravel Boost** for project/runtime context and framework documentation where version-specific behavior mattered.

## Where AI materially helped

AI was most useful for connecting the end-to-end slices across Laravel routes, Form Requests, policies, Eloquent models, Inertia pages, and React components while keeping the existing starter-kit conventions. It also helped produce focused regression coverage for sharing permissions, upload validation, and document access, and made rapid feedback-driven iterations practical within the assessment time.

## Output reviewed and changed

AI suggestions were treated as drafts, not accepted blindly. For example:

- The existing share endpoint could update an existing share's permission. For the requested duplicate-share validation UX, that behavior was changed to return an inline validation error for an already-shared user instead.
- Import validation was strengthened to check both the supplied extension and the detected file type; extension alone does not establish the file's content type.
- TypeScript caught that concise Inertia error callbacks returned Sonner's toast identifier instead of `void`. Those callbacks were rewritten with block bodies, and the type check was rerun.

The implementation was kept within the requested feature set; suggestions that would expand scope (such as autosave or realtime collaboration) were not pursued.

## Verification approach

Correctness was checked with Pest feature tests, including owner/editor/viewer/stranger access, share validation, and import failure cases. PHPStan, Pint, frontend lint/format checks, TypeScript, the production build, and `git diff --check` were also run. Browser smoke checks exercised demo-owner login, the dashboard/editor, title validation, duplicate-share feedback, and save/share loading feedback.

The checks above verify the local implementation, not a production deployment. Reviewers should still inspect the code and repeat the acceptance flow against the deployed environment once a live URL is available.

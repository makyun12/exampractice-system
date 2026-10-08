# ExamPractice System

## Product
Laravel application for structured learning and timed multiple-choice practice.
Interface languages: English (default), Japanese, Indonesian. Content stays in its authored language.

## Admin
- Dashboard with real counts and recent practice results.
- Create, edit, archive programs, modules, packages, questions, and materials.
- Program > Module > Practice Package > Question; materials belong to modules.
- Manual question authoring (A-D), answer key and explanation.
- Fixed-template CSV/XLSX import with preview, validation, atomic confirmation, and template download.
- Create student IDs/passwords, activate/deactivate accounts, reset passwords.
- Assign access to entire programs/modules or individual packages/materials. Parent selection includes all active descendants; leaf selection exposes only that leaf and its navigation ancestors.
- Device binding per student, device metadata, last seen, and reset/revoke sessions.
- Filter results by student, program, module, package, and dates; inspect answer reviews and export CSV.

## Student
- Login with admin-issued credentials; no public registration.
- Dashboard, accessible programs/modules/packages, and learning materials.
- Material reader with safe Markdown rendering and completion tracking.
- Timed practice, visible sticky timer, question navigator, automatic answer saves.
- Server-enforced deadline; refresh cannot restart the timer. Expired sessions are finalized on requests and by the scheduled expiry command.
- Immutable question snapshots for reliable historical scores.
- Automatic scoring, correct/wrong/unanswered totals, optional explanations, attempt history.
- Profile, password change, and interface language selection.

## Security boundaries
- Authorization enforced on server routes and resource lookups, not just menus.
- Device binding identifies a browser installation through a random encrypted cookie; it cannot reliably identify a physical device. Clearing cookies or changing browsers requires admin reset when binding is enabled.
- Basic copy deterrence is optional per package. Screenshots cannot be blocked reliably by a website.
- No watermark or tab-switch warning.
- CSRF protection, hashed passwords, rate-limited login, safe material rendering.

## Delivery
- Runnable local application with clearly documented demo credentials.
- MySQL-compatible schema; SQLite for self-contained local preview.
- Focused automated feature/security tests and desktop/mobile browser checks.
- Deployment guide for Hostinger, including scheduler and production setup.

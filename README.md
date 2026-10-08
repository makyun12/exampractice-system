# ExamPractice System

A multilingual learning and exam practice platform built with Laravel. Organize learning materials, deliver timed quizzes, and manage student access from one application.

**English / Japanese / Indonesian** · **Admin and student workspaces** · **Responsive web interface**

[Live Website](https://exam.poch12.id)

> Access requires an administrator-issued account. Public student registration is not available.

## Overview

ExamPractice supports structured learning for language study, exam preparation, and workplace training. Administrators decide what each student can access, while students read materials, practice with a visible countdown, and review their results.

Content follows a simple hierarchy:

```text
Program
└── Module
    ├── Learning Materials
    └── Practice Packages
        └── Multiple-Choice Questions
```

For example: **Japanese Language Training → Basic Vocabulary → JLPT N5 Practice – Part 1**.

## Features

### Student Workspace

- Sign in with an ID and password issued by an administrator.
- Browse only the active programs, modules, packages, and materials assigned to the account.
- Read learning materials and track completion.
- Answer A–D multiple-choice questions with a sticky timer and question navigation.
- Resume an ongoing attempt without resetting its deadline.
- Save answers automatically while working.
- Receive automatic scores and review correct, incorrect, and unanswered questions.
- View attempt history and explanations when enabled.
- Update the account password and interface language.

### Admin Workspace

- Create and manage student accounts, passwords, and account status.
- Create, edit, and archive programs, modules, practice packages, and questions.
- Write questions manually or import a fixed-template CSV/XLSX file with validation and a preview.
- Write Markdown learning materials with formatting controls and a preview.
- Grant access to an entire program/module or individual packages and materials.
- Filter practice results and export them as CSV.
- Manage optional browser binding, inspect recorded browser activity, and reset access.

## How It Works

1. An administrator prepares programs, materials, and practice packages.
2. The administrator creates student accounts and assigns access.
3. Students read their materials and start a selected practice package.
4. The server saves answers and enforces the attempt deadline.
5. Submission produces a score and a reviewable result.

A scheduled task finalizes expired attempts even when the student's browser is closed. Without that task, expired attempts are finalized on the next relevant request.

## Technology Stack

| Layer | Technologies |
| --- | --- |
| Backend | PHP, Laravel 13, Eloquent |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Lucide |
| Database | MySQL/MariaDB for hosting; SQLite for local development |
| Question imports | PhpSpreadsheet |
| Learning materials | Marked and DOMPurify |
| Build tooling | Vite, pnpm |
| Testing | PHPUnit, Playwright |

## Engineering Highlights

- **Server-side authorization:** access is checked on routes and resources, not just hidden in navigation.
- **Reliable attempt history:** each attempt snapshots its questions and grading data, so later content edits do not alter past results.
- **Server-enforced timing:** refreshing the page does not restart the timer; repeated submission cannot create a second final result.
- **Validated imports:** invalid rows block the whole import, and duplicate questions are rejected.
- **Session protection:** password changes and administrative resets invalidate earlier sessions.
- **Safe material rendering:** Markdown content is sanitized before display.

Core logic is separated into [LearningAccess](app/Services/LearningAccess.php), [AttemptService](app/Services/AttemptService.php), and [QuestionImport](app/Services/QuestionImport.php).

## Scope and Limitations

- Interface language selection does not translate administrator-authored questions or materials.
- Question imports require the supplied template, with a maximum of 500 questions and a 2 MB file.
- Browser binding uses a cookie, not a physical-device identifier. Clearing cookies or switching browsers may require an administrator reset.
- Only answers received by the server before the deadline are counted. Network retries require the practice page to remain open.
- Optional copy deterrence is not DRM. A website cannot reliably prevent screenshots.
- Device records do not provide screen surveillance or precise location tracking.
- No public student registration, watermark, or tab-switch warning is included.
- Sample questions are illustrative and are not official exam materials.

## Asset Credits

Program photographs are sourced from Unsplash: photo IDs `1493976040374-85c8e12f0c0e`, `1507842217343-583bb7270b66`, and `1497366754035-f200968a6e72`.

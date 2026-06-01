# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack & running locally

Plain PHP + MySQL (PDO). No build step, no package manager, no test suite. Each `.php` file in the project root is a page served directly by the web server.

Serve locally with PHP's built-in server from the project root:

```
php -S localhost:8000
```

The app expects a MySQL database matching the credentials in `db.php` (`$db_host`, `$db_user`, `$db_pwd`, `$db_db`). There is no migrations file — the schema must be created manually before first run.

## Database schema (inferred from queries)

There is no schema file in the repo. The following tables are referenced across the PHP files and must exist:

- `Users(user_id PK, email, password, screenname, avatar_url)` — `password` stores a `password_hash()` value (added in the most recent commit; pre-existing rows from before that commit will not verify).
- `Topics(topic_id PK, user_id FK→Users, title, created_at)`
- `Notes(note_id PK, topic_id FK→Topics, user_id FK→Users, content, created_at)`
- `Access(topic_id, user_id, status)` — composite/uniq on (topic_id, user_id); `status=1` means granted, `status=0` means revoked. `Access.php` toggles `status` rather than deleting rows.

## Architecture

**Page-per-file routing.** Each top-level `.php` file is both controller and view: it `session_start()`s, opens a PDO connection from `db.php`, handles its own `POST`/`GET`, then renders HTML inline. There is no router, framework, or shared layout.

**Auth.** Session-based. Every protected page does `if (!isset($_SESSION['user_id'])) header("Location: login.php")` at the top. `login.php` populates `$_SESSION['user_id']`, `screenname`, `avatar_url`; `logout.php` destroys the session.

**Authorization for topic access.** A user may read/write a topic if they own it (`Topics.user_id = session user`) OR have an active grant (`Access.status = 1`). This check is duplicated in `viewNote.php` and `ajax_backend.php` — if you change the access model, update both. Only owners see "Manage Access" on `topiclist.php`; `Access.php` rejects non-owners.

**AJAX endpoint.** `ajax_backend.php` is a single dispatcher keyed on `$input['action']`. It accepts JSON (`php://input`) or form `$_POST`, and currently handles `create_topic` and `add_note`. Note submissions on `viewNote.php` use this endpoint client-side, but the page also keeps a server-side `POST` handler as a fallback — both code paths exist for the same operation.

**Per-page JavaScript convention.** Each page includes the shared stub `js/eventHandlers.js` in `<head>`, then a page-specific `js/eventRegister<Page>.js` at the end of `<body>` that wires up that page's form validation/handlers. Adding a new page with a form follows this same pattern.

**XSS handling.** Inputs are sanitized with a local `test_input()` (`trim` + `stripslashes` + `htmlspecialchars`) defined separately in `Signup.php`, `login.php`, and `createNewtopic.php`. Note content is intentionally stored raw and escaped on render (`viewNote.php` uses `nl2br(htmlspecialchars(...))`, and `ajax_backend.php` comments explicitly: "DO NOT encode here, encode on display to avoid double encoding"). Preserve this convention when touching note storage.

**Avatar uploads.** Saved as `uploads/<user_id>.<ext>` in `Signup.php`. Display code in `topiclist.php` falls back to `images/default.jpeg` if the file is missing, so a stale `avatar_url` in the DB won't break rendering.

# Poll Site

A simple, modern recurring poll website built with PHP, Apache, SQLite3, and Bootstrap.

## Features

### User Features
- **Poll Participation Form**: Add your name and join directly in the web UI.
- **Name Validation**: Names are restricted to letters and numbers only (no spaces).
- **Email Notifications**: Add an email address to receive notifications about new participants.
- **Unsubscribe Link**: Subscribers can unsubscribe using a personal token link.
- **Modern Success Notification**: Adding an entry via form shows a centered Bootstrap toast (larger and longer visible).
- **Theme Toggle**: Light/dark mode switch with local preference storage.
- **Responsive UI**: Bootstrap-based layout for desktop and mobile.

### Poll Display Features
- **Slug-based Poll URLs**: Polls are accessible via slug routes.
- **Poll Cycle Metadata in Header**:
  - Interval in days (with singular/plural handling, e.g. `1 day` vs `2 days`)
  - Next clear date
- **Recurring Clear Logic**: Poll entries are automatically archived/cleared based on schedule.

### Admin Features
- **Admin Login**: Protected admin area.
- **Dashboard**: Central poll management view.
- **Entry Management**: Edit and delete entries.
- **Poll Settings**: Configure title, slug, description, interval, and next clear date.
- **History View**: Search and inspect archived poll cycles.

## Direct URL Participation (No Browser Form Interaction)

You can add a participation entry directly via URL query parameters.

Supported route formats:
- `/<slug>?name=<name>`
- `/<slug>/index.php?name=<name>`

Example:

```text
https://example.org/poll-site/myslug/index.php?name=myname
```

Optional email example:

```text
https://example.org/poll-site/myslug/index.php?name=myname&email=myname@example.org
```

Behavior:
- Requires `name`.
- `name` must match letters/numbers only (no spaces).
- `email` is optional, but if provided it must be valid.
- If a valid email is provided, subscription is enabled automatically.
- Returns JSON (`201` on success, `4xx` on invalid input or missing poll).

## Response Examples

### `201 Created` (successful participation)

```json
{
  "success": true,
  "entry_id": 42,
  "poll_slug": "myslug"
}
```

### `400 Bad Request` (invalid name, e.g. contains spaces)

```json
{
  "error": "Name must contain only letters and numbers without spaces"
}
```

### `400 Bad Request` (invalid email)

```json
{
  "error": "Invalid email"
}
```

### `404 Not Found` (poll slug not found)

```json
{
  "error": "Poll not found"
}
```

## Requirements

- PHP 7.4+
- Apache 2.4+ (with `mod_rewrite` enabled)
- SQLite3
- Bootstrap 5

## Installation

1. Clone this repository.
2. Place files in your Apache web root (for example `C:\Apache24\htdocs\poll-site`).
3. Ensure the `data` directory is writable by Apache/PHP.
4. Copy `.env.example` to `.env` and adjust values.
5. Set `ADMIN_USERNAME` and `ADMIN_PASSWORD_HASH` in `.env`.
6. Configure email settings in `.env`.
7. Open admin login (for example `http://localhost/poll-site/admin/login.php`) and sign in.
8. Create and configure polls in the admin area (regular users can only join existing polls).
9. Open the poll list in your browser (for example `http://localhost/poll-site/poll-list`).

### Admin Setup (required for creating polls)

Only admins can create polls. Regular users can participate, but cannot create or manage polls.

Generate a password hash for `.env`:

```powershell
php -r "echo password_hash('YourStrongPasswordHere', PASSWORD_BCRYPT), PHP_EOL;"
```

Then put the generated value into:

- `ADMIN_PASSWORD_HASH=<generated_hash>`

## License

MIT
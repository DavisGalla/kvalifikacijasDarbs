# SportWeb

A sports competition management platform built with Laravel. Users sign in with Google. They can
organise and enter competitions as individuals or teams, record results, keep a training log with
personal records, and post on a blog. An optional Google Calendar connection adds the
competitions they register for to their calendar.

## Contents

- [Stack](#stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Creating the first admin](#creating-the-first-admin)
- [Running in production](#running-in-production)
- [Tests](#tests)
- [Business rules](#business-rules)
- [Design decisions and limitations](#design-decisions-and-limitations)

## Stack

| Part | Technology |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Admin panel | Filament 5 at `/admin` |
| Sign-in | Google OAuth through Laravel Socialite |
| Calendar | Google Calendar API (`google/apiclient`), synced by queued jobs |
| Front end | Blade, Tailwind CSS, Alpine.js, Vite |
| Database | SQLite by default; MySQL/MariaDB are supported through `DB_CONNECTION` |
| Tests | Pest |

## Requirements

- PHP 8.2 or newer with the `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `openssl`, `intl` and `fileinfo` extensions
- Composer 2
- Node.js 20+ and npm
- A Google Cloud project with an OAuth client (see [Google OAuth](#google-oauth)). Without one,
  nobody can sign in.

## Installation

```bash
git clone <repository-url> sportweb
cd sportweb

composer install
cp .env.example .env
php artisan key:generate

# SQLite: create the database file (skip when using MySQL)
touch database/database.sqlite

# Fill in GOOGLE_* and ADMIN_EMAIL in .env (see Configuration), then:
php artisan migrate --seed

npm install
npm run build
```

To start everything for development (web server, queue worker, log viewer and Vite) in one go:

```bash
composer run dev
```

The app is then served at http://localhost:8000. `composer run dev` already runs a queue
worker. If you start the server some other way, also run `php artisan queue:work` (see
[Queue worker](#queue-worker)).

`php artisan migrate --seed` does the following:

- creates the first admin account for `ADMIN_EMAIL` (see below);
- **only when `APP_ENV` is `local` or `testing`:** adds demo users, teams and competitions (all
  `@example.com` addresses). Demo data is never seeded in other environments. To add it on its
  own, run `php artisan db:seed --class=DemoDataSeeder`.

## Configuration

All settings are in `.env`. These are the ones that matter beyond a standard Laravel install:

| Variable | Purpose |
| --- | --- |
| `APP_ENV` | `local` for development, `production` on a server. It controls only whether demo data is seeded; it never grants admin access. |
| `APP_KEY` | Created by `key:generate`. It also encrypts the stored Google tokens, so **do not change it on a running installation**: connected users would have to reconnect Google Calendar. |
| `ADMIN_EMAIL` | Google account email for the first admin. Leave it empty to skip. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | OAuth client credentials. |
| `GOOGLE_REDIRECT_URI` | Must exactly match an authorised redirect URI of the OAuth client, e.g. `http://localhost:8000/auth/google/callback`. |
| `QUEUE_CONNECTION` | `database` by default. A worker must be running; see below. |
| `DB_*` | Database connection. SQLite needs no further settings. |

### Google OAuth

1. In the [Google Cloud Console](https://console.cloud.google.com/) create a project and enable the
   **Google Calendar API**.
2. Configure the **OAuth consent screen**. Add the scopes `openid`, `email`, `profile` and
   `https://www.googleapis.com/auth/calendar`. While the app is in *Testing* mode, add every
   Google account that should be able to sign in as a test user.
3. Under **Credentials**, create an **OAuth client ID** of type *Web application*. Add
   `GOOGLE_REDIRECT_URI` (e.g. `http://localhost:8000/auth/google/callback`) as an authorised
   redirect URI.
4. Copy the client ID and secret into `.env`.

Signing in requests only the basic scopes. Calendar access is requested separately, after a page
that explains it, and only when a user chooses to connect their calendar. Everything else works
without a calendar connection.

### Queue worker

Google Calendar events are created and removed by queued jobs, so registering or withdrawing never
waits on Google. With `QUEUE_CONNECTION=database`, **nothing reaches Google unless a worker is
running**. Registrations still succeed, but no calendar events are created or removed.

```bash
php artisan queue:work --tries=5
```

When a temporary failure happens (network error, rate limit, Google 5xx), the job is retried with
increasing back-off (30 s, 2 min, 10 min, 30 min). If the user's access has been revoked or has
expired, the job does not retry. Instead it forgets the stored tokens, and the user is asked to
reconnect. Reconnecting adds any upcoming registrations that are missing from their calendar. Jobs
that still fail after every retry are kept in `failed_jobs` (`php artisan queue:failed`,
`php artisan queue:retry all`).

## Creating the first admin

Access to the admin panel depends only on the `is_admin` flag. It does not depend on `APP_ENV`.

- **New installation:** set `ADMIN_EMAIL` to your Google account and run
  `php artisan db:seed --class=AdminUserSeeder` (or `migrate --seed`). The seeder creates an admin
  account with that email only if the database has no admin yet. It **never promotes an account
  that already exists**. After that, sign in with that Google account and open `/admin`.
- **Promoting or demoting an existing user:**

  ```bash
  php artisan user:set-admin someone@example.com
  php artisan user:set-admin someone@example.com --revoke
  ```

## Running in production

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force   # first deployment only
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- Set `APP_ENV=production` and `APP_DEBUG=false`, and use HTTPS for `APP_URL` and
  `GOOGLE_REDIRECT_URI`.
- Run `php artisan queue:work` permanently, e.g. under Supervisor or a systemd service, and restart
  it after each deployment (`php artisan queue:restart`).
- Back up the database **and** `APP_KEY`. Without the key, stored Google tokens cannot be
  decrypted.

Maintenance commands:

| Command | Purpose |
| --- | --- |
| `php artisan user:set-admin {email} [--revoke]` | Grant or revoke admin access |
| `php artisan results:recalculate-positions [competition]` | Recompute result positions (they are kept up to date automatically; this is for repairs) |

## Tests

```bash
php artisan test
```

The suite runs on an in-memory SQLite database with the `sync` queue. Google APIs are replaced by
mocks, so no credentials are needed. Besides the regular feature tests, it covers:

- direct HTTP requests that bypass the interface, such as results for unconfirmed participants,
  winners outside the competition, and actions before a competition has started;
- concurrent registrations and roster changes (`ConcurrentRequestsTest` runs separate PHP
  processes);
- roster changes after a team has registered, and team deletion after results were saved;
- invalid results for each sport format, and winners that contradict the results;
- repeated and cancelled Google Calendar jobs, revoked access and refused consent;
- the same rules applied in the Filament admin panel.

## Business rules

The rules live in services and models, not only in controllers. The organizer pages and the
Filament admin panel therefore enforce the same rules.

### Competition lifecycle

A competition is `draft`, `published` or `cancelled`. While published, its schedule determines its
phase: *upcoming*, then *in progress*, then *finished*.

| Action | Allowed when |
| --- | --- |
| Create with a registration deadline | The deadline is in the future and before the start |
| Register | Published, and before the registration deadline |
| Withdraw | Before the start |
| Record results and matchups | Published and started (corrections after the end are allowed) |
| Declare the winner | Published and finished |

### Registration and team rosters

- Registering takes a lock on the competition row. Concurrent registrations therefore cannot exceed
  `max_participants` or register the same participant twice.
- Every roster change goes through `TeamRoster` and holds a lock on the team, including changes
  made in the admin panel. A team registered for an upcoming competition cannot grow past that
  competition's `max_team_members` or shrink below its `min_team_members`.

### Results

- Each sport defines its result format: **time** (lowest wins) or **score** (highest wins), the
  number of decimals (0–3) and an optional maximum. Times can be entered as `63.48`, `1:03.48` or
  `1:02:03.4` and must be greater than zero. Scores cannot be negative. `results.value` stores the
  value in seconds or points as `decimal(10,3)`, so every allowed value is stored exactly.
- Only confirmed participants can receive a result. The `Result` model validates every write,
  wherever it comes from.
- **Positions are always calculated, never entered by hand.** They are recomputed every time a
  result changes, according to the sport's direction. Tied values share a position (1, 2, 2, 4).

### Matchups (team competitions)

- Only confirmed teams can play, a team cannot play itself, and **each team plays at most once per
  round**. Unique indexes back this up. Submitting the same game twice is therefore refused, while
  a rematch is recorded in a later round. Each game can also have the date it was played.
- The standings award 3 points for a win and 1 for a draw. Ties are broken by goal difference, then
  by goals scored.

### Winner

The ranking used is the matchup standings for team competitions that have matchups, and the
results otherwise. The winner is recorded in one of two ways:

- **Automatic:** the sole leader of the ranking. It is updated whenever results or matchups change.
  While first place is tied or empty, there is no winner.
- **Manual:** a confirmed participant chosen by the organizer, e.g. after a disqualification or a
  judging decision. A reason is required, and it is shown with the winner. Later result changes do
  not override it.

### Deleting teams and accounts

Registrations, results and winners refer to users and teams polymorphically
(`registrant_type`/`registrant_id`). The database cannot enforce foreign keys on these columns, so
the application protects them:

- A team with any competition history (registrations, results, matchups, a win) is **archived**
  (soft deleted) instead of deleted. It keeps appearing in past results under its name. A team
  without history is deleted. A team registered for an upcoming competition must withdraw first.
- A user with competition history is **anonymized**: their personal data is removed and the row is
  kept, so results stay intact. A user without history is deleted. Posts, comments and training
  data are always removed. When the account is deleted, the Google access token is revoked as well.

### Google Calendar

- Access and refresh tokens are stored encrypted (`encrypted` casts using `APP_KEY`) and are hidden
  from serialization. Disconnecting revokes the access at Google, and does not just delete the
  tokens locally.
- The calendar event id is derived from the registration. A job that runs twice therefore updates
  the same event instead of creating a duplicate. A cancellation can delete the event even when
  the create job has not saved its id yet.
- The create job checks the registration before calling Google, and checks it again afterwards
  under a row lock. If the registration was cancelled in between, the event is removed again.

### Rate limits

To prevent automated flooding, each user can create at most 3 blog posts per minute (20 per
hour) and 6 comments per minute (60 per hour).

## Design decisions and limitations

- **Training data model.** A workout entry stores the exercise name and the weight of each set
  (`set_weights`). Repetitions and set types are not stored. This was a deliberate scope decision:
  the feature records the weights used, not a full training analysis. "100 kg × 1" and
  "100 kg × 10" therefore look the same. Supporting progress analysis would need a `workout_sets`
  table (reps, weight, set type) and an `exercises` catalogue, so that names like "Bench press" and
  "Bench" refer to the same exercise. Exercises are currently free-text names.
- **Workout sessions** are saved in one transaction: a session is saved completely or not at all.
- **Lists are paginated:** competitions, competition history, teams, blog posts, comments and
  workout history (paged by training day, so a day is never split). The exercise suggestions are
  built from the full history, not just the visible page.
- **Moderation:** authors can delete their own posts and comments. Admins can manage all data in
  the admin panel. There is no reporting or review queue for inappropriate content yet.
- **Competition detail page:** participants, results and matchups of one competition are loaded
  together. This is fine for typical competition sizes, but very large events would need
  pagination there too.

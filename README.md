# Chat System

A real-time, private messaging web application built with Laravel 12. Users can search for other registered users, start one-on-one conversations, send text messages and file attachments, and see live typing indicators and online presence — all powered by WebSockets via Laravel Reverb.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 12 |
| Templating | Blade |
| Styling | Tailwind CSS 3 |
| Database | MySQL |
| WebSocket Server | Laravel Reverb |
| WebSocket Client | Laravel Echo + Pusher.js |
| Authentication | Laravel Breeze |

---

## Requirements

| Software | Minimum Version |
|---|---|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 18.x+ |
| npm | 9.x+ |
| MySQL | 8.0+ |
| Git | 2.x |

---

## Project Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd chat-system
```

### 2. Copy the Environment File

**macOS / Linux**
```bash
cp .env.example .env
```

**Windows**
```bash
copy .env.example .env
```

### 3. Configure the Database

Open the `.env` file and update the database credentials to match your local MySQL setup.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_system
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL if it does not already exist.

```sql
CREATE DATABASE chat_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Install PHP Dependencies

```bash
composer install
```

### 5. Generate the Application Key

```bash
php artisan key:generate
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Install JavaScript Dependencies

```bash
npm install
```

### 8. Configure Laravel Reverb

The `.env.example` ships with pre-filled Reverb values. Verify they are present in your `.env` file.

```env
REVERB_APP_ID=chat-system-app
REVERB_APP_KEY=chat-system-key
REVERB_APP_SECRET=chat-system-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> The `VITE_*` variables expose the Reverb connection details to the browser-side JavaScript bundle. Both sets must be present.

---

## Running the Application

The application requires **four terminal processes** running simultaneously during development.

**Terminal 1 — Laravel Development Server**
```bash
php artisan serve
```

**Terminal 2 — Vite Asset Bundler**
```bash
npm run dev
```

**Terminal 3 — Laravel Reverb WebSocket Server**
```bash
php artisan reverb:start
```

**Terminal 4 — Queue Worker**
```bash
php artisan queue:listen
```

Once all four processes are running, open your browser and navigate to `http://localhost:8000`.

> **Tip:** The `composer dev` script starts all four processes concurrently in a single terminal using Laravel's concurrency helper. Run `composer dev` as an alternative to opening four terminals.

---

## Frontend Routes

All routes require an authenticated and email-verified session unless marked otherwise.

| Route | Page | Auth Required | Description |
|---|---|---|---|
| `GET /` | Redirect | No | Redirects to `/chat` |
| `GET /login` | Login | No (guest only) | Session login form |
| `POST /login` | Login | No (guest only) | Authenticate user credentials |
| `GET /register` | Register | No (guest only) | New account registration form |
| `POST /register` | Register | No (guest only) | Create a new user account |
| `GET /forgot-password` | Forgot Password | No (guest only) | Request a password reset email |
| `POST /forgot-password` | Forgot Password | No (guest only) | Send password reset link |
| `GET /reset-password/{token}` | Reset Password | No (guest only) | Password reset form |
| `POST /reset-password` | Reset Password | No (guest only) | Apply new password |
| `GET /verify-email` | Verify Email | Yes | Prompt to verify email address |
| `GET /verify-email/{id}/{hash}` | Verify Email | Yes | Confirm email verification link |
| `GET /confirm-password` | Confirm Password | Yes | Re-authenticate for sensitive actions |
| `GET /chat` | Chat | Yes | Main real-time chat interface |
| `GET /profile` | Profile | Yes | View and edit profile information |
| `POST /logout` | — | Yes | Log out and invalidate session |

---

## Endpoint Reference

These endpoints are consumed by the Alpine.js frontend via Axios. All protected endpoints require a valid authenticated session cookie and the `X-CSRF-TOKEN` header.

---

### Authentication

#### Register

```
POST /register
```

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `email` | string | required, email, unique |
| `password` | string | required, min 8, confirmed |
| `password_confirmation` | string | required |

**Success:** Redirects to `/chat` with authenticated session.

---

#### Login

```
POST /login
```

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, email |
| `password` | string | required |

**Success:** Redirects to `/chat` with authenticated session.

---

#### Logout

```
POST /logout
```

**Headers**
```
X-CSRF-TOKEN: {token}
```

**Success:** Session destroyed, redirects to `/login`.

---

#### Forgot Password

```
POST /forgot-password
```

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, email |

**Success:** Password reset link sent to the provided email address.

---

#### Reset Password

```
POST /reset-password
```

| Field | Type | Rules |
|---|---|---|
| `token` | string | required |
| `email` | string | required, email |
| `password` | string | required, min 8, confirmed |
| `password_confirmation` | string | required |

**Success:** Password updated, redirects to `/login`.

---

### Chat

#### Search Users

```
GET /chat/users?q={query}
```

Returns a list of users whose name or email matches the search query (excludes the authenticated user).

**Success Response**
```json
[
  {
    "id": 2,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "avatar_url": null,
    "initials": "JD",
    "avatar_color": "#4f46e5",
    "is_online": true
  }
]
```

---

### Conversations

#### List Conversations

```
GET /conversations
```

Returns all conversations for the authenticated user, ordered by most recent message.

**Success Response**
```json
[
  {
    "id": 1,
    "last_message_at": "2026-07-06T10:00:00Z",
    "other_user": {
      "id": 2,
      "name": "Jane Doe",
      "avatar_url": null,
      "initials": "JD",
      "avatar_color": "#4f46e5",
      "is_online": true
    },
    "last_message": {
      "message": "Hey there!",
      "type": "text"
    },
    "unread_count": 3
  }
]
```

---

#### Create or Find Conversation

```
POST /conversations
```

Finds an existing one-on-one conversation between the authenticated user and the target user, or creates one if it does not exist.

**Request Body**
```json
{
  "user_id": 2
}
```

**Success Response**
```json
{
  "id": 1,
  "last_message_at": null,
  "other_user": { ... }
}
```

---

#### Get Conversation

```
GET /conversations/{id}
```

Returns a single conversation. The authenticated user must be a participant.

**Success Response**
```json
{
  "id": 1,
  "other_user": { ... },
  "last_message": { ... },
  "unread_count": 0
}
```

---

#### Mark Conversation as Read

```
POST /conversations/{id}/read
```

Updates the authenticated user's read cursor to the latest message in the conversation.

**Success Response**
```json
{
  "success": true
}
```

---

### Messages

#### List Messages

```
GET /conversations/{id}/messages?cursor={cursor}
```

Returns the 30 most recent messages using cursor-based pagination, ordered newest first. Pass the `next_cursor` value from the previous response to load older messages.

**Success Response**
```json
{
  "data": [
    {
      "id": 42,
      "conversation_id": 1,
      "user_id": 1,
      "message": "Hello!",
      "type": "text",
      "attachment": null,
      "attachment_url": null,
      "formatted_time": "10:32 AM",
      "created_at": "2026-07-06T10:32:00Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "initials": "JD",
        "avatar_color": "#7c3aed",
        "avatar_url": null
      }
    }
  ],
  "next_cursor": "eyJpZCI6MX0",
  "has_more": true
}
```

---

#### Send Message

```
POST /conversations/{id}/messages
```

Rate limited to **60 requests per minute** per user.

**Request Body (JSON — text only)**
```json
{
  "message": "Hello!"
}
```

**Request Body (multipart/form-data — with attachment)**
```
message=Hello!           (optional when attachment is present)
attachment=<file>        (image or file, max 10 MB)
```

Accepted MIME types for attachments: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`, `application/zip`, `text/plain`, `application/msword`, and common Office formats.

**Success Response**
```json
{
  "id": 43,
  "conversation_id": 1,
  "user_id": 1,
  "message": "Hello!",
  "type": "text",
  "attachment_url": null,
  "formatted_time": "10:33 AM",
  "created_at": "2026-07-06T10:33:00Z",
  "user": { ... }
}
```

**Validation Errors**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "message": ["The message field is required when attachment is not present."],
    "attachment": ["The attachment may not be greater than 10240 kilobytes."]
  }
}
```

---

#### Broadcast Typing Indicator

```
POST /conversations/{id}/typing
```

Broadcasts a `UserTyping` event on the private conversation channel to all other participants. The typing indicator automatically disappears after 4 seconds on the client side.

**Success Response**
```json
{
  "success": true
}
```

---

### Profile

#### Show Profile Edit Form

```
GET /profile
```

Returns the profile edit page (Blade view).

---

#### Update Profile

```
PATCH /profile
```

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `email` | string | required, email, unique (excluding current user) |

**Success:** Redirects back to `/profile` with a success flash message. If the email is changed, email verification status is reset.

---

#### Delete Account

```
DELETE /profile
```

| Field | Type | Rules |
|---|---|---|
| `password` | string | required, must match current password |

**Success:** Account deleted, session invalidated, redirects to `/`.

---

#### Update Password

```
PUT /password
```

| Field | Type | Rules |
|---|---|---|
| `current_password` | string | required |
| `password` | string | required, min 8, confirmed |
| `password_confirmation` | string | required |

**Success:** Redirects back with a success flash message.

---

## Troubleshooting

### `composer install` fails with memory errors

PHP may run out of memory during dependency resolution.

```bash
php -d memory_limit=-1 /usr/local/bin/composer install
```

---

### `npm install` fails or produces errors

Clear the npm cache and retry.

```bash
npm cache clean --force
npm install
```

---

### Database connection error (`SQLSTATE[HY000] [2002]`)

- Verify MySQL is running.
- Confirm `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` are correct.
- Ensure the database named in `DB_DATABASE` exists.

```bash
php artisan config:clear
```

---

### `No application encryption key has been specified`

Run the key generation command.

```bash
php artisan key:generate
```

If the key is already set but the error persists, clear the config cache.

```bash
php artisan config:clear
```

---

### Queue jobs are not processing

Ensure the queue worker is running and that `QUEUE_CONNECTION=database` is set in `.env`.

```bash
php artisan queue:listen
```

If jobs are stuck, check the `jobs` and `failed_jobs` tables in the database.

```bash
php artisan queue:failed
```

---

### WebSocket / Reverb not broadcasting

1. Confirm `BROADCAST_CONNECTION=reverb` is set in `.env`.
2. Confirm the Reverb server is running (`php artisan reverb:start`).
3. Confirm the `VITE_REVERB_*` variables in `.env` match the `REVERB_*` values.
4. Rebuild the frontend bundle after any `.env` changes.

```bash
npm run dev
```

5. Check the browser console for WebSocket connection errors.
6. Verify the `reverb` entry in `config/broadcasting.php` and clear config cache.

```bash
php artisan config:clear
```

---

### Vite assets not loading (404 on `/build/...`)

Ensure the Vite dev server is running.

```bash
npm run dev
```

For production, build the assets first.

```bash
npm run build
```

---

### File uploads not appearing after send

Ensure the storage symlink exists.

```bash
php artisan storage:link
```

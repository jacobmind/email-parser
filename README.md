# Backend Engineering Assessment – Submission

## ✔ What I Did

- Connected to the provided MySQL `emails` database on the server
- Built a Laravel app with:
    - Artisan command to parse `email` → `raw_text` (plain text only)
    - RESTful API (Store, Get, Update, Delete, List)
    - Token-based auth middleware
- Parsing uses `php-mime-mail-parser/php-mime-mail-parser` (no external services)
- Skips already processed rows
- API and parsing logic tested and working

## 📂 Server Info

- Project path: `/var/www/html/email-parser`
- DB used: `emails` (loaded via `successful_emails.sql`)

## 🧪 How to Test

- Run parser manually: `php artisan emails:parse`
- API: use `Authorization: Bearer {TOKEN}` header
- Create token by running `php artisan tinker` and executing the following:
  ```php
  $user = \App\Models\User::find(1); // Replace 1 with the user ID
  echo $user->createToken('API Token')->plainTextToken;    
 - Use the token in your API requests. Select Bearer Token in Postman and paste the token in the field.

## ✅ To-Do List for Backend Engineering Assessment

### Setup
- [x] Log into the server and verify database access
- [ ] Clone project to `/var/www/email-parser`
- [ ] Set up `.env` with correct DB credentials
- [x] Run `composer install` and `php artisan key:generate`

### Email Parsing
- [x] Create `emails:parse` Artisan command
- [x] Use `php-mime-mail-parser/php-mime-mail-parser` to extract plain text
- [x] Strip HTML and headers
- [x] Keep only printable characters and `\n`
- [x] Skip already processed records
- [x] Limit batch size for safety
- [x] Test parsing on real data

### Scheduler
- [x] Register the command
- [x] Run Laravel scheduler via `schedule:work` or cron

### RESTful API
- [x] Create POST `/api/emails` to store and auto-parse
- [x] Create GET `/api/emails/{id}` to fetch by ID
- [x] Create PUT `/api/emails/{id}` to update
- [x] Create GET `/api/emails` to list (exclude soft-deleted)
- [x] Create DELETE `/api/emails/{id}` for soft delete
- [x] Add token-based authentication middleware

### Finalization
- [x] Parse all existing unprocessed records
- [x] Write README with setup and API instructions
- [ x Email submission with:
    - GitHub repo link
    - Server path: `/var/www/html/email-parser`
    - Confirmation that everything is working

## ✅ My Deployment Tasks

- [x] Import existing email data into the newly migrated Laravel database on the server
- [x] Ensure the imported data matches the structure expected by Laravel
- [x] Create a user using `php artisan tinker` for API authentication
- [x] Generate and assign an API token or notification key for the user
- [x] Set up and confirm that cron is running the parsing job on the server
- [x] Test the entire setup directly on the server to make sure everything works as expected

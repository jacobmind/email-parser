# Backend Engineering Assessment – Submission

## ✔ What I Did

- Connected to the provided MySQL `emails` database on the server
- Built a Laravel app with:
    - Artisan command to parse `email` → `raw_text` (plain text only)
    - RESTful API (Store, Get, Update, Delete, List)
    - Token-based auth middleware
- Parsing uses `zbateson/mail-mime-parser` (no external services)
- Skips already processed rows
- API and parsing logic tested and working

## 📂 Server Info

- Project path: `/var/www/html/email-parser`
- DB used: `emails` (loaded via `successful_emails.sql`)

## 🧪 How to Test

- Run parser manually: `php artisan emails:parse`
- API: use `Authorization: Bearer {TOKEN}` header

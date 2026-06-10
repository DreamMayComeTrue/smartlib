# SmartLib — Run Guide

This is a quick reference for getting both halves of SmartLib running on Windows.

The system has two parts that run independently:

| Part | Stack | Where it runs |
|------|-------|---------------|
| **Backend** | PHP Slim 4 + MySQL | Laragon's Apache on `http://smartlib.test` |
| **Frontend** | Vue 3 + Vite | Node dev server on `http://localhost:5173` |

You need both running at the same time during development.

---

## 0. Prerequisites — install once

Install these on your machine (one-time setup):

1. **Laragon Full** — https://laragon.org/download (gives you Apache + PHP 8.2 + MySQL 8 + Composer in one bundle).
2. **Node.js LTS** — https://nodejs.org (v20 or v22). Tick "Add to PATH" in the installer.
3. **Postman** — https://www.postman.com/downloads (for testing API endpoints).
4. **VS Code** — https://code.visualstudio.com with the Vue and PHP Intelephense extensions.

Verify they're installed by opening PowerShell and running:

```powershell
php --version
composer --version
node --version
npm --version
```

If any fail, fix that before continuing.

---

## 1. Backend setup (do this first)

### 1.1 Move backend into Laragon's web root

Laragon serves whatever is in `C:\laragon\www\`. The cleanest path is to point Laragon at our existing folder; the alternative is to copy. Pick **one** of the two:

**Option A — symlink (recommended, keeps editing in your project folder):**

Open PowerShell **as Administrator**:

```powershell
cmd /c mklink /D "C:\laragon\www\smartlib" "D:\Software Engineering MJIIT\Cross-Section Development\Project\Cross Platform Development Project\smartlib\backend"
```

**Option B — copy (simpler, but you have to keep both folders in sync):**

```powershell
Copy-Item -Recurse "D:\Software Engineering MJIIT\Cross-Section Development\Project\Cross Platform Development Project\smartlib\backend" "C:\laragon\www\smartlib"
```

### 1.2 Install PHP dependencies

```powershell
cd C:\laragon\www\smartlib
composer install
```

This downloads Slim 4, php-jwt, and phpdotenv into a `vendor/` folder.

### 1.3 Configure the virtual host

By default, Laragon serves `C:\laragon\www\smartlib\` at `http://smartlib.test`. But our app lives in the `public/` subfolder for security (so users can't browse to `vendor/` or `.env`).

Edit `C:\laragon\etc\apache2\sites-enabled\auto.smartlib.test.conf` so the `DocumentRoot` ends with `public`:

```apache
DocumentRoot "C:/laragon/www/smartlib/public"
<Directory "C:/laragon/www/smartlib/public">
    AllowOverride All
    Require all granted
</Directory>
```

Then in Laragon: **Menu → Apache → Reload**.

> **Shortcut**: in Laragon, right-click the running Apache → "Auto Virtual Hosts" — it'll detect a `public/` folder automatically if you enable that option. Restart Apache after enabling.

### 1.4 Create `.env`

```powershell
cd C:\laragon\www\smartlib
copy .env.example .env
notepad .env
```

In `notepad`, fill in at minimum:

```ini
DB_HOST=localhost
DB_NAME=smartlib
DB_USER=root
DB_PASS=

JWT_SECRET=<paste a long random string here>
CORS_ORIGIN=http://localhost:5173
```

Generate a strong JWT secret with PowerShell:

```powershell
[Convert]::ToBase64String([Security.Cryptography.RandomNumberGenerator]::GetBytes(48))
```

Paste the output as `JWT_SECRET`. **Do not leave it as the placeholder** — the middleware will refuse to run.

### 1.5 Create and seed the database

In Laragon: **Menu → MySQL → phpMyAdmin** (it opens in your browser).

1. Click **New** on the left → name it `smartlib` → choose `utf8mb4_unicode_ci` → Create.
2. Click the `smartlib` database, then the **SQL** tab.
3. Paste the contents of `database/schema.sql` and click **Go**.
4. Repeat with `database/seed.sql`.

You should now see 3 tables: `books`, `members`, `borrow_records`, with sample rows.

**Or via command line:**

```powershell
cd C:\laragon\www\smartlib\database
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root smartlib < schema.sql
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root smartlib < seed.sql
```

(Adjust the MySQL path to whatever version Laragon installed.)

### 1.6 Verify the backend

Open a browser and visit:

```
http://smartlib.test/
```

You should see:

```json
{ "status": "success", "code": 200, "data": { "service": "SmartLib API", "version": "1.0.0" } }
```

Then try:

```
http://smartlib.test/api/books
```

You should see the seeded books as JSON. **If you do — backend is working.**

---

## 2. Frontend setup

The frontend folder stays in your project directory — no need to move it.

### 2.1 Install Node dependencies

```powershell
cd "D:\Software Engineering MJIIT\Cross-Section Development\Project\Cross Platform Development Project\smartlib\frontend"
npm install
```

This will take a minute the first time (downloads Vue, Vue Router, Axios, Vite).

### 2.2 (Optional) Override the API URL

If the backend is running at the default `http://smartlib.test`, skip this. Otherwise:

```powershell
copy .env.example .env.local
notepad .env.local
```

Set `VITE_API_BASE_URL=http://your-backend-url`.

### 2.3 Start the dev server

```powershell
npm run dev
```

Vite will print something like:

```
  VITE v5.4.0  ready in 412 ms

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
```

A browser tab opens automatically. You should see the SmartLib catalogue with the 12 seeded books.

---

## 3. Try the full flow

1. Click **Sign up** (top right) → create an account with any email and an 8+ char password.
2. Log in with those credentials.
3. Click **Borrow** on any book. You should see the available count drop by 1.
4. Click **My Borrows** in the navbar — your borrow appears with a due date 14 days out.
5. Click **Return** — the book reappears in the catalogue.

To test admin features, log in with the seed admin: **`admin@smartlib.test` / `password`**. The **Admin** tab appears in the navbar — you can add/edit/delete books.

---

## 4. Test the API with Postman

1. Open Postman → File → Import → select `postman/SmartLib.postman_collection.json`.
2. Run **"4. Public — POST /api/members/login (Admin)"** first. Its test script auto-saves the JWT into a collection variable.
3. Now every other protected request will work because the variable is set.
4. Use the Runner to fire all 12 requests in one go.

---

## 5. Daily dev workflow

When you come back tomorrow:

```powershell
# Terminal 1 — make sure Laragon is started (Apache + MySQL green)
# Nothing else to do; the API stays live.

# Terminal 2 — start the Vue dev server
cd "D:\…\smartlib\frontend"
npm run dev
```

Vite hot-reloads on every save. PHP changes are picked up immediately by Apache.

---

## 6. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `smartlib.test` shows Apache welcome page | DocumentRoot still points at `C:\laragon\www\smartlib\`, not the `public/` subfolder | Edit the vhost conf (Section 1.3), reload Apache |
| `/api/books` returns 500 | `.env` missing or `JWT_SECRET` still placeholder | Section 1.4 |
| `/api/books` returns "could not find driver" | PHP's `pdo_mysql` extension disabled | Laragon menu → PHP → Extensions → tick `pdo_mysql` → restart Apache |
| Vue page is blank, console says "CORS" | `CORS_ORIGIN` in `.env` doesn't match the Vite port | Set `CORS_ORIGIN=http://localhost:5173`, restart Apache |
| Login returns 401 even with correct password | You rotated `seed.sql` hashes but didn't re-run it | Re-run `seed.sql` or update the hash with `php tools/hash_password.php yourpassword` |
| "Class 'Dotenv\\Dotenv' not found" | `composer install` was skipped | Section 1.2 |
| `npm run dev` errors about port in use | Something else is on 5173 | Edit `vite.config.js` → change `port` |
| Borrow returns 409 immediately | You already have an active borrow for that book (by design) | Return it first, or borrow a different one |
| Token expired after 1 hour | By design — log in again | Adjust `JWT_TTL` in `.env` if you want longer for dev |

---

## 7. Folder map

```
smartlib/
├── README.md                            ← you are here
├── backend/                             → goes into C:\laragon\www\smartlib
│   ├── composer.json
│   ├── .env.example  →  copy to .env
│   ├── .htaccess
│   ├── public/index.php                 ← Slim entry point
│   ├── src/
│   │   ├── routes.php                   ← all 10 endpoints
│   │   ├── db.php
│   │   ├── helpers.php
│   │   └── middleware/JwtMiddleware.php
│   ├── database/
│   │   ├── schema.sql                   ← run first
│   │   └── seed.sql                     ← run second
│   └── tools/hash_password.php
├── frontend/                            → stays in this folder
│   ├── package.json
│   ├── .env.example  →  copy to .env.local (only if needed)
│   ├── vite.config.js
│   ├── index.html
│   └── src/
│       ├── main.js
│       ├── App.vue
│       ├── assets/main.css
│       ├── api/http.js                  ← shared Axios client
│       ├── router/index.js
│       ├── components/                  ← BookCard, BorrowForm, SearchBar, LoadingSpinner
│       └── views/                       ← BookListView, LoginView, RegisterView, BorrowView, ReturnView, AdminView
├── postman/SmartLib.postman_collection.json
└── docs/
    ├── Technical_Report_Skeleton.md
    └── generate-report-docx.js
```

---

*Polar Bear Technologies · SmartLib · SCSM2223 Semester 2, 2025/2026*

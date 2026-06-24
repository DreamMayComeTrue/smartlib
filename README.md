# SmartLib : Run Guide

This is a quick reference for getting both halves of SmartLib running on Windows.

The system has two parts that run independently:

| Part | Stack | Where it runs |
|------|-------|---------------|
| **Backend** | PHP Slim 4 + MySQL | Laragon's Apache on `http://smartlib.test` |
| **Frontend** | Vue 3 + Vite | Node dev server on `http://localhost:5173` |

You need both running at the same time during development.

---

## 0. Prerequisites : install once

Install these on your machine (one-time setup):

1. **Laragon Full** - https://laragon.org/download (gives you Apache + PHP 8.3 + Composer in one bundle). We use Laragon for Apache + PHP only, the database lives elsewhere (see Section 1.5).
2. **MySQL 8.0** - https://dev.mysql.com/downloads/installer/ (Community Edition, standalone Windows installer). Pick "Server only" or "Developer Default" so the MySQL service runs on port 3306. Set a root password and remember it.
3. **MySQL Workbench** - bundled with the MySQL installer above, or download standalone. We use it to run SQL scripts.
4. **Node.js LTS** - https://nodejs.org (v20 or v22). Tick "Add to PATH" in the installer.
5. **Postman** - https://www.postman.com/downloads (for testing API endpoints).
6. **VS Code** - https://code.visualstudio.com with the Vue and PHP Intelephense extensions.
7. **Git** - bundled with Laragon, or https://git-scm.com if you want it on system PATH.

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

**Option A : symlink (recommended, keeps editing in your project folder):**

Open PowerShell **as Administrator**:

```powershell
cmd /c mklink /D "C:\laragon\www\smartlib" "D:\Software Engineering MJIIT\Cross-Section Development\Project\Cross Platform Development Project\smartlib\backend"
```

**Option B : copy (simpler, but you have to keep both folders in sync):**

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

> **Shortcut**: in Laragon, right-click the running Apache → "Auto Virtual Hosts" it'll detect a `public/` folder automatically if you enable that option. Restart Apache after enabling.

### 1.4 Create `.env`

```powershell
cd C:\laragon\www\smartlib
copy .env.example .env
notepad .env
```

In `notepad`, fill in at minimum:

```ini
DB_HOST=127.0.0.1
DB_NAME=smartlib
DB_USER=root
DB_PASS=<your MySQL root password>

JWT_SECRET=<paste a long random string here>
CORS_ORIGIN=http://localhost:5173
```

> ⚠️ **Use `127.0.0.1`, not `localhost`.** Under MySQL 8.x on Windows, `localhost` can resolve to a named-pipe socket whose user account differs from `root@127.0.0.1`, causing "Access denied … using password: YES" even when the password is correct. Forcing TCP via `127.0.0.1` sidesteps this entirely.

Generate a strong JWT secret with PHP (works regardless of PowerShell version):

```powershell
php -r "echo base64_encode(random_bytes(48));"
```

Paste the output as `JWT_SECRET`. **Do not leave it as the placeholder** the middleware will refuse to run.

### 1.5 Create and seed the database (MySQL Workbench)

We use the **standalone MySQL 8.0** server (installed in Section 0), **not** Laragon's bundled MySQL. The standalone install plays better with our schema and avoids the auth-plugin conflicts that Laragon's MySQL 8.4 can hit with MariaDB-based clients.

1. Open **MySQL Workbench** → click the **"Local instance MySQL80"** tile → enter your root password.
2. Open `backend/database/schema.sql` (File → Open SQL Script).
3. Click the **⚡ lightning bolt** (Execute) - this creates the `smartlib` database AND the three tables (`books`, `members`, `borrow_records`). The `DROP TABLE` warnings are harmless on first run.
4. Repeat for `backend/database/seed.sql`. The file starts with `SET SQL_SAFE_UPDATES = 0;` so Workbench's safe-update mode won't block the `DELETE` statements.
5. Refresh the **SCHEMAS** panel on the left → expand `smartlib` → you should see `books` (12 rows), `members` (4 rows), `borrow_records` (0 rows).

**Or via command line** (if you prefer):

```powershell
cd "D:\…\smartlib\backend\database"
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p smartlib < schema.sql
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p smartlib < seed.sql
```

> **Why not Laragon's MySQL?** Laragon Full ships MySQL 8.4, whose default `caching_sha2_password` auth plugin can conflict with the bundled HeidiSQL client (which uses `libmariadb.dll`). The standalone MySQL 8.0 + Workbench combination avoids the issue entirely. If you want both, set Laragon's MySQL to port `3307` to avoid clashing with the standalone server on `3306` see troubleshooting.

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

You should see the seeded books as JSON. **If you do, backend is working.**

---

## 2. Frontend setup

The frontend folder stays in your project directory no need to move it.

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
3. Click **Borrow** on any book. A **confirmation modal** appears showing the book title and the 14-day due-date rule. Click **Confirm borrow**.
4. A green toast slides in at the top: *"Borrowed 'Clean Code'. Check 'My Borrows' for the due date."* The available count drops by 1.
5. Click **My Borrows** in the navbar, your borrow appears with the due date.
6. Click **Return** the book goes back into the catalogue with availability restored.

To test admin features, log in with the seed admin: **`admin@smartlib.test` / `password`**. The **Admin** tab appears in the navbar you can add/edit/delete books via the modal form.

### Testing business rules
- **Borrow the same book twice** → the second attempt fails with a red 409 toast: *"You already have an active borrow for this book."* By design.
- **Log out and try Borrow** → you're redirected to `/login` with a `?redirect=/` query param so you bounce back after logging in.
- **Try to visit `/admin` as a student** → router guard redirects you home.

---

## 4. Test the API with Postman

1. Open Postman → File → Import → select `postman/SmartLib.postman_collection.json`.
2. Run **"4. Public POST /api/members/login (Admin)"** first. Its test script auto-saves the JWT into a collection variable.
3. Now every other protected request will work because the variable is set.
4. Use the Runner to fire all 12 requests in one go.

---

## 5. Daily dev workflow

When you come back tomorrow:

```powershell
# Terminal 1 - make sure Laragon is started (Apache + MySQL green)
# Nothing else to do; the API stays live.

# Terminal 2 - start the Vue dev server
cd "D:\…\smartlib\frontend"
npm run dev
```

Vite hot-reloads on every save. PHP changes are picked up immediately by Apache.

---

## 6. Troubleshooting

### Setup / environment

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `php` or `composer` not recognized | Laragon's PHP/Composer not on PATH | Use Laragon's terminal (Cmder), or add `C:\laragon\bin\php\php-X.X.X` and `C:\laragon\bin\composer` to Windows PATH |
| `smartlib.test` shows directory listing | DocumentRoot points at the project root, not `public/` | Edit `C:\laragon\etc\apache2\sites-enabled\auto.smartlib.test.conf` so both `DocumentRoot` and `<Directory>` end in `/public`. Reload Apache |
| `smartlib.test` shows Apache welcome page | Vhost not created | Restart Laragon; check `C:\laragon\www\smartlib` symlink exists |
| "Class 'Dotenv\\Dotenv' not found" | `composer install` was skipped | Section 1.2 |
| `composer install` rejects `firebase/php-jwt` (PKSA advisory) | Composer 2.7+ blocks installs with known advisories | We've added the advisory to `composer.json`'s `config.audit.ignore` block. If it still complains, run `composer install --no-audit` once |

### Database / MySQL

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `/api/books` returns "Access denied for user 'root'@'localhost' (using password: YES/NO)" | `.env` `DB_HOST=localhost` is hitting a different MySQL user than expected (named-pipe socket vs TCP) | Set `DB_HOST=127.0.0.1` in `.env` to force TCP |
| HeidiSQL "Access denied" even with correct password | MySQL 8.4 `caching_sha2_password` conflict with libmariadb.dll | Use MySQL Workbench instead, or downgrade Laragon's MySQL to 8.0/MariaDB. See Section 1.5 |
| Two MySQL servers fighting for port 3306 | Standalone MySQL + Laragon's MySQL both trying to bind 3306 | Either disable Laragon's MySQL (Menu → MySQL → Stop), or move Laragon's MySQL to port 3307 (edit `my.ini`) |
| `seed.sql` fails with "Error 1175: safe update mode" | MySQL Workbench's safe-update guard | The file now starts with `SET SQL_SAFE_UPDATES = 0;` - re-open it in Workbench and run again |
| `/api/books` returns "could not find driver" | PHP's `pdo_mysql` extension disabled | Laragon menu → PHP → Extensions → tick `pdo_mysql` → restart Apache |
| Login returns 401 with correct password | Seed hashes rotated but `seed.sql` not re-run | Re-run `seed.sql`, or update the hash with `php tools/hash_password.php yourpassword` |

### Runtime / frontend

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `/api/books` returns `{"status":"error","message":"Internal server error"}` | Slim caught an exception but `APP_DEBUG=false` hides details | Set `APP_DEBUG=true` in `.env` temporarily to see the real error. **Set back to `false` before demo/submission!** |
| Borrow click does nothing visible | Toast was rendering below the fold | Already fixed - toast now `position: fixed` at the top |
| Vue page blank, console says "CORS" | `CORS_ORIGIN` in backend `.env` doesn't match the Vite port | Set `CORS_ORIGIN=http://localhost:5173`, restart Apache |
| `npm run dev` errors about port in use | Something else is on 5173 | `npm run dev -- --port 5174` and update `CORS_ORIGIN` accordingly |
| Borrow returns 409 immediately | You already have an active borrow for that book (by design) | Return it first, or borrow a different one |
| Token expired after 1 hour | By design - log in again | Adjust `JWT_TTL` in `.env` if you want longer for dev (e.g. `JWT_TTL=86400` for 24 hours) |
| `Method not allowed. Must be one of: OPTIONS` | DocumentRoot still points at project root, so Slim sees `/public/api/books` instead of `/api/books` | Section 6 → "directory listing" row above |

---

## 7. Folder map

```
smartlib/                                ← git repo root
├── README.md                            ← you are here
├── .gitignore                           ← excludes .env, vendor/, node_modules/, dist/
│
├── backend/                             → goes into C:\laragon\www\smartlib (symlink or copy)
│   ├── composer.json                    ← Slim 4, php-jwt, phpdotenv + audit ignore
│   ├── .env.example                     ← template (commit this)
│   ├── .env                             ← real secrets (GITIGNORED never commit)
│   ├── .gitignore                       ← excludes vendor/ and .env
│   ├── .htaccess                        ← rewrites all requests to public/index.php
│   ├── public/
│   │   └── index.php                    ← Slim entry point (CORS + error handler)
│   ├── src/
│   │   ├── routes.php                   ← all 10 + 2 bonus endpoints
│   │   ├── db.php                       ← PDO singleton (no emulated prepares)
│   │   ├── helpers.php                  ← jsonSuccess / jsonError
│   │   └── middleware/JwtMiddleware.php ← HS256 verify + role check
│   ├── database/
│   │   ├── schema.sql                   ← run first (creates tables)
│   │   └── seed.sql                     ← run second (12 books, 4 accounts)
│   └── tools/hash_password.php          ← `php tools/hash_password.php yourpass`
│
├── frontend/                            → stays in this folder
│   ├── package.json                     ← Vue 3, Vue Router 4, Axios, Vite
│   ├── .env.example                     ← template (commit this)
│   ├── .env / .env.local                ← personal overrides (GITIGNORED)
│   ├── .gitignore                       ← excludes node_modules/, dist/, .env
│   ├── vite.config.js                   ← @/ alias → ./src
│   ├── index.html
│   └── src/
│       ├── main.js                      ← bootstraps app
│       ├── App.vue                      ← navbar + footer + RouterView
│       ├── assets/main.css              ← design tokens (CSS variables)
│       ├── api/http.js                  ← Axios instance + JWT interceptor
│       ├── router/index.js              ← routes + navigation guards
│       ├── components/                  ← BookCard, BorrowForm, SearchBar, LoadingSpinner
│       └── views/                       ← BookListView, LoginView, RegisterView,
│                                          BorrowView, ReturnView, AdminView
│
├── postman/
│   └── SmartLib.postman_collection.json ← all 10 + bonus endpoints (auto JWT capture)
│
└── docs/
    ├── Technical_Report_Skeleton.md     ← fill in for the report deliverable
    ├── PPT_Fixes.md                     ← slide-by-slide audit vs the code
    └── generate-report-docx.js          ← optional: convert MD → DOCX with `node`
```

---

## 8. Source control (Git + GitHub)

The repo is hosted on GitHub. Clone it on a fresh machine with:

```powershell
git clone https://github.com/DreamMayComeTrue/smartlib.git
cd smartlib
```

Then follow Sections 1 and 2 to bring up the backend and frontend.

### Daily workflow

```powershell
git pull            # before starting work, get teammates' changes
# … edit code …
git status          # review what changed
git add .
git commit -m "Add overdue badge to MyBorrows"
git push
```

### Things that must NEVER be committed
- `backend/.env` - contains `JWT_SECRET` and DB password
- `backend/vendor/` - Composer dependencies (regenerate with `composer install`)
- `frontend/node_modules/` - npm dependencies (regenerate with `npm install`)
- `frontend/dist/` - production build output (regenerate with `npm run build`)
- Any `.env` / `.env.local` / `*.pem` / `*.key` file

All of these are excluded by the root `.gitignore` plus per-folder `.gitignore` files. Run `git check-ignore -v <filename>` to confirm a specific file is excluded.

### If you accidentally committed `.env`

Don't panic, but act fast:

1. Rotate the JWT secret in `.env` (generate a new one).
2. Rotate your MySQL root password.
3. Remove the file from history with `git rm --cached backend/.env`, commit, and push.
4. For thorough cleanup of history, look into `git filter-repo` or BFG Repo-Cleaner.

---



*Polar Bear · SmartLib · SCSM2223 Semester 2, 2025/2026*

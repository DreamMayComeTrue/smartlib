# SmartLib — Technical Report

**SCSM2223 Cross-Platform Application Development**
**Polar Bear**
Kai · Jeffrey Tan Zhi Yao · Qiu Jiang Yi
Semester 2, 2025/2026

---

## Abstract
*(150–200 words — write this last)*

> Summarise the problem (university library managed manually), the solution
> (a cross-platform JSON API + Vue.js SPA), the tech stack chosen, the key
> design decisions (stateless API, JWT auth, prepared statements), and the
> outcome (10 endpoints implemented, X test cases passing, etc.).

---

## 1. Introduction

### 1.1 Background
*Briefly describe the existing manual library system at the campus — counter-based
borrow/return, paper records, no real-time stock visibility.*

### 1.2 Problem Statement
*State the gap: students cannot check availability remotely, admins lose time on
manual tracking, no path to a future mobile app.*

### 1.3 Objectives
- Build a stateless JSON API consumable by any client (web, mobile, kiosk).
- Provide a responsive Vue.js frontend covering both student and admin flows.
- Enforce industry-standard security (prepared statements, bcrypt, JWT, CORS allow-list).
- Deliver a fully testable system via Postman covering all 10 endpoints.

### 1.4 Scope
*One paragraph each on what's in (catalogue, borrow/return, admin CRUD, JWT login)
and what's out (payments, fines, multi-branch, e-book reader).*

---

## 2. System Design

### 2.1 Architecture Overview
*Insert the architecture diagram from the dev guide (Section 3). Reference it as
Figure 1.*

The system follows a **three-tier architecture**:

| Tier | Technology | Responsibility |
|------|------------|----------------|
| Presentation | Vue.js 3 + Vite | Reactive UI, client-side routing, JWT storage |
| Application | PHP Slim 4 + Apache | Routing, validation, business rules, auth |
| Data | MySQL 8 + PDO | Persistent storage with referential integrity |

The frontend and backend share *only* JSON over HTTP — this is the cross-platform
property. A future React Native or Flutter client could consume the same API
without any backend change.

### 2.2 Entity-Relationship Diagram (ERD)
*Insert the Chen-notation ERD here. It must show:*
- `members` (1) ──< `borrow_records` >── (M) `books`
- *Attributes for each entity, primary keys underlined, FK arrows from
  `borrow_records.member_id` and `borrow_records.book_id`.*

### 2.3 Use Case Diagram
*Insert the use-case diagram with two actors (Student, Admin) and the use cases:
search book, borrow, return, register, login, manage catalogue, view all members.*

### 2.4 Database Schema
See `database/schema.sql`. Key design choices:

- `available_count` is denormalised onto `books` for fast catalogue reads.
  Consistency is maintained inside transactions whenever a borrow or return
  happens (see Section 3.3).
- `borrow_records.status` uses an ENUM rather than free text so MySQL enforces
  the allowed values.
- Foreign keys use `ON DELETE RESTRICT` for `book_id` so admins can't silently
  destroy borrow history.

---

## 3. Implementation

### 3.1 Backend (PHP Slim 4)
The backend is built as a single Slim 4 application initialised in
`public/index.php`. The lifecycle of a request is:

1. Apache rewrites the URL via `.htaccess` to `public/index.php`.
2. `phpdotenv` loads environment variables.
3. The CORS middleware injects the allow-list origin header.
4. Slim's router matches the URL to a handler in `src/routes.php`.
5. The handler runs PDO prepared statements against MySQL.
6. The handler writes a JSON response via the shared `jsonSuccess()`/`jsonError()` helpers.

### 3.2 Frontend (Vue.js 3)
The frontend uses the Composition API and Vue Router. Axios is configured once
in `src/api/http.js` and reused everywhere; a request interceptor attaches the
JWT from `localStorage`, and a response interceptor catches `401` and pushes
the user to `/login`.

### 3.3 Concurrency-Safe Borrow Flow
*This is one of the most interesting parts of the project — discuss it in detail.*

When two students click "Borrow" on the last copy at the same moment, a naive
implementation would let both succeed and leave `available_count` at `-1`. We
prevent this by wrapping the borrow in a transaction with `SELECT … FOR UPDATE`:

```php
$pdo->beginTransaction();
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? FOR UPDATE');
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if ($book['available_count'] < 1) {
    $pdo->rollBack();
    return jsonError($rs, 'No copies available', 409);
}
// … insert borrow_record, decrement available_count …
$pdo->commit();
```

The row lock causes the second transaction to wait until the first commits,
so it then sees the decremented count and is correctly refused.

### 3.4 Code Snippets
*Pick 3–4 representative blocks to discuss:*
- `routes.php` — the GET /api/books handler (prepared statement)
- `JwtMiddleware.php` — token decoding + role check
- `BookListView.vue` — reactive loading, search, optimistic UI
- `router/index.js` — navigation guard

---

## 4. Security

| Threat | Where it's handled | How |
|--------|-------------------|-----|
| SQL Injection | `routes.php`, `db.php` | PDO with `ATTR_EMULATE_PREPARES = false`; every query uses `?` placeholders. |
| XSS | Vue templates | `{{ … }}` auto-escapes HTML; we never use `v-html` on user content. |
| Broken Auth | `routes.php` (login), `JwtMiddleware.php` | bcrypt via `password_hash()`; JWT signed with HS256, secret stored in `.env`, 1-hour expiry. |
| CORS misconfiguration | `public/index.php` | Allow-list of exactly `http://localhost:5173` — never `*`. |
| Privilege escalation | `JwtMiddleware('admin')` | Role claim is read from the *signed* token, not from a header the client controls. |
| Email enumeration | login route | Same generic `Invalid credentials` for unknown email and wrong password. |
| Race conditions | borrow route | `SELECT … FOR UPDATE` inside a transaction. |
| Token theft via JS | `http.js` | *Discuss trade-off: localStorage is convenient but vulnerable to XSS; an HttpOnly cookie would be safer. We accept the trade-off for this prototype.* |

*Document one penetration test you ran (e.g., curl with `' OR 1=1 --` in the
search param) and show that PDO defused it.*

---

## 5. Testing

### 5.1 API Tests (Postman)
The `postman/SmartLib.postman_collection.json` collection covers all 10
endpoints plus the bonus `/api/borrow/me`. The login request has a test script
that automatically captures the JWT into a collection variable, so subsequent
protected calls authenticate transparently.

*Insert a screenshot of the Postman Runner output here showing all green.*

### 5.2 Manual UI Walkthrough
| # | Scenario | Expected | Actual |
|---|----------|----------|--------|
| 1 | Anonymous user visits `/` | Catalogue loads, Borrow asks for login | ✅ |
| 2 | Student registers, logs in, borrows | Record appears in My Borrows | ✅ |
| 3 | Student tries to borrow the same book twice | 409 with explanatory message | ✅ |
| 4 | Student tries to open `/admin` | Redirected to `/` | ✅ |
| 5 | Admin adds, edits, deletes a book | Catalogue reflects change | ✅ |
| 6 | Invalid login | "Invalid credentials" — same message for both wrong-email and wrong-password | ✅ |

---

## 6. Challenges & Lessons Learned

*One paragraph each — pick the three biggest ones.*

- **CORS pre-flight**: Browsers send `OPTIONS` before any cross-origin
  `POST` with JSON. We had to add a catch-all OPTIONS route or every request
  failed silently.
- **JWT secret bootstrapping**: A misconfigured secret looked the same as a
  valid one until tokens started rejecting at random. We added a check that
  refuses to start the server with the placeholder secret.
- **Race conditions**: The first version of borrow let two users grab the
  last copy. We learned about row-level locking and rewrote it as a
  transaction.

---

## 7. Future Work

- E-mail notifications for overdue borrows (cron + PHPMailer).
- Refresh tokens to extend sessions without re-login.
- Switch JWT storage from `localStorage` to an HttpOnly cookie to reduce XSS exposure.
- React Native mobile client consuming the same API (proves the
  cross-platform claim).
- Pagination on `GET /api/books` and `GET /api/members`.

---

## 8. Conclusion

*Two short paragraphs — restate that the cross-platform goal is achieved
(same backend would serve a mobile app verbatim), and that the security
checklist is met within the time budget.*

---

## References

1. Slim Framework Documentation — https://www.slimframework.com/docs/v4/
2. Vue.js 3 Guide — https://vuejs.org/guide/
3. OWASP Top 10 (2021) — https://owasp.org/Top10/
4. Firebase PHP-JWT — https://github.com/firebase/php-jwt
5. MySQL 8 Reference Manual — *InnoDB Locking Reads*.

---

## Appendix A — API Endpoint Reference
*(Copy the table from the dev guide §9 here.)*

## Appendix B — Folder Structure
*(Copy the tree from the dev guide §4 here, updated with our added files like
`api/http.js` and `database/`.)*

---

*Polar Bear — SmartLib — 2025/2026*

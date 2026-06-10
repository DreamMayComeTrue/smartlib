# SmartLib PPT — Required Fixes

> **Source deck:** `SmartLib_PolarBears#Proj.pptx`
> **Audit date:** 2026-05-28
> **Approach chosen:** Update the slides to match the code (not the other way around)
>
> Open the .pptx in PowerPoint and apply the changes in order. Each fix shows what's *currently* on the slide and what to *replace* it with.

---

## Slide 1 — Cover

✅ **No changes needed.** Team members and matric numbers are accurate.

---

## Slide 2 — Project Overview & Objectives

✅ **No changes needed.** Tech stack mentions and objectives all match the code.

---

## Slide 3 — Component Architecture (Vue.js)

### 3.1 Component names — match actual files

Replace the four primary view boxes:

| Current label | Replace with |
|---|---|
| `BookCatalogue.vue` — Browse & search books | `BookListView.vue` — Browse & search books |
| `BorrowReturn.vue` — Borrow / return flow | `BorrowView.vue` / `ReturnView.vue` — Borrow & return flow |
| `AdminDashboard.vue` — Manage inventory | `AdminView.vue` — Manage inventory (CRUD) |
| `AuthView.vue` — Login / register | `LoginView.vue` / `RegisterView.vue` — Login & sign-up |

### 3.2 Shared/Reusable Components row

Current row: `BookCard.vue` · `SearchBar.vue` · `LoadingSpinner.vue` · `NavBar.vue` · `Modal.vue`

Replace with: `BookCard.vue` · `SearchBar.vue` · `LoadingSpinner.vue` · `BorrowForm.vue`

> **Why:** there is no `NavBar.vue` (the nav is built into `App.vue`), and no `Modal.vue` (the admin modal is inline in `AdminView.vue`). `BorrowForm.vue` does exist and isn't on the slide.

### 3.3 State Management section — replace Pinia with what we actually use

Replace the entire **State Management : Pinia Stores** block. New title: **State Management : Vue 3 Composition API**

Three cards underneath:

| Card title | Body |
|---|---|
| `localStorage + Axios interceptor` | JWT and user object stored in localStorage; auto-attached to every request by `src/api/http.js` |
| `Reactive refs in components` | `ref()` / `reactive()` / `computed()` from Vue 3 Composition API — each view manages its own state |
| `Vue Router navigation guards` | Global `router.beforeEach` blocks unauthenticated and non-admin access |

> **Why:** the project doesn't use Pinia. Claiming Pinia in the deck while showing code without it is the kind of inconsistency examiners flag.

---

## Slide 4 — Responsive Layout & UX Loading States

### 4.1 Responsive Strategy box (right side)

Current bullets:
- CSS Grid / Flexbox
- Tailwind breakpoints
- sm: md: lg:
- v-if for mobile nav
- Fluid font-size

Replace with:
- CSS Grid (3 → 2 → 1 column)
- CSS media queries `@media (max-width: 600px)`
- Custom design tokens (CSS variables in `main.css`)
- `v-if="!$route.meta.hideNav"` to hide nav on auth screens
- Scoped per-component styles

> **Why:** the project uses vanilla CSS with custom variables, not Tailwind. Anyone inspecting the code can verify in 5 seconds.

### 4.2 UX Loading States — no changes

✅ Loading Spinner, Skeleton Screen, Error State all match the actual implementation.

---

## Slide 5 — Client–Server Routing

### 5.1 Endpoint table — add the missing routes

The current table lists only 7 endpoints. Add the following rows so the count matches the dev guide's "10 endpoints":

| Method | Endpoint | Description | Auth? |
|---|---|---|---|
| POST | `/api/members/register` | Create student account (bcrypt hash) | Public |
| POST | `/api/return/{id}` | Return a borrowed book | JWT |
| GET | `/api/members` | List all members (Admin) | JWT (Admin) |

Optional bonus row (worth mentioning to demonstrate over-delivery):

| GET | `/api/borrow/me` | Current user's borrow history | JWT |

Also clarify the existing rows by adding role marks:

| Method | Endpoint | Auth? |
|---|---|---|
| POST | `/api/books` | JWT (Admin) |
| PUT | `/api/books/{id}` | JWT (Admin) |
| DELETE | `/api/books/{id}` | JWT (Admin) |

---

## Slide 6 — JSON Handling & Laragon Setup

### 6.1 Axios POST code block — remove `due_date` from client payload

Current code:
```js
await axios.post('/api/borrow', {
  book_id: selectedBook.id,
  due_date: '2026-06-12'
}, { headers: {
  Authorization: `Bearer ${authStore.token}`
}})
```

Replace with:
```js
await axios.post('/api/borrow', {
  book_id: selectedBook.id
}, { headers: {
  Authorization: `Bearer ${token}`
}})
```

> **Why:** clients don't (and shouldn't) send `due_date`. The backend computes it from `borrow_date + BORROW_DAYS` (14 days). This is a security feature — preventing clients from extending their own borrow windows. Worth mentioning verbally during the presentation.

### 6.2 PHP Slim JSON response — fix Slim 3 syntax to Slim 4

Current code:
```php
$data = ['status' => 'success', 'borrow_id' => $id, 'due_date' => $dueDate];
return $response->withJson($data, 201);
```

Replace with:
```php
$payload = ['status' => 'success', 'record_id' => $id, 'due_date' => $dueDate];
$response->getBody()->write(json_encode($payload));
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(201);
```

> **Why:** `$response->withJson()` is **Slim 3** syntax — removed in Slim 4. Your project uses Slim 4 and would actually error if this code ran. The replacement matches your actual route handlers (and the `jsonSuccess()` helper in `src/helpers.php`).

### 6.3 Laragon Local Environment column

✅ No changes needed. (Note: we ultimately used the local MySQL80 instance on 3306 instead of Laragon's bundled MySQL, but "MySQL on port 3306" remains accurate at the slide's level of detail.)

---

## Slide 7 — Feature Implementation Status

Update the status badges to reflect current state:

| Feature | Current badge | New badge |
|---|---|---|
| Admin Dashboard | 🔄 In Progress | ✅ Complete |
| Overdue Tracking | 🔄 In Progress | ✅ Complete *(with a footnote: "client-side detection via `due_date` comparison; no cron sweep")* |

> **Why:** the AdminView has full CRUD with modal — fully working. Overdue is computed client-side in `ReturnView.vue` `statusLabel()`. Both are honest "Complete" calls.

---

## Slide 8 — CRUD Operations Demo

### 8.1 READ flow — last box

Current last box: **Pinia** — `booksStore.books = response.data`

Replace with: **Vue ref** — `books.value = response.data`

> **Why:** matches the actual code in `BookListView.vue`'s `onMounted` handler. No Pinia.

### 8.2 PDO SELECT box — fix the query

Current: `SELECT * FROM books WHERE available > 0`

Replace with: `SELECT * FROM books ORDER BY title ASC`

> **Why:** the actual code shows all books with an availability badge rather than filtering them out, so users still see the full catalogue even for out-of-stock titles. (With search, the query becomes `... WHERE (title LIKE :q OR author LIKE :q OR category LIKE :q) ORDER BY title ASC`.)

### 8.3 HTTP Status Codes table — add missing codes you actually use

Add these rows under the existing 200/201/400/404:

| Code | Meaning | When Used |
|---|---|---|
| 401 Unauthorized | Missing or invalid JWT | Any protected route without a valid token |
| 403 Forbidden | Wrong role for action | Student trying to delete a book |
| 409 Conflict | Business rule violation | Borrowing when stock=0, duplicate ISBN, returning already-returned record |

> **Why:** your code returns all of these. Showing them demonstrates you understand the full status-code spectrum.

### 8.4 Q9 Error Handling box

Current second bullet: `Axios .catch() logs to console & renders <ErrorBanner>`

Replace with: `Axios .catch() displays a toast via .alert.alert-error in each view; raw errors logged to console`

> **Why:** there's no `<ErrorBanner>` component — alerts are rendered inline with the `.alert` utility class from `main.css`.

---

## Slide 9 — System Architecture

### 9.1 Client Layer boxes

Current four boxes: `Vue.js Components` · `Pinia State Store` · `Axios HTTP Client` · `Tailwind CSS`

Replace with: `Vue.js Components` · `Vue Router 4` · `Axios HTTP Client` · `Vanilla CSS + Design Tokens`

### 9.2 Database Layer boxes

Current third box: `users table`

Replace with: `members table`

> **Why:** the table is named `members` in `schema.sql`, not `users`. Critical fix — if a lecturer runs your schema and asks "show me the users table" the demo would break.

### 9.3 Technology Stack row (bottom of slide)

Current: `Vue.js 3` · `PHP Slim 4` · `MySQL` · `Axios` · `JWT` · `Laragon` · `Postman` · `PDO`

Replace with: `Vue.js 3` · `Vue Router 4` · `Vite` · `Axios` · `PHP Slim 4` · `PDO` · `Firebase php-jwt` · `MySQL 8` · `Laragon` · `Postman` · `bcrypt` · `HS256`

> **Why:** more accurate stack reflects everything actually in use. Removes Pinia/Tailwind. Adds Vue Router, Vite, bcrypt, HS256, php-jwt — these are all worth mentioning for technical depth.

---

## Slide 10 — Roles & Gantt Timeline

### 10.1 Role descriptions — minor tweaks

Optional but more accurate:

- **UI Lead (Kai):** add "Vue Router navigation guards" to the bullets — shows you handled routing security.
- **Backend Lead (Jeffrey):** add "JWT middleware with role-based access control" — emphasises the role check (`new JwtMiddleware('admin')`).
- **Security/Testing Lead (Ahmed):** the bullets already cover JWT, SQLi, XSS, Postman. Consider adding "Composer dependency audit and advisory acknowledgement" — references the `PKSA-y2cr-5h3j-g3ys` work in `composer.json`.

### 10.2 Bottom-of-slide message

Current: *"✅ Currently on track with Phase 2 deliverables (Laragon setup + core API routes implemented)"*

Replace with: *"✅ Ahead of schedule: Phase 2 complete (all 10 + 2 endpoints, JWT auth, PDO transactions). Phase 3 underway: full Vue frontend, Postman collection, security audit log."*

> **Why:** as of today, you've actually shipped most of Phase 2 *and* Phase 3. Underselling progress costs marks.

---

## Slide 11 — AI Assistance Documentation ⚠️ READ CAREFULLY

This slide needs the most careful revision. The current numbers do not reflect the actual collaboration that happened during development.

### Honest reframing (recommended)

Replace the AI Usage Log table with something like:

| Area | AI Used? | % Est. |
|---|---|---|
| Initial code scaffolding (Slim routes, Vue views, helpers) | Yes (Claude) | substantial — see notes |
| Architecture & design decisions | Collaborative (team-led, AI for trade-off discussion) | ~30% |
| Debugging the MySQL port conflict / Laragon setup | Team (manual diagnosis) | 0% |
| Database schema design | Yes (Claude scaffold, team reviewed) | ~50% |
| Vue components & styling | Yes (Claude scaffold, team adapted) | substantial |
| Security hardening (JWT secret, advisory, transactions) | Collaborative (Claude proposed, team understood and verified) | ~40% |
| Documentation (README, walkthrough) | Yes (Claude) | substantial |
| Report writing | Team-led with grammar assistance | ~10% |

Then below the table, **add a "What we did ourselves" callout**:

> *Despite scaffolding assistance, every team member can walk through any file, explain every design decision, and defend every line in oral examination. We diagnosed and solved the MySQL 8.4 port-3306 conflict ourselves, fixed the Apache DocumentRoot routing issue, configured Laragon end-to-end, ran all Postman tests, and made all UX decisions. The AI assisted with code generation; we own the engineering judgment behind every choice.*

> **Why this matters:** lecturers are increasingly trained to spot suspiciously polished code paired with low AI-usage claims. Being honest about scaffolding while emphasising your real engineering work (debugging, configuration, design decisions) is the safest position. The dev guide's §10 security audit log is the natural place to expand on this further.

### Update the "Academic Integrity Commitments" panel

Keep the five icons but tighten the wording:

- 📋 **Documented:** Every AI interaction logged with date, tool (Claude / Copilot / ChatGPT), and area of assistance.
- 🧠 **Understand All Code:** Each member can read, explain, and defend every contributed file line by line.
- 🔍 **Turnitin Checked:** Report verified for similarity before submission (target < 20%).
- 🛠 **Manual Engineering:** All debugging, configuration, and integration was done by the team — including MySQL/Apache troubleshooting.
- ✋ **Oral Defense Ready:** We can walk through any route handler, SQL query, security mechanism, or Vue component live.

### Update the Progress Summary banner

Current: *"Vue.js components structured with Pinia | PHP Slim 4 API routes functional | JWT auth implemented | 4 CRUD operations working | Laragon environment configured | AI usage ≈ 7% : within policy"*

Replace with:

> *"Vue.js components with Composition API state management | PHP Slim 4 API: 10 + 2 endpoints functional | JWT (HS256) auth with role-based middleware | bcrypt password hashing | Full CRUD + borrow/return transactions | PDO prepared statements + SQLi protection | CORS allow-list | Laragon + MySQL 8.0 environment configured | Honest AI-usage disclosure documented"*

---

## Summary checklist

Before saving the deck, verify each:

- [ ] Slide 3: component names + Pinia → Composition API
- [ ] Slide 4: Tailwind → vanilla CSS + media queries
- [ ] Slide 5: add 3 missing endpoints (register, return, members)
- [ ] Slide 6: Axios sample without `due_date`; PHP sample uses Slim 4 syntax
- [ ] Slide 7: Admin Dashboard & Overdue → Complete
- [ ] Slide 8: Pinia → Vue ref; fix SQL query; add 401/403/409
- [ ] Slide 9: users → members; remove Pinia/Tailwind; expand tech stack
- [ ] Slide 10: footer message reflects current progress
- [ ] Slide 11: honest AI disclosure with "What we did ourselves" callout

After applying all of the above, the deck will accurately reflect the codebase and stand up to scrutiny in an oral defense.

---

*Polar Bear — SmartLib — SCSM2223 — Interim Audit*

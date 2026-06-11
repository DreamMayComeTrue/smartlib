<?php
/**
 * SmartLib — API route definitions (10 endpoints).
 *
 * Public routes:
 *   GET  /api/books              List available books
 *   GET  /api/books/{id}         Get one book
 *   POST /api/members/register   Register a student
 *   POST /api/members/login      Log in, get JWT
 *
 * Protected routes (JWT required):
 *   POST   /api/books            Create book                 [Admin]
 *   PUT    /api/books/{id}       Update book                 [Admin]
 *   DELETE /api/books/{id}       Delete book                 [Admin]
 *   POST   /api/borrow           Borrow a book               [Any authed user]
 *   POST   /api/return/{id}      Return a borrow record      [Owner or Admin]
 *   GET    /api/members          List all members            [Admin]
 *
 * Response shape: { status, code, message?, data? }.
 * Every error path uses jsonError() and returns the matching HTTP status.
 */

declare(strict_types=1);

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SmartLib\Middleware\JwtMiddleware;

require __DIR__ . '/helpers.php';
require __DIR__ . '/middleware/JwtMiddleware.php';

/** @var \Slim\App $app  (provided by public/index.php) */
$pdo = getDB();

// =========================================================================
// 1. GET /api/books — list available books (with optional ?search=)
// =========================================================================
$app->get('/api/books', function (Request $rq, Response $rs) use ($pdo): Response {
    $params = $rq->getQueryParams();
    $search = trim($params['search'] ?? '');

    if ($search !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM books
             WHERE (title LIKE :q OR author LIKE :q OR category LIKE :q)
             ORDER BY title ASC'
        );
        $stmt->execute([':q' => '%' . $search . '%']);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM books ORDER BY title ASC');
        $stmt->execute();
    }

    return jsonSuccess($rs, ['data' => $stmt->fetchAll()]);
});

// =========================================================================
// 2. GET /api/books/{id} — fetch single book
// =========================================================================
$app->get('/api/books/{id}', function (Request $rq, Response $rs, array $args) use ($pdo): Response {
    $id = (int) $args['id'];

    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if (!$book) {
        return jsonError($rs, 'Book not found', 404);
    }

    return jsonSuccess($rs, ['data' => $book]);
});

// =========================================================================
// 3. POST /api/books — create a new book (Admin only)
// =========================================================================
$app->post('/api/books', function (Request $rq, Response $rs) use ($pdo): Response {
    $body = $rq->getParsedBody() ?? [];
    $title    = trim($body['title']    ?? '');
    $author   = trim($body['author']   ?? '');
    $isbn     = trim($body['isbn']     ?? '');
    $category = trim($body['category'] ?? '');
    $stock    = (int) ($body['stock']  ?? 0);

    if ($title === '' || $author === '' || $stock < 1) {
        return jsonError($rs, 'Missing required fields: title, author, stock (>=1)', 400);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO books (title, author, isbn, category, stock, available_count)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $title,
            $author,
            $isbn !== '' ? $isbn : null,
            $category !== '' ? $category : null,
            $stock,
            $stock,
        ]);
    } catch (PDOException $e) {
        // 1062 = duplicate ISBN
        if ($e->errorInfo[1] ?? null === 1062) {
            return jsonError($rs, 'A book with this ISBN already exists', 409);
        }
        throw $e;
    }

    return jsonSuccess($rs, ['id' => (int) $pdo->lastInsertId()], 201);
})->add(new JwtMiddleware('admin'));

// =========================================================================
// 4. PUT /api/books/{id} — update a book (Admin only)
// =========================================================================
$app->put('/api/books/{id}', function (Request $rq, Response $rs, array $args) use ($pdo): Response {
    $id   = (int) $args['id'];
    $body = $rq->getParsedBody() ?? [];

    // Confirm book exists first
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) {
        return jsonError($rs, 'Book not found', 404);
    }

    // Merge submitted fields over existing record
    $title    = trim($body['title']    ?? $book['title']);
    $author   = trim($body['author']   ?? $book['author']);
    $isbn     = $body['isbn']     ?? $book['isbn'];
    $category = $body['category'] ?? $book['category'];
    $stock    = isset($body['stock']) ? (int) $body['stock'] : (int) $book['stock'];

    if ($title === '' || $author === '' || $stock < 0) {
        return jsonError($rs, 'Invalid fields: title and author required, stock >= 0', 400);
    }

    // Keep available_count in sync if stock changed
    $borrowedNow = (int) $book['stock'] - (int) $book['available_count'];
    $newAvailable = max(0, $stock - $borrowedNow);

    $stmt = $pdo->prepare(
        'UPDATE books
            SET title = ?, author = ?, isbn = ?, category = ?, stock = ?, available_count = ?
          WHERE id = ?'
    );
    $stmt->execute([$title, $author, $isbn, $category, $stock, $newAvailable, $id]);

    return jsonSuccess($rs);
})->add(new JwtMiddleware('admin'));

// =========================================================================
// 5. DELETE /api/books/{id} — remove a book (Admin only)
// =========================================================================
$app->delete('/api/books/{id}', function (Request $rq, Response $rs, array $args) use ($pdo): Response {
    $id = (int) $args['id'];

    // Block deletion if any active borrow still references this book
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM borrow_records WHERE book_id = ? AND status = 'active'"
    );
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        return jsonError($rs, 'Cannot delete — book has active borrows', 409);
    }

    $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        return jsonError($rs, 'Book not found', 404);
    }

    return jsonSuccess($rs);
})->add(new JwtMiddleware('admin'));

// =========================================================================
// 6. POST /api/members/register — create a student account
// =========================================================================
$app->post('/api/members/register', function (Request $rq, Response $rs) use ($pdo): Response {
    $body  = $rq->getParsedBody() ?? [];
    $name  = trim($body['name']     ?? '');
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = (string) ($body['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
        return jsonError($rs, 'Invalid input — name required, valid email, password >= 8 chars', 400);
    }

    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return jsonError($rs, 'Email already registered', 409);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO members (name, email, password_hash, role)
         VALUES (?, ?, ?, 'student')"
    );
    $stmt->execute([$name, $email, $hash]);

    return jsonSuccess($rs, ['id' => (int) $pdo->lastInsertId()], 201);
});

// =========================================================================
// 7. POST /api/members/login — issue JWT
// =========================================================================
$app->post('/api/members/login', function (Request $rq, Response $rs) use ($pdo): Response {
    $body  = $rq->getParsedBody() ?? [];
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = (string) ($body['password'] ?? '');

    if ($email === '' || $pass === '') {
        return jsonError($rs, 'Email and password required', 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM members WHERE email = ?');
    $stmt->execute([$email]);
    $member = $stmt->fetch();

    // Use the same generic error for both "no such user" and "wrong password"
    // so we don't leak which emails are registered.
    if (!$member || !password_verify($pass, $member['password_hash'])) {
        return jsonError($rs, 'Invalid credentials', 401);
    }

    $secret = $_ENV['JWT_SECRET'] ?? '';
    $algo   = $_ENV['JWT_ALGO']   ?? 'HS256';
    $ttl    = (int) ($_ENV['JWT_TTL'] ?? 3600);

    $payload = [
        'iss'  => 'smartlib',
        'iat'  => time(),
        'exp'  => time() + $ttl,
        'id'   => (int) $member['id'],
        'name' => $member['name'],
        'role' => $member['role'],
    ];

    $token = JWT::encode($payload, $secret, $algo);

    return jsonSuccess($rs, [
        'token' => $token,
        'user'  => [
            'id'    => (int) $member['id'],
            'name'  => $member['name'],
            'email' => $member['email'],
            'role'  => $member['role'],
        ],
    ]);
});

// =========================================================================
// 8. POST /api/borrow — borrow a book (any authed user)
// =========================================================================
$app->post('/api/borrow', function (Request $rq, Response $rs) use ($pdo): Response {
    $body    = $rq->getParsedBody() ?? [];
    $bookId  = (int) ($body['book_id'] ?? 0);
    $userId  = (int) $rq->getAttribute('user_id');

    if ($bookId < 1) {
        return jsonError($rs, 'book_id is required', 400);
    }

    $borrowDays = (int) ($_ENV['BORROW_DAYS'] ?? 14);

    // Wrap in a transaction so the stock decrement and the insert succeed together.
    $pdo->beginTransaction();
    try {
        // Lock the row to prevent two concurrent borrows from over-issuing the last copy
        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book) {
            $pdo->rollBack();
            return jsonError($rs, 'Book not found', 404);
        }
        if ((int) $book['available_count'] < 1) {
            $pdo->rollBack();
            return jsonError($rs, 'No copies available', 409);
        }

        // Block double-borrow of the same book by the same user
        $stmt = $pdo->prepare(
            "SELECT id FROM borrow_records
              WHERE member_id = ? AND book_id = ? AND status = 'active'"
        );
        $stmt->execute([$userId, $bookId]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return jsonError($rs, 'You already have an active borrow for this book', 409);
        }

        $borrowDate = date('Y-m-d');
        $dueDate    = date('Y-m-d', strtotime("+{$borrowDays} days"));

        $stmt = $pdo->prepare(
            "INSERT INTO borrow_records (member_id, book_id, borrow_date, due_date, status)
             VALUES (?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$userId, $bookId, $borrowDate, $dueDate]);
        $recordId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('UPDATE books SET available_count = available_count - 1 WHERE id = ?');
        $stmt->execute([$bookId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return jsonSuccess($rs, [
        'record_id' => $recordId,
        'due_date'  => $dueDate,
    ], 201);
})->add(new JwtMiddleware());

// =========================================================================
// 9. POST /api/return/{id} — return a borrow record
// =========================================================================
$app->post('/api/return/{id}', function (Request $rq, Response $rs, array $args) use ($pdo): Response {
    $recordId = (int) $args['id'];
    $userId   = (int) $rq->getAttribute('user_id');
    $role     = (string) $rq->getAttribute('user_role');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM borrow_records WHERE id = ? FOR UPDATE');
        $stmt->execute([$recordId]);
        $record = $stmt->fetch();

        if (!$record) {
            $pdo->rollBack();
            return jsonError($rs, 'Borrow record not found', 404);
        }

        // Only the borrower or an admin may return
        if ((int) $record['member_id'] !== $userId && $role !== 'admin') {
            $pdo->rollBack();
            return jsonError($rs, 'Forbidden', 403);
        }

        if ($record['status'] !== 'active') {
            $pdo->rollBack();
            return jsonError($rs, 'This record is already returned', 409);
        }

        $returnDate = date('Y-m-d');
        $stmt = $pdo->prepare(
            "UPDATE borrow_records
                SET return_date = ?, status = 'returned'
              WHERE id = ?"
        );
        $stmt->execute([$returnDate, $recordId]);

        $stmt = $pdo->prepare('UPDATE books SET available_count = available_count + 1 WHERE id = ?');
        $stmt->execute([$record['book_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return jsonSuccess($rs, ['return_date' => $returnDate]);
})->add(new JwtMiddleware());

// =========================================================================
// 10. GET /api/members — list all members (Admin only)
// =========================================================================
$app->get('/api/members', function (Request $rq, Response $rs) use ($pdo): Response {
    // Never expose password_hash
    $stmt = $pdo->prepare(
        'SELECT id, name, email, role, created_at
           FROM members
          ORDER BY created_at DESC'
    );
    $stmt->execute();

    return jsonSuccess($rs, ['data' => $stmt->fetchAll()]);
})->add(new JwtMiddleware('admin'));

// =========================================================================
// POST /api/members/password — logged-in user changes their own password.
//
// Requires:
//   - Valid JWT (any role)
//   - current_password matches the stored bcrypt hash
//   - new_password is at least 8 chars and different from current
// =========================================================================
$app->post('/api/members/password', function (Request $rq, Response $rs) use ($pdo): Response {
    $userId  = (int) $rq->getAttribute('user_id');
    $body    = $rq->getParsedBody() ?? [];
    $current = (string) ($body['current_password'] ?? '');
    $new     = (string) ($body['new_password']     ?? '');

    if ($current === '' || $new === '') {
        return jsonError($rs, 'Both current_password and new_password are required', 400);
    }
    if (strlen($new) < 8) {
        return jsonError($rs, 'New password must be at least 8 characters', 400);
    }
    if ($current === $new) {
        return jsonError($rs, 'New password must be different from the current one', 400);
    }

    $stmt = $pdo->prepare('SELECT id, password_hash FROM members WHERE id = ?');
    $stmt->execute([$userId]);
    $member = $stmt->fetch();

    if (!$member) {
        return jsonError($rs, 'Account not found', 404);
    }

    // Constant-time check — protects against timing attacks.
    if (!password_verify($current, $member['password_hash'])) {
        return jsonError($rs, 'Current password is incorrect', 401);
    }

    $newHash = password_hash($new, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE members SET password_hash = ? WHERE id = ?');
    $stmt->execute([$newHash, $userId]);

    // NOTE: existing JWTs are still valid until they expire (stateless tokens).
    // For a real production system you would add a token-blacklist or
    // "password_changed_at" timestamp to invalidate older tokens. For this
    // course project we accept the trade-off — documented in the security log.
    return jsonSuccess($rs, ['message' => 'Password updated successfully']);
})->add(new JwtMiddleware());

// =========================================================================
// Bonus: GET /api/borrow/me — list current user's borrow history (handy for ReturnView)
// =========================================================================
$app->get('/api/borrow/me', function (Request $rq, Response $rs) use ($pdo): Response {
    $userId = (int) $rq->getAttribute('user_id');

    $stmt = $pdo->prepare(
        'SELECT br.*, b.title, b.author
           FROM borrow_records br
           JOIN books b ON b.id = br.book_id
          WHERE br.member_id = ?
          ORDER BY br.borrow_date DESC'
    );
    $stmt->execute([$userId]);

    return jsonSuccess($rs, ['data' => $stmt->fetchAll()]);
})->add(new JwtMiddleware());

// =========================================================================
// Health check — handy for verifying the server is up
// =========================================================================
$app->get('/', function (Request $rq, Response $rs): Response {
    return jsonSuccess($rs, ['data' => ['service' => 'SmartLib API', 'version' => '1.0.0']]);
});

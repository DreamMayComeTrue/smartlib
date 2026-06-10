-- =============================================================
-- SmartLib — Seed Data
--
-- Run AFTER schema.sql to populate sample books and accounts.
--
-- Test accounts (use on the login screen):
--   Admin   →  admin@smartlib.test    /  password
--   Student →  student@smartlib.test  /  password
--
-- All seed accounts use the bcrypt hash of the literal string "password".
-- BEFORE handing in / demoing, regenerate strong hashes with PHP:
--   php -r "echo password_hash('Admin@1234', PASSWORD_BCRYPT);"
-- Then replace the password_hash column values below.
-- The helper script `php tools/hash_password.php Admin@1234` also works.
-- =============================================================

USE smartlib;

-- MySQL Workbench enables "safe update mode" by default, which blocks the
-- DELETE statements below (no WHERE clause). Turn it off for this session so
-- the script runs cleanly. (Has no effect outside Workbench.)
SET SQL_SAFE_UPDATES = 0;

-- Clear in dependency order (children first) to keep FKs happy
DELETE FROM borrow_records;
DELETE FROM books;
DELETE FROM members;
ALTER TABLE borrow_records AUTO_INCREMENT = 1;
ALTER TABLE books          AUTO_INCREMENT = 1;
ALTER TABLE members        AUTO_INCREMENT = 1;

-- -------------------------------------------------------------
-- Members
-- -------------------------------------------------------------
INSERT INTO members (name, email, password_hash, role) VALUES
('Site Admin',     'admin@smartlib.test',   '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin'),
('Test Student',   'student@smartlib.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student'),
('Jeffrey Tan',    'jeffrey@smartlib.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student'),
('Qiu Jiang Yi',   'qiu@smartlib.test',     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student');

-- NOTE: All four seed accounts share the bcrypt hash above which
-- decodes to the literal string "password". Rotate before demoing.

-- -------------------------------------------------------------
-- Books (12 sample titles across multiple categories)
-- -------------------------------------------------------------
INSERT INTO books (title, author, isbn, category, stock, available_count) VALUES
('Clean Code',                            'Robert C. Martin',     '9780132350884', 'Programming',      3, 3),
('The Pragmatic Programmer',              'Andrew Hunt',          '9780201616224', 'Programming',      2, 2),
('Design Patterns',                       'Erich Gamma',          '9780201633610', 'Programming',      2, 2),
('Cracking the Coding Interview',         'Gayle Laakmann McDowell','9780984782857','Career',          4, 4),
('Computer Networking: A Top-Down Approach','James F. Kurose',    '9780133594140', 'Networking',       2, 2),
('Operating System Concepts',             'Abraham Silberschatz', '9781118063330', 'Systems',          2, 2),
('Database System Concepts',              'Abraham Silberschatz', '9780073523323', 'Databases',        2, 2),
('Introduction to Algorithms',            'Thomas H. Cormen',     '9780262033848', 'Algorithms',       3, 3),
('Hacking: The Art of Exploitation',      'Jon Erickson',         '9781593271442', 'Cybersecurity',    2, 2),
('The Web Application Hackers Handbook',  'Dafydd Stuttard',      '9781118026472', 'Cybersecurity',    1, 1),
('Eloquent JavaScript',                   'Marijn Haverbeke',     '9781593279509', 'Web Development',  2, 2),
('Vue.js Up & Running',                   'Callum Macrae',        '9781491997246', 'Web Development',  3, 3);

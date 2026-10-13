<?php
/**
 * Portal Kampus FIKTIF (PHP) - untuk latihan keamanan web (lab pribadi).
 * Semua akun & data bersifat sintetis.
 *
 * KERENTANAN YANG SENGAJA DIBUAT (hanya 2):
 *   1. IDOR / kontrol akses lemah -> public/grades.php  (parameter student_id)
 *   2. Stored XSS                 -> public/discussion.php (isi pesan di-echo tanpa escape)
 *
 * Bagian lain dibuat aman (prepared statement, CSRF token, password_hash, dll.)
 * agar hanya 2 celah tersebut yang menjadi target latihan.
 * JANGAN dijalankan di jaringan publik / server produksi.
 */
declare(strict_types=1);

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

const DB_FILE = __DIR__ . '/../data/campus.db';
const UPLOAD_DIR = __DIR__ . '/../data/uploads';
const ALLOWED_EXT = ['pdf', 'docx', 'txt', 'zip', 'png', 'jpg'];
const MAX_UPLOAD = 5 * 1024 * 1024;

// ------------------------------------------------------------ akun sintetis
function demo_users(): array
{
    return [
        // username, password, nama, role, nim/nip, email
        ['admin.sistem',  'Adm!n-Kampus-7391',  'Administrator Sistem',             'admin',     'ADM-0001',   'admin@kampus-fiktif.test'],
        ['dosen.hartono', 'Dsn#Hartono-5528',   'Dr. Bambang Hartono (fiktif)',     'dosen',     'NIP-198001', 'hartono@kampus-fiktif.test'],
        ['dosen.sulastri','Dsn#Sulastri-4417',  'Prof. Sulastri Wibisono (fiktif)', 'dosen',     'NIP-197512', 'sulastri@kampus-fiktif.test'],
        ['mhs.rina',      'Mhs#Rina-2026-81',   'Rina Anggraini (fiktif)',          'mahasiswa', 'NIM-24001',  'rina@mhs.kampus-fiktif.test'],
        ['mhs.dimas',     'Mhs#Dimas-2026-42',  'Dimas Prakoso (fiktif)',           'mahasiswa', 'NIM-24002',  'dimas@mhs.kampus-fiktif.test'],
        ['mhs.putri',     'Mhs#Putri-2026-67',  'Putri Maharani (fiktif)',          'mahasiswa', 'NIM-24003',  'putri@mhs.kampus-fiktif.test'],
        ['mhs.fajar',     'Mhs#Fajar-2026-15',  'Fajar Nugraha (fiktif)',           'mahasiswa', 'NIM-24004',  'fajar@mhs.kampus-fiktif.test'],
    ];
}

// ------------------------------------------------------------ database
function init_db(): void
{
    if (file_exists(DB_FILE)) {
        return;
    }
    @mkdir(dirname(DB_FILE), 0775, true);
    @mkdir(UPLOAD_DIR, 0775, true);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("
    CREATE TABLE users(
      id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL,
      full_name TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('mahasiswa','dosen','admin')),
      nim_nip TEXT UNIQUE, email TEXT UNIQUE, active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE courses(id INTEGER PRIMARY KEY, code TEXT UNIQUE, name TEXT, lecturer_id INTEGER);
    CREATE TABLE enrollments(student_id INTEGER, course_id INTEGER, PRIMARY KEY(student_id, course_id));
    CREATE TABLE assignments(id INTEGER PRIMARY KEY, course_id INTEGER, title TEXT, description TEXT, due TEXT);
    CREATE TABLE submissions(
      id INTEGER PRIMARY KEY, assignment_id INTEGER, student_id INTEGER, filename TEXT,
      stored_name TEXT, submitted_at TEXT, grade REAL, feedback TEXT,
      UNIQUE(assignment_id, student_id));
    CREATE TABLE posts(id INTEGER PRIMARY KEY, course_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT);
    ");

    $users = demo_users();
    $ins = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,nim_nip,email) VALUES(?,?,?,?,?,?)');
    foreach ($users as $u) {
        $ins->execute([$u[0], password_hash($u[1], PASSWORD_DEFAULT), $u[2], $u[3], $u[4], $u[5]]);
    }
    $pdo->exec("INSERT INTO courses(code,name,lecturer_id) VALUES('IF101','Pemrograman Web',2),('IF202','Basis Data',3)");
    $pdo->exec("INSERT INTO enrollments VALUES(4,1),(5,1),(6,1),(7,1),(4,2),(5,2),(6,2)");
    $pdo->exec("INSERT INTO assignments(course_id,title,description,due) VALUES
        (1,'Tugas 1: Halaman HTML','Buat halaman profil statis.','2026-11-01'),
        (2,'Tugas 1: ERD','Rancang ERD sistem perpustakaan.','2026-11-05')");
    $pdo->exec("INSERT INTO submissions(assignment_id,student_id,filename,stored_name,submitted_at,grade,feedback) VALUES
        (1,4,'profil_rina.txt','seed_1.txt','2026-10-01 09:00',88,'Rapi dan lengkap.'),
        (1,5,'profil_dimas.txt','seed_2.txt','2026-10-01 10:30',72,'Kurang responsif.'),
        (2,6,'erd_putri.txt','seed_3.txt','2026-10-02 08:15',95,'Sangat baik.')");
    foreach (['seed_1.txt', 'seed_2.txt', 'seed_3.txt'] as $n) {
        file_put_contents(UPLOAD_DIR . '/' . $n, "Berkas contoh sintetis.\n");
    }
    $pdo->exec("INSERT INTO posts(course_id,user_id,body,created_at) VALUES
        (1,2,'Selamat datang di forum Pemrograman Web!','2026-10-01 08:00'),
        (1,4,'Pak, format berkas tugas boleh PDF?','2026-10-01 08:30')");
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        init_db();
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// ------------------------------------------------------------ helper
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $msg): void
{
    $_SESSION['flash'][] = $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('CSRF token tidak valid');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function forbid(int $code = 403, string $msg = 'Akses ditolak'): never
{
    http_response_code($code);
    page_header((string)$code);
    echo '<div class="card"><h2>' . $code . '</h2><p>' . e($msg) . '</p></div>';
    page_footer();
    exit;
}

function current_user(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $u = q('SELECT * FROM users WHERE id=? AND active=1', [$_SESSION['uid']])->fetch();
    return $u ?: null;
}

function require_login(array $roles = []): array
{
    $u = current_user();
    if (!$u) {
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        forbid();
    }
    return $u;
}

function course_member(array $user, int $courseId): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }
    if ($user['role'] === 'dosen') {
        return (bool)q('SELECT 1 FROM courses WHERE id=? AND lecturer_id=?', [$courseId, $user['id']])->fetch();
    }
    return (bool)q('SELECT 1 FROM enrollments WHERE student_id=? AND course_id=?', [$user['id'], $courseId])->fetch();
}

// ------------------------------------------------------------ layout
function page_header(string $title): void
{
    $me = current_user();
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> - Portal Kampus Fiktif</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php if ($me): ?>
<nav>
  <strong>🎓 Portal Kampus (Fiktif)</strong>
  <a href="dashboard.php">Beranda</a>
  <?php if ($me['role'] === 'mahasiswa'): ?>
    <a href="grades.php">Nilai</a>
    <a href="assignments.php">Tugas</a>
  <?php endif; ?>
  <a href="discussion_list.php">Diskusi</a>
  <?php if ($me['role'] === 'dosen'): ?><a href="lecturer.php">Penilaian</a><?php endif; ?>
  <?php if ($me['role'] === 'admin'): ?><a href="admin_users.php">Kelola Akun</a><?php endif; ?>
  <span class="sp"></span>
  <span><?= e($me['full_name']) ?> <span class="tag"><?= e($me['role']) ?></span></span>
  <form method="post" action="logout.php"><?= csrf_field() ?><button>Keluar</button></form>
</nav>
<?php endif; ?>
<main>
  <div class="banner">⚠️ Aplikasi latihan keamanan dengan data sintetis. Jalankan hanya secara lokal.</div>
  <?php
    foreach ($_SESSION['flash'] ?? [] as $m) {
        echo '<div class="flash">' . e($m) . '</div>';
    }
    unset($_SESSION['flash']);
}

function page_footer(): void
{
    echo "</main>\n</body>\n</html>\n";
}

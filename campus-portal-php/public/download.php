<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login();
$s = q('SELECT s.*, a.course_id FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE s.id=?',
       [(int)($_GET['id'] ?? 0)])->fetch();
if (!$s) {
    forbid(404, 'Berkas tidak ditemukan');
}
if ($me['role'] === 'mahasiswa' && (int)$s['student_id'] !== (int)$me['id']) {
    forbid();
}
if ($me['role'] === 'dosen' && !course_member($me, (int)$s['course_id'])) {
    forbid();
}
$path = UPLOAD_DIR . '/' . basename($s['stored_name']);
if (!is_file($path)) {
    forbid(404, 'Berkas tidak ada di server');
}
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . addslashes($s['filename']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);

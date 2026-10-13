<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['mahasiswa']);
if (!is_post()) {
    redirect('assignments.php');
}
csrf_check();

$aid = (int)($_POST['aid'] ?? 0);
$a = q('SELECT * FROM assignments WHERE id=?', [$aid])->fetch();
if (!$a || !course_member($me, (int)$a['course_id'])) {
    forbid();
}

$f = $_FILES['file'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    flash('Pilih berkas terlebih dahulu (maks 5 MB).');
    redirect('assignments.php');
}
if ($f['size'] > MAX_UPLOAD) {
    flash('Berkas terlalu besar.');
    redirect('assignments.php');
}
$orig = basename((string)$f['name']);
$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXT, true)) {
    flash('Tipe berkas tidak diizinkan.');
    redirect('assignments.php');
}
$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
$stored = bin2hex(random_bytes(8)) . '.' . $ext;
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}
move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $stored);

q('INSERT INTO submissions(assignment_id,student_id,filename,stored_name,submitted_at)
   VALUES(?,?,?,?,?)
   ON CONFLICT(assignment_id,student_id)
   DO UPDATE SET filename=excluded.filename, stored_name=excluded.stored_name, submitted_at=excluded.submitted_at',
   [$aid, $me['id'], $safeName, $stored, date('Y-m-d H:i')]);
flash('Tugas berhasil diunggah.');
redirect('assignments.php');

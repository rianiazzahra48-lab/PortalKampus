<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['mahasiswa', 'dosen', 'admin']);

// Mahasiswa tanpa parameter -> nilai sendiri. Dosen/admin tanpa parameter -> daftar mahasiswa.
$sid = isset($_GET['student_id']) ? (int)$_GET['student_id'] : ($me['role'] === 'mahasiswa' ? (int)$me['id'] : 0);

if ($sid === 0) {
    $list = q("SELECT id, full_name, nim_nip FROM users WHERE role='mahasiswa' ORDER BY full_name")->fetchAll();
    page_header('Nilai mahasiswa');
    echo '<div class="card"><h2>Pilih mahasiswa</h2><ul>';
    foreach ($list as $s) {
        echo '<li><a href="grades.php?student_id=' . (int)$s['id'] . '">' . e($s['full_name']) . ' (' . e($s['nim_nip']) . ')</a></li>';
    }
    echo '</ul></div>';
    page_footer();
    exit;
}

// >>> KERENTANAN #1 (IDOR) <<<
// Tidak ada pengecekan bahwa $sid == $me['id'] untuk peran mahasiswa.
// Perbaikan: if ($me['role'] === 'mahasiswa' && $sid !== (int)$me['id']) forbid();
$student = q("SELECT * FROM users WHERE id=? AND role='mahasiswa'", [$sid])->fetch();
if (!$student) {
    forbid(404, 'Mahasiswa tidak ditemukan');
}
$rows = q("SELECT c.code, c.name AS course, a.title, s.grade, s.feedback, s.submitted_at
           FROM submissions s JOIN assignments a ON a.id=s.assignment_id
           JOIN courses c ON c.id=a.course_id
           WHERE s.student_id=? ORDER BY c.code", [$sid])->fetchAll();

page_header('Nilai');
?>
<div class="card">
  <h2>Nilai: <?= e($student['full_name']) ?></h2>
  <p><?= e($student['nim_nip']) ?> &middot; <?= e($student['email']) ?></p>
  <table>
    <tr><th>Mata kuliah</th><th>Tugas</th><th>Nilai</th><th>Catatan</th><th>Dikumpulkan</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['code']) ?> - <?= e($r['course']) ?></td>
      <td><?= e($r['title']) ?></td>
      <td><?= $r['grade'] !== null ? e($r['grade']) : 'Belum dinilai' ?></td>
      <td><?= e($r['feedback'] ?: '-') ?></td>
      <td><?= e($r['submitted_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5">Belum ada nilai.</td></tr><?php endif; ?>
  </table>
</div>
<?php page_footer();

<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login();

function stat_count(string $sql, array $p = []): int
{
    return (int)q($sql, $p)->fetchColumn();
}

page_header('Beranda');
?>
<div class="card">
  <h2>Selamat datang, <?= e($me['full_name']) ?></h2>
  <p>ID pengguna: <?= e($me['nim_nip']) ?> &middot; Peran: <span class="tag"><?= e($me['role']) ?></span></p>
</div>

<?php if ($me['role'] === 'mahasiswa'):
    $total = stat_count('SELECT COUNT(*) FROM assignments a JOIN enrollments e ON e.course_id=a.course_id AND e.student_id=?', [$me['id']]);
    $done = stat_count('SELECT COUNT(*) FROM submissions WHERE student_id=?', [$me['id']]);
    $avg = q('SELECT AVG(grade) FROM submissions WHERE student_id=? AND grade IS NOT NULL', [$me['id']])->fetchColumn();
    ?>
<div class="grid">
  <div class="card stat"><span><?= $done ?>/<?= $total ?></span>Tugas terkumpul</div>
  <div class="card stat"><span><?= $avg !== null && $avg !== false ? e(number_format((float)$avg, 1)) : '-' ?></span>Rata-rata nilai</div>
</div>
<div class="grid">
  <a class="card menu" href="grades.php"><strong>Nilai</strong><br>Lihat nilai dan catatan dosen</a>
  <a class="card menu" href="assignments.php"><strong>Tugas</strong><br>Unggah tugas per mata kuliah</a>
  <a class="card menu" href="discussion_list.php"><strong>Diskusi</strong><br>Forum tiap mata kuliah</a>
</div>

<?php elseif ($me['role'] === 'dosen'):
    $courses = stat_count('SELECT COUNT(*) FROM courses WHERE lecturer_id=?', [$me['id']]);
    $pending = stat_count('SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id=s.assignment_id
                           JOIN courses c ON c.id=a.course_id AND c.lecturer_id=? WHERE s.grade IS NULL', [$me['id']]);
    ?>
<div class="grid">
  <div class="card stat"><span><?= $courses ?></span>Mata kuliah diampu</div>
  <div class="card stat"><span><?= $pending ?></span>Menunggu dinilai</div>
</div>
<div class="grid">
  <a class="card menu" href="lecturer.php"><strong>Penilaian</strong><br>Beri nilai dan buat tugas baru</a>
  <a class="card menu" href="discussion_list.php"><strong>Diskusi</strong><br>Forum tiap mata kuliah</a>
</div>

<?php else:
    $byRole = q('SELECT role, COUNT(*) AS n FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
    ?>
<div class="grid">
  <div class="card stat"><span><?= (int)($byRole['mahasiswa'] ?? 0) ?></span>Mahasiswa</div>
  <div class="card stat"><span><?= (int)($byRole['dosen'] ?? 0) ?></span>Dosen</div>
  <div class="card stat"><span><?= (int)($byRole['admin'] ?? 0) ?></span>Admin</div>
  <div class="card stat"><span><?= stat_count('SELECT COUNT(*) FROM users WHERE active=0') ?></span>Akun nonaktif</div>
</div>
<div class="grid">
  <a class="card menu" href="admin_users.php"><strong>Kelola Akun</strong><br>Buat, ubah, nonaktifkan, reset password</a>
  <a class="card menu" href="grades.php"><strong>Nilai mahasiswa</strong><br>Lihat nilai semua mahasiswa</a>
  <a class="card menu" href="discussion_list.php"><strong>Diskusi</strong><br>Pantau semua forum</a>
</div>
<?php endif; ?>
<?php page_footer();

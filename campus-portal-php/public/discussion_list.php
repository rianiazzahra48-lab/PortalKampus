<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['mahasiswa', 'dosen', 'admin']);
if ($me['role'] === 'admin') {
    $courses = q('SELECT * FROM courses')->fetchAll();
} elseif ($me['role'] === 'dosen') {
    $courses = q('SELECT * FROM courses WHERE lecturer_id=?', [$me['id']])->fetchAll();
} else {
    $courses = q('SELECT c.* FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE e.student_id=?', [$me['id']])->fetchAll();
}
page_header('Diskusi');
?>
<div class="card">
  <h2>Forum Diskusi</h2>
  <ul>
    <?php foreach ($courses as $c): ?>
      <li><a href="discussion.php?course_id=<?= (int)$c['id'] ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?></a></li>
    <?php endforeach; ?>
    <?php if (!$courses): ?><li>Belum ada mata kuliah.</li><?php endif; ?>
  </ul>
</div>
<?php page_footer();

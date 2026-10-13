<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['mahasiswa', 'dosen', 'admin']);
$cid = (int)($_GET['course_id'] ?? 0);
$course = q('SELECT * FROM courses WHERE id=?', [$cid])->fetch();
if (!$course || !course_member($me, $cid)) {
    forbid();
}
if (is_post()) {
    csrf_check();
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body !== '') {
        // Isi pesan disimpan APA ADANYA (tanpa sanitasi)
        q('INSERT INTO posts(course_id,user_id,body,created_at) VALUES(?,?,?,?)',
          [$cid, $me['id'], $body, date('Y-m-d H:i')]);
    }
    redirect('discussion.php?course_id=' . $cid);
}
$posts = q('SELECT p.*, u.full_name, u.role FROM posts p JOIN users u ON u.id=p.user_id
            WHERE p.course_id=? ORDER BY p.id', [$cid])->fetchAll();
page_header('Diskusi');
?>
<div class="card">
  <h2>Diskusi: <?= e($course['code']) ?> - <?= e($course['name']) ?></h2>
  <?php foreach ($posts as $p): ?>
  <div class="post">
    <strong><?= e($p['full_name']) ?></strong> <span class="tag"><?= e($p['role']) ?></span>
    <small><?= e($p['created_at']) ?></small>
    <?php
    // >>> KERENTANAN #2 (Stored XSS) <<<
    // Isi pesan di-echo tanpa htmlspecialchars().
    // Perbaikan: gunakan e($p['body']) dan/atau tambahkan header Content-Security-Policy.
    ?>
    <div><?= $p['body'] ?></div>
  </div>
  <?php endforeach; ?>
</div>
<div class="card">
  <form method="post">
    <?= csrf_field() ?>
    <label>Tulis pesan</label>
    <textarea name="body" rows="3" required></textarea>
    <p><button class="primary">Kirim</button></p>
  </form>
</div>
<?php page_footer();

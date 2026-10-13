<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['mahasiswa']);
$rows = q("SELECT a.*, c.code, c.name AS course, s.id AS sub_id, s.filename, s.submitted_at
           FROM assignments a JOIN courses c ON c.id=a.course_id
           JOIN enrollments e ON e.course_id=c.id AND e.student_id=?
           LEFT JOIN submissions s ON s.assignment_id=a.id AND s.student_id=?
           ORDER BY a.due", [$me['id'], $me['id']])->fetchAll();
page_header('Tugas');
?>
<div class="card">
  <h2>Tugas</h2>
  <table>
    <tr><th>Mata kuliah</th><th>Tugas</th><th>Tenggat</th><th>Status</th><th>Unggah</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['code']) ?> - <?= e($r['course']) ?></td>
      <td><strong><?= e($r['title']) ?></strong><br><small><?= e($r['description']) ?></small></td>
      <td><?= e($r['due']) ?></td>
      <td>
        <?php if ($r['sub_id']): ?>
          <a href="download.php?id=<?= (int)$r['sub_id'] ?>"><?= e($r['filename']) ?></a><br>
          <small><?= e($r['submitted_at']) ?></small>
        <?php else: ?>Belum dikumpulkan<?php endif; ?>
      </td>
      <td>
        <form method="post" action="upload.php" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="aid" value="<?= (int)$r['id'] ?>">
          <input type="file" name="file" required>
          <button class="primary sm">Kirim</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p><small>Tipe berkas: pdf, docx, txt, zip, png, jpg (maks 5 MB).</small></p>
</div>
<?php page_footer();

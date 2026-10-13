<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['dosen']);

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'grade';

    if ($action === 'new_assignment') {
        $cid = (int)($_POST['course_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $due = (string)($_POST['due'] ?? '');
        if (!course_member($me, $cid) || $title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
            flash('Data tugas tidak valid.');
        } else {
            q('INSERT INTO assignments(course_id,title,description,due) VALUES(?,?,?,?)',
              [$cid, mb_substr($title, 0, 120), mb_substr(trim((string)($_POST['description'] ?? '')), 0, 300), $due]);
            flash('Tugas baru dibuat.');
        }
        redirect('lecturer.php');
    }

    $subId = (int)($_POST['sub_id'] ?? 0);
    $s = q('SELECT s.id, a.course_id FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE s.id=?', [$subId])->fetch();
    if (!$s || !course_member($me, (int)$s['course_id'])) {
        forbid();
    }
    $g = $_POST['grade'] ?? '';
    if (!is_numeric($g) || (float)$g < 0 || (float)$g > 100) {
        flash('Nilai harus angka 0-100.');
    } else {
        q('UPDATE submissions SET grade=?, feedback=? WHERE id=?',
          [(float)$g, mb_substr((string)($_POST['feedback'] ?? ''), 0, 300), $subId]);
        flash('Nilai tersimpan.');
    }
    redirect('lecturer.php');
}

$myCourses = q('SELECT * FROM courses WHERE lecturer_id=?', [$me['id']])->fetchAll();
$rows = q('SELECT s.id, s.filename, s.submitted_at, s.grade, s.feedback, u.full_name, u.nim_nip, a.title, c.code
           FROM submissions s JOIN assignments a ON a.id=s.assignment_id
           JOIN courses c ON c.id=a.course_id AND c.lecturer_id=?
           JOIN users u ON u.id=s.student_id
           ORDER BY c.code, a.id, u.full_name', [$me['id']])->fetchAll();
page_header('Penilaian');
?>
<div class="card">
  <h2>Penilaian Tugas</h2>
  <table>
    <tr><th>Mahasiswa</th><th>Tugas</th><th>Berkas</th><th>Nilai &amp; catatan</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['full_name']) ?><br><small><?= e($r['nim_nip']) ?></small></td>
      <td><?= e($r['code']) ?><br><?= e($r['title']) ?></td>
      <td><a href="download.php?id=<?= (int)$r['id'] ?>"><?= e($r['filename']) ?></a><br><small><?= e($r['submitted_at']) ?></small></td>
      <td>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="grade">
          <input type="hidden" name="sub_id" value="<?= (int)$r['id'] ?>">
          <div class="row">
            <input name="grade" type="number" min="0" max="100" step="0.1" value="<?= e($r['grade']) ?>" placeholder="0-100" required>
            <input name="feedback" value="<?= e($r['feedback']) ?>" placeholder="Catatan" maxlength="300">
            <button class="primary sm">Simpan</button>
          </div>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="4">Belum ada pengumpulan.</td></tr><?php endif; ?>
  </table>
</div>
<div class="card">
  <h3>Buat tugas baru</h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="new_assignment">
    <div class="row">
      <select name="course_id" required>
        <?php foreach ($myCourses as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="title" placeholder="Judul tugas" maxlength="120" required>
      <input name="due" type="date" required>
    </div>
    <label>Deskripsi</label>
    <textarea name="description" rows="2" maxlength="300"></textarea>
    <p><button class="primary">Buat tugas</button></p>
  </form>
</div>
<?php page_footer();

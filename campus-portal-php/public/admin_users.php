<?php
require __DIR__ . '/../includes/lib.php';
$me = require_login(['admin']);

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $role = $_POST['role'] ?? '';
            $pw = (string)($_POST['password'] ?? '');
            if (!in_array($role, ['mahasiswa', 'dosen', 'admin'], true) || strlen($pw) < 8) {
                throw new RuntimeException('Data tidak valid (password min. 8 karakter).');
            }
            q('INSERT INTO users(username,password_hash,full_name,role,nim_nip,email) VALUES(?,?,?,?,?,?)', [
                trim((string)$_POST['username']), password_hash($pw, PASSWORD_DEFAULT),
                trim((string)$_POST['full_name']), $role,
                trim((string)($_POST['nim_nip'] ?? '')) ?: null, trim((string)($_POST['email'] ?? '')) ?: null,
            ]);
            flash('Akun dibuat.');
        } elseif ($action === 'edit') {
            $uid = (int)($_POST['uid'] ?? 0);
            $role = $_POST['role'] ?? '';
            $name = trim((string)($_POST['full_name'] ?? ''));
            if (!in_array($role, ['mahasiswa', 'dosen', 'admin'], true) || $name === '') {
                throw new RuntimeException('Data tidak valid.');
            }
            if ($uid === (int)$me['id'] && $role !== 'admin') {
                throw new RuntimeException('Tidak bisa mengubah peran akun sendiri.');
            }
            q('UPDATE users SET full_name=?, role=?, nim_nip=?, email=? WHERE id=?', [
                $name, $role,
                trim((string)($_POST['nim_nip'] ?? '')) ?: null, trim((string)($_POST['email'] ?? '')) ?: null, $uid,
            ]);
            flash('Akun diperbarui.');
        } elseif ($action === 'toggle') {
            $uid = (int)($_POST['uid'] ?? 0);
            if ($uid === (int)$me['id']) {
                flash('Tidak bisa menonaktifkan akun sendiri.');
            } else {
                q('UPDATE users SET active = 1 - active WHERE id=?', [$uid]);
            }
        } elseif ($action === 'reset') {
            $pw = (string)($_POST['password'] ?? '');
            if (strlen($pw) < 8) {
                flash('Password minimal 8 karakter.');
            } else {
                q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), (int)($_POST['uid'] ?? 0)]);
                flash('Password diganti.');
            }
        }
    } catch (Throwable $ex) {
        flash($ex instanceof PDOException ? 'Gagal: username/NIM/email sudah dipakai atau data tidak valid.' : 'Gagal: ' . $ex->getMessage());
    }
    redirect('admin_users.php');
}

$users = q('SELECT * FROM users ORDER BY role, id')->fetchAll();
page_header('Kelola Akun');
?>
<div class="card">
  <h2>Kelola Akun</h2>
  <table>
    <tr><th>ID</th><th>Username</th><th>Nama</th><th>Peran</th><th>NIM/NIP</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td><td><?= e($u['username']) ?></td><td><?= e($u['full_name']) ?></td>
      <td><span class="tag"><?= e($u['role']) ?></span></td><td><?= e($u['nim_nip']) ?></td>
      <td><?= $u['active'] ? 'Aktif' : 'Nonaktif' ?></td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
          <button class="sm"><?= $u['active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
        </form>
        <form method="post" style="margin-top:6px">
          <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
          <input name="password" type="password" placeholder="Password baru" minlength="8" required>
          <button class="sm">Reset</button>
        </form>
        <details>
          <summary>Ubah data</summary>
          <form method="post" style="margin-top:6px">
            <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
            <input name="full_name" value="<?= e($u['full_name']) ?>" required>
            <input name="nim_nip" value="<?= e($u['nim_nip']) ?>" placeholder="NIM/NIP">
            <input name="email" value="<?= e($u['email']) ?>" placeholder="Email">
            <select name="role">
              <?php foreach (['mahasiswa', 'dosen', 'admin'] as $r): ?>
                <option<?= $u['role'] === $r ? ' selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
            <button class="sm">Simpan</button>
          </form>
        </details>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="card">
  <h3>Tambah akun</h3>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="row">
      <input name="username" placeholder="Username" required>
      <input name="password" type="password" placeholder="Password (min. 8)" minlength="8" required>
      <input name="full_name" placeholder="Nama lengkap" required>
      <select name="role"><option>mahasiswa</option><option>dosen</option><option>admin</option></select>
      <input name="nim_nip" placeholder="NIM/NIP (unik)">
      <input name="email" placeholder="Email (unik)">
    </div>
    <p><button class="primary">Buat akun</button></p>
  </form>
</div>
<?php page_footer();

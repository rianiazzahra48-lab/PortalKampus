<?php
require __DIR__ . '/../includes/lib.php';
if (current_user()) {
    redirect('dashboard.php');
}
$error = '';
if (is_post()) {
    csrf_check();
    $u = q('SELECT * FROM users WHERE username=? AND active=1', [trim($_POST['username'] ?? '')])->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION = ['uid' => (int)$u['id'], 'csrf' => bin2hex(random_bytes(16))];
        redirect('dashboard.php');
    }
    $error = 'Username atau password salah.';
}
page_header('Masuk');
?>
<div class="card login">
  <h2>Masuk</h2>
  <?php if ($error): ?><div class="flash"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username</label>
    <input name="username" required autofocus>
    <label>Password</label>
    <input name="password" type="password" required>
    <p><button class="primary">Masuk</button></p>
  </form>
</div>
<div class="card" style="max-width:720px;margin:0 auto 16px">
  <h3>Akun latihan (sintetis)</h3>
  <p><small>Hanya untuk lab lokal. Setiap akun memiliki nama, NIM/NIP, email, dan password yang berbeda.</small></p>
  <table>
    <tr><th>Peran</th><th>Username</th><th>Password</th><th>Nama</th></tr>
    <?php foreach (demo_users() as $d): ?>
    <tr>
      <td><span class="tag"><?= e($d[3]) ?></span></td>
      <td><code><?= e($d[0]) ?></code></td>
      <td><code><?= e($d[1]) ?></code></td>
      <td><?= e($d[2]) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php page_footer();

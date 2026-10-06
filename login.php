<?php
require_once 'includes/init.php';
$pageTitle = 'Login';
$error = '';

// Where to go after login (only simple local page names are allowed)
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if (!preg_match('/^[a-z_]+\.php(\?id=\d+)?$/', $redirect)) $redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // password_verify compares the typed password with the stored hash
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // prevents session fixation
        $_SESSION['user'] = [
            'id' => $user['id'], 'name' => $user['name'],
            'email' => $user['email'], 'role' => $user['role']
        ];
        if ($user['role'] === 'admin') header('Location: admin/index.php');
        else header('Location: ' . ($redirect ?: 'index.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}
require 'includes/header.php';
?>
<div class="card form-card">
  <h3>Login</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
    <div class="form-group"><label>Email</label>
      <input type="email" name="email" required></div>
    <div class="form-group"><label>Password</label>
      <input type="password" name="password" required></div>
    <button class="btn btn-primary btn-block">Login</button>
  </form>
  <p class="mt text-center">New here? <a href="register.php">Create an account</a></p>
  <hr>
  <small class="muted">Demo: admin@events.com / admin123 &nbsp;|&nbsp; riya@example.com / user123</small>
</div>
<?php require 'includes/footer.php'; ?>

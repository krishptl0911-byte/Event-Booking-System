<?php
require_once 'includes/init.php';
$pageTitle = 'Register';
$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Server-side validation
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // Email must be unique
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }

    if (!$errors) {
        // Never store plain passwords: password_hash uses bcrypt with a random salt
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'user')")
            ->execute([$name, $email, $hash]);
        setFlash('success', 'Registration successful. Please login.');
        header('Location: login.php');
        exit;
    }
}
require 'includes/header.php';
?>
<div class="card form-card">
  <h3>Create account</h3>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <div id="jsError" class="alert alert-danger hidden"></div>
  <form method="post" id="registerForm">
    <div class="form-group"><label>Full name</label>
      <input type="text" name="name" value="<?= e($name) ?>" required></div>
    <div class="form-group"><label>Email</label>
      <input type="email" name="email" value="<?= e($email) ?>" required></div>
    <div class="form-group"><label>Password</label>
      <input type="password" name="password" id="password" required></div>
    <div class="form-group"><label>Confirm password</label>
      <input type="password" name="confirm" id="confirm" required></div>
    <button class="btn btn-primary btn-block">Register</button>
  </form>
  <p class="mt text-center">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php require 'includes/footer.php'; ?>

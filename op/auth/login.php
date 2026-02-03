<?php
require_once "config.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: ../index.php");
            exit;
        } else {
            $error = "Username or password ভুল হয়েছে।";
        }
    } else {
        $error = "Username এবং password দুটোই লাগবে।";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - School Note Manager</title>
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">

</head>
<body class="bg-gradient-to-br from-blue-100 to-indigo-100 min-h-screen flex items-center justify-center">

<div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-md">
  <h1 class="text-2xl font-bold mb-6 text-center text-indigo-600">🔐 Login</h1>

  <?php if ($error): ?>
    <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-semibold mb-1">Username</label>
      <input type="text" name="username" class="w-full p-2 border rounded" required>
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Password</label>
      <input type="password" name="password" class="w-full p-2 border rounded" required>
    </div>
    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded mt-2">
      Login
    </button>
  </form>
</div>

</body>
</html>

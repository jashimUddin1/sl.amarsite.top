<?php
// pages/settings.php
require_once '../auth/config.php';
require_login();

$pageTitle   = 'Settings - School Note Manager';
$pageHeading = 'Settings';
$activeMenu  = 'settings';

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Flash message
$flash = $_SESSION['flash'] ?? ['type' => '', 'msg' => ''];
unset($_SESSION['flash']);

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ✅ Handle POST (Only Password Change kept)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new     = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if ($new === '' || strlen($new) < 6) {
                throw new Exception('New password কমপক্ষে 6 অক্ষরের হওয়া উচিত।');
            }
            if ($new !== $confirm) {
                throw new Exception('New password এবং Confirm password এক হয়নি।');
            }

            // ✅ users table থেকে current hash আনো
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $user_id]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current, $hash)) {
                throw new Exception('Current password ভুল।');
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
            $up->execute([':p' => $newHash, ':id' => $user_id]);

            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Password changed ✅'];
            header('Location: settings.php#security');
            exit;
        }

        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Unknown action'];
        header('Location: settings.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => $e->getMessage()];
        header('Location: settings.php#security');
        exit;
    }
}

require '../layout/layout_header.php';
?>

<div class="container-fluid">

    <?php if (!empty($flash['msg'])): ?>
        <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo h($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        🔐 Security
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab">
                        ℹ️ About
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4">

                <!-- Security -->
                <div class="tab-pane fade show active" id="security" role="tabpanel" aria-labelledby="security-tab">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="fw-semibold mb-2">Change Password</div>

                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="col-12">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-dark">Update Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="alert alert-info mb-0">
                                ✅ Tip: Password কমপক্ষে 6+ অক্ষর রাখো, letter+number mix হলে ভালো।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <div class="tab-pane fade" id="about" role="tabpanel" aria-labelledby="about-tab">
                    <div class="text-secondary">
                        <div class="fw-semibold text-dark mb-2">School List / Invoice Manager</div>

                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex flex-wrap gap-3">
                                <div>
                                    <div class="fw-semibold text-dark">Current Version</div>
                                    <div>v1.05.05 (Release: 10 February 2026)</div>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Upcoming Version</div>
                                    <div>v1.10 (Release: 6 July 2026)</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <ul class="mb-0">
                                <li>এই Settings page থেকে account password manage করতে পারবেন।</li>
                                <li>Version info এখানে দেখাবে।</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div><!-- tab-content -->

        </div><!-- card-body -->
    </div><!-- card -->

</div><!-- container -->

<script>
// ✅ Deep link: #security / #about
(function () {
    const hash = window.location.hash;
    if (!hash) return;
    const trigger = document.querySelector(`button[data-bs-target="${hash}"]`);
    if (trigger && window.bootstrap) {
        const tab = new bootstrap.Tab(trigger);
        tab.show();
    }
})();
</script>

<?php require '../layout/layout_footer.php'; ?>

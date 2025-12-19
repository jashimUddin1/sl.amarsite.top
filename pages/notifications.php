<?php
// notifications.php
require_once '../auth/config.php';
require_login();



$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// ❌ Alert dismiss করলে সেশনে ফ্ল্যাগ সেট করে দেব
if (isset($_GET['dismiss_alert'])) {
    $_SESSION['notifications_first_visit'] = true; // মানে: alert already shown/dismissed
    header('Location: notifications.php');
    exit;
}

/**
 * next_meet এখন থেকে 24 ঘন্টার মধ্যে যেসব note আছে,
 * এবং এই user + note এর জন্য notifications টেবিলে এখনো এন্ট্রি নেই,
 * সেগুলোর জন্য unread notification তৈরি করবে।
 * (নোটিফিকেশন তৈরি করার লজিক ২৪ ঘন্টার window, কিন্তু view তে সব দেখাবো)
 */
function generateNotificationsForUser(PDO $pdo, int $user_id): void
{
    $sql = "
        INSERT INTO notifications (user_id, note_id, status, action_at)
        SELECT 
            :user_id AS user_id,
            sn.id    AS note_id,
            'unread' AS status,
            NOW()    AS action_at
        FROM school_notes sn
        LEFT JOIN notifications n
            ON n.note_id = sn.id
           AND n.user_id = :user_id
        WHERE sn.next_meet IS NOT NULL
          AND sn.next_meet > NOW()
          AND sn.next_meet <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
          AND n.id IS NULL
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
}

// ✅ প্রতি পেজ লোডে নতুন notification জেনারেট করার চেষ্টা (২৪ ঘণ্টার window অনুযায়ী)
generateNotificationsForUser($pdo, (int)$user_id);

// ✅ সবগুলো unread notification একসাথে read করার হ্যান্ডলার (?mark_all=1)
if (isset($_GET['mark_all'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET status = 'read',
                action_at = NOW()
            WHERE user_id = :user_id
              AND status = 'unread'
        ");
        $stmt->execute([
            ':user_id' => $user_id,
        ]);
    } catch (Exception $e) {
        // error_log($e->getMessage());
    }

    header('Location: notifications.php');
    exit;
}

// ✅ আগামী ২৪ ঘন্টার notification গুলো আবার unread করার হ্যান্ডলার (?unread_next24=1)
if (isset($_GET['unread_next24'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications n
            JOIN school_notes sn ON sn.id = n.note_id
            SET n.status = 'unread',
                n.action_at = NOW()
            WHERE n.user_id = :user_id
              AND sn.next_meet IS NOT NULL
              AND sn.next_meet > NOW()
              AND sn.next_meet <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([
            ':user_id' => $user_id,
        ]);
    } catch (Exception $e) {
        // error_log($e->getMessage());
    }

    header('Location: notifications.php');
    exit;
}

// ✅ single notification read করার হ্যান্ডলার (?mark_read=notification_id)
if (isset($_GET['mark_read'])) {
    $notifId = (int) $_GET['mark_read'];

    if ($notifId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET status = 'read',
                    action_at = NOW()
                WHERE id = :id
                  AND user_id = :user_id
            ");
            $stmt->execute([
                ':id'      => $notifId,
                ':user_id' => $user_id,
            ]);
        } catch (Exception $e) {
            // error_log($e->getMessage());
        }
    }

    header('Location: notifications.php');
    exit;
}

// ✅ এই user-এর সব notification list আনব (past + future সব) + স্কুল নামসহ
$sql = "
    SELECT 
        n.id        AS notification_id,
        n.status,
        n.action_at,
        sn.id       AS note_id,
        sn.school_id,
        sn.note_text,
        sn.next_meet,
        s.school_name,
        s.district,
        s.upazila
    FROM notifications n
    JOIN school_notes sn ON sn.id = n.note_id
    LEFT JOIN schools s   ON s.id = sn.school_id
    WHERE n.user_id = :user_id
      AND sn.next_meet IS NOT NULL
    ORDER BY 
        (sn.next_meet >= NOW()) DESC,   -- আগে future গুলো
        sn.next_meet ASC,               -- future গুলোর মধ্যে যেটা আগে সেট আগে
        n.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ unread কতগুলো আছে (সব সময়ের মধ্যে যত unread)
$unreadCount = 0;
foreach ($notifications as $row) {
    if ($row['status'] === 'unread') {
        $unreadCount++;
    }
}

// ✅ প্রথমবার alert দেখাবে কিনা (যদি dismiss করা না থাকে)
$showFirstTimeAlert = !($_SESSION['notifications_first_visit'] ?? false);

$pageTitle   = 'Notifications';
$pageHeading = 'Notifications';
$activeMenu  = 'notifications';

include '../layout/layout_header.php';
?>

<div class="px-2 py-4">

    <?php if ($showFirstTimeAlert): ?>
        <?php if ($unreadCount > 0): ?>
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                আপনার <strong><?php echo $unreadCount; ?></strong> টি unread notification আছে।
                <a href="notifications.php?dismiss_alert=1"
                   class="btn-close"
                   data-bs-dismiss="alert"
                   aria-label="Close"></a>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                এই মুহূর্তে আপনার জন্য কোনো unread notification নেই।
                <a href="notifications.php?dismiss_alert=1"
                   class="btn-close"
                   data-bs-dismiss="alert"
                   aria-label="Close"></a>
            </div>
        <?php endif; ?>
    <?php endif; ?>



    <!-- Heading + Buttons Row -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">
            All Notifications
        </h2>

        <div class="flex items-center gap-2">
            <?php if (!empty($notifications) && $unreadCount > 0): ?>
                <!-- Mark all as read button with confirm -->
                <a href="notifications.php?mark_all=1"
                   class="px-3 py-2 text-xs sm:text-sm rounded bg-slate-900 text-white hover:bg-slate-700"
                   onclick="return confirm('সব unread notification কি read হিসেবে মার্ক করতে চান?');">
                    Mark all as read
                </a>
            <?php elseif (!empty($notifications) && $unreadCount == 0): ?>
                <!-- সব read হয়ে গেলে: Unread next 24 hours button -->
                <a href="notifications.php?unread_next24=1"
                   class="px-3 py-1 text-xs sm:text-sm rounded bg-amber-600 text-white hover:bg-amber-700"
                   onclick="return confirm('পরবর্তী ২৪ ঘন্টার সব notification আবার unread করতে চান?');">
                    Unread next 24 hours
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="bg-white shadow rounded-lg p-4 text-sm text-slate-600">
            কোনো notification পাওয়া যায়নি।
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $note): ?>
                <?php
                $isRead       = ($note['status'] === 'read');
                $meetDateTime = new DateTime($note['next_meet']);
                $meetLabel    = $meetDateTime->format('d M Y, h:i A');

                $schoolId   = (int)($note['school_id'] ?? 0);
                $schoolName = $note['school_name'] ?? 'Unknown School';
                $district   = $note['district'] ?? '';
                $upazila    = $note['upazila'] ?? '';

                $now      = new DateTime();
                $isFuture = ($meetDateTime > $now);

                // 🔴 Border Color Logic
                if ($isFuture) {
                    $borderClass = $isRead ? 'border-gray-300' : 'border-green-500'; // future unread = green
                } else {
                    $borderClass = $isRead ? 'border-gray-300' : 'border-red-500';   // past unread = red
                }
                ?>

                <div class="bg-white shadow rounded-lg p-3 border-l-4 <?php echo $borderClass; ?>">
                    <div class="flex justify-between items-start gap-3">
                        <div>
                            <div class="text-xs text-slate-500 mb-1">
                                <?php
                                // ফরম্যাট: #ID → school_name → district, upazila
                                echo '<h1 style="font-size: 1.2rem;"><strong>' . htmlspecialchars($schoolName) . '</strong></h1>';

                                if ($district || $upazila) {
                                    echo ' → ' . htmlspecialchars($district);
                                    if ($upazila) {
                                        echo ', ' . htmlspecialchars($upazila);
                                    }
                                }
                                ?>
                            </div>

                            <div class="text-sm font-semibold mb-1 <?php echo $isRead ? 'text-slate-500' : 'text-slate-800'; ?>">
                                মিটিং এর সময়: <?php echo htmlspecialchars($meetLabel); ?>
                            </div>
                            <div class="text-sm text-slate-700 <?php echo $isRead ? 'opacity-70' : ''; ?>">
                                <?php echo nl2br(htmlspecialchars($note['note_text'])); ?>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1 text-xs">
                            <?php if (!$isRead): ?>

                                <?php if ($isFuture): ?>
                                    <!-- 🟢 Future unread = Green button -->
                                    <a href="notifications.php?mark_read=<?php echo (int)$note['notification_id']; ?>"
                                       class="px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700"
                                       onclick="return confirm('এই notification টি কি read হিসেবে মার্ক করতে চান?');">
                                       Unread
                                    </a>
                                <?php else: ?>
                                    <!-- 🔴 Past unread = Red button -->
                                    <a href="notifications.php?mark_read=<?php echo (int)$note['notification_id']; ?>"
                                       class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                                       onclick="return confirm('সময় পেরিয়ে গেছে! Read হিসেবে মার্ক করতে চান?');">
                                       Unread
                                    </a>
                                <?php endif; ?>

                            <?php else: ?>
                                <span class="px-2 py-1 rounded bg-gray-200 text-gray-700">
                                    Read
                                </span>
                            <?php endif; ?>

                            <!-- ⏱️ Live Remaining time / Time reached -->
                            <span class="text-[11px] text-slate-500">
                                Remaining:<br>
                                <span class="countdown"
                                      data-target="<?php echo htmlspecialchars(str_replace(' ', 'T', $note['next_meet'])); ?>">
                                    Calculating...
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- 🔁 Live countdown script -->
<script>
function startCountdown() {
    const items = document.querySelectorAll('.countdown');

    items.forEach(function (el) {
        const target = el.getAttribute('data-target');
        if (!target) return;

        const targetTime = new Date(target).getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            let diff = targetTime - now;

            if (diff <= 0) {
                el.textContent = "Time reached";
                return;
            }

            let seconds = Math.floor(diff / 1000);
            let minutes = Math.floor(seconds / 60);
            let hours   = Math.floor(minutes / 60);
            let days    = Math.floor(hours / 24);

            hours   = hours % 24;
            minutes = minutes % 60;
            seconds = seconds % 60;

            let text = "";
            if (days > 0) text += days + "d ";
            text += hours + "h " + minutes + "m " + seconds + "s";

            el.textContent = text;

            // প্রতি ১ সেকেন্ড পর আপডেট
            setTimeout(updateCountdown, 1000);
        }

        updateCountdown();
    });
}

document.addEventListener('DOMContentLoaded', startCountdown);
</script>

<?php
if (file_exists('../layout/layout_footer.php')) {
    include '../layout/layout_footer.php';
}
?>

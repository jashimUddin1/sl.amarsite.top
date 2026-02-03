<?php
require_once '../auth/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT note_text, note_date, next_meet, created_at
    FROM school_notes
    WHERE school_id = :id
    ORDER BY created_at DESC
");
$stmt->execute([':id' => $id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$notes) {
    echo "<p class='text-muted small mb-0'>কোনো নোট পাওয়া যায়নি।</p>";
    exit;
}

foreach ($notes as $n):
    // note_date থাকলে সেটা, না থাকলে created_at
    $dateSource = !empty($n['note_date']) ? $n['note_date'] : ($n['created_at'] ?? null);

    $dateStr = '';
    $timeStr = '';

    if ($dateSource) {
        $ts = strtotime($dateSource);
        if ($ts !== false && $ts > 0) {
            $dateStr = date("Y-m-d", $ts);
            $timeStr = date("h:i A", $ts);
        }
    }

    // next_meet format
    $nextMeetStr = '';
    if (!empty($n['next_meet'])) {
        $tsMeet = strtotime($n['next_meet']);
        if ($tsMeet !== false && $tsMeet > 0) {
            $nextMeetStr = date("Y-m-d h:i A", $tsMeet);
        }
    }

    $noteTextSafe = nl2br(htmlspecialchars($n['note_text'] ?? '', ENT_QUOTES, 'UTF-8'));
    $nextMeetTitle = htmlspecialchars($nextMeetStr, ENT_QUOTES, 'UTF-8');
    $dateTitle = htmlspecialchars(trim($dateStr . ' ' . $timeStr), ENT_QUOTES, 'UTF-8');
?>
    <div class="border rounded-3 p-2 mb-2 bg-white shadow-sm">
        <div class="small">
            <?= $noteTextSafe; ?>
        </div>

        <?php if ($nextMeetStr || $dateStr): ?>
            <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                
                <!-- Left: Next Meeting (truncate) -->
                <small class="text-primary text-truncate"
                       style="max-width: 65%;"
                       title="<?= $nextMeetTitle ?>">
                    <?php if ($nextMeetStr): ?>
                        📅 Next Meeting: <?= htmlspecialchars($nextMeetStr, ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                        <!-- empty placeholder to keep right aligned -->
                        &nbsp;
                    <?php endif; ?>
                </small>

                <!-- Right: Note/Created time (truncate) -->
                <small class="text-secondary text-truncate text-end"
                       style="max-width: 35%;"
                       title="<?= $dateTitle ?>">
                    <?php if ($dateStr): ?>
                        <?= htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'); ?>
                        <?= htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </small>

            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

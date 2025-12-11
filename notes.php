<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT note_text, note_date, created_at
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
?>
    <div class="border rounded-3 p-2 mb-2 bg-white shadow-sm">
        <div class="small">
            <?= nl2br(htmlspecialchars($n['note_text'])); ?>
        </div>

        <?php if ($dateStr): ?>
            <div class="text-end mt-1">
                <small class="text-secondary">
                     <?= $dateStr ?>  <?= $timeStr ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

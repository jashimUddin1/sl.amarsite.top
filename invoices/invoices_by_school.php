<?php
// invoices/invoices_by_school.php
require_once '../auth/config.php';
require_login();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$school_id = (int)($_GET['school_id'] ?? 0);
if ($school_id <= 0) {
    http_response_code(400);
    exit('Invalid school_id');
}

// স্কুল ইনফো
$sStmt = $pdo->prepare("SELECT id, school_name, district, upazila, mobile FROM schools WHERE id = :id LIMIT 1");
$sStmt->execute([':id' => $school_id]);
$school = $sStmt->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    http_response_code(404);
    exit('School not found');
}

$pageTitle = 'Invoices - ' . ($school['school_name'] ?? 'School');
$pageHeading = 'Invoices';
$activeMenu = 'schools';

function safe_json($s){
    $d = json_decode($s ?? '', true);
    return is_array($d) ? $d : [];
}

/**
 * "Softw.." এর মতো বানায়:
 * - description এর প্রথম word নেয়
 * - 5 অক্ষর পর্যন্ত কাটে
 * - শেষে ".."
 */
function chip_from_desc(string $desc, int $maxChars = 5): string {
    $desc = trim(preg_replace('/\s+/u', ' ', $desc));
    if ($desc === '') return '';
    $parts = preg_split('/\s+/u', $desc);
    $w = trim((string)($parts[0] ?? ''));
    if ($w === '') return '';

    if (function_exists('mb_substr')) {
        $w = mb_substr($w, 0, $maxChars, 'UTF-8');
    } else {
        $w = substr($w, 0, $maxChars);
    }
    return $w . '..';
}

/**
 * 2টা item পর্যন্ত chip বানায়।
 * item > 2 হলে শেষে " .." যোগ করে।
 */
function items_preview($items): string {
    if (!is_array($items) || !$items) return '';

    $chips = [];
    $added = 0;

    foreach ($items as $it) {
        $desc = trim((string)($it['description'] ?? ''));
        if ($desc === '') continue;

        $chips[] = chip_from_desc($desc, 5); // Softw..
        $added++;

        if ($added >= 2) break;
    }

    if (!$chips) return '';

    $out = implode(' ', $chips);

    if (count($items) > 2) {
        $out .= ' ..';
    }

    return $out;
}

// ঐ স্কুলের সব ইনভয়েস
$iStmt = $pdo->prepare("
    SELECT id, in_no, data, created_at, updated_at
    FROM invoices
    WHERE school_id = :sid
    ORDER BY in_no DESC, id DESC
");
$iStmt->execute([':sid' => $school_id]);
$rows = $iStmt->fetchAll(PDO::FETCH_ASSOC);

require '../layout/layout_header.php';
?>

<div class="container-fluid ">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-semibold fs-5"><?php echo h($school['school_name']); ?></div>
            <div class="text-secondary small">
                <?php echo h(trim(($school['district'] ?? '').(($school['district'] && $school['upazila']) ? ', ' : '').($school['upazila'] ?? ''))); ?>
                <?php if (!empty($school['mobile'])): ?>
                    <span class="mx-2">|</span> <?php echo h($school['mobile']); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="../schools/schools.php" class="btn btn-light border">← Back</a>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="alert alert-info">এই স্কুলের কোনো invoice পাওয়া যায়নি।</div>
    <?php else: ?>
        <div class="card shadow-sm border-0 rounded-2">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">

                    <?php foreach ($rows as $r): ?>
                        <?php
                        $data   = safe_json($r['data'] ?? '');
                        $invNo  = $r['in_no'] ?? $r['id'];

                        $items  = $data['items'] ?? [];
                        $total  = $data['totals']['total'] ?? 0;
                        $status = strtoupper($data['totals']['status'] ?? 'UNPAID');

                        $invoiceDate = $data['invoiceDate'] ?? '';
                        $dateRaw = $invoiceDate ?: ($r['created_at'] ?? '');
                        $ts = strtotime((string)$dateRaw);

                        $dateShow   = $ts ? date('j/n/Y', $ts) : '—';
                        $month_name = $ts ? date('F Y', $ts) : '';
                        $items_count = is_array($items) ? count($items) : 0;

                        $itemsText = items_preview($items);

                        $badgeClass = ($status === 'PAID') ? 'success' : 'danger';
                        ?>

                        <div class="list-group-item py-3">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">

                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark fs-6">
                                        #<?php echo h($invNo); ?> — <?php echo h($dateShow); ?>
                                        <?php if ($month_name): ?>
                                            <span class="text-secondary fw-normal">, <?php echo h($month_name); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-secondary small mt-1">
                                        <?php if ($itemsText): ?>
                                            <span><?php echo h($itemsText); ?></span>
                                            <span class="mx-2">|</span>
                                        <?php endif; ?>

                                        <span class="me-2">Items: <?php echo (int)$items_count; ?></span>
                                        <span class="mx-2">|</span>
                                        <span>Total: ৳<?php echo h(number_format((float)$total, 2)); ?></span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <span class="badge rounded-pill bg-<?php echo h($badgeClass); ?> bg-opacity-10 text-<?php echo h($badgeClass); ?> px-3 py-2">
                                        <?php echo h($status); ?>
                                    </span>

                                    <a class="btn btn-outline-primary btn-sm fw-semibold"
                                       href="invoice_edit.php?invoice_id=<?php echo (int)$r['id']; ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="controllers/invoice_delete.php" class="d-inline"
                                          onsubmit="return confirm('Delete this invoice?');">
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$r['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require '../layout/layout_footer.php'; ?>

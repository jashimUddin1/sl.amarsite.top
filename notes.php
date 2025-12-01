<?php
require_once "config.php";
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<p class='text-red-600'>Invalid School ID</p>";
    exit;
}

// School name ber korbo
$stmtS = $pdo->prepare("SELECT school_name FROM schools WHERE id = :id");
$stmtS->execute([':id' => $id]);
$school = $stmtS->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    echo "<p class='text-red-600'>School not found.</p>";
    exit;
}
$schoolName = $school['school_name'];

// Notes
$stmt = $pdo->prepare("SELECT * FROM school_notes WHERE school_id = :id ORDER BY note_date DESC");
$stmt->execute([':id' => $id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// echo "<h3 class='text-lg font-semibold mb-3'>School: " . htmlspecialchars($schoolName) . "</h3>";

if (!$notes) {
    echo "<p class='text-gray-500'>No notes available.</p>";
    exit;
}

// Protiti note er jonno edit + delete form
foreach ($notes as $n){
    ?>
    <form method="POST" action="note_action.php" class="bg-gray-100 border p-3 rounded space-y-2">
        <textarea
            name="note_text"
            class="w-full p-2 border rounded"
            rows="3"
        ><?php echo htmlspecialchars($n['note_text']); ?></textarea>

        <div class="flex items-center justify-between text-xs text-gray-500">
            <span><?php echo htmlspecialchars($n['note_date']); ?></span>
            <div class="flex gap-2">
                <input type="hidden" name="note_id" value="<?php echo (int)$n['id']; ?>">
                <input type="hidden" name="school_id" value="<?php echo (int)$id; ?>">

                <button
                    type="submit"
                    name="action"
                    value="update"
                    class="px-3 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                >
                    Save
                </button>

                <button
                    type="submit"
                    name="action"
                    value="delete"
                    class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                    onclick="return confirm('Are you sure you want to delete this note?');"
                >
                    Delete
                </button>
            </div>
        </div>
    </form>
    <?php
}

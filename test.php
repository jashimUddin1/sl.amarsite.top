d-none d-md-inline ==> desktop/tab mobile = none
d-inline d-md-none ==> only mobile

akta table create korte chai accounts name

id user_id description,	method,	amount, Category, created_at, updated_at






table invoices => 
id
in_no
school_id
data => {"invoiceDate":"2025-12-25","billTo":{"school":"F.K. Technical","client_name":"","mobile":"01718731850"},"items":[{"description":"Monthly Fee","qty":1,"rate":460,"amount":460}],"totals":{"total":460,"pay":0,"due":460,"status":"UNPAID"},"note":""}
created_at
updated_at



akhane ami chassi status = paid hole sei paid invoice gular data dekhbo?

date = last updated_at ,
description = items.description
Method = '', default cash
amount = paid invoice total taka




CheckLIst
|
home ==> 
    notes view all btn => modal ok -> data fetch error -> style problem -> fixed all -> ok
    manage note => note_view.php -> back button -> ok
                => note_view.php -> ad note btn -> ok
                                 -> add note save -> error -> meeting column missing -> fixed => ok 
 
    add note => btn ok , submit ok , insert sucessful
dashboard ==> Latest Schools view all btn -> fixed -> ok
school ==> ok 
all page => simple check ok 


<!-- accounts/index.php add entry marged  -->
<div class="">
    <form action="core/add_core.php" method="post" class="row g-3 align-items-center top-row">
        <input type="hidden" name="action" value="insert_add">

        <!-- Date -->
        <div class="col-6 col-md-2">
            <input type="date" class="form-control" id="date" name="date" required>
        </div>

        <!-- Type -->
        <div class="col-6 col-md-1">
            <select class="form-select" name="type" required>
                <option value="expense">Expense</option>
                <option value="income">Income</option>
            </select>
        </div>

        <!-- Description -->
        <div class="col-12 col-md-4">
            <input type="text" class="form-control" id="desc" name="description" placeholder="Description"
                maxlength="255" required>
        </div>

        <!-- Amount -->
        <div class="col-4 col-md-2">
            <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount" min="0"
                step="0.01" required>
        </div>

        <!-- Payment Method -->
        <div class="col-4 col-md-1">
            <select class="form-select" name="payment_method" required>
                <option value="Cash">Cash</option>
                <option value="bKash">bKash</option>
                <option value="Nagad">Nagad</option>
                <option value="Bank">Bank</option>
                <option value="Card">Card</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Category -->
        <div class="col-4 col-md-1">
            <select class="form-select" name="category" required>
                <option value="" selected disabled>Category</option>
                <option value="buy">buy</option>
                <option value="marketing_cost">Marketing Cost</option>
                <option value="office_supply">Office Supply</option>
                <option value="cost2">cost2</option>
                <option value="Transport">Transport</option>
                <option value="Rent">Rent</option>
                <option value="Utilities">Utilities</option>
                <option value="revenue">Revenue</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Add Button -->
        <div class="col-12 col-md-1 d-grid">
            <button type="submit" class="btn btn-success btn-add">Add</button>
        </div>

    </form>
</div>



ata amar final file.  ami akhane aro kichu add korte chai.

A) app-wrap ar right cornar a akta 3dot button thakbe 
3dot button => desktop/tab = px type and mobile py type.
3dot button a click korle akta setting modal open hobe

modal => 1. insert add toggle (on/off) = ata dara insert ar hidde show hobe
         2. view button toggle hobe mani show/hide
         3. sheet system toggle 

aigula korte chai peramiter chara and session chara . amar users table diye handling korte chai jate pore login korle o oi user ar same obosthay thake.

B) view te click korle oi entry full details ta modal a dekhabe.


ami direct file chai na . amake kothay ki kon line change , add korso bolo ami step by step change korbo


note_logs table => id note_id school_id user_id action old_text new_text action_at,

note_id = null
school_id = null
user_id 
action = 'account entry'
old_text = ata akhon proyojon nai just update a lagbe
new_text = akhane json thakbe ja ja add kora hoice tar
action_at 


<?php
// accounts/core/add_core.php
require_once "../../auth/config.php";
require_login();

/* ---------- Basic request check ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: ../index.php");
    exit;
}

if (($_POST['action'] ?? '') !== 'insert_add') {
    $_SESSION['flash_error'] = 'Invalid action';
    header("Location: ../index.php");
    exit;
}

/* ---------- Collect raw inputs ---------- */
$date_raw   = $_POST['date'] ?? '';
$desc_raw   = $_POST['description'] ?? '';
$amount_raw = $_POST['amount'] ?? '';
$type_raw   = $_POST['type'] ?? '';
$method_raw = $_POST['payment_method'] ?? '';
$cat_raw    = $_POST['category'] ?? '';

/* ---------- Validate date (YYYY-MM-DD strict) ---------- */
$dt = DateTime::createFromFormat('Y-m-d', $date_raw);
if (!$dt || $dt->format('Y-m-d') !== $date_raw) {
    $_SESSION['flash_error'] = 'Invalid date format';
    header("Location: ../index.php");
    exit;
}
$date = $dt->format('Y-m-d');

/* ---------- Validate description ---------- */
$description = trim($desc_raw);
if ($description === '' || mb_strlen($description) > 255) {
    $_SESSION['flash_error'] = 'Invalid description';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate amount ---------- */
if (!is_numeric($amount_raw) || (float)$amount_raw < 0) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header("Location: ../index.php");
    exit;
}
$amount = (float)$amount_raw;

/* ---------- Validate type ---------- */
$type = strtolower(trim($type_raw));
if (!in_array($type, ['income', 'expense'], true)) {
    $_SESSION['flash_error'] = 'Invalid type';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate method ---------- */
$allowedMethods = ['Cash','bKash','Nagad','Bank','Card','Other'];
$method = trim($method_raw);
if (!in_array($method, $allowedMethods, true)) {
    $_SESSION['flash_error'] = 'Invalid payment method';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate category ---------- */
$allowedCats = [
    'buy','marketing_cost','office_supply',
    'Transport','Rent','Utilities','revenue','Other'
];
$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: ../index.php");
    exit;
}

/* ---------- Get user id ---------- */
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    $_SESSION['flash_error'] = 'Unauthorized user';
    header("Location: ../index.php");
    exit;
}
$user_id = (int)$user_id;

/* ---------- Insert using PDO ---------- */
try {
    $sql = "INSERT INTO accounts
            (user_id, date, description, method, amount, category, type, created_at, updated_at)
            VALUES
            (:user_id, :date, :description, :method, :amount, :category, :type, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'     => $user_id,
        ':date'        => $date,
        ':description' => $description,
        ':method'      => $method,
        ':amount'      => $amount,
        ':category'    => $category,
        ':type'        => $type
    ]);

    akhane chassi insert success hole note_logs o insert hobe uporer jemne bolsi seirokom vabe
    $_SESSION['flash_success'] = 'Record added successfully';

} catch (Throwable $e) {
    // error_log($e->getMessage()); // production এ রাখলে ভালো
    $_SESSION['flash_error'] = 'Failed to add record';
}

header("Location: ../index.php");
exit;









note_logs table => id note_id school_id user_id action old_text new_text action_at,

note_id = null
school_id = null
user_id 
action = 'Entry Updated'
old_text = ager data 
new_text = new data
action_at 


<?php
require_once "../../auth/config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: ../index.php");
    exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    $_SESSION['flash_error'] = 'Invalid CSRF token';
    header("Location: ../index.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['flash_error'] = 'Unauthorized';
    header("Location: ../index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid id';
    header("Location: ../index.php");
    exit;
}

$date_raw   = $_POST['date'] ?? '';
$desc_raw   = $_POST['description'] ?? '';
$amount_raw = $_POST['amount'] ?? '';
$type_raw   = $_POST['type'] ?? '';
$method_raw = $_POST['payment_method'] ?? '';
$cat_raw    = $_POST['category'] ?? '';

// date validate
$dt = DateTime::createFromFormat('Y-m-d', $date_raw);
if (!$dt || $dt->format('Y-m-d') !== $date_raw) {
    $_SESSION['flash_error'] = 'Invalid date';
    header("Location: ../index.php");
    exit;
}
$date = $dt->format('Y-m-d');

// description
$description = trim($desc_raw);
if ($description === '' || mb_strlen($description) > 255) {
    $_SESSION['flash_error'] = 'Invalid description';
    header("Location: ../index.php");
    exit;
}

// amount
if (!is_numeric($amount_raw)) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header("Location: ../index.php");
    exit;
}
$amount = (float)$amount_raw;
if ($amount < 0) {
    $_SESSION['flash_error'] = 'Amount must be >= 0';
    header("Location: ../index.php");
    exit;
}

// type
$type = strtolower(trim($type_raw));
if (!in_array($type, ['income', 'expense'], true)) {
    $_SESSION['flash_error'] = 'Invalid type';
    header("Location: ../index.php");
    exit;
}

// method
$allowedMethods = ['Cash','bKash','Nagad','Bank','Card','Other'];
$method = trim($method_raw);
if (!in_array($method, $allowedMethods, true)) {
    $_SESSION['flash_error'] = 'Invalid method';
    header("Location: ../index.php");
    exit;
}

// category
$allowedCats = ['buy','marketing_cost','office_supply','cost2','Transport','Rent','Utilities','revenue','Other'];
$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: ../index.php");
    exit;
}

try {
    $sql = "UPDATE accounts
            SET date = :date,
                description = :description,
                method = :method,
                amount = :amount,
                category = :category,
                type = :type,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':description' => $description,
        ':method' => $method,
        ':amount' => $amount,
        ':category' => $category,
        ':type' => $type,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['flash_error'] = 'Nothing updated (not found or not yours)';
    } else {
        $_SESSION['flash_success'] = 'Record updated successfully';
    }

} catch (Throwable $e) {
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Update failed';
}

header("Location: ../index.php");
exit;

amar ai file a o kore daw



akhon delete ar kaj korbo akhane aktu change hobe . 
note_logs table => id note_id school_id user_id action old_text new_text action_at,

note_id = null
school_id = null
user_id 
action = 'Entry delete'
old_text = accounts id
new_text = data
action_at 

akhane kaj hobe age accounts_trash table insert hobe 
accounts_trash =>
id
del_acc_id
user_id
date
description
method
amount
category
type
deleted_at


CREATE TABLE accounts_trash (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    del_acc_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    method VARCHAR(20) NOT NULL,
    amount DECIMAL(12,0) NOT NULL,
    category VARCHAR(91) NOT NULL,
    type ENUM('income','expense') NOT NULL,

    deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


then data delete hobe 

then note_logs a insert hobe 

amar file ==> 
<?php //accounts/core/delete_core.php
require_once "../../auth/config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: ../index.php");
    exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    $_SESSION['flash_error'] = 'Invalid CSRF token';
    header("Location: ../index.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['flash_error'] = 'Unauthorized';
    header("Location: ../index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid id';
    header("Location: ../index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['flash_error'] = 'Delete failed (not found or not yours)';
    } else {
        $_SESSION['flash_success'] = 'Record deleted successfully';
    }

} catch (Throwable $e) {
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Delete failed';
}

header("Location: ../index.php");
exit;








akhon ami chassi amar ai file a 3dot a click korle setting modal open hoy sei modal ar niche close button ase sei close button left a (mani duita between hobe) started cash primary button thakbe . database a  notun table create koro proyojone . akber balance set hole started cash button ta secondary hoye jabe pashe amount o show korbe => started 2000 . and ata clickable na , edit o delete o kora jabe na . tai first add ar somoy return confirmed dibe "tumi sotti sure ? ata kintu ar edit kora jabe na". 

set kora sesh hole oi amount ta dhore balance show korbe 

accha ar aktu kaj korte parba seta holo ami jehetu started balance akbar e add korbo then poroborti month started ta amar ai month ar last day ar 11:59 ar balance ta varible a rekhe then seta porer month ar shurute use korte chassi jate full dynamic hoy . airokom kora jabe ki? 

ami ja ja korte bolsi ta korar jonno kothay kon line a ki ki korte hobe bolo
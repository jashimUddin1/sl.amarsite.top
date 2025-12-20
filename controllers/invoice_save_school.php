<?php 
require_once '../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0);
if(!$user_id){
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if(!is_array($payload)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid JSON payload']);
    exit;
}

$school_id = (int)($payload['school_id'] ?? 0);
$data = $payload['data'] ?? null;

if($school_id <= 0 || !is_array($data)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Missing School Id or data']);
    exit;
}

$invoiceNumber = (int)($data['invoiceNumber'] ?? 0);
if($invoiceNumber <= 0){
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid Invoice Number']);
    exit;
}

// akhane aro validation or logic lekha jabe 


try{
    $pdo->beginTransaction();

    //duplicate check
    $chk = $pdo->prepare("
        SELECT id FROM invoices WHERE school_id = :school_id
        AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceNumber')) AS UNSIGNED) = :inv LIMIT 1
    ");
    $chk->execute(['school_id' => $school_id, 'inv' => $invoiceNumber]);
    $exists = $chk->fetchColumn();

    if($exists){
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number ইতিমধ্যে আছে। নতুন নম্বর দিন।']);
        exit;
    }

    // all ok now start insert
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    $ins = $pdo->prepare("INSERT INTO invoices(school_id, data,  created_at) VALUES (:school_id, :data, NOW() )");
    $ins->execute([
        'school_id' => $school_id,
        'data' => $json
    ]);

    $invoice_id = (int)$pdo->lastInsertId();

    //note_logs insert this information
    $action = 'INVOICE CREATED';
    $log = $pdo->prepare("INSERT INTO note_logs (user_id, school_id, action, new_text, action_at) VALUES (:user_id, :school_id, :action, :new_text, NOW()) ");
    $log->execute([
        'user_id' => $user_id,
        'school_id' =>  $school_id,
        'action' => $action,
        'new_text' => $json
    ]);

    $pdo->commit();

    echo json_encode([
        'ok' => true, 
        'msg' => 'Invoice save Successfully!',
        'invoice_id' => $invoice_id,
        'invoice_number' => $invoiceNumber
    ]);
}catch(PDOException $e){
    if($pdo->inTransaction()) $pdo->rollBack();

    //database and mysql error hangle
    if((int)($e->errorInfo[1] ?? 0) === 1062){
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number ইতিমধ্যে আছে। নতুন নম্বর দিন। @2']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error', 'err' => $e->getMessage()]);
    
}
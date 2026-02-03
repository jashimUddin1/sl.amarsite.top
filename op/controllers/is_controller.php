<?php
// controllers/is_controller.php

require_once __DIR__ . '/../auth/config.php';

function getSchoolById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            id,
            school_name,
            client_name,
            mobile
        FROM schools
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute(['id' => $id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    return $school ?: null;
}

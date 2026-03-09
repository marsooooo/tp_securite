<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db     = db();

$current = current_user();

if ($action === 'search') {
    $q = $_GET['q'] ?? '';
    // $rows = $db->query("SELECT * FROM products WHERE name LIKE '%$q%'")->fetchAll(PDO::FETCH_ASSOC);
    $s = $db->prepare("SELECT * FROM products WHERE name LIKE ?");
    $s->execute(['%' . $q . '%']);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($action === 'user') {
    if (!$current) { echo json_encode(['error' => 'Non authentifie']); exit; }
    $id = intval($_GET['id'] ?? 0);
    // $user = $db->query("SELECT * FROM users WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
    if ($current['role'] !== 'admin' && $current['id'] !== $id) {
        echo json_encode(['error' => 'Acces non autorise']); exit;
    }
    $s = $db->prepare("SELECT id,username,email,role,bio,balance FROM users WHERE id=?");
    $s->execute([$id]);
    echo json_encode($s->fetch(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'users') {
    // $rows = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    // echo json_encode($rows);
    //
    if (!$current || $current['role'] !== 'admin') {
        echo json_encode(['error' => 'Acces non autorise']); exit;
    }
    $rows = $db->query("SELECT id,username,email,role,bio,balance FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

if ($action === 'orders') {
    if (!$current) { echo json_encode(['error' => 'Non authentifie']); exit; }
    $uid = intval($_GET['uid'] ?? 0);
    // $rows = $db->query("SELECT * FROM orders WHERE user_id=$uid")->fetchAll(PDO::FETCH_ASSOC);
    if ($current['role'] !== 'admin' && $current['id'] !== $uid) {
        echo json_encode(['error' => 'Acces non autorise']); exit;
    }
    $s = $db->prepare("SELECT * FROM orders WHERE user_id=?");
    $s->execute([$uid]);
    echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'transfer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$current) { echo json_encode(['error' => 'Non authentifie']); exit; }
    $from   = intval($_POST['from_id'] ?? 0);
    $to     = intval($_POST['to_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    // $db->query("UPDATE users SET balance=balance-$amount WHERE id=$from");
    // $db->query("UPDATE users SET balance=balance+$amount WHERE id=$to");
    //
    if ($current['id'] !== $from) {
        echo json_encode(['error' => 'Acces non autorise']); exit;
    }
    if ($amount <= 0) { echo json_encode(['error' => 'Montant invalide']); exit; }
    $db->prepare("UPDATE users SET balance=balance-? WHERE id=?")->execute([$amount, $from]);
    $db->prepare("UPDATE users SET balance=balance+? WHERE id=?")->execute([$amount, $to]);
    echo json_encode(['status' => 'ok', 'transferred' => $amount]);
    exit;
}

if ($action === 'delete_all_reviews') {
    if (!$current || $current['role'] !== 'admin') {
        echo json_encode(['error' => 'Acces non autorise']); exit;
    }
    $pid = intval($_GET['pid'] ?? 0);
    // $db->query("DELETE FROM reviews WHERE product_id=$pid");
    $db->prepare("DELETE FROM reviews WHERE product_id=?")->execute([$pid]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// if ($action === 'raw_query') {
//     $sql  = $_GET['sql'] ?? '';
//     $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
//     echo json_encode($rows);
//     exit;
// }

echo json_encode(['error' => 'Action inconnue']);

<?php
session_start();
require 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$reference_id = (int)$_GET['id'];
$type = $_GET['type'];

// واش favorite موجودة
$check = $pdo->prepare("
SELECT id
FROM favorites
WHERE user_id=?
AND reference_id=?
AND type=?
");

$check->execute([
    $user_id,
    $reference_id,
    $type
]);

$favorite = $check->fetch();

if($favorite){

    $delete = $pdo->prepare("
    DELETE FROM favorites
    WHERE id=?
    ");

    $delete->execute([
        $favorite['id']
    ]);

}else{

    $insert = $pdo->prepare("
    INSERT INTO favorites(user_id,type,reference_id)
    VALUES(?,?,?)
    ");

    $insert->execute([
        $user_id,
        $type,
        $reference_id
    ]);
}

header("Location: ".$_SERVER['HTTP_REFERER']);
exit();
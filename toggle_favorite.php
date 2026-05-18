<?php
session_start();

$id = (int)$_GET['id'];

if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

if (in_array($id, $_SESSION['favorites'])) {

    // نحيد من favorites
    $_SESSION['favorites'] = array_diff(
        $_SESSION['favorites'],
        [$id]
    );

} else {

    // نزيد
    $_SESSION['favorites'][] = $id;
}

header("Location: ".$_SERVER['HTTP_REFERER']);
exit();
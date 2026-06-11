<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Langues supportées
$supported_langs = ['fr', 'en', 'ar'];
$default_lang    = 'fr';

// Si changement de langue via URL (?lang=ar)
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];

    // Redirect sans le param lang dans l'URL
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $url");
    exit;
}

// Langue active
$lang_code = $_SESSION['lang'] ?? $default_lang;

// Charger le fichier de traduction
$lang_file = __DIR__ . "/{$lang_code}.php";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/{$default_lang}.php";
}
$lang = require $lang_file;

// Direction (RTL pour l'arabe)
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

// Helper : récupérer une clé
function t($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
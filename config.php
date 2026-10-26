<?php
declare(strict_types=1);

const APP_NAME = 'Titik Temu Kopi';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'ruang_seduh';
const DB_USER = 'root';
const DB_PASS = '';

date_default_timezone_set('Asia/Jakarta');
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secureCookie,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
function db(): PDO {
    static $pdo;
    if (!$pdo) $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function rupiah(int|float|string $n): string { return 'Rp '.number_format((float)$n,0,',','.'); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void {
    if (!current_user()) { header('Location: index.php?page=login'); exit; }
    $check=db()->prepare('SELECT status FROM users WHERE id=?');$check->execute([current_user()['id']]);
    if (!(int)$check->fetchColumn()) { $_SESSION=[];session_regenerate_id(true);header('Location: index.php?page=login');exit; }
}
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('Akses khusus administrator.'); } }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir kedaluwarsa. Muat ulang halaman.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type','message'); }
function redirect(string $page): never { header('Location: index.php?page='.rawurlencode($page)); exit; }
function post_string(string $key, int $max=180): string { return mb_substr(trim((string)($_POST[$key] ?? '')),0,$max); }

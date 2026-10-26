<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/app/Support/helpers.php';
require_once APP_ROOT . '/app/Core/Database.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Repositories/UserRepository.php';
require_once APP_ROOT . '/app/Repositories/ComplaintRepository.php';
require_once APP_ROOT . '/app/Services/AuthService.php';
require_once APP_ROOT . '/app/Services/ComplaintService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function database(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $connection = Database::connect();
    }
    return $connection;
}

function user_repository(): UserRepository
{
    return new UserRepository(database());
}

function complaint_repository(): ComplaintRepository
{
    return new ComplaintRepository(database());
}

function auth_service(): AuthService
{
    return new AuthService(user_repository());
}

function complaint_service(): ComplaintService
{
    return new ComplaintService(complaint_repository(), user_repository());
}

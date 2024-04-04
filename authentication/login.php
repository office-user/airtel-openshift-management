<?php

header('Content-Type: application/json; charset=utf-8');
$referer = $_SERVER['HTTP_REFERER'];
error_log("Referer: $referer");
require_once '../dbconfig.php';
session_start();

function escape($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function login($username, $password) {
    if (empty($username) || empty($password)) {
        error_log("Invalid Login Request. Username & Password should not be empty.");
        return ['status' => 'error', 'message' => 'Username and password are required', 'url'=> 'login.html'];
    }
    $_SESSION['username'] = $username;
    if ($username == 'guest' && $password == 'guest') {
        $_SESSION['role'] = 'view';
        error_log("Logged in as " . $_SESSION['username']);
        return ['status' => 'success', 'message' => 'Login successful', 'url' => 'index.html', 'data' => ['username' => $_SESSION['username'], 'role' => $_SESSION['role']]];
    } elseif ($username == 'admin' && $password == 'admin') {
        $_SESSION['role'] = 'admin';
        error_log("Logged in as " . $_SESSION['username']);
        return ['status' => 'success', 'message' => 'Login successful', 'url' => 'index.html', 'data' => ['username' => $_SESSION['username'], 'role' => $_SESSION['role']]];
    } else {
        session_destroy();
        error_log("Loggin failed. Wrong Username & Password.");
        return ['status' => 'error', 'message' => 'Wrong Username & Password', 'url' => 'login.html'];
    }
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
error_log("Action: $action");

switch ($action) {
    case 'logout':
        error_log("Logged out by " . $_SESSION['username']);
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Logout successful', 'url' => 'login.html']);
        break;
    case 'login':
        $username = escape($_POST['username']);
        $password = escape($_POST['password']);
        error_log("Trying to login as $username");
        $loginResult = login($username, $password);
        echo json_encode($loginResult);
        break;
    case 'verifySession':
        if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
            echo json_encode(['status' => 'success', 'message' => 'Session is active on backend', 'url' => 'index.html', 'data' => ['username' => $_SESSION['username'], 'role' => $_SESSION['role']]]);
        } else {
            error_log("Session for " . $_SESSION['username'] . "expired");
            session_destroy();
            echo json_encode(['status' => 'error', 'message' => 'Session does not exist on backend', 'url' => 'login.html']);
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        break;
}

$cnx->close();

?>
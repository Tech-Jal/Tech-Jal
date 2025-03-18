<?php
require_once '../includes/middleware.php';

$middleware = new Middleware();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'accept':
            $middleware->acceptCookieConsent();
            echo json_encode(['success' => true, 'message' => 'Cookies accepted']);
            break;
            
        case 'reject':
            // Delete all cookies except the one that remembers the user's choice
            if (isset($_SERVER['HTTP_COOKIE'])) {
                $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    if ($name !== 'cookie_consent' && $name !== 'cookie_consent_pending') {
                        setcookie($name, '', time() - 3600, '/');
                    }
                }
            }
            
            // Set cookie to remember the user's choice
            setcookie('cookie_consent', '0', time() + (86400 * 365), "/"); // 1 year
            setcookie('cookie_consent_pending', '', time() - 3600, "/"); // Delete pending cookie
            
            echo json_encode(['success' => true, 'message' => 'Cookies rejected']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 
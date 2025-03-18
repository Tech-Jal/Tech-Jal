<?php
require_once __DIR__ . '/auth.php';

class Middleware {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function requireAuth() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }
    }

    public function requireGuest() {
        if ($this->auth->isLoggedIn()) {
            header('Location: /index.php');
            exit();
        }
    }

    public function requireAdmin() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }

        $user = $this->auth->getCurrentUser();
        if ($user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            exit('Access Denied');
        }
    }

    public function setCookieConsent() {
        if (!isset($_COOKIE['cookie_consent'])) {
            setcookie('cookie_consent_pending', '1', time() + (86400 * 30), "/"); // 30 days
        }
    }

    public function acceptCookieConsent() {
        setcookie('cookie_consent', '1', time() + (86400 * 365), "/"); // 1 year
        setcookie('cookie_consent_pending', '', time() - 3600, "/"); // Delete pending cookie
    }

    public function hasCookieConsent() {
        return isset($_COOKIE['cookie_consent']);
    }

    public function isPendingCookieConsent() {
        return isset($_COOKIE['cookie_consent_pending']);
    }

    public function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeInput($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    public function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('HTTP/1.1 403 Forbidden');
                exit('Invalid CSRF token');
            }
        }
    }

    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        // Enforce HTTPS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        // Restrict referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net");
    }

    public function rateLimit($key, $max_requests, $time_window) {
        $redis_key = "rate_limit:{$key}";
        
        // In a production environment, you would use Redis or a similar caching system
        // For this example, we'll use the session
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }

        $current_time = time();
        $window_start = $current_time - $time_window;

        // Clean up old entries
        if (isset($_SESSION['rate_limits'][$redis_key])) {
            $_SESSION['rate_limits'][$redis_key] = array_filter(
                $_SESSION['rate_limits'][$redis_key],
                function($timestamp) use ($window_start) {
                    return $timestamp >= $window_start;
                }
            );
        } else {
            $_SESSION['rate_limits'][$redis_key] = [];
        }

        // Check if limit is exceeded
        if (count($_SESSION['rate_limits'][$redis_key]) >= $max_requests) {
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . ($time_window - ($current_time - min($_SESSION['rate_limits'][$redis_key]))));
            exit('Rate limit exceeded. Please try again later.');
        }

        // Add current request
        $_SESSION['rate_limits'][$redis_key][] = $current_time;
    }
} 
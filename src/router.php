<?php

require_once __DIR__ . '/Backend/controller/FileController.php';
require_once __DIR__ . '/Backend/controller/UserController.php';
require_once __DIR__ . '/Backend/utils/Logging.php';

class Router
{
    private static $instance = null;
    private $request;
    private $base_path;
    private $api_path;
    private $pages_path;

    private $current_page;

    private $fc; // FileController
    private $uc; // UserController

    private $logger;

    private function __construct()
    {
        $this->initSecureSession();

        $this->request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Define the paths
        $this->base_path = __DIR__;
        $this->api_path = "{$this->base_path}/Backend/api";
        $this->pages_path = "{$this->base_path}/frontend/pages";

        $this->fc = new FileController();
        $this->uc = new UserController();

        $this->logger = applicationLogger();

        $this->current_page = '';
    }

    public static function getInstance(){
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Main route handler
    public function handleRequest()
    {
        // Distinguish API requests from page requests
        if (strpos($this->request, '/api/') === 0) {
            $this->handleApiRequest();
        } else {
            $this->handlePageRequest();
        }
    }

    // Handle API requests
    private function handleApiRequest()
    {
        // Remove the /api/ prefix to get the endpoint
        $apiRequest = str_replace('/api/', '', $this->request);
        
        // Map API endpoints to handlers and required permissions
        $apiEndpoints = [
            'upload_file' => ['handler' => [$this->fc, 'upload'], 'auth' => 'authenticated', 'method' => "POST"],
            'download_file' => ['handler' => [$this->fc, 'downloadFile'], 'auth' => 'authenticated', 'method' => "POST"],
            'delete_file' => ['handler' => [$this->fc, 'deleteFile'], 'auth' => 'authenticated', 'method' => "POST"],
            'show_files' => ['handler' => [$this->fc, 'showFiles'], 'auth' => 'authenticated', 'method' => "GET"],
            'login' => ['handler' => [$this->uc, 'login'], 'auth' => 'unauthenticated', 'method' => "POST"],
            'register' => ['handler' => [$this->uc, 'register'], 'auth' => 'unauthenticated', 'method' => "POST"],
            'logout' => ['handler' => [$this->uc, 'logout'], 'auth' => 'authenticated', 'method' => "POST"],
            'show_users' => ['handler' => [$this->uc, 'showUsers'], 'auth' => 'admin', 'method' => "GET"],
            'change_role' => ['handler' => [$this->uc, 'changeUserRole'], 'auth' => 'admin', 'method' => "POST"],
            'verify_user' => ['handler' => [$this->uc, 'verifyUser'], 'auth' => 'unauthenticated', 'method' => "POST"],
            'forgot_pwd' => ['handler' => [$this->uc, 'forgotPassword'], 'auth' => 'unauthenticated', 'method' => "POST"],
            'reset_pwd' => ['handler' => [$this->uc, 'resetPassword'], 'auth' => 'unauthenticated', 'method' => "POST"],
        ];
    
        // Check whether the endpoint exists in the map
        if (!is_string($apiRequest) || !array_key_exists($apiRequest, $apiEndpoints)) {
            logEvent($this->logger, "error", 'handleRequest', 'API not found.', 404);
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'API not found']);
            exit();
        }
    
        // Get the endpoint configuration
        $endpoint = $apiEndpoints[$apiRequest];
        
        // Check the endpoint permissions
        if ($endpoint['auth'] === 'admin' && !$this->isAdmin()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }
        if ($endpoint['auth'] === 'authenticated' && !$this->isAuthenticated()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }
        if ($endpoint['auth'] === 'unauthenticated' && $this->isAuthenticated()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }

        // Check the request method
        if ($_SERVER['REQUEST_METHOD'] !== $endpoint['method']) {
            logEvent($this->logger, "error", 'handleRequest', 'Method not allowed.', 405);
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit();
        }
    
        // Call the endpoint handler
        call_user_func($endpoint['handler']);
    }

    // Handle page requests
    private function handlePageRequest()
    {   
        // Map paths to pages and authentication requirements
        $pages = [
            '' => ['page' => 'index', 'auth' => 'unauthenticated'],
            '/' => ['page' => 'index', 'auth' => 'unauthenticated'],
            '/login' => ['page' => 'login', 'auth' => 'unauthenticated'],
            '/register' => ['page' => 'register', 'auth' => 'unauthenticated'],
            '/dashboard' => ['page' => 'dashboard', 'auth' => 'authenticated'],
            '/upload' => ['page' => 'upload', 'auth' => 'authenticated'],
            '/admin' => ['page' => 'admin', 'auth' => 'admin'],
            '/logout' => ['page' => 'logout', 'auth' => 'authenticated'],
            '/verify_user' => ['page' => 'verified_user', 'auth' => 'unauthenticated'],
            '/forgot_password' => ['page' => 'forgot_password', 'auth' => 'unauthenticated'],
            '/reset_password' => ['page' => 'reset_password', 'auth' => 'unauthenticated'],
        ];

        // Check whether the path exists in the map
        if (!is_string($this->request) || !array_key_exists($this->request, $pages)) {
            // Load the 404 page when the path does not exist
            logEvent($this->logger, "error", 'handleRequest', 'Page not found.', 404);
            require "{$this->pages_path}/404.php";
            return;
        }

        $pageInfo = $pages[$this->request];

        // Check authentication requirements
        if ($pageInfo['auth'] === 'authenticated' && !$this->isAuthenticated()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            header("Location: /login");
            exit();
        }

        if ($pageInfo['auth'] === 'admin' && !$this->isAdmin()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            header("Location: /dashboard");
            exit();
        }

        if ($pageInfo['auth'] === 'unauthenticated' && $this->isAuthenticated()) {
            logEvent($this->logger, "error", 'handleRequest', 'Unauthorized.', 401);
            header('Location: /dashboard');
            exit();
        }

        // Set the current page and load its file
        $this->current_page = $pageInfo['page'];
        require "{$this->pages_path}/{$this->current_page}.php";
    }

    private function isAuthenticated()
    {
        return isset($_SESSION['username']);
    }

    private function isAdmin()
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    private function initSecureSession(){
        if( session_status() == PHP_SESSION_NONE ){
            // Determine if we're on HTTPS (true for production, may be false behind proxy)
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                        $_SERVER['REQUEST_SCHEME'] === 'https';
            
            session_start(
                [
                    'cookie_lifetime' => 0, // Expire the session when the browser closes
                    'cookie_httponly' => true,
                    'cookie_secure' => $is_https, // Send only over HTTPS when HTTPS is active
                    'cookie_samesite' => 'Lax',
                    
                ]
            );

            $sessionTimeout = 30 * 60; // 30 minutes
            if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $sessionTimeout) {
                session_unset();
                session_destroy();
                session_start(
                    [
                        'cookie_lifetime' => 0,
                        'cookie_httponly' => true,
                        'cookie_secure' => $is_https,
                        'cookie_samesite' => 'Lax',
                    ]
                );
            }

            $_SESSION['last_activity'] = time();

            // Anti-CTRF token creation
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            // Force XSS browser protection if present
            header("X-XSS-Protection: 1; mode=block");
            // Content-Security Policy
            header(
                "Content-Security-Policy: " .
                "default-src 'self'; " .
                "script-src 'self'; " .
                "style-src 'self'; " .
                "img-src 'self'; " .
                "connect-src 'self'; " .
                "object-src 'none'; " .
                "base-uri 'self'; " .
                "form-action 'self'; " .
                "frame-ancestors 'none'"
            );
            header('Referrer-Policy: no-referrer');

        }
    }

    // Return the current page
    public function getCurrentPage()
    {
        return $this->current_page;
    }
}

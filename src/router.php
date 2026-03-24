<?php

require_once __DIR__ . '/Backend/controller/FileController.php';
require_once __DIR__ . '/Backend/controller/UserController.php';
require_once __DIR__ . '/Backend/utils/Logger.php';

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
        $this->pages_path = "{$this->base_path}/Frontend/pages";

        $this->fc = new FileController();
        $this->uc = new UserController();

        $this->logger = Logger::getInstance();

        $this->current_page = '';
    }

    public static function getInstance(){
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Metodo principale per gestire le rotte
    public function handleRequest()
    {
        // Distinzione tra API e pagine
        if (strpos($this->request, '/api/') === 0) {
            $this->handleApiRequest();
        } else {
            $this->handlePageRequest();
        }
    }

    // Gestisce le richieste API
    private function handleApiRequest()
    {
        // Rimuove il prefisso /api/ per ottenere l'endpoint
        $apiRequest = str_replace('/api/', '', $this->request);
        
        // Mappatura degli endpoint API ai metodi corrispondenti e ai permessi richiesti
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
            'verify_user' => ['handler' => [$this->uc, 'verifyUser'], 'auth' => 'unauthenticated', 'method' => "GET"],
            'forgot_pwd' => ['handler' => [$this->uc, 'forgotPassword'], 'auth' => 'unauthenticated', 'method' => "POST"],
            'reset_pwd' => ['handler' => [$this->uc, 'resetPassword'], 'auth' => 'unauthenticated', 'method' => "POST"],
        ];
    
        // Controlla se l'endpoint esiste nella mappatura
        if (!is_string($apiRequest) || !array_key_exists($apiRequest, $apiEndpoints)) {
            $this->logger->error('handleRequest', 'API not found.', 404);
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'API not found']);
            exit();
        }
    
        // Recupera le informazioni sull'endpoint
        $endpoint = $apiEndpoints[$apiRequest];
        
        // Verifica i permessi dell'endpoint
        if ($endpoint['auth'] === 'admin' && !$this->isAdmin()) {
            $this->logger->error('handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }
        if ($endpoint['auth'] === 'authenticated' && !$this->isAuthenticated()) {
            $this->logger->error('handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }
        if ($endpoint['auth'] === 'unauthenticated' && $this->isAuthenticated()) {
            $this->logger->error('handleRequest', 'Unauthorized.', 401);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
            exit();
        }

        // Verifica il metodo dell'endpoint
        if ($_SERVER['REQUEST_METHOD'] !== $endpoint['method']) {
            $this->logger->error('handleRequest', 'Method not allowed.', 405);
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            exit();
        }
    
        // Chiama il metodo corrispondente all'endpoint
        call_user_func($endpoint['handler']);
    }

    // Gestisce le richieste delle pagine
    private function handlePageRequest()
    {   
        // Mappatura dei percorsi alle pagine e requisiti di autenticazione
        $pages = [
            '' => ['page' => 'homepage', 'auth' => 'unauthenticated'],
            '/' => ['page' => 'homepage', 'auth' => 'unauthenticated'],
            '/login' => ['page' => 'login', 'auth' => 'unauthenticated'],
            '/register' => ['page' => 'register', 'auth' => 'unauthenticated'],
            '/dashboard' => ['page' => 'dashboard', 'auth' => 'authenticated'],
            '/logout' => ['page' => 'logout', 'auth' => 'unauthenticated'], // chi ci puo accedere alla pagina?
            '/verify_user' => ['page' => 'verify_user', 'auth' => 'unauthenticated'],
            '/forgot_password' => ['page' => 'forgot_password', 'auth' => 'unauthenticated'],
            '/reset_password' => ['page' => 'reset_password', 'auth' => 'unauthenticated'],
        ];

        // Controllo se il percorso non esiste nella mappatura
        if (!is_string($this->request) || !array_key_exists($this->request, $pages)) {
            // Carica la pagina 404 se il percorso non esiste
            $this->logger->error('handleRequest', 'Page not found.', 404);
            require "{$this->pages_path}/404.php";
            return;
        }

        $pageInfo = $pages[$this->request];

        // Controllo dei requisiti di autenticazione
        if ($pageInfo['auth'] === 'authenticated' && !$this->isAuthenticated()) {
            $this->logger->error('handleRequest', 'Unauthorized.', 401);
            header("Location: /login");
            exit();
        }

        if ($pageInfo['auth'] === 'unauthenticated' && $this->isAuthenticated()) {
            $this->logger->error('handleRequest', 'Unauthorized.', 401);
            header('Location: /dashboard');
            exit();
        }

        // Imposta la pagina corrente e richiede il file della pagina
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
                    'cookie_lifetime' => 0, // La sessione scade alla chiusura del browser
                    'cookie_httponly' => true,
                    'cookie_secure' => $is_https, // Solo su HTTPS (dynamic based on actual protocol)
                    'cookie_samesite' => 'Lax',
                    
                ]
            );
            // Anti-CTRF token creation
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            // Force XSS browser protection if present
            header("X-XSS-Protection: 1; mode=block");
            // Content-Security Policy
            header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com  https://fonts.gstatic.com; style-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://fonts.googleapis.com https://www.w3schools.com; img-src 'self' data:; script-src 'self' https://apis.google.com  ; media-src 'self' https://favicon.ico; frame-ancestors 'none'");

        }
    }

    // Getter per la pagina corrente
    public function getCurrentPage()
    {
        return $this->current_page;
    }
}

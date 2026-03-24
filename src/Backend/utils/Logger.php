<?php

class Logger {
    private static $instance = null; // Unica istanza del logger
    private $logFile;
    private $sensitiveFields = [
        'password','new_password', 'conf_new_password',
        'csrf_token', 'token', 'conf_password', 'PHPSESSID'
    ];

    // Costruttore privato per prevenire l'uso diretto di "new"
    private function __construct($filePath) {
        $this->logFile = $filePath;
    }

    // FIXME: cambia il percorso del file di log e i permessi di scrittura
    // Singleton: ottiene l'unica istanza del logger
    public static function getInstance() {
        if (self::$instance === null) {
            $logDir = getenv('LOG_PATH') ?: __DIR__ . '/../logs';
            $logFile = $logDir . '/app_log.txt';
            self::$instance = new self($logFile);
        }
        return self::$instance;
    }

    private function getUserData(){
        if(isset($_SESSION['username'])){
            return $_SESSION;
        }
        return null;
    }

    // Funzione principale per loggare i dati
    private function logRequest($action, $responseCode, $message = '', $level = 'INFO') {
        // Rileva i dati della richiesta in base al metodo
        // $requestData = $this->getRequestData();

        // Filtra i campi sensibili come password
        $filteredRequestDataPost = $this->filterSensitiveData($_POST);
        $filteredRequestDataGet  = $this->filterSensitiveData($_GET);

        $filteredSessionData = $this->filterSensitiveData($this->getUserData());

        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'log_level' => $level, 
            'client_ip' => $this->getClientIp(),
            'action' => $action,
            'method' => $_SERVER['REQUEST_METHOD'],
            'url' => $uriPath,
            'query_params' => $filteredRequestDataGet, // Se presenti
            'body_params' => $filteredRequestDataPost, // Dati del form o POST
            'session_data' => $filteredSessionData,
            'response_code' => $responseCode,
            'message' => $message,
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        $this->writeLog($logEntry);
    }

    // Filtra i campi sensibili dai dati della richiesta
    private function filterSensitiveData($requestData) {
        if (is_array($requestData)) {
            foreach ($this->sensitiveFields as $field) {
                if (isset($requestData[$field])) {
                    $requestData[$field] = '[FILTERED]'; // Maschera il campo
                }
            }
        }
        return $requestData;
    }

    // Scrivi il log su file
    private function writeLog($logEntry) {
        // FIXME: meglio un CSV
        $logMessage = json_encode($logEntry) . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    // Ottieni l'IP del client
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    // Funzioni di livello per semplificare l'uso del logger
    public function debug($action, $message = '', $responseCode = 200) {
        $this->logRequest($action, $responseCode, $message, 'DEBUG');
    }

    public function info($action, $message = '', $responseCode = 200) {
        $this->logRequest($action, $responseCode, $message, 'INFO');
    }

    public function warning($action, $message = '', $responseCode = 200) {
        $this->logRequest($action, $responseCode, $message, 'WARNING');
    }

    public function error($action, $message = '', $responseCode = 500) {
        $this->logRequest($action, $responseCode, $message, 'ERROR');
    }
}

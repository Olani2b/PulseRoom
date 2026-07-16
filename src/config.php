<?php
// Global configuration and initialization

// Set session save path BEFORE any session operations
$session_path = '/tmp/php_sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0777, true);
}
ini_set('session.save_path', $session_path);

// Disable display_errors to prevent HTML error pages
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/tmp/php-error.log');

// Set reasonable PHP limits
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '10M');

// Global error handler to convert PHP errors to JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        //'debug' => error_get_last()
    ]);
    exit();
});

// Fatal error handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal error occurred',
            //'debug' => $error
        ]);
        exit();
    }
});

?>

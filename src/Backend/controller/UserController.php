<?php
require_once __DIR__ . '/../utils/dbManager.php';
require_once __DIR__ . '/../utils/PostMan.php';
require_once __DIR__ . '/../service/TokenService.php';
require_once __DIR__ . '/../service/UserService.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__.'/../../vendor/autoload.php';
use ZxcvbnPhp\Zxcvbn;

const ACTIVE = 1;
const INACTIVE = 0;

const URL_PSW_RST_PAGE = 'https://localhost/reset_password';
const URL_REGISTER_PAGE = 'https://localhost/verify_user';

class UserController
{
    private $conn, $postman;
    private $token_service, $user_service;
    private $psw_rst_page_url, $register_page_url;
    private $logger;

    public function __construct()
    {
        $this->conn = dbManager::getInstance()->getConnection();
        $this->postman = new PostMan();
        $this->token_service = new TokenService($this->conn);
        $this->user_service = new UserService($this->conn);
        $this->logger = Logger::getInstance();
    }
    
    private function checkPasswordFormat($password, $userData = [])
    {
        // Check Password length and format
        // Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }
        $zxcvbn = new Zxcvbn();
        $sec_level = $zxcvbn->passwordStrength($password, $userData);
        $feedback = '';
        if (isset($sec_level['feedback']['suggestions'][0]))
            $feedback = $sec_level['feedback']['suggestions'][0];
        if ($sec_level['score'] < 2)
            $feedback = $sec_level['feedback']['warning'] . '. ' . $feedback;
        
        if($sec_level['score'] < 4)
            return 'Password is too weak!' . $feedback;

        return false;
    }

    public function register()
    {
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('register', 'Invalid request.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 401);
        }

        if(!isset($_POST['username']) || !isset($_POST['email']) 
                || !isset($_POST['password']) || !isset($_POST['conf_password'])) {
            $this->logger->error('register', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        if(!is_string($_POST['username']) || !is_string($_POST['email']) 
                || !is_string($_POST['password']) || !is_string($_POST['conf_password'])) {
            $this->logger->error('register', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }
        
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $conf_password = $_POST['conf_password'];

        if($password !== $conf_password) {
            $this->logger->error('register', 'Passwords do not match.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Passwords do not match.'], 400);
        }

        // Check the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('register', 'Invalid email format.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid email format.'], 400);
        }

        // Check the password format

        $passwordError = $this->checkPasswordFormat($password, [$username, $email]);
        if ($passwordError !== false) {
            $this->logger->error('register', 'Weak password.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => $passwordError], 400);
        }
        
        // Check if the user already exists
        if($this->user_service->checkUserExistence($email)) {
            // Send alert email
            $message = file_get_contents(__DIR__ . '/../template/alertEmail.html');
            // Send an email with the OTP
            $subject = 'Pulse Room email reuse';
            try {
                $this->postman->send($email, $subject, $message);
            } catch (Exception $e) {
                $this->logger->error('register', 'Failed to send alarm email.', 500);
            }
            $this->logger->error('register', 'email re-use for registration.', 409);
            return $this->sendResponse([
                'status' => 'success', 
                'message' => 'A confirmation email has been sent to your account.'
            ], 201);
        }

        // Generate token
        $token = $this->token_service->generateToken(100);

        // Registra il nuovo utente
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        $check = $stmt->execute();
        
        // Store the token in the tokens table
        if($this->token_service->storeToken($token, $email) == false) {
            $this->logger->error('register', 'Failed to store token.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Registration failed.'], 500);
        }
        $htmlTemplate = file_get_contents(__DIR__ . '/../template/confirmationEmail.html');

        // Replace placeholder with the actual link
        $actionLink = URL_REGISTER_PAGE."?email=".$email."&token=".$token;
        $message = str_replace('{{ACTION_LINK}}', $actionLink, $htmlTemplate);
        // Send an email with the OTP
        $subject = 'Verify your email address for Pulse Room';

        try {
            $this->postman->send($email, $subject, $message);
        } catch (Exception $e) {
            $this->logger->error('register', 'Failed to send email.', 500);
            $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
            while($this->token_service->deleteToken($email, 'register') == false) {
                $this->logger->error('register', 'Failed to remove register token after failed email send', 500);
            }
            return $this->sendResponse(['status' => 'error', 'message' => 'Registration failed.'], 500);
        }

        $stmt->close();
        $this->logger->info('register', 'User registered successfully.', 201);
        return $this->sendResponse(['status' => 'success', 'message' => 'User registered successfully.'], 201);
    }

    public function verifyUser()
    {
        if(!isset($_GET['token']) || !isset($_GET['email'])
            || !is_string($_GET['token']) || !is_string($_GET['email'])) {
            $this->logger->error('verifyUser', 'Missing parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        $receive_token = $_GET['token'];
        $email = $_GET['email'];
        
        // Check the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('verifyUser', 'Invalid email format.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid email format.'], 400);
        }
        
        // get the token from the database
        if($this->token_service->checkToken($receive_token, $email, 'register') == false) {
            $this->logger->error('verifyUser', 'Invalid Token.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid Token.'], 401);
        }

        if(!$this->user_service->setUserStatus($email, ACTIVE)) {
            $this->logger->error('verifyUser', 'User Staus not updated', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to verify user.'], 500);
        }
            
        // Delete the token from the tokens table
        if($this->token_service->deleteToken($email, 'register') == false) {
            $this->logger->error('verifyUser', 'Failed to delete token', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to verify user.'], 500);
        }
        $this->logger->info('verifyUser', 'User verified successfully.', 201);
        return $this->sendResponse(['status' => 'success', 'message' => 'User verified successfully.'], 201);
    }

    public function forgotPassword()
    {
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('forgotPassword', 'Invalid request.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 401);
        }

        if (!isset($_POST['email']) || !is_string($_POST['email'])) {
            $this->logger->error('forgotPassword', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Email is required.'], 400);
        }
    
        $email = $_POST['email'];
        // Check the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('forgotPassword', 'Invalid email format.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid email format.'], 400);
        }        
    
        // Check if the user exists
        if($this->user_service->checkUserExistence($email) == false)
        {
            $this->logger->warning('forgotPassword', 'User not Found', 409);
            return $this->sendResponse(['status' => 'error', 'message' => 'User not found.'], 409);
        }        
    
        // Generate token
        $token = $this->token_service->generateToken(100);

        // Store the token in the tokens table
        if ($this->token_service->storeToken($token, $email, 'reset') == false) {
            $this->logger->error('forgotPassword', 'Failed to initiate password reset.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to initiate password reset.'], 500);
        }

        if(!$this->user_service->setUserStatus($email, INACTIVE)){
            $this->logger->error('forgotPassword', 'Failed to disable user.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to initiate password reset.'], 500);
        }
        
        $htmlTemplate = file_get_contents(__DIR__ . '/../template/resetEmail.html');

        // Replace placeholder with the actual link
        $actionLink = URL_PSW_RST_PAGE."?email=".$email."&token=".$token;
        $message = str_replace('{{ACTION_LINK}}', $actionLink, $htmlTemplate);
        // Send an email with the OTP
        $subject = 'Reset your password account for Pulse Room';
        
        try {
            $this->postman->send($email, $subject, $message);
        } catch (Exception $e) {
            $stmt = $this->conn->prepare("DELETE FROM tokens WHERE email = ? AND type = 'reset'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to initiate password reset.'], 500);
        }
        $this->logger->info('forgotPassword', 'Password reset email sent.', 200);
        // Send email with token  
        return $this->sendResponse(['status' => 'success', 'message' => 'An email has been sent to reset your password.'], 200);
    }
    
    public function resetPassword()
    {
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('resetPassword', 'Invalid request.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 401);
        }

        if (!isset($_POST['token']) || !isset($_POST['email']) || !isset($_POST['new_password'])
             || !isset($_POST['conf_new_password'])) {
            $this->logger->error('resetPassword', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        if(!is_string($_POST['token']) || !is_string($_POST['email']) 
                || !is_string($_POST['new_password']) || !is_string($_POST['conf_new_password'])) {
            $this->logger->error('resetPassword', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }
    
        $receive_token = $_POST['token'];
        $email = $_POST['email'];
        $new_password = $_POST['new_password'];
        $conf_new_password = $_POST['conf_new_password'];

        // Check the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('resetPassword', 'Invalid email format.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid email format.'], 400);
        }
            
        // Check if the token is valid
        if ($this->token_service->checkToken($receive_token, $email, 'reset') == false) {
            $this->logger->error('resetPassword', 'Token expired.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Token expired, make a new request.'], 401);
        }

        // Check if the user exists
        if ($this->user_service->checkUserExistence($email) == false) {
            $this->logger->error('resetPassword', 'User not found.', 404);
            return $this->sendResponse(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Check the password format
        $username = $this->user_service->getUsername($email);
        $passwordError = $this->checkPasswordFormat($new_password, [$username, $email]);
        if ($passwordError !== false) {
            $this->logger->error('resetPassword', 'Weak password.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => $passwordError], 400);
        }

        // Check if the new password and confirm new password match
        if ($new_password !== $conf_new_password) {
            $this->logger->error('resetPassword', 'Passwords do not match.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Passwords do not match.'], 400);
        }
        
        // Hash the password
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $executed = $stmt->execute();
        $stmt->close();
 
        if ($executed == false) {
            $this->logger->error('resetPassword', 'Password reset failed.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Password reset failed.'], 500);
        }
        
        // Delete the token from the tokens table
        if($this->token_service->deleteToken($email, 'reset') == false) {
            $this->logger->error('resetPassword', 'Failed to delete token.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to verify user.'], 500);
        }
        
        if(!$this->user_service->setUserStatus($email, ACTIVE)){
            $this->logger->error('resetPassword', 'Failed to enable user.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Failed to enable user.'], 500);
        }

        $this->logger->info('resetPassword', 'Password reset successfully.', 200);
        return $this->sendResponse(['status' => 'success', 'message' => 'Password reset successfully.'], 200);
    }  

    public function login()
    {   
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('login', 'Invalid request.', 401);
            return $this->sendResponse([
                'status' => 'error',
                'message' => 'Invalid request.'
            ], 401);
        }

        if (!isset($_POST['email']) || !isset($_POST['password']) 
                || !is_string($_POST['email']) || !is_string($_POST['password'])) {
            $this->logger->error('login', 'Invalid request parameters.', 400);
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'Invalid request.'
            ], 400);
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('login', 'Invalid email format.', 400);
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'Invalid email format.'
            ], 400);
        }

        // Controlla se l'utente esiste
        $stmt = $this->conn->prepare( 
            "SELECT id, username, password, role, active, first_attempt, last_attempt, timedout, attempts 
                    FROM users 
                    WHERE email = ?"
                );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $stmt->close();
            $this->logger->error(
                'login', 
                'Invalid email or password.', 
                401
            );
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'Invalid credentials or too many failed attempts.'
            ], 401);
        }

        $stmt->bind_result($id, $username, $hashedPassword, $role, $status, 
                    $first_attempt, $last_attempt, $timedout, $attempts);
        $stmt->fetch();
        $stmt->close();

        //Check if the user is timedout
        if($timedout)
        {
            $timeout_time =  $this->user_service->differenceInMinutes(
                new DateTime(), 
                new DateTime($last_attempt)
            );
            if($timeout_time >= $this->user_service->timeout_time)
            {
                $this->user_service->resetAttempts($email);
                $timedout = false;
            }
            else {
                $this->logger->error('login', 'User is timed out.', 401);
                return $this->sendResponse([
                    'status' => 'error', 
                    'message' => 'Invalid credentials or too many failed attempts.'
                ], 401);
            }
        }

        if(!password_verify($password, $hashedPassword)) {
            if($status === ACTIVE){
                $this->user_service->updateLoginAttempts(
                    $email, $first_attempt, 
                    $timedout, $attempts
                );
            }
            $this->logger->error(
                'login', 
                'Invalid email or password.', 
                401
            );
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'Invalid credentials or too many failed attempts.'
            ], 401);
        }

        if ($status === INACTIVE) {
            $this->logger->error(
                'login', 
                'User is inactive.', 
                401
            );
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'User is inactive.'
            ], 401);
        }

        $this->user_service->resetAttempts($email);

        // Regenerate session ID to prevent fixation after successful login
        session_regenerate_id(true);
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['user_id'] = $id;
        $_SESSION['email'] = $email;

        $this->logger->info(
            'login', 
            'Login successful.', 
            200
        );
        return $this->sendResponse([
            'status' => 'success', 
            'message' => 'Login successful.', 
            'user' => ['id' => $id, 'username' => $username]
        ], 200);
    }
    public function logout()
    {
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('logout', 'Invalid request parameters.', 403);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 403);
        }
        $this->logger->info(
            'logout',
            'Logout successful.', 200
        );
        // Destroy the session
        session_unset();
        session_destroy();
        return $this->sendResponse([
            'status' => 'success',
            'message' => 'Logout successful.'
        ], 200);
    }

    public function showUsers()
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ?  $_GET['page'] : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 10;

        if($page < 1){
            $page = 1; //FIXME: come controllo la pagina massima da ritornare?
        }
        if($limit < 1 || $limit > 10){
            $limit = 10;
        }

        $offset = ($page - 1) * $limit;

        $limit++; // To check if there are more pages

        $stmt = $this->conn->prepare(
            "SELECT id, username, email, role 
            FROM users 
            Where role != 'admin' 
            LIMIT ?, ?"
        );
        
        $stmt->bind_param("ii", $offset, $limit);

        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        $isLastPage = true;

        if($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            if (count($users) == $limit) {
                $isLastPage = false;
                array_pop($users);
            }
        }

        $stmt->close();
        $this->logger->info('showUsers', 'Users retrieved successfully.', 200);
        return $this->sendResponse(['status' => 'success', 'data' => $users, 'last-page' => $isLastPage], 200);

    }

    public function changeUserRole()
    {
        if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logger->error('changeUserRole', 'Invalid request.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 401);
        }

        if(!isset($_POST['id']) || !isset($_POST['new_role']) || !isset($_POST['actual_role'])) {
            $this->logger->error('changeUserRole', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        if(!is_numeric($_POST['id']) || !is_string($_POST['new_role']) 
                || !is_string($_POST['actual_role'])) {
            $this->logger->error('changeUserRole', 'Invalid request parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        //$email = $_POST['email'];
        $id = $_POST['id'];
        $newRole = $_POST['new_role'];
        $actualRole = $_POST['actual_role'];

        if(!in_array($newRole, ['free', 'admin', 'pro'], true)
            || !in_array($actualRole, ['free', 'admin', 'pro'], true)) {
            $this->logger->error(
                'changeUserRole',
                 'Invalid role.',
                  400
            );
            return $this->sendResponse([
                'status' => 'error', 
                'message' => 'Invalid role.'
            ], 400);
        }

        if ($actualRole === 'admin' || $newRole === 'admin') {
            $this->logger->error('changeUserRole', 'Unauthorized.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        if ( $actualRole ===  $newRole ){
            $this->logger->error('changeUserRole', 'Invalid request.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }

        $stmt = $this->conn->prepare(
            "SELECT role 
                    FROM users 
                    WHERE id = ?"
                );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        //$stmt->close();
        
        if($stmt->num_rows == 0) {
            $this->logger->error('changeUserRole', 'User not found.', 404);
            return $this->sendResponse(['status' => 'error', 'message' => 'User not found.'], 404);
        }
        $stmt->bind_result($role);
        $stmt->fetch();
        $stmt->close();

        if($role === 'admin') {
            $this->logger->error('changeUserRole', 'Unauthorized.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        if($role === $newRole || $role !== $actualRole) {
            $this->logger->error('changeUserRole', 'Invalid request.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
        }
        
        $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $id);

        if ($stmt->execute()) {
            $stmt->close();
            $this->logger->info('changeUserRole', 'Role changed successfully.', 200);
            return $this->sendResponse(['status' => 'success', 'message' => 'Role changed successfully.'], 200);
        }

        $stmt->close();
        $this->logger->error('changeUserRole', 'Role change failed.', 500);
        return $this->sendResponse(['status' => 'error', 'message' => 'Role change failed.'], 500);   
    }

    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }
}

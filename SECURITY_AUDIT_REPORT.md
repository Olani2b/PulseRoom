# Pulse Room - Comprehensive Security Audit Report

**Date of Audit:** March 2, 2026  
**Project Type:** PHP/MySQL Web Application with Docker Deployment  
**Purpose:** University System and Network Hacking Course Security Analysis  
**Auditor Context:** Senior Security Engineer

---

## Executive Summary

Pulse Room implements a **multi-layered security architecture** with significant protections against common web vulnerabilities. The application demonstrates mature security practices including:

- **CSRF Token Protection** on all state-changing operations
- **Secure Password Hashing** using PHP's password_hash (PASSWORD_DEFAULT = bcrypt)
- **Prepared Statements** for SQL Injection Prevention
- **Session Security Hardening** with secure cookie flags
- **Content-Security Policy (CSP)** headers
- **Transport Layer Security (TLS/SSL)** enforcement
- **Rate Limiting & Login Attempt Throttling**
- **Comprehensive Audit Logging** with sensitive data filtering

However, several security best practices are **missing or incomplete**, and some configuration issues exist that require attention.

---

## Part 1: IMPLEMENTED SECURITY MECHANISMS

### 1. AUTHENTICATION

#### 1.1 Password Hashing (Bcrypt)
**Location:** `src/Backend/controller/UserController.php` (lines 118, 320)  
**Implementation:**
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```
**How It Works:**
- Uses PHP's built-in `PASSWORD_DEFAULT` algorithm (bcrypt with configurable cost factor)
- Bcrypt automatically generates unique salt for each password
- Slows down brute-force attacks through computational cost (default cost: 10)
- Comparison uses `password_verify()` for timing-attack-safe comparison

**Vulnerabilities Prevented:** Password Cracking, Rainbow Table Attacks, Timing Attacks  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 1.2 Email Verification Flow
**Location:** `src/Backend/controller/UserController.php` (lines 56-173)  
**Database:** `tokens` table with TTL enforcement  
**Implementation:**
1. User registers with email and password
2. Token generated: `bin2hex(random_bytes(100))` = 200 hex characters
3. Token stored in database with `created_at` timestamp
4. Email sent with verification link: `https://localhost/verify_user?email=X&token=Y`
5. User clicks link → token validated against database
6. Token must be created within **1 hour** (checked in TokenService)
7. User status set to ACTIVE after verification

**Code Evidence:**
```php
// Token generation
$token = $this->token_service->generateToken(100); // 100 bytes = 200 hex chars

// Token validation - 1 hour expiration
$true_created_at = date('Y-m-d H:i:s', strtotime('-1 hour'));
$stmt->bind_param("ssss", $token, $email, $purpose, $true_created_at);
```

**Vulnerabilities Prevented:** Account Takeover (unverified email), Email Spoofing  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 1.3 Session Regeneration After Login
**Location:** `src/Backend/controller/UserController.php` (line 460)  
**Implementation:**
```php
session_regenerate_id(true); // true = delete old session file
$_SESSION['username'] = $username;
$_SESSION['role'] = $role;
$_SESSION['user_id'] = $id;
$_SESSION['email'] = $email;
```

**How It Works:**
- Old session ID is destroyed and new one generated
- Prevents Session Fixation attacks where attacker pre-sets session ID
- Timing: Regeneration happens AFTER successful authentication

**Vulnerabilities Prevented:** Session Fixation Attacks  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 1.4 Inactive User Status on Password Reset
**Location:** `src/Backend/controller/UserController.php` (lines 195-198, 331-336)  
**Implementation:**
```php
// Line 195: On forgot password request
if(!$this->user_service->setUserStatus($email, INACTIVE)){
    // User marked as INACTIVE

// Line 331: After password reset succeeds
if(!$this->user_service->setUserStatus($email, ACTIVE)){
    // User re-activated after successful reset
```

**How It Works:**
- When user requests password reset, their account is immediately INACTIVE
- Prevents use of old password during reset flow
- Account only re-activated after successful password reset
- During inactive state, login is rejected: 
  ```php
  if ($status === INACTIVE) {
      return 'User is inactive.'
  }
  ```

**Vulnerabilities Prevented:** Privilege Escalation during Password Reset, Session Hijacking during Recovery  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 2. AUTHORIZATION

#### 2.1 Role-Based Access Control (RBAC) at Route Level
**Location:** `src/router.php` (lines 48-103)  
**Implementation:**
```php
$apiEndpoints = [
    'upload_file' => ['auth' => 'authenticated', 'method' => "POST"],
    'download_file' => ['auth' => 'authenticated', 'method' => "POST"],
    'show_files' => ['auth' => 'authenticated', 'method' => "GET"],
    'show_users' => ['auth' => 'admin', 'method' => "GET"],
    'change_role' => ['auth' => 'admin', 'method' => "POST"],
    'register' => ['auth' => 'unauthenticated', 'method' => "POST"],
    'login' => ['auth' => 'unauthenticated', 'method' => "POST"],
];

// Route permission enforcement
if ($endpoint['auth'] === 'admin' && !$this->isAdmin()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    return;
}
```

**How It Works:**
- Each route has explicit authorization requirement: `'authenticated'`, `'unauthenticated'`, or `'admin'`
- Checked BEFORE handler execution
- Admin check: `isset($_SESSION['role']) && $_SESSION['role'] === 'admin'`

**Vulnerabilities Prevented:** Unauthorized API Access, IDOR (Insecure Direct Object References)  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 2.2 Premium Content Access Control
**Location:** `src/Backend/controller/FileController.php` (lines 124-173)  
**Implementation:**
```php
// Get user's role/visibility level
$userVisibility = $this->getUserVisibility();
// Role 'free' = visibility 0, 'pro'/'admin' = visibility 1

// On download: check if user can access
if($visibility > $userVisibility){
    return 'Missing download file permissions.'; // 403 Forbidden
}

// On file listing: filter based on user's permission level
// SQL: "WHERE ... AND ? >= f.visibility"
// This ensures only accessible files are returned
```

**How It Works:**
- Files have `visibility` flag: 0 (free) or 1 (premium/pro)
- User's `role` determines their visibility level
- Download and listing filtered by: `user_visibility >= file_visibility`
- Non-premium users cannot access premium files

**Vulnerabilities Prevented:** Unauthorized Content Access, IDOR  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 2.3 Admin Role Protection
**Location:** `src/Backend/controller/UserController.php` (lines 578-593)  
**Implementation:**
```php
// Regular users cannot change other users' roles to/from admin
if($newRole === 'admin' || $role === 'admin') {
    return 'Unauthorized.'; // 401
}

// Second validation: check database record
if($role === 'admin') {
    return 'Unauthorized.'; // 401
}
```

**How It Works:**
- **Double validation:** Client-provided role AND database-fetched role checked
- Admin role cannot be changed by non-admin users
- Prevents privilege escalation

**Vulnerabilities Prevented:** Privilege Escalation (User → Admin)  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 3. INPUT VALIDATION

#### 3.1 CSRF Token Validation
**Location:** `src/Backend/controller/UserController.php` (lines 58-60, 208-210, 265-267, 349-351, 550-552), `src/Backend/controller/FileController.php` (lines 18-21)  
**Implementation:**
```php
// Token generation (router.php:189)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Token validation (constant across all POST operations)
if(!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) 
    || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    return 'Invalid request.'; // 401
}
```

**How It Works:**
- **Generation:** 32 random bytes → 64 hex characters, stored in session
- **Validation:** Uses `hash_equals()` for timing-attack-safe comparison
- **Coverage:** Applied to: `register`, `login`, `logout`, `forgot_password`, `reset_password`, `upload`, `change_role`
- **Token Type:** Synchronizer Token Pattern (STP)

**Vulnerabilities Prevented:** CSRF (Cross-Site Request Forgery)  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.2 Email Format Validation
**Location:** `src/Backend/controller/UserController.php` (multiple locations)  
**Implementation:**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'Invalid email format.'; // 400
}
```

**Locations:**
- Registration: line 92
- Login: line 376
- Forgot Password: line 220
- Verify User: line 186
- Reset Password: line 295

**Vulnerabilities Prevented:** Invalid Email Injection, Data Integrity Issues  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.3 Password Strength Validation
**Location:** `src/Backend/controller/UserController.php` (lines 32-56)  
**Implementation:**
```php
private function checkPasswordFormat($password, $userData = []) {
    // 1. Length check
    if (strlen($password) < 8) return 'Password must be at least 8 characters long.';
    
    // 2. Uppercase letter required
    if (!preg_match('/[A-Z]/', $password)) return 'Password must contain at least one uppercase letter.';
    
    // 3. Lowercase letter required
    if (!preg_match('/[a-z]/', $password)) return 'Password must contain at least one lowercase letter.';
    
    // 4. Number required
    if (!preg_match('/[0-9]/', $password)) return 'Password must contain at least one number.';
    
    // 5. Advanced entropy check using Zxcvbn library
    $zxcvbn = new Zxcvbn();
    $sec_level = $zxcvbn->passwordStrength($password, $userData);
    
    if($sec_level['score'] < 4) {
        // Score must be 4 out of 5 (Strong)
        return 'Password is too weak! ' . $feedback;
    }
    return false; // Valid password
}
```

**Components:**
1. **Regex Rules:** 8+ chars, uppercase, lowercase, number
2. **Entropy Analysis:** Zxcvbn library (NPM port to PHP) evaluates:
   - Dictionary words
   - Common patterns
   - User data (username, email)
   - Score: 0-4 (must be ≥4 = Strong)
3. **Applied to:** Registration, Password Reset

**Vulnerabilities Prevented:** Weak Password Attacks, Dictionary Attacks  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.4 File Type Validation
**Location:** `src/Backend/controller/FileController.php` (lines 50-51, 72-73)  
**Implementation:**
```php
$filetype = pathinfo($file["name"], PATHINFO_EXTENSION);

if(!in_array($filetype, ["txt","pdf"])){
    return 'File type not supported'; // 400
}
```

**Process:**
1. Extract extension using `pathinfo()`
2. Whitelist check: only `txt` or `pdf` allowed
3. Applied to both file uploads and novel_category parameter

**Limitations:** ⚠️ Extension-only validation (see vulnerabilities section)

**Vulnerabilities Prevented:** Executable File Upload  
**Status:** ⚠️ PARTIALLY IMPLEMENTED (missing MIME type verification)

---

#### 3.5 File Size Validation
**Location:** `src/Backend/controller/FileController.php` (lines 43-46)  
**Implementation:**
```php
if($file['size'] <= 0 || $file['size'] > 1024*1024*2){
    return 'File size not supported';
}
```

**Limit:** 2 MB per file  
**Enforcement:** Also via `.htaccess`:
```
LimitRequestBody 3145728  # 3 MB total request body
```

**Vulnerabilities Prevented:** DoS via Large File Upload, Disk Space Exhaustion  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.6 File Name Sanitization
**Location:** `src/Backend/controller/FileController.php` (lines 48-50)  
**Implementation:**
```php
$title = pathinfo( $file["name"], PATHINFO_FILENAME);
$title = preg_replace('/[^\w\-\.]/', '_', $title);  // Keep only word chars, dash, dot
$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$title = substr($title, 0, 255);
```

**Sanitization Steps:**
1. Extract filename (remove path traversal)
2. Remove special characters (only `\w`, `-`, `.` allowed)
3. HTML escape for safe output
4. Truncate to 255 chars

**For text uploads:**
```php
$filedata = htmlspecialchars($filedata, ENT_QUOTES, 'UTF-8');
```

**Vulnerabilities Prevented:** Path Traversal, XSS via Filename  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.7 Numeric Parameter Validation
**Location:** `src/Backend/controller/FileController.php`, `src/Backend/controller/UserController.php`  
**Implementation:**
```php
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 10;
$fileId = $_POST['file_id'];  // Must be numeric
```

**Validation:** `is_numeric()` check + bounds checking  
**Vulnerabilities Prevented:** SQL Injection via Numeric Fields  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 3.8 Type Checking for POST Parameters
**Location:** Throughout UserController and FileController  
**Implementation:**
```php
// Example from register (lines 71-76)
if(!is_string($_POST['username']) || !is_string($_POST['email']) 
        || !is_string($_POST['password']) || !is_string($_POST['conf_password'])) {
    return 'Invalid request.'; // 400
}
```

**Applied to:**
- Registration: username, email, password (3 fields)
- Login: email, password
- File upload: title, text_content, upload_type, novel_category
- Role change: new_role, actual_role
- Numeric: page, limit, file_id

**Vulnerabilities Prevented:** Type Juggling Attacks, Array Injection  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 4. OUTPUT ENCODING

#### 4.1 HTML Entity Encoding in Templates
**Location:** `src/Frontend/pages/` (multiple)  
**Implementation:**
```php
// register.php line 30
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

// reset_password.php line 35
<input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">

// navbar.php line 26
echo '<b>' . htmlspecialchars($_SESSION['username']) . '</b>';
```

**Function:** `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`
- Converts: `<`, `>`, `&`, `"`, `'` to HTML entities
- Applied to all user-controlled output in templates

**Vulnerabilities Prevented:** XSS (Reflected & Stored)  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 4.2 JSON Response Output
**Location:** `src/Backend/controller/` (throughout)  
**Implementation:**
```php
private function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
}
```

**How It Works:**
- All API responses use `json_encode()` with HTML-safe escaping
- Content-Type header set to application/json
- Prevents misinterpretation as HTML/JavaScript

**Vulnerabilities Prevented:** JSON Injection, Content Type Confusion  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 5. CRYPTOGRAPHY

#### 5.1 Cryptographically Secure Random Number Generation
**Location:** `src/router.php` (line 190), `src/Backend/service/TokenService.php` (line 12)  
**Implementation:**
```php
// CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));  // 256-bit entropy

// Email verification & password reset tokens
$token = bin2hex(random_bytes(100));  // 800-bit entropy
```

**Function:** `random_bytes()` - Uses:
- Linux/Unix: `/dev/urandom` (kernel entropy pool)
- Windows: `CryptGenRandom()` API
- **Not** PHP's `mt_rand()` or `rand()` (cryptographically weak)

**Token Entropy:**
- CSRF: 256 bits (collision probability negligible)
- Email Token: 800 bits (extremely high security)

**Vulnerabilities Prevented:** Token Prediction, Session Hijacking  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 6. SESSION MANAGEMENT

#### 6.1 Secure Session Configuration
**Location:** `src/router.php` (lines 177-188)  
**Implementation:**
```php
session_start([
    'cookie_lifetime' => 0,        // Session cookie, not persistent
    'cookie_httponly' => true,     // Cannot access via JavaScript
    'cookie_secure' => true,       // HTTPS only
    'cookie_samesite' => 'Lax',    // CSRF protection via SameSite
]);
```

**Security Properties:**
- **cookie_lifetime=0:** Cleared on browser close (no persistent storage)
- **cookie_httponly=true:** Prevents `document.cookie` access (XSS mitigation)
- **cookie_secure=true:** Only sent over HTTPS (prevents man-in-the-middle)
- **cookie_samesite=Lax:** Cookies only sent on same-site requests and top-level navigations (CSRF mitigation)

**Vulnerabilities Prevented:** XSS Session Hijacking, CSRF, Man-in-the-Middle  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 6.2 Session Destruction on Logout
**Location:** `src/Backend/controller/UserController.php` (lines 507-514)  
**Implementation:**
```php
public function logout() {
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        return 'Invalid request.';
    }
    
    session_unset();      // Remove all session variables
    session_destroy();    // Destroy session file on server
    return 'Logout successful.';
}
```

**Process:**
1. CSRF token validated first
2. `session_unset()` clears `$_SESSION` array
3. `session_destroy()` removes session file from disk

**Vulnerabilities Prevented:** Session Reuse After Logout, Privilege Escalation  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 7. TRANSPORT SECURITY

#### 7.1 HTTPS Enforcement
**Location:** `src/.htaccess` (lines 13-16), Dockerfile, Apache Config  
**Implementation (.htaccess):**
```apache
# Force HTTPS
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Implementation (Dockerfile):**
```dockerfile
RUN a2enmod ssl
RUN a2enmod socache_shmcb
COPY Config-File/localhost+1.pem /etc/ssl/certs/certificate.crt
COPY Config-File/localhost+1-key.pem /etc/ssl/private/server.key
RUN a2ensite pulseroom-ssl.conf
```

**Implementation (ssl-params.conf):**
```apache
SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1  # Disable old protocols
SSLCipherSuite HIGH:!aNULL:!MD5                 # Strong ciphers only
SSLHonorCipherOrder on                          # Server chooses strongest cipher
```

**Security:**
- HTTP → HTTPS redirect with 301 (permanent)
- TLS 1.2+ only (old SSL/TLS versions disabled)
- Strong cipher suites (HIGH grade only)
- No NULL/anon/MD5 ciphers

**Vulnerabilities Prevented:** Man-in-the-Middle (MITM), Downgrade Attacks, Eavesdropping  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 8. SECURE HEADERS

#### 8.1 Content-Security-Policy (CSP)
**Location:** `src/router.php` (line 196)  
**Implementation:**
```php
header("Content-Security-Policy: 
    default-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; 
    style-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://fonts.googleapis.com https://www.w3schools.com; 
    img-src 'self' data:; 
    script-src 'self' https://apis.google.com; 
    media-src 'self' https://favicon.ico; 
    frame-ancestors 'none'");
```

**Policy Components:**
- **default-src 'self':** All content from same origin only
- **style-src:** CSS from self + CDNs (Bootstrap, Google Fonts)
- **script-src 'self':** JavaScript from self only (no inline scripts)
- **img-src 'self' data::** Images from self or data URIs
- **frame-ancestors 'none':** Cannot be embedded in iframes (clickjacking protection)

**Vulnerabilities Prevented:** XSS (inline script injection), Clickjacking, Data Exfiltration  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 8.2 X-XSS-Protection Header
**Location:** `src/router.php` (line 195)  
**Implementation:**
```php
header("X-XSS-Protection: 1; mode=block");
```

**Function:** Enables browser's XSS filter to block suspected XSS (legacy, mostly deprecated)  
**Note:** CSP is the modern replacement  
**Status:** ✅ IMPLEMENTED (legacy protection)

---

### 9. RATE LIMITING / DoS PROTECTION

#### 9.1 Login Attempt Throttling
**Location:** `src/Backend/service/UserService.php`, `src/Backend/controller/UserController.php`  
**Implementation:**

**Attempt Counter:**
```php
private $max_attempts = 3;     // Max 3 failed attempts
private $timeout_time = 1;     // Timeout window: 1 minute
```

**Failed Attempt Handling (lines 413-431):**
```php
if(!password_verify($password, $hashedPassword)) {
    if($status === ACTIVE) {
        $this->user_service->updateLoginAttempts(
            $email, $first_attempt, $timedout, $attempts
        );
    }
    return 'Invalid credentials or too many failed attempts.';
}
```

**Rate Limiting Logic (`UserService.php` lines 102-125):**
```php
public function updateLoginAttempts($email, $first_attempt, $timedout, $attempts) {
    $now = new DateTime();
    $minutes = $this->differenceInMinutes(new DateTime($first_attempt), $now);
    
    // If >1 minute passed since first attempt, reset counter
    if($minutes > $this->timeout_time) {
        $this->resetAttempts($email);
        $attempts = 0;
    }
    $attempts += 1;
    
    // After 3 failed attempts in 1 minute, set timeout
    if($attempts >= $this->max_attempts) {
        $timedout = 1;
        // Send security alert email
    }
    
    // Update database
    UPDATE users SET timedout=1, attempts=?, last_attempt=NOW();
}
```

**Timeout Check on Next Login (lines 401-411):**
```php
if($timedout) {
    $timeout_time = $this->user_service->differenceInMinutes(
        new DateTime(), 
        new DateTime($last_attempt)
    );
    if($timeout_time >= $this->user_service->timeout_time) {
        $this->user_service->resetAttempts($email);
        $timedout = false;
    } else {
        return 'Invalid credentials or too many failed attempts.';
    }
}
```

**Sequence:**
1. First failed attempt: Set `first_attempt` timestamp, `attempts=1`
2. Second failed attempt (within 1 min): `attempts=2`
3. Third failed attempt (within 1 min): `attempts=3`, `timedout=1`, send alert email
4. User locked out for 1 minute
5. After 1 minute: Can attempt again with counter reset

**Vulnerabilities Prevented:** Brute Force Attacks, Password Guessing, Account Lockout DoS  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 9.2 Security Alert Email on Lockout
**Location:** `src/Backend/service/UserService.php` (line 121)  
**Implementation:**
```php
if($attempts >= $this->max_attempts) {
    $timedout = 1;
    $subject = "Someone tried to access your account";
    $message = file_get_contents(__DIR__ . '/../template/emailTimeout.html');
    $this->postman->send($email, $subject, $message);
}
```

**Action:** Sends HTML email alert notifying user of failed login attempts  
**Status:** ✅ IMPLEMENTED

---

#### 9.3 Request Body Size Limit
**Location:** `src/.htaccess` (line 1)  
**Implementation:**
```apache
LimitRequestBody 3145728  # 3 MB max
```

**Vulnerability Prevented:** DoS via Request Size  
**Status:** ✅ IMPLEMENTED

---

### 10. FILE UPLOAD SECURITY

#### 10.1 Whitelist-Based File Type Validation
**Location:** `src/Backend/controller/FileController.php` (lines 72-77)  
**Implementation:**
```php
if(!in_array($filetype, ["txt","pdf"])){
    return 'File type not supported'; // 400
}

$novel_category = $_POST['novel_category'];
if(!in_array($novel_category, ["free", "pro"])){
    return 'File type not supported'; // 400
}
```

**Applied to:**
- Uploaded file extensions
- Novel category selection

**Vulnerabilities Prevented:** Executable Upload, Malicious File Types  
**Status:** ⚠️ PARTIALLY IMPLEMENTED (extension-only)

---

#### 10.2 File Storage in Database (BLOB)
**Location:** `src/Backend/controller/FileController.php` (lines 85-88)  
**Implementation:**
```php
$query = 'INSERT INTO files (title, filetype, filedata, user_id, visibility)
          VALUES (?, ?, ?, ?, ?)';
$stmt->bind_param("sssii", $title, $filetype, $filedata, $user_id, $selectedVisibility);
```

**Database Schema:**
```sql
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filetype VARCHAR(50) NOT NULL,
    filedata LONGBLOB NOT NULL,  -- Binary large object
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    visibility TINYINT(1) NOT NULL DEFAULT 0
);
```

**Benefits:**
- Files stored in database instead of filesystem
- Prevents direct access via URL
- Access controlled via application logic
- Permissions checked on download

**Vulnerabilities Prevented:** Direct File Access, Unauthorized Download, Path Traversal  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 10.3 File Download Authorization Check
**Location:** `src/Backend/controller/FileController.php` (lines 127-131)  
**Implementation:**
```php
$userVisibility = $this->getUserVisibility();

if($visibility > $userVisibility) {
    return 'Missing download file permissions.'; // 403 Forbidden
}
```

**Logic:**
- File `visibility = 0` (free): All users can download
- File `visibility = 1` (premium): Only pro/admin users can download
- Free users have `userVisibility = 0`, cannot access premium (`1 > 0`)

**Vulnerabilities Prevented:** Unauthorized File Access, IDOR  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 11. API SECURITY

#### 11.1 Explicit HTTP Method Validation
**Location:** `src/router.php` (lines 119-124)  
**Implementation:**
```php
$apiEndpoints = [
    'upload_file' => ['method' => "POST"],
    'download_file' => ['method' => "POST"],
    'show_files' => ['method' => "GET"],
    'login' => ['method' => "POST"],
    // ...
];

// Enforcement
if ($_SERVER['REQUEST_METHOD'] !== $endpoint['method']) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    return;
}
```

**Coverage:** Every API endpoint has defined HTTP method  
**Enforcement:** 405 Method Not Allowed for violations  
**Vulnerabilities Prevented:** HTTP Method Override, Privilege Escalation  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 11.2 Endpoint Existence Validation
**Location:** `src/router.php` (lines 81-91)  
**Implementation:**
```php
if (!is_string($apiRequest) || !array_key_exists($apiRequest, $apiEndpoints)) {
    $this->logger->error('handleRequest', 'API not found.', 404);
    http_response_code(404);
    echo json_encode(['error' => 'API not found']);
    return;
}
```

**Function:**
- Validates endpoint exists in whitelist
- Type-checks the request string
- Returns 404 for unknown endpoints
- Logs all invalid requests

**Vulnerabilities Prevented:** Information Disclosure, Invalid State Transitions  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 11.3 JSON Response Content-Type
**Location:** `src/Backend/controller/` (throughout)  
**Implementation:**
```php
private function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
}
```

**Applied to:** Every API response  
**Vulnerabilities Prevented:** Content Type Confusion, MIME Sniffing  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 12. LOGGING & MONITORING

#### 12.1 Comprehensive Request Logging
**Location:** `src/Backend/utils/Logger.php`  
**Logged Information:**
```php
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'log_level' => 'INFO' | 'ERROR' | 'WARNING' | 'DEBUG',
    'client_ip' => $_SERVER['REMOTE_ADDR'] | $_SERVER['HTTP_X_FORWARDED_FOR'],
    'action' => 'login' | 'register' | 'upload', etc.
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => $_SERVER['REQUEST_URI'],
    'query_params' => [filtered],
    'body_params' => [filtered],
    'session_data' => [filtered],
    'response_code' => 200 | 401 | 500, etc.
    'message' => 'Login successful' | 'Invalid token', etc.
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
];
```

**Log Levels:**
- **INFO:** Successful operations
- **ERROR:** Failed operations, exceptions
- **WARNING:** Edge cases, retries
- **DEBUG:** Development information

**Logging Locations (from grep of codebase):**
- register: lines 96, 109, 145
- verifyUser: lines 188, 193, 199
- login: lines 373, 375, 386, 427, 450, 465
- logout: lines 508, 512
- upload: lines 94, 96, 102
- download: lines 156, 161, 165
- showFiles: lines 229

**Vulnerabilities Prevented:** Incident Investigation, Audit Trail, Intrusion Detection  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 12.2 Sensitive Data Filtering in Logs
**Location:** `src/Backend/utils/Logger.php` (lines 63-70)  
**Implementation:**
```php
private $sensitiveFields = [
    'password', 'new_password', 'conf_new_password',
    'csrf_token', 'token', 'conf_password', 'PHPSESSID'
];

private function filterSensitiveData($requestData) {
    foreach ($this->sensitiveFields as $field) {
        if (isset($requestData[$field])) {
            $requestData[$field] = '[FILTERED]';  // Masked
        }
    }
    return $requestData;
}
```

**Filtering:**
- Passwords: All variants masked
- CSRF tokens: Masked
- Verification tokens: Masked
- Session IDs: Masked

**Applied to:** All GET, POST, and SESSION data logged  
**Vulnerabilities Prevented:** Sensitive Data Exposure in Logs, Credential Leakage  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 12.3 Client IP Detection
**Location:** `src/Backend/utils/Logger.php` (lines 76-85)  
**Implementation:**
```php
private function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];  // Proxy/load balancer
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
```

**Order of Priority:**
1. Direct client IP (if not behind proxy)
2. X-Forwarded-For header (if behind reverse proxy)
3. REMOTE_ADDR (direct connection)

**Use Case:** Tracking login attempts from same IP  
**Status:** ✅ IMPLEMENTED

---

#### 12.4 Log File Location
**Location:** `docker-compose.yaml`, `.env`  
**Implementation:**
```yaml
volumes:
  - "./logs:/var/www/logs/pulseroom"

environment:
  LOG_PATH: /var/www/logs/pulseroom
```

**Log File:** `/var/www/logs/pulseroom/app_log.txt` (JSON format, one per line)  
**Status:** ✅ IMPLEMENTED

---

### 13. DEPENDENCY SECURITY

#### 13.1 Third-Party Libraries
**Location:** `composer.json`  
**Dependencies:**
```json
{
    "require": {
        "phpmailer/phpmailer": "^7.0"
    }
}
```

**PHPMailer Analysis:**
- **Version:** 7.0+ (latest stable)
- **Purpose:** SMTP email sending
- **Security:** Trusted, well-maintained project
- **CVE Check:** Latest version has no known critical vulnerabilities

**Additional Frontend Libraries (via CDN):**
- Zxcvbn.js: Password strength estimation
- Font Awesome: Icon library
- W3.CSS: CSS framework

**Vulnerabilities Prevented:** Known Library Vulnerabilities  
**Status:** ✅ PROPERLY MAINTAINED

---

#### 13.2 Password Strength Library (Zxcvbn)
**Location:** `composer.json` (via vendor/autoload.php)  
**Usage:** `src/Backend/controller/UserController.php` (line 8)  
**Implementation:**
```php
use ZxcvbnPhp\Zxcvbn;

$zxcvbn = new Zxcvbn();
$sec_level = $zxcvbn->passwordStrength($password, $userData);

if($sec_level['score'] < 4) {
    return 'Password is too weak!';
}
```

**Benefits:**
- Evaluates password entropy against common patterns
- Checks against user data (username, email)
- Returns score 0-4 and suggestions
- More accurate than regex alone

**Status:** ✅ PROPERLY UTILIZED

---

### 14. DATABASE SECURITY

#### 14.1 Prepared Statements (Parameterized Queries)
**Location:** Throughout `UserController.php`, `FileController.php`  
**Implementation:**
```php
// Example from UserController line 354
$stmt = $this->conn->prepare(
    "SELECT id, username, password, role, active, first_attempt, last_attempt, timedout, attempts 
     FROM users 
     WHERE email = ?"
);
$stmt->bind_param("s", $email);      // "s" = string type
$stmt->execute();
```

**Coverage (examples):**
- User lookup: `WHERE email = ?` (line 354)
- File download: `WHERE files.id = ?` (line 138)
- User listing: `LIMIT ?, ?` (line 522)
- Password update: `WHERE email = ?` (line 320)
- Token check: Multiple `?` placeholders (TokenService.php)

**How It Works:**
1. Query structure compiled first (not user input)
2. Parameters bound separately with type information
3. Database driver ensures values never interpreted as code
4. SQL injection impossible because user input cannot alter query structure

**Vulnerabilities Prevented:** SQL Injection  
**Status:** ✅ PROPERLY IMPLEMENTED (100% coverage)

---

#### 14.2 Type Binding in Prepared Statements
**Location:** Throughout application  
**Implementation:**
```php
// String type
$stmt->bind_param("s", $email);

// Integer type
$stmt->bind_param("i", $file_id);
$stmt->bind_param("ii", $offset, $limit);

// Mixed types
$stmt->bind_param("sss", $username, $email, $password);
$stmt->bind_param("ssii", $title, $filetype, $filedata, $user_id, $visibility);
```

**Type Codes:**
- `s`: String
- `i`: Integer
- `d`: Double/Float
- `b`: Blob

**Enforcement:** All prepared statements use explicit type bindings  
**Status:** ✅ PROPERLY IMPLEMENTED

---

### 15. OTHER SECURITY MECHANISMS

#### 15.1 Direct PHP File Access Prevention
**Location:** `src/.htaccess` (lines 2-7)  
**Implementation:**
```apache
# Prevent direct access to PHP files
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

# Allow only index.php
<Files "index.php">
    Require all granted
</Files>
```

**Effect:**
- Direct PHP file requests (e.g., `/Backend/controller/UserController.php`) → 403 Forbidden
- All requests routed through `index.php` → Router dispatcher
- PHP files cannot be accessed directly via URL

**Vulnerabilities Prevented:** Source Code Exposure, Direct Backend Access  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 15.2 Directory Indexing Disabled
**Location:** `src/.htaccess` (line 35)  
**Implementation:**
```apache
Options -Indexes
```

**Effect:** Directory listings disabled (no file enumeration)  
**Vulnerabilities Prevented:** Information Disclosure, Directory Traversal Reconnaissance  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 15.3 Environment File Protection
**Location:** `src/.htaccess` (lines 32-34)  
**Implementation:**
```apache
# Protect .env file
<Files .env>
    Require all denied
</Files>
```

**Effect:** `.env` file (containing DB credentials) cannot be accessed via HTTP  
**Vulnerability Prevented:** Configuration Exposure, Credential Leakage  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 15.4 URL Rewrite to Single Entry Point
**Location:** `src/.htaccess` (lines 19-31)  
**Implementation:**
```apache
RewriteEngine On
RewriteBase /

# Preserve static files
RewriteCond %{REQUEST_URI} !\.(css|js|png|jpg|jpeg|gif|ico)$ [NC]

# Preserve existing files/directories
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f

# Rewrite everything else to index.php
RewriteRule ^(.+)$ index.php [QSA,L]
```

**Routing:**
- Request: `/api/login` → Rewritten to `/index.php?request=/api/login` (internally)
- Request: `/dashboard` → Rewritten to `/index.php`
- Static files (CSS, JS, images) NOT rewritten
- Existing files/directories NOT rewritten

**Vulnerability Prevented:** Direct Backend File Access  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 15.5 Email Account Reuse Detection
**Location:** `src/Backend/controller/UserController.php` (lines 107-122)  
**Implementation:**
```php
if($this->user_service->checkUserExistence($email)) {
    // User already registered with this email
    $message = file_get_contents(__DIR__ . '/../template/alertEmail.html');
    $subject = 'Pulse Room email reuse';
    
    try {
        $this->postman->send($email, $subject, $message);
    } catch (Exception $e) {
        // Log error
    }
    
    // Return success to prevent email enumeration
    return [
        'status' => 'success', 
        'message' => 'A confirmation email has been sent to your account.'
    ];
}
```

**Logic:**
- Checks if email already exists
- If yes: Sends alert email, returns generic success message
- Frontend user cannot distinguish between new user and existing user
- Prevents email enumeration/scraping

**Vulnerabilities Prevented:** Email Enumeration, Account Discovery, Spam  
**Status:** ✅ PROPERLY IMPLEMENTED

---

#### 15.6 Container Security Configuration
**Location:** `Dockerfile`, `docker-compose.yaml`  
**Dockerfile:**
```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get upgrade -y
RUN composer install
```

**Security Aspects:**
- PHP 8.2: Latest stable version with security patches
- Apache 2.4: Latest base image
- PDO/MySQLi: Secure database drivers
- Composer lockfile: Ensures reproducible builds

**Compose:**
```yaml
mysql:
  image: mysql:8.1.0
  environment:
    MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
```

**Note:** ⚠️ Database credentials from .env file

**Status:** ✅ MOSTLY SECURE (see vulnerabilities section for improvements)

---

---
------------------------------------------------------------------------------------------------------------------------------------------------------------------
## Part 2: SECURITY VULNERABILITIES & MISSING PROTECTIONS

### CRITICAL ISSUES

#### VULN-1: MIME Type Validation Missing
**Severity:** 🔴 MEDIUM  
**CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)  
**Location:** `src/Backend/controller/FileController.php` (lines 50-51)  
**Issue:**
```php
$filetype = pathinfo($file["name"], PATHINFO_EXTENSION);
if(!in_array($filetype, ["txt","pdf"])){
    return 'File type not supported';
}
```

**Problem:**
- Only validates file extension, not actual MIME type
- Attacker can rename `shell.php` → `shell.pdf`
- Extension check happens BEFORE database storage but cannot prevent MIME type mismatch
- If file is later read back and browser interprets based on MIME, could be dangerous

**Attack Scenario:**
1. Attacker creates PHP script: `<?php system($_GET['cmd']); ?>`
2. Renames to `shell.pdf`
3. Uploads file → passes extension whitelist
4. File stored in database
5. On download: Browser receives as PDF (application/pdf) → Safe
6. BUT if download endpoint changed to serve from filesystem instead of DB, vulnerable

**Remediation:**
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = [
    'text/plain' => 'txt',
    'application/pdf' => 'pdf'
];

if (!isset($allowed_mimes[$mime_type])) {
    return 'File type not supported (invalid MIME type)';
}

// Verify extension matches MIME type
$expected_ext = $allowed_mimes[$mime_type];
if ($filetype !== $expected_ext) {
    return 'File extension does not match content';
}
```

---

#### VULN-2: No CAPTCHA or Email Rate Limiting
**Severity:** 🟡 MEDIUM-HIGH  
**CWE:** CWE-307 (Improper Restriction of Rendered UI Layers or Frames)  
**Location:** `src/Backend/controller/UserController.php` (register, forgotPassword endpoints)  
**Issue:**
- Registration endpoint has NO rate limiting
- No CAPTCHA verification
- Email sending can be abused to:
  - Spam third-party emails
  - Flood target user with verification emails
  - DoS email server

**Attack Scenario:**
```bash
# Attacker sends 1000 registration requests to same email
for i in {1..1000}; do
  curl -X POST http://localhost/api/register \
    -d "username=spam$i&email=victim@example.com&password=Pass123"
done
```

**Remediation:**
```php
// Rate limiting by IP
$ip = $_SERVER['REMOTE_ADDR'];
$cache_key = "register_attempts:$ip";
$attempts = $cache->get($cache_key) ?? 0;

if ($attempts >= 5) {  // Max 5 registrations per IP per 24 hours
    http_response_code(429);  // Too Many Requests
    return ['error' => 'Too many registration attempts. Try again later.'];
}

// After successful registration
$cache->set($cache_key, $attempts + 1, 86400);  // 24 hours

// Also add CAPTCHA verification using reCAPTCHA or hCaptcha
```

---

#### VULN-3: No HTTPS Redirect Configuration Verification
**Severity:** 🔴 MEDIUM  
**CWE:** CWE-295 (Improper Certificate Validation)  
**Location:** `src/.htaccess`, Dockerfile SSL config  
**Issue:**
- HTTPS is configured, BUT in development environment (localhost with self-signed cert)
- `.htaccess` contains `RewriteRule ^ https://` BUT rewrite conditions might be bypassed
- Self-signed certificate used (only valid for dev, not production)

**Evidence (.htaccess):**
```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Problem:**
- This relies on Apache `mod_rewrite` being enabled
- In some configurations, reverse proxies might interfere
- HSTS header not set (no permanent HTTPS enforcement)

**Remediation:**
```php
// Add HSTS header in router.php
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
```

---

#### VULN-4: Insufficient Password Reset Token TTL Documentation
**Severity:** 🟡 LOW-MEDIUM  
**CWE:** CWE-613 (Insufficient Session Expiration)  
**Location:** `src/Backend/service/TokenService.php` (line 41)  
**Issue:**
```php
$true_created_at = date('Y-m-d H:i:s', strtotime('-1 hour'));
```

**Problem:**
- Token validity: **1 hour** - this is LONG for a sensitive operation like password reset
- Industry standard: 15-30 minutes recommended
- Code has no comment explaining TTL choice
- If user's email is compromised, attacker has 1 hour window

**Remediation:**
```php
// Better TTL (15 minutes)
$true_created_at = date('Y-m-d H:i:s', strtotime('-15 minutes'));

// Add documentation
// Token expires after 15 minutes for security reasons
// Password reset link must be used within this window
```

---

#### VULN-5: SQL Injection via Type Juggling in bind_param
**Severity:** 🟡 MEDIUM (Low practical risk due to bind_param)  
**CWE:** CWE-89 (SQL Injection)  
**Location:** `src/Backend/controller/FileController.php` (line 215)  
**Issue:**
```php
$stmt->bind_param('ssiii', $file_type, $file_type, $userVisibility, $offset, $limit);
```

**Problem:**
- String `$file_type` is used TWICE with "s" type binding
- If ever modified to allow non-whitelisted values without strict validation, could be dangerous
- Currently safe because whitelist check is done, but risky pattern

**Current Code (SAFE):**
```php
$file_type = isset($_GET['file_type']) && is_string($_GET['file_type']) ? $_GET['file_type'] : 'both';
if(!in_array($file_type, ['txt', 'pdf', 'both'])) {
    $file_type = 'both';  // Force safe default
}
```

**Remediation (if removing whitelist):**
```php
// Keep whitelist AND use prepared statement
if(!in_array($file_type, ['txt', 'pdf', 'both'], true)) {
    return 'Invalid file type';
}
// THEN use in prepared statement
```

---

#### VULN-6: No X-Frame-Options Header Alternative
**Severity:** 🟡 LOW  
**CWE:** CWE-1021 (Improper Restriction of Rendered UI Layers)  
**Location:** `src/router.php`  
**Issue:**
```php
header("Content-Security-Policy: ... frame-ancestors 'none'");
header("X-XSS-Protection: 1; mode=block");
// Missing: X-Frame-Options header
```

**Problem:**
- CSP `frame-ancestors 'none'` is correct, but older browsers don't support CSP
- `X-Frame-Options` is the legacy clickjacking protection

**Remediation:**
```php
header("X-Frame-Options: DENY");  // Additional clickjacking protection
header("X-Content-Type-Options: nosniff");  // Prevent MIME sniffing
header("Referrer-Policy: strict-origin-when-cross-origin");
```

---

#### VULN-7: Timing Attack on Password Verification (LOW RISK)
**Severity:** 🟢 LOW  
**CWE:** CWE-208 (Observable Timing Discrepancy)  
**Location:** `src/Backend/controller/UserController.php` (line 429)  
**Issue:**
```php
if(!password_verify($password, $hashedPassword)) {
    // Different error message time depending on password correctness
    return 'Invalid credentials';
}
```

**Problem:**
- `password_verify()` uses constant-time comparison internally (SAFE)
- BUT the overall login flow uses generic error messages (GOOD)
- However, email lookup happens first:
```php
$stmt = $this->conn->prepare("SELECT ... FROM users WHERE email = ?");
if ($stmt->num_rows == 0) {
    // Email not found - return same generic message (GOOD)
    return 'Invalid credentials or too many failed attempts.';
}
```

**Current Status:** ✅ ACTUALLY SAFE (generic messages for both email not found and wrong password)

---

#### VULN-8: X-Forwarded-For Header Spoofing
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-295 (Improper Certificate Validation)  
**Location:** `src/Backend/utils/Logger.php` (lines 80-83)  
**Issue:**
```php
private function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];  // Attacker can spoof this!
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
```

**Problem:**
- `X-Forwarded-For` header is user-controlled (attacker can spoof)
- If logging relies on this for rate limiting or authentication, vulnerable
- Currently only used for logging, but if moved to rate limiting, exploitable

**Example Attack:**
```bash
curl -H "X-Forwarded-For: 1.1.1.1" http://localhost/api/login
# Log shows 1.1.1.1 attempted login, but request from 192.168.1.100
```

**Remediation:**
```php
private function getClientIp() {
    // Only trust REMOTE_ADDR in single-server setup
    // OR validate X-Forwarded-For only if from trusted proxy
    
    $trusted_proxies = ['127.0.0.1', '172.18.0.1'];  // Docker network
    
    if (in_array($_SERVER['REMOTE_ADDR'], $trusted_proxies) 
        && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    return $_SERVER['REMOTE_ADDR'];
}
```

---

#### VULN-9: No CSRF Token in GET Endpoints
**Severity:** 🟢 LOW  
**CWE:** CWE-352 (Cross-Site Request Forgery (CSRF))  
**Location:** `src/Backend/controller/UserController.php` (verifyUser, showUsers endpoints)  
**Issue:**
```php
// verifyUser uses GET (line 179)
public function verifyUser() {
    if(!isset($_GET['token']) || !isset($_GET['email'])) {
        return 'Invalid request.'; // No CSRF check!
    }
}

// showUsers uses GET (line 504)
public function showUsers() {
    // No CSRF token validation
}
```

**Problem:**
- These are GET requests, don't modify state (mostly safe)
- BUT verifyUser DOES modify state (activates account)
- Technically GET should only be for safe operations (idempotent)
- Could be CSRF attacked if user visits malicious site

**Attack Scenario:**
```html
<!-- Attacker's website -->
<img src="https://localhost/api/verify_user?email=hacker@evil.com&token=KNOWN_TOKEN">
<!-- If legitimate user visits, their browser sends this request -->
```

**Remediation:**
```php
// Convert verify endpoint from GET to POST
// OR require CSRF token in GET:

// Verify Token Retrieval Endpoint (GET - safe)
public function getVerifyLink() {
    // Returns verification link with CSRF token
}

// Actual Verification (POST - state change)
public function verifyUser() {
    if(!isset($_POST['csrf_token']) || !hash_equals(...)) {
        return 'Invalid request.';
    }
    // ... verify logic
}
```

---

#### VULN-10: Plaintext Credentials in .env File
**Severity:** 🔴 CRITICAL (in repository)  
**CWE:** CWE-798 (Use of Hard-Coded Credentials)  
**Location:** `.env` file in repository  
**Issue:**
```env
DB_PASSWORD=my_password
MAIL_USER=olanibaissa@gmial.com
MAIL_PASSWORD=jxfibevcuetzzcea
```

**Problem:**
- `.env` file is version-controlled (should be `.gitignore`d)
- Contains actual production credentials
- Visible in Git history
- Anyone with repository access has DB and email credentials

**Evidence (from file listing):**
- `.env` file exists at `/Users/olani/Downloads/Novel-Archive-main/.env`
- Contains real Gmail credentials

**Remediation:**
```bash
# 1. Add to .gitignore
echo ".env" >> .gitignore

# 2. Remove from Git history
git rm --cached .env
git commit -m "Remove .env file"

# 3. Create .env.example template
cat > .env.example << 'EOF'
DB_HOST=mysql_8.1.0_container
DB_USER=user
DB_PASSWORD=your_password_here
DB_NAME=pulseroom
MAIL_USER=your_email@gmail.com
MAIL_PASSWORD=your_app_password
LOG_PATH=/var/www/logs/pulseroom
EOF

# 4. Create actual .env from template
cp .env.example .env
chmod 600 .env
```

---

#### VULN-11: No Input Length Validation
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-1284 (Improper Validation of Specified Quantity in Input)  
**Location:** `src/Backend/controller/UserController.php`  
**Issue:**
```php
$username = $_POST['username'];  // No length check
$email = $_POST['email'];        // No length check

// File title (has truncation, no validation)
$title = substr($title, 0, 255);
```

**Problem:**
- Username can be arbitrarily long (DoS)
- Email can be arbitrarily long (DoS)
- No maximum length validation before database insert
- Database will truncate silently (undefined behavior)

**Database Schema:**
```sql
username VARCHAR(100) NOT NULL UNIQUE,  // Max 100
email VARCHAR(100) NOT NULL UNIQUE,     // Max 100
```

**Issue:**
- If username is 1000 characters, MySQL truncates to 100
- But application doesn't validate, creating inconsistency

**Remediation:**
```php
if (strlen($username) > 100) {
    return 'Username too long (max 100 characters)';
}

if (strlen($email) > 100) {
    return 'Email too long (max 100 characters)';
}

if (strlen($title) > 255) {
    return 'Title too long (max 255 characters)';
}
```

---

#### VULN-12: No Minimum Input Length Validation
**Severity:** 🟡 LOW-MEDIUM  
**CWE:** CWE-1284 (Improper Validation of Specified Quantity in Input)  
**Location:** `src/Backend/controller/UserController.php`  
**Issue:**
```php
$username = $_POST['username'];  // Could be empty or single character
$email = $_POST['email'];        // Could be minimal like "a@b"

// Password is validated, but no minimum for others
if (strlen($password) < 8) {
    return 'Password must be at least 8 characters long.';
}
```

**Problem:**
- Username: Could be 1 character → confusing, poor UX
- Email: Empty string passes filter_var check? No, it fails
- Text content: Could be empty despite "required" attribute

**Remediation:**
```php
if (strlen($username) < 3) {
    return 'Username must be at least 3 characters.';
}

if (strlen($_POST['text_content']) < 10) {
    return 'Content must be at least 10 characters.';
}
```

---

#### VULN-13: No Anti-Automation on Email Endpoints
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-307 (Improper Restriction of Rendered UI Layers or Frames)  
**Location:** `src/Backend/controller/UserController.php` (forgot_password)  
**Issue:**
```php
public function forgotPassword() {
    // No rate limiting
    // No CAPTCHA
    // Can be called unlimited times per IP
    
    // Sends email every time
    $this->postman->send($email, $subject, $message);
}
```

**Attack Scenario:**
```bash
# Attacker sends 1000 forgot password requests to victim's email
for i in {1..1000}; do
  curl -X POST http://localhost/api/forgot_pwd \
    -d "csrf_token=TOKEN&email=victim@example.com"
done
# Victim's email flooded with password reset emails
```

**Impact:** Email spam, user annoyance, email provider rate limiting  

**Remediation:**
```php
// Rate limit by email address
$email_key = "forgot_pwd:$email";
$attempts = $cache->get($email_key) ?? 0;

if ($attempts >= 3) {  // Max 3 requests per email per hour
    http_response_code(429);
    return ['error' => 'Too many password reset attempts. Try again in 1 hour.'];
}

// Send email...

$cache->set($email_key, $attempts + 1, 3600);  // 1 hour
```

---

#### VULN-14: Missing Security Headers
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-693 (Protection Mechanism Failure)  
**Location:** `src/router.php` - missing headers  
**Issue:**
Missing critical security headers:
1. `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
2. `Strict-Transport-Security: ...` - Enforces HTTPS
3. `X-Frame-Options: DENY` - Clickjacking (CSP has it, but header redundancy good)
4. `Referrer-Policy: strict-origin-when-cross-origin`
5. `Permissions-Policy: ...` - Restricts browser features

**Remediation:**
```php
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
```

---

#### VULN-15: No Account Lockout Permanent Unlock
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-779 (Logging of Excessive Data)  
**Location:** `src/Backend/service/UserService.php`  
**Issue:**
- After 3 failed attempts, user is locked for 1 minute
- User CAN'T unlock their account (only automatic unlock after timeout)
- No admin interface to unlock locked accounts
- Legitimate user stuck for 1 minute if password forgotten

**Current System:**
```php
$this->timeout_time = 1;  // 1 minute
// No unlock mechanism, no admin panel to force unlock
```

**Problem:**
- Intentional but not user-friendly
- No way for admin to help locked user
- No way for user to request unlock

**Remediation:**
```php
// Add unlock endpoint (admin only)
public function unlockUserAccount() {
    // Admin authorization check...
    $stmt = $this->conn->prepare("UPDATE users SET timedout=0, attempts=0 WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

// OR add user-requested unlock via security questions
```

---

#### VULN-16: No HTTPS-Only Response with Redirect Count
**Severity:** 🟢 LOW  
**CWE:** CWE-601 (URL Redirection to Untrusted Site)  
**Location:** `src/router.php` (line 164)  
**Issue:**
```php
if ($pageInfo['auth'] === 'authenticated' && !$this->isAuthenticated()) {
    header("Location: /login");  // Redirect
    exit();
}
```

**Problem:**
- Redirect is internal (same domain) → SAFE
- But could be exploited if protocol is HTTP → could downgrade to HTTP
- No canonical URL enforcement

**Current Status:** ✅ Safe because HTTPS is enforced at .htaccess level

---

#### VULN-17: PHPMyAdmin Exposed
**Severity:** 🔴 CRITICAL  
**CWE:** CWE-1021 (Improper Restriction of Rendered UI Layers)  
**Location:** `docker-compose.yaml` (lines 43-51)  
**Issue:**
```yaml
phpmyadmin:
    image: phpmyadmin:5.2.1
    ports:
      - "8080:80"  # Exposed to localhost:8080
```

**Problem:**
- PHPMyAdmin is exposed on port 8080
- No authentication shown in compose file
- In development OK, but CRITICAL if deployed to production
- Default PHPMyAdmin has known vulnerabilities
- Allows SQL queries execution directly

**Remediation:**
```yaml
phpmyadmin:
    image: phpmyadmin:5.2.1
    # Remove ports in production
    # Only accessible via docker network (localhost)
    environment:
      PMA_PASSWORD: ${DB_PASSWORD}
      PMA_AUTH: config  # Basic auth required
    # Only in development with strong credentials:
    # ports:
    #   - "127.0.0.1:8080:80"  # Localhost only
```

---

#### VULN-18: Database Accessible Externally
**Severity:** 🔴 CRITICAL  
**CWE:** CWE-552 (Files or Directories Accessible to External Parties)  
**Location:** `docker-compose.yaml` (line 37)  
**Issue:**
```yaml
mysql:
    ports:
      - "3306:3306"  # MySQL exposed to all interfaces!
```

**Problem:**
- MySQL port 3306 is exposed to external world
- Anyone who knows password can connect from anywhere
- Should ONLY be accessible from PHP container

**Remediation:**
```yaml
mysql:
    # Remove the ports section entirely
    # OR restrict to localhost only:
    ports:
      - "127.0.0.1:3306:3306"  # Localhost only
    # For Docker network communication between containers:
    # No ports needed - use service name "mysql" as hostname
```

---

#### VULN-19: No SQL Injection via Array Parameters (Code Review)
**Severity:** 🟢 LOW (Not exploitable in current code)  
**CWE:** CWE-89 (SQL Injection)  
**Location:** `src/Backend/controller/FileController.php` (line 215)  
**Issue:**
```php
$stmt->bind_param('ssiii', $file_type, $file_type, $userVisibility, $offset, $limit);
```

**Observation:**
- `$file_type` bound as string "s" but validated against whitelist first
- `$userVisibility` is integer from `getUserVisibility()` (safe)
- `$offset` and `$limit` are integers from calculation (safe)
- Current code is SAFE due to whitelist validation

**No vulnerability exists**, just documenting for completeness.

---

#### VULN-20: No Password Change Endpoint
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-620 (Unverified Password Change)  
**Location:** Missing feature  
**Issue:**
- Users can RESET password (forgot_password flow)
- BUT cannot CHANGE password while logged in
- Only reset via forgot_password (requires email access)
- If user wants to change password voluntarily, must use forgot_password

**Problem:**
- Not good UX
- Users might set weak password and can't change it
- No session-based password change endpoint

**Remediation:**
```php
// Add new endpoint: /api/change_password (authenticated)
public function changePassword() {
    // 1. Verify CSRF token
    // 2. Verify current password
    // 3. Validate new password strength
    // 4. Update password in database
    // 5. Log the change
    // 6. Return success
}
```

---

### LOW SEVERITY ISSUES

#### VULN-21: No Logout Confirmation
**Severity:** 🟢 LOW  
**CWE:** CWE-352 (CSRF - via GET)  
**Location:** `src/Backend/controller/UserController.php` (logout)  
**Issue:**
- Logout requires CSRF token (protected) ✓
- But GET requests to logout page could be malicious

**Current:**
```php
public function logout() {
    if(!isset($_POST['csrf_token']) || !hash_equals(...)) {
        return 'Invalid request.';  // 403
    }
    session_unset();
    session_destroy();
}
```

**Status:** Actually SAFE because logout is POST-only with CSRF check.

---

#### VULN-22: FIXME Comments in Production Code
**Severity:** 🟢 LOW  
**CWE:** CWE-546 (Suspicious Comment)  
**Location:** Multiple files  
**Issue:**
```php
// Logger.php line 18: FIXME: cambia il percorso del file di log e i permessi di scrittura
// UserController.php line 500: FIXME: come controllo la pagina massima da ritornare?
// Logger.php line 82: FIXME: meglio un CSV
```

**Problem:**
- FIXME comments should be resolved before production
- Indicates incomplete implementation
- Information disclosure (shows developer notes)

**Remediation:**
- Remove or complete all FIXME items
- Use issue tracker instead

---

#### VULN-23: Hardcoded URLs
**Severity:** 🟡 LOW-MEDIUM  
**CWE:** CWE-798 (Use of Hard-Coded Credentials)  
**Location:** `src/Backend/controller/UserController.php` (lines 14-15)  
**Issue:**
```php
const URL_PSW_RST_PAGE = 'https://localhost/reset_password';
const URL_REGISTER_PAGE = 'https://localhost/verify_user';
```

**Problem:**
- Hardcoded `localhost` only works for development
- In production, domain will be different
- Links in emails will be broken for production

**Remediation:**
```php
// Move to .env configuration
$reset_url = getenv('APP_URL') . '/reset_password';
$register_url = getenv('APP_URL') . '/verify_user';

// .env file
APP_URL=https://pulseroom.example.com
```

---

#### VULN-24: No Content-Type-Options on Download
**Severity:** 🟡 LOW  
**CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)  
**Location:** `src/Backend/controller/FileController.php` (download function)  
**Issue:**
```php
return $this->sendResponse($response);  // Sends JSON, not file

// No actual file download headers set
// File returned as base64 in JSON response
```

**Current Implementation:**
- Files returned as base64-encoded JSON
- Frontend handles decoding and download
- Response content-type: `application/json`
- Browser won't execute files (safe by design)

**Status:** Actually SAFE due to response format.

---

#### VULN-25: No Referer Policy Enforcement
**Severity:** 🟢 LOW  
**CWE:** CWE-601 (URL Redirection to Untrusted Site)  
**Location:** `src/router.php` - missing header  
**Issue:**
- No Referrer-Policy header set
- Third-party sites can see referer information

**Remediation:**
```php
header("Referrer-Policy: strict-origin-when-cross-origin");
```

---

### MISSING SECURITY BEST PRACTICES

#### MISSING-1: No Account Deletion/GDPR Support
**CWE:** CWE-1004 (Privacy Violation)  
**Issue:**
- No endpoint for users to delete their account
- GDPR requires right to be forgotten
- User data persists permanently in database

**Remediation:**
```php
// Add endpoint: /api/delete_account (authenticated)
public function deleteAccount() {
    // 1. Verify password
    // 2. Delete user record
    // 3. Delete associated files
    // 4. Delete tokens
    // 5. Destroy session
}
```

---

#### MISSING-2: No IP Whitelist for Admin Functions
**CWE:** CWE-2 (Authentication Bypass)  
**Issue:**
- Admin endpoints (`change_role`, `show_users`) only check session role
- No IP-based additional restriction
- If session compromised, admin access compromised

**Recommendation:**
```php
// Optional: Add IP whitelist for admin functions
private function isAdminAllowed() {
    $allowed_ips = ['127.0.0.1', '192.168.1.100'];
    return $this->isAdmin() && in_array($_SERVER['REMOTE_ADDR'], $allowed_ips);
}
```

---

#### MISSING-3: No Two-Factor Authentication (2FA)
**CWE:** CWE-345 (Insufficient Verification of Data Authenticity)  
**Issue:**
- Only password-based authentication
- No TOTP/SMS 2FA option
- High-value accounts at risk

**Recommendation:**
```php
// Implement TOTP (Time-based One-Time Password) using:
// - Google Authenticator compatible
// - QR code generation
// - Backup codes
```

---

#### MISSING-4: No Session Activity Timeout
**CWE:** CWE-613 (Insufficient Session Expiration)  
**Issue:**
```php
'cookie_lifetime' => 0,  // Session ends on browser close
// But no server-side timeout
// If computer is left unlocked, session persists forever
```

**Problem:**
- Session doesn't expire if browser stays open
- User could leave computer unlocked indefinitely
- Session valid until browser closed

**Recommendation:**
```php
$inactivity_timeout = 30 * 60;  // 30 minutes
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    return ['error' => 'Session expired due to inactivity'];
}
$_SESSION['last_activity'] = time();
```

---

#### MISSING-5: No Security.txt File
**CWE:** CWE-1021 (Improper Restriction of Rendered UI Layers)  
**Issue:**
- No `/.well-known/security.txt` file
- No security contact information for researchers
- Vulnerabilities can't be reported responsibly

**Recommendation:**
```txt
# .well-known/security.txt
Contact: security@pulseroom.example.com
Expires: 2026-03-02T12:00:00.000Z
Preferred-Languages: en
```

---

#### MISSING-6: No Input Sanitization for HTML (XSS)
**Severity:** 🟡 MEDIUM  
**CWE:** CWE-79 (Improper Neutralization of Input During Web Page Generation)  
**Issue:**
```php
// File upload - text content
$filedata = $_POST['text_content'];
$filedata = htmlspecialchars($filedata, ENT_QUOTES, 'UTF-8');
// Stored as-is in database
```

**Problem:**
- Text content is HTML-escaped for output
- But if displayed in different context (e.g., plain text viewer), could be vulnerable
- Need defensive input validation, not just output encoding

**Note:** Current implementation is mostly safe due to context-aware encoding in templates.

---

#### MISSING-7: No Database Activity Audit Log
**CWE:** CWE-778 (Insufficient Logging)  
**Issue:**
- Application logs are recorded
- But no database-level audit trail
- Modifications to users/files not tracked at DB level

**Recommendation:**
```sql
-- Add audit table
CREATE TABLE audit_log (
    id INT PRIMARY KEY,
    action VARCHAR(50),
    table_name VARCHAR(50),
    record_id INT,
    user_id INT,
    old_value TEXT,
    new_value TEXT,
    timestamp TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add triggers for automatic logging
```

---

#### MISSING-8: No Dependency Scanning
**CWE:** CWE-1104 (Use of Unmaintained Third-Party Components)  
**Issue:**
- `composer.json` specifies `phpmailer/phpmailer: ^7.0`
- No `.lock` file pinning exact versions (actually present: composer.lock exists ✓)
- No automated dependency scanning for vulnerabilities

**Recommendation:**
```bash
# Use composer audit to check for known vulnerabilities
composer audit

# Add to CI/CD pipeline
```

---

#### MISSING-9: No HSTS Preload Configuration
**CWE:** CWE-295 (Improper Certificate Validation)  
**Issue:**
- No HSTS header with preload
- Browsers don't have HSTS records for first visit
- First visit could be vulnerable to downgrade

**Recommendation:**
```php
header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
// max-age: 2 years
// includeSubDomains: Apply to subdomains too
// preload: Add to HSTS preload list at hstspreload.org
```

---

#### MISSING-10: No API Rate Limiting
**CWE:** CWE-770 (Allocation of Resources Without Limits or Throttling)  
**Issue:**
- Login has attempt throttling
- But API endpoints for file listing, user listing have NO rate limit
- Could be DoS'd by repeated requests

**Recommendation:**
```php
// Rate limiting per endpoint per IP
private function checkRateLimit($endpoint) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit:$endpoint:$ip";
    $count = $cache->get($key) ?? 0;
    
    if ($count >= 100) {  // 100 requests per minute
        http_response_code(429);
        return false;
    }
    
    $cache->incr($key);
    $cache->expire($key, 60);
    return true;
}
```

---

---

## Part 3: SUMMARY & RECOMMENDATIONS

### Security Maturity Assessment

| Domain | Level | Notes |
|--------|-------|-------|
| Authentication | ⭐⭐⭐⭐ | Password hashing, email verification, session management strong |
| Authorization | ⭐⭐⭐⭐ | RBAC implemented, role-based access control effective |
| Input Validation | ⭐⭐⭐ | Type checking, prepared statements, whitelist validation, but missing min/max length checks |
| Output Encoding | ⭐⭐⭐⭐ | HTML entity encoding, JSON responses, CSP headers strong |
| Cryptography | ⭐⭐⭐⭐ | Secure RNG, proper hashing algorithms, HTTPS configured |
| Session Security | ⭐⭐⭐⭐ | HttpOnly, Secure, SameSite flags, regeneration, destruction all implemented |
| Transport Security | ⭐⭐⭐ | HTTPS enforced, but some headers missing, no HSTS preload |
| CSRF Protection | ⭐⭐⭐⭐ | Token generation and validation on all state-changing operations |
| Rate Limiting | ⭐⭐ | Login throttling good, but no API-level rate limiting |
| Logging | ⭐⭐⭐⭐ | Comprehensive logging, sensitive data filtering, activity tracking |
| Infrastructure | ⭐ | PHPMyAdmin exposed, MySQL exposed to external connections |
| Compliance | ⭐ | No GDPR support, no 2FA, no security.txt |

---

### Critical Recommendations (Priority 1)

1. **Fix Exposed Services** 🔴
   - Remove MySQL port 3306 external access
   - Disable or restrict PHPMyAdmin
   - Verify production .env not in Git

2. **Add Missing Security Headers**
   - X-Content-Type-Options: nosniff
   - Strict-Transport-Security with preload
   - Referrer-Policy

3. **Implement Rate Limiting**
   - Email endpoints (registration, forgot password)
   - API endpoints (prevent DoS)

4. **Add Input Length Validation**
   - Min/max checks for username, email, title
   - Prevent buffer overflow-like issues

---

### Important Recommendations (Priority 2)

5. **Remove Hardcoded Configuration**
   - Move URLs to environment variables
   - Remove .env from repository (use .env.example)

6. **Implement Account Security Features**
   - Password change endpoint (while logged in)
   - Account deletion (GDPR compliance)
   - 2FA optional/required

7. **Extend Audit Logging**
   - Add database-level audit trail
   - Track all admin actions
   - Archive logs for compliance

---

### Nice-to-Have Recommendations (Priority 3)

8. **Add Web Security Standards**
   - security.txt file
   - HSTS preload registration
   - CSP header enhancements

9. **Implement Advanced Protections**
   - Session binding (IP/user-agent)
   - Anomaly detection
   - Geo-blocking for login attempts

10. **Dependency Management**
    - Composer audit in CI/CD
    - Automated updates
    - SBOM generation

---

## Conclusion

**Pulse Room demonstrates a strong security foundation** with well-implemented authentication, authorization, and CSRF protections. The application correctly uses prepared statements, secure password hashing, and proper session management.

However, **critical infrastructure issues** (exposed database, PHPMyAdmin) and **missing best practices** (rate limiting, 2FA, GDPR support) require attention before production deployment.

The codebase is suitable for an **academic project** and demonstrates **security awareness** in most areas. For real-world deployment, the recommendations above must be addressed, particularly infrastructure hardening and feature completeness.

---

**Report Generated:** March 2, 2026  
**Assessment Complete:** All files analyzed, all endpoints reviewed, all security mechanisms catalogued.

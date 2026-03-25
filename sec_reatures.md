## Security Measures Checklist

- [x] **SQL Injection Defense**  
  Prepared statements are used throughout:
  - `UserController.php` (line 125, 381)
  - `FileController.php` (line 109)
  - `UserService.php` (line 17)
  - `TokenService.php` (line 19)

- [x] **CSRF Defense**  
  - Session CSRF token generated in `router.php` (line 199)  
  - Validated on:
    - register/login/logout/reset → `UserController.php` (line 61, 201, 264)
    - upload/delete → `FileController.php` (line 18, 301)  
  - Inserted into forms:
    - `login.php` (line 28)
    - `register.php` (line 29)

- [x] **XSS Defense**  
  - Output encoding with `htmlspecialchars()`:
    - `FileController.php` (line 73, 98)
    - `navbar.php` (line 28)  
  - Security headers:
    - CSP + X-XSS-Protection → `router.php` (line 205)

- [x] **Session Hijacking Mitigation**  
  - Cookies: `HttpOnly`, `Secure`, `SameSite=Lax` → `router.php` (line 190)  
  - Session ID regeneration after login → `UserController.php` (line 461)  
  - Session destruction on logout → `UserController.php` (line 490)

- [x] **Weak Auth / Brute-Force Defense**  
  - Password policy + `zxcvbn` strength check → `UserController.php` (line 32)  
  - Password hashing (`bcrypt`) → `UserController.php` (line 125)  
  - Login throttling → `UserService.php` (line 7, 90)

- [x] **Email Verification / Reset Token Security**  
  - Secure tokens via `random_bytes()`  
  - 1-hour TTL → `TokenService.php` (line 12)

- [x] **Access Control / Hidden Content Protection**  
  - Route auth checks → `router.php` (line 93)  
  - Admin-only endpoints → `router.php` (line 74)  
  - Premium content visibility → `FileController.php` (line 183)  
  - Owner-only delete enforcement → `FileController.php` (line 345)

- [x] **Hidden Content / Direct Access Hardening**  
  - Direct PHP access blocked  
  - `.env` blocked  
  - Directory listing disabled  
  - HTTPS redirect → `src/.htaccess` (line 3)

- [x] **MITM Transport Defense**  
  - HTTPS redirect → `src/.htaccess` (line 16)  
  - TLS 1.2+ enforced → `ssl-params.conf` (line 1)

- [x] **File Upload Validation**  
  - Size/type/name checks → `FileController.php` (line 47)
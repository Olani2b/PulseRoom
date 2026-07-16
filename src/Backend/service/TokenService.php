<?php

class TokenService
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function generateToken(int $n_bytes = 32)
    {
        return bin2hex(random_bytes($n_bytes));
    }

    public function storeToken($token, $email, $purpose = 'register')
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->conn->prepare("INSERT INTO tokens (email, token_hash, purpose) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), purpose = VALUES(purpose), created_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sss", $email, $tokenHash, $purpose);
        $executed = $stmt->execute();
        $stmt->close();
        
        return $executed;
    }

    public function deleteToken($email, $purpose)
    {
        $stmt = $this->conn->prepare("DELETE FROM tokens WHERE email = ? AND purpose = ?");
        $stmt->bind_param("ss", $email, $purpose);
        $executed = $stmt->execute();
        $stmt->close();
        return $executed;
    }

    public function checkToken($token, $email, $purpose)
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->conn->prepare("SELECT 1 FROM tokens WHERE token_hash = ? AND email = ? AND purpose = ? AND created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)");
        $stmt->bind_param("sss", $tokenHash, $email, $purpose);
        $stmt->execute();
        $stmt->store_result();
        $rows_check = $stmt->num_rows > 0;
        $stmt->close();
        return $rows_check;
    }
}

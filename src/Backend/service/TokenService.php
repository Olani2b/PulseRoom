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
        $stmt = $this->conn->prepare("INSERT INTO tokens (email, token, purpose) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token)");
        $stmt->bind_param("sss", $email, $token, $purpose);
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
        $true_created_at = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $stmt = $this->conn->prepare("SELECT * FROM tokens WHERE token = ? AND email = ? AND purpose = ? AND created_at > ?");
        $stmt->bind_param("ssss", $token, $email, $purpose, $true_created_at);
        $stmt->execute();
        $stmt->store_result();
        $rows_check = $stmt->num_rows > 0;
        $stmt->close();
        return $rows_check;
    }
}

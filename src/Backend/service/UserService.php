<?php
require_once __DIR__ . '/../utils/PostMan.php';

class UserService
{
    private $conn, $postman;
    public $max_attempts = 3;
    public $timeout_time = 1; // minutes

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->postman = new Postman();
    }
    public function getUsername($email)
    {
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();
        return $username;
    }

    public function checkUserExistence($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $rows_check = $stmt->num_rows > 0;
        $stmt->close();

        // If the user is found, return true; otherwise, return false
        return $rows_check;
    }

    public function checkUsernameExistence($username)
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $rows_check = $stmt->num_rows > 0;
        $stmt->close();

        return $rows_check;
    }

    public function setUserStatus($email, $status)
    {
        // Treat "already in this state" as success too. A repeated password-reset
        // request should not fail just because the account is already inactive.
        $stmt = $this->conn->prepare("UPDATE users SET active = ? WHERE email = ?");
        $stmt->bind_param("is", $status, $email);
        $executed = $stmt->execute();
        $stmt->close();

        return $executed;
    }

    public function updateUserRole($id, $newRole)
    {
        $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $id);
        $executed = $stmt->execute();
        $stmt->close();
        return $executed;
    }

    public function differenceInMinutes($first_dt , $second_dt)
    {
        $interval = $first_dt->diff($second_dt);
        $minutes = ($interval->y * 365 * 24 * 60) +  // Convert years to minutes
           ($interval->m * 30 * 24 * 60) +   // Convert months to minutes (approximately 30 days each)
           ($interval->d * 24 * 60) +        // Convert days to minutes
           ($interval->h * 60) +             // Convert hours to minutes
           $interval->i;
           #$interval->s; // Convert seconds to minutes
        return $minutes;      
        //$interval_seconds = $interval->i * 60 + $interval->s; // Convert the difference to seconds
        //return $interval_seconds / 60; // Convert the difference to minutes
    }

    public function resetAttempts($email)
    {
        $stmt = $this->conn->prepare(
            "UPDATE users
             SET attempts = 0, timedout = 0, last_attempt = NOW()
             WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $executed = $stmt->execute();
        $stmt->close();
        return $executed;
    }

    public function updateLoginAttempts($email, $last_attempt, $timedout, $attempts)
    {
        if($timedout)
            return;
        // Update the login attempts
        // Reset the failure counter when the timeout window has elapsed since the last attempt
        $now = new DateTime();
        $minutes = $this->differenceInMinutes(
            new DateTime($last_attempt),
            $now
        );
        
        if($minutes > $this->timeout_time){
            $this->resetAttempts($email);
            $attempts = 0; 
        }
        $attempts += 1;

        // If the user has reached the maximum number of attempts, set the timedout flag to 1
        if($attempts >= $this->max_attempts){
            $timedout = 1;
            // Send an email with the OTP
            $subject = "Someone tried to access your account";
            $message = file_get_contents(__DIR__ . '/../template/emailTimeout.html');
            $this->postman->send($email, $subject, $message);
        }

        $stmt = $this->conn->prepare(
            "UPDATE users 
             SET timedout = ?, attempts = ?, last_attempt = NOW() 
             WHERE email = ?"
        );
        $stmt->bind_param("iis", $timedout ,$attempts, $email);
        $executed = $stmt->execute();
        $stmt->close();
    }
}

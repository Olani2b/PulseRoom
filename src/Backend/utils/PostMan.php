<?php
/*
PHP code to send an email using PHPMailer
        require '../utils/PostMan.php';
        $postman = new PostMan();
        $postman->send("example@gmail.com", "FUNZIONA", "Possiamo inviare le email, finalmente!");
*/
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require __DIR__.'/../../vendor/autoload.php';
class PostMan {
    // Proprietà della classe
    private $postman;

    public function __construct() {
        $this->postman = new PHPMailer(true);

        //Postman settings
        $this->postman->isSMTP();                                           //Send using SMTP
        $this->postman->Host       = 'smtp.gmail.com';                      //Set the SMTP server to send through
        $this->postman->SMTPAuth   = true;                                  //Enable SMTP authentication
        $this->postman->Username   = getenv('MAIL_USER');             //SMTP username
        $this->postman->Password   = getenv('MAIL_PASSWORD');         //SMTP password
        $this->postman->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        //Enable implicit TLS encryption
        $this->postman->Port       = 587;                                   //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $this->postman->setFrom(getenv('MAIL_USER'), 'NovelArchive');   
        //Content
        $this->postman->isHTML(true);                                  //Set email format to HTML


    }

    public function send($email, $subject, $body) {
        try {
            //echo "Sending email to $email with subject $subject and body $body\n";
            $this->postman->addAddress($email);
            $this->postman->Subject = $subject;
            $this->postman->Body    = $body;
            $this->postman->AltBody = $body;
            $this->postman->send();
            //echo 'Message has been sent';
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$this->postman->ErrorInfo}");
        }
    }
}
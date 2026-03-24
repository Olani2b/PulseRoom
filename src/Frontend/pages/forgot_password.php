<!DOCTYPE html>
<html lang="en">
<head>
    <title>PulseRoom | Forgot password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="./Frontend/imgs/icon.ico">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./Frontend/css/main.css">
    <link rel="stylesheet" href="./Frontend/css/forgot_password.css">
    <script src="./Frontend/js/forgot_pwd.js"></script>
    <script src="./Frontend/js/toast.js"></script>
    <link rel="stylesheet" href="./Frontend/css/toast.css">
    <script src="./Frontend/js/navbar.js"></script>
</head>

<body>
    <div class="toast-container">
        <ul class="notifications"></ul>
    </div>
    <?php require __DIR__ . '/../includes/navbar.php';?>
    <header class="bgimg-1 w3-display-container w3-grayscale-min" id="home">
        <div class="w3-display-left w3-text-white padding48">
            <span class="w3-jumbo w3-hide-small w3-animate-bottom w3-">Reset your password</span><br>
            <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom" >Login</span><br>
            <span class="w3-large w3-animate-bottom">Please, insert your email to reset your password.</span>
        <form id="forgotPwdForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="email" class="w3-input w3-border w3-animate-bottom" name="email" placeholder="Email" required><br>
            <button type="submit" class="w3-button w3-black w3-animate-bottom"><i class="fa fa-envelope"></i> Send Reset Link</button>
        </form>
        </div>
    </header>
</body>
</html>
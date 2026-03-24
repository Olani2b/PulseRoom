<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./Frontend/css/main.css">
    <link rel="stylesheet" href="./Frontend/css/toast.css">
    <link rel="stylesheet" href="./Frontend/css/register.css">

    <link rel="icon" href="./Frontend/imgs/icon.ico">
    <script type="text/javascript" src="./Frontend/js/zxcvbn.js"></script>
    <script src="./Frontend/js/register.js"></script>
</head>
 
<body>
    <div class="toast-container">
        <ul class="notifications"></ul>
    </div>
    <?php require __DIR__ . '/../includes/navbar.php'; ?>
    <header class="bgimg-1 w3-display-container w3-grayscale-min" id="home">
    <div class="w3-display-left w3-text-white padding48">
        <span class="w3-jumbo w3-hide-small w3-animate-bottom">Registration</span><br>
        <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom" >Registration</span><br>
        <span class="w3-large w3-animate-bottom">Insert your credentials to create a new account.</span>
        <form id="registerForm" class="w3-animate-bottom">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="text" class="w3-input w3-border" name="username" placeholder="Username" required><br>
            <input type="email"  class="w3-input w3-border" name="email" placeholder="Email" required><br>
                <div class="password-container">
                    <input type="password"  class="w3-input w3-border" id="password" name="password" placeholder="Password" required>
                    <button type= "button" class="w3-button w3-circle" id="show_psw"><i class="w3-text-black fa fa-eye" id="togglePassword"></i></button> 
                </div>
                <div class="w3-margin-left password-box">
                    <meter max="4" id="password-strength-meter"></meter>  
                    <span class="w3-medium" id="zxcvbn-text"></span>  
                </div>
                <div class="password-container">
                    <input type="password"  class="w3-input w3-border" id="conf_password" name="conf_password" placeholder="Confirm Password" required>
                    <button type="button" class="w3-button w3-circle" id="show_conf"><i class="w3-text-black fa fa-eye" id="togglePassword"></i></button>
                </div>
                <span class="w3-medium w3-margin-left w3-padding-left" id="match_psw"></span> 
            <br>
            <button class="w3-button w3-black w3-animate-bottom" type="submit"><i id="register-icon" class="fa fa-user-plus"></i> REGISTER</button>       
        </form>
    </div>
    </header>
    <script src="./Frontend/js/navbar.js"></script>
    <script src="./Frontend/js/toast.js"></script>
</body>
</html>

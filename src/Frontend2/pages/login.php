<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="./Frontend/imgs/icon.ico">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./Frontend/css/main.css">
    <link rel="stylesheet" href="./Frontend/css/toast.css">
    <link rel="stylesheet" href="./Frontend/css/login.css">

    <script src="./Frontend/js/login.js"></script>
</head>
 
<body>
    <div class="toast-container">
        <ul class="notifications"></ul>
    </div>
    <?php require __DIR__ . '/../includes/navbar.php'; ?>
    <header class="bgimg-1 w3-display-container w3-grayscale-min" id="home">
    <div class="w3-display-left w3-text-white padding48" >
        <span class="w3-jumbo w3-hide-small w3-animate-bottom">Login</span><br>
        <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom" >Login</span><br>
        <span class="w3-large w3-animate-bottom">Insert you email and password to access.</span>
        <form id="loginForm" class="w3-animate-bottom">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="email"  class="w3-input w3-border" name="email" placeholder="Email" required><br>
            <div class="password-container">
                <input type="password"  class="w3-input w3-border" name="password" id="password" placeholder="Password" required>
                <button type="button" class="w3-button w3-circle" id="show_psw"><i class="w3-text-black fa fa-eye" id="togglePassword"></i></button><br> 
            </div>
            <br>
            <button class="w3-button w3-black w3-animate-bottom" type="submit"><i class="fa fa-sign-in"></i> LOGIN</button>
            <a href="/forgot_password"  class="w3-animate-bottom">Forgot password?</a>
        </form>
  </div> 
  
  </header>
  <script src="./Frontend/js/navbar.js"></script>
  <script src="./Frontend/js/toast.js"></script>

</body>
</html>

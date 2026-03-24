<!DOCTYPE html>
<html lang="en">
<head>
    <title>PulseRoom | Reset password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="./Frontend/imgs/icon.ico">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./Frontend/css/main.css">
    <script src="./Frontend/js/reset_password.js"></script>
    <link rel="stylesheet" href="./Frontend/css/toast.css">
    <link rel="stylesheet" href="./Frontend/css/reset_password.css">
    <script type="text/javascript" src="./Frontend/js/zxcvbn.js"></script>
    <script src="./Frontend/js/navbar.js"></script>
    <script src="./Frontend/js/toast.js"></script>
</head>

<body>
    <div class="toast-container">
        <ul class="notifications"></ul>
    </div>
    <?php require __DIR__ . '/../includes/navbar.php';?>
    <header class="bgimg-1 w3-display-container w3-grayscale-min" id="home">
    <input type="hidden" name="csrf" id="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="w3-display-left w3-text-white padding48" id="ResetPswContentId">
    <span class="w3-jumbo w3-hide-small w3-animate-bottom">Insert a new password</span>
        <br>
        <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom">Insert your new password</span>
        <br>
        <span class="w3-xlarge w3-animate-bottom">Use a strong password to increase security.</span>
        <form id="resetpswForm" class="w3-animate-bottom">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            <div class="password-container">
                <input type="password" id="password" class="w3-input w3-border" name="new_password" placeholder="New Password" required>
                <button type="button" class="w3-button w3-circle" id="show_psw">
                    <i class="w3-text-black fa fa-eye" id="togglePassword"></i>
                </button>
            </div>
            <div class="w3-margin-left password-box">
                <meter max="4" id="password-strength-meter"></meter>
                <span id="zxcvbn-text" class="w3-medium"></span>
            </div>
            <div class="password-container">
                <input type="password" id="conf_password" class="w3-input w3-border" name="conf_new_password" placeholder="Confirm Password" required>
                <button type="button" class="w3-button w3-circle" id="show_conf">
                    <i class="w3-text-black fa fa-eye" id="togglePassword"></i>
                </button>
            </div>
            <span class="w3-medium w3-margin-left w3-padding-left" id="match_psw"></span>
            <br><br>
            <button type="submit" class="w3-button w3-black w3-animate-bottom">
            <i class="fa fa-user-plus"></i> RESET PASSWORD
            </button>
        </form>
    </div>
    </header>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Logout</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="./Frontend/imgs/icon.ico">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./Frontend/css/main.css">
    <link rel="stylesheet" href="./Frontend/css/logout.css">
</head>
 
<body>
    <?php require __DIR__ . '/../includes/navbar.php'; ?>
    <header class="bgimg-1 w3-display-container w3-grayscale-min" id="home">
    <input type="hidden" id="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="w3-display-left w3-text-white padding48">
        <span class="w3-jumbo w3-hide-small w3-animate-bottom w3-">Logout</span><br>
        <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom" >Logout</span><br>
        <span class="w3-large w3-animate-bottom">You have successfully logged out, you will be redirected to the homepage in <span id="countdown">3</span> seconds.</span>       
  </div> 
  </header>
  <script src="./Frontend/js/navbar.js"></script>
  <script src="./Frontend/js/logout_timer.js"></script>
</body>
</html>

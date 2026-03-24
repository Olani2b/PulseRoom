<!DOCTYPE html>
<html>
    <head>
    <title>Dashboard</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="./Frontend/css/toast.css">
        <link rel="icon" href="./Frontend/imgs/icon.ico">
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="./Frontend/css/main.css">
        <link rel="stylesheet" href="./Frontend/css/book.css">
        <link rel="stylesheet" href="./Frontend/css/dashboard.css">
        <script src="./Frontend/js/navbar.js"></script>
        <script src="./Frontend/js/logout.js"></script>
        <script src="./Frontend/js/upload_file.js"></script>
        <sceript src="./Frontend/js/toast.js"></sceript>
        <?php if ($_SESSION['role'] == 'admin') : ?>
            <script src="./Frontend/js/dashboard_admin.js"></script>
        <?php else : ?>
            <script src="./Frontend/js/dashboard_user.js"></script>
        <?php endif; ?>

    </head>

<body>
    <?php require __DIR__ . '/../includes/navbar.php';?>
    <div class="toast-container">
        <ul class="notifications"></ul>
    </div>
    <input type="hidden" name="csrf" id="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="w3-main bgimg-1" id="mainContent">    
        <!-- Catalogue Section -->
        <div id="catalogueSection" class="w3-section">
            <div class="w3-center w3-padding-64">
                <div class="w3-center w3-text-black w3-margin-top">
                    <span class="w3-xxxlarge w3-hide-small w3-animate-bottom">Catalogue</span>
                    <br>
                    <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom">Catalogue</span>
                    <br>
                </div>
                <div class="w3-left-align w3-margin-left w3-section w3-bottombar w3-padding-16 w3-margin-bottom w3-animate-bottom">
                    <span class="w3-margin-right w3-hide-small"><b>Filter:</b></span>
                    <button class="w3-button w3-white" id="latestBtn">Latest</button>
                    <button class="w3-button w3-black" id="pdfBtn"><i class="fa fa-file-audio-o w3-margin-right"></i>MP3</button>
                    <button class="w3-button w3-black" id="txtBtn"><i class="fa fa-file-text-o w3-margin-right"></i>Lyrics</button>
                </div>
                <div class="w3-row-padding w3-animate-bottom bg"></div>
                <div class="w3-row-padding w3-animate-bottom second-container"></div>
                <div class="w3-center w3-padding-16 w3-animate-bottom">
                    <button class="w3-button w3-black" id="prevCataloguePageBtn">Previous</button>
                    <span id="cataloguePageInfo" ></span>
                    <button class="w3-button w3-black" id="nextCataloguePageBtn">Next</button>
                </div>
            </div>
        </div>

        <!-- Upload File Section -->
        <div id="uploadFileSection" class=" w3-section w3-hide w3-margin-left">
            <div class="w3-display-topmiddle w3-text-black" >
                <div class="w3-margin-top w3-padding-top-64">
                    <span class="w3-xxxlarge w3-hide-small w3-animate-bottom">Upload your track</span>
                    <br>
                    <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom">Upload your track</span>
                    <br>
                </div>
                <form id="uploadForm" class="w3-animate-bottom">
                    <input type="hidden" id="csrf_form" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="overlap-container">
                        <div id="file_upload_section">
                            <label id="upload_file_label" for="file"><b>Select your MP3 file (Max Size: 2MB)</b></label>
                            <input type="file" class="w3-input w3-border" name="upload_file" id="file" accept=".mp3,audio/mpeg">
                            <br>
                        </div>
                        <div id="text_upload_section" class="hidden">
                            <label id="title_label" for="title"><b>Insert your title</b></label>
                            <input type="text" class="w3-input w3-border" name="title" id="title">
                            <br>
                            <label id="text_content_label" for="text_content"><b>Insert your lyrics</b></label>
                            <textarea class="w3-input w3-border textarea-max-height" name="text_content" id="text_content" rows="6" cols="150"></textarea>
                            <br>
                        </div>
                    
                        <input type="radio" name="novel-category" id="novel-category-free-pdf" value="free" class="w3-radio">
                        <span class="w3-medium"><b>Free</b></span>
                        <input type="radio" name="novel-category" id="novel-category-pro-pdf" value="pro" class="w3-radio">
                        <span class="w3-medium"><b>Pro</b></span>
                    </div>
                    <button type="submit" class="w3-button w3-black w3-animate-bottom" id="submitBtn">
                        <i class="fa fa-upload"></i> UPLOAD
                    </button>
                </form>
            </div>
        </div>

       <!-- Manage Users Section -->
       <?php if ($_SESSION['role'] === 'admin'): ?>
        <div id="manageUsersSection" class="w3-section w3-hide w3-padding-64">
            <div class="w3-display-topmiddle w3-half w3-text-black">
                <div class="w3-margin-top w3-padding-top-64">
                    <span class="w3-xxxlarge w3-hide-small w3-animate-bottom w3-margin-left" id="manage-title">Manage Users</span>
                    <br>
                    <span class="w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom w3-margin-left">Manage Users</span>
                    <br>
                </div>
                <div class="w3-container w3-responsive w3-padding-16">
                    <table class="w3-table w3-card-4 w3-border-black w3-round-large w3-centered w3-animate-bottom">
                        <thead>
                            <tr class="w3-black">
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                        </tbody>
                    </table>
                   
                </div>
                 <div class="w3-center w3-padding-16 w3-animate-bottom">
                    <button class="w3-button w3-black" id="prevUserPageBtn">Previous</button>
                    <span id="usersPageInfo"></span>
                    <button class="w3-button w3-black" id="nextUserPageBtn">Next</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

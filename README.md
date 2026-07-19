
# Pulse Room
## Overview

Pulse Room is the project for the "System and network Hacking"'s course of University of Pisa. The scope is to create a web platform that is secure in respect to the actual standards. The platform address amateur writers, providing them with a space to upload and read novels. So users can upload their works as short stories or full-length novels (in PDF format), with the option to mark them as "premium". Premium readers have exclusive access to novels marked as such.

## Features

- **User Registration and Login:**
  - Users can self-register by choosing their username and password.
  - Users log in with their username and password.
  - Forgot your password? Users can recover their account.

- **Lyrics Uploading and Reading:**
  - Users can upload novels in two formats: short stories or full-length novels (PDFs).
  - Short stories are displayed directly in the user's browser.
  - Full-length novels are uploaded as PDFs and can be downloaded by users.
  - Users can mark their uploaded novels as premium. Only premium users can read premium novels.

- **Administrator Panel:**
  - Admin can manage user privileges, promoting them to premium or demoting them to non-premium.
  
- **User Privileges:**
  - Only premium users have access to premium novels.

## Security
The platform has implemented defenses and mitigation for:

## Installation
The system can be deployed with docker.
Here are the installation steps:

1. Clone the repository:
    ```
    git clone https://github.com/Olani2b/PulseRoom
    cd PulseRoom
    ```
2. Create or prepare a valid email for the application use.
3. Create `.env` file with the following structure:
    ``` 
    DB_HOST=mysql_8.1.0_container
    DB_USER=user
    DB_PASSWORD=my_password
    DB_NAME=pulseroom

    MAIL_USER=my_email@my_domain.com
    MAIL_PASSWORD=my_mail_password
    LOG_PATH=/var/www/logs/pulseroom
    ```
    Insert in the corrisponding fields the database and mail credentials.
4. Download and install [composer](https://getcomposer.org/).
5. Use composer for installing phpmailer:
    ```bash
    composer require phpmailer/phpmailer
    ```
6. Run:
   ```bash
   docker-compose 
   ```

# API structure

## USER

### Register User
Endpoint per la registrazione di un nuovo utente. Questo endpoint richiede che l'utente invii i dati di registrazione tramite una richiesta POST, comprensiva di username, email e password. Il sistema verifica il formato dei dati, la disponibilità dell'email e registra l'utente nel database. Inoltre, viene generato un token di verifica inviato all'email dell'utente.

- **URL:** `POST /api/register`
- **Request Body:**
  ```json
  {
    "username": "testuser",
    "email": "utente@example.com",
    "password": "SicuraPassword123!"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status",
    "message": "Message."
  }
  ```

### Login User
Endpoint per il login di un utente. Questo endpoint richiede che l'utente invii i dati di login tramite una richiesta POST, comprensiva di email e password. Il sistema verifica la correttezza dell'email, la validità della password e lo stato dell'utente, e, se tutto è valido, avvia una sessione per l'utente.

- **URL:** `POST /api/login`
- **Request Body:**
  ```json
  {
    "email": "utente@example.com",
    "password": "SicuraPassword123!"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status",
    "message": "Message."
  }
  ```

### Verify User
Endpoint per verificare l'utente. Questo endpoint richiede che l'utente invii il token di verifica e l'email tramite una richiesta GET. Il sistema verifica il token e l'email, e se validi, attiva l'utente e cancella il token.

- **URL:** `GET /api/verifyUser`
- **Request Parameters:**
  ```json
  {
    "token": "uniqueVerificationToken",
    "email": "utente@example.com"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status",
    "message": "Message."
  }
  ```

### Logout User 
Endpoint per eseguire il logout dell'utente. Questo endpoint richiede che l'utente invii il token di sessione e l'email tramite una richiesta GET. Se i dati sono validi, la sessione viene distrutta.

- **URL:** `GET /api/logout`
- **Request Body:**
  ```json
  {
    "token": "uniqueSessionToken",
    "email": "utente@example.com"
  }
  ```
- **Response Body:**
  
  ```json
  {
    "status": "status",
    "message": "Message."
  }
  ```

### Show Users
Endpoint per recuperare l'elenco degli utenti. Questo endpoint restituisce una lista di utenti (esclusi gli admin) con informazioni come ID, username, email e ruolo. La paginazione è supportata tramite i parametri `page` e `limit`.

- **URL:** `GET /api/showUsers`
- **Request Body:**
  ```json
  {
    "page": "1",
    "limit": "10"
  }
  ```
- **Response Body:**
  ```json
  {
  "status": "status",
  "data": [
    {
      "id": 1,
      "username": "user1",
      "email": "user1@example.com",
      "role": "user"
    },
    {
      "id": 2,
      "username": "user2",
      "email": "user2@example.com",
      "role": "user"
    }
  ],
  "last-page": true
  }
  ```  
### Change User Role
Endpoint per modificare il ruolo di un utente. Questo endpoint permette a un amministratore di cambiare il ruolo di un utente esistente, previa verifica della validità della richiesta.

- **URL:** `POST /api/changeUserRole`
- **Request Body:**
  ```json
  {
    "id": "user_id",
    "new_role": "new_role_value",
    "actual_role": "current_role_value"
  }
- **Response Body:**
  
  ```json
  {
    "status": "status",
    "message": "Message."
  }
  ```

### Forgot Password
Endpoint per l'invio della richiesta di reset della password. L'utente invia un'email tramite una richiesta POST. Se l'email è valida, il sistema genera un token di reset, disabilita temporaneamente l'account e invia all'utente un link di reset tramite email.

- **URL**: `POST /api/forgot_pwd`
- **Request Body:**
  ```json
  {
    "email": "utente@example.com"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status"
    "message": "Message"
  }
  ```

### Reset Password
Endpoint per eseguire il reset della password. L'utente invia una richiesta POST contenente un token di reset, l'email e la nuova password. Se tutti i controlli sono superati, la password viene aggiornata e l'accesso all'account viene ripristinato.

- **URL**: `POST /api/reset_pwd`
- **Request Body:**
  ```json
  {
    "token": "abc123def456",
    "email": "utente@example.com",
    "new_password": "NuovaPasswordSicura!",
    "conf_new_password": "NuovaPasswordSicura!"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status"
    "message": "Message"
  }
  ```
## FILE

### Upload File/Text
Endpoint per caricare contenuti, sia file che testo. Questo endpoint permette agli utenti di caricare file o testo, con un controllo sui permessi in base alla visibilità dell'utente e alla categoria del contenuto.

- **URL:** `POST /api/upload`
- **Request Body:**
  ```json
  {
    "upload_type": "file | text",
    "file": "file_data (solo se upload_type è file)",
    "text_content": "text_data (solo se upload_type è text)",
    "title": "content_title (solo se upload_type è text)",
    "novel_category": "free | pro" 
  }
  ```
  **Response Body:**
  ```json
  {
    "status": "status"
    "message": "Message"
  }
  ``` 
### Download File
Endpoint per scaricare un file dal server. L'utente deve fornire l'ID del file che desidera scaricare. Attualmente, non c'è un controllo sui permessi dell'utente per il download, quindi chiunque in possesso dell'ID del file può scaricarlo.

- **URL:** `GET /api/downloadFile`
- **Request Body:**
  ```json
  {
    "file_id": "uniqueFileId"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "success | failure",
    "title": "file_title (solo in caso di success)",
    "filetype": "file_extension (solo in caso di success)",
    "author": "username (solo in caso di success)",
    "filedata": "base64_encoded_file_data (solo in caso di success)"
    "message" : "Message(solo in caso di success)"
  }
  ```

### Show Files
Endpoint per visualizzare i file caricati dall'utente, con la possibilità di filtrare per tipo di file e di paginare i risultati.

- **URL:** `GET /api/showFiles`
- **Request Body:**
  ```json
  {
    "page": "pageNumber",
    "limit": "numberOfItemsPerPage",
    "file_type": "fileType"
  }
  ```
- **Response Body:**
  ```json
  {
    "status": "status",
    "files": [
      {
        "id": "fileId",
        "title": "fileTitle",
        "filetype": "fileType",
        "username": "authorUsername",
        "uploaded_at": "uploadTimestamp",
        "visibility": "fileVisibility"
      },
      ...
    ],
    "last-page": "isLastPage"
  }
  ```



<?php
require_once __DIR__ . '/../utils/dbManager.php';
require_once __DIR__ . '/../utils/Logging.php';

class FileController
{
    private $conn;
    private $logger;

    public function __construct()
    {
        $this->conn = dbManager::getInstance()->getConnection();
        $this->logger = applicationLogger();
    }

    public function upload()
    {
        if(!isset($_SESSION['csrf_token'], $_POST['csrf_token']) || !is_string($_POST['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
            logEvent($this->logger, "error", 'upload', 'CSFR Token missing.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'File upload failed.'], 401);
        }

        $visibilityInput = $_POST['track_visibility'] ?? null;
        $titleInput = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
        $lyricsInput = isset($_POST['text_content']) && is_string($_POST['text_content']) ? trim($_POST['text_content']) : '';

        if ($visibilityInput === null) {
            logEvent($this->logger, "error", 'upload', 'Missing parameters.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'File upload failed.'], 400);
        }

        if(!is_string($visibilityInput)){
            logEvent($this->logger, "error", 'upload', 'Types of parameters incorrect.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'File upload failed.'], 400);
        }

        $trackVisibility = $visibilityInput;
        if(!in_array($trackVisibility, ["free", "pro"], true)){
            logEvent($this->logger, "error", 'upload', 'File type not supported.', 400);
            return $this->sendResponse(["status"=>"error", "message" => "File type not supported"]);
        }

        $uploads = [];
        $sharedTitle = '';

        if (isset($_FILES['file']) && is_array($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                logEvent($this->logger, "error", 'upload', 'File upload error code: ' . $file['error'], 400);
                return $this->sendResponse(["status" => "error", "message" => "File upload failed."]);
            }

            if($file['size'] <= 0 || $file['size'] > 1024*1024*5){
                logEvent($this->logger, "error", 'upload', 'File size not supported.', 400);
                return $this->sendResponse(["status"=>"error", "message" => "File size not supported"]);
            }

            if(empty($file["name"])){
                logEvent($this->logger, "error", 'upload', 'File name not supported.', 400);
                return $this->sendResponse(["status"=>"error", "message" => "File name not supported"]);
            }

            $filetype = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            if($filetype !== "mp3"){
                logEvent($this->logger, "error", 'upload', 'File type not supported.', 400);
                return $this->sendResponse(["status"=>"error", "message" => "File type not supported"]);
            }

            $sharedTitle = $this->normalizeTitle(
                $titleInput !== '' ? $titleInput : pathinfo($file["name"], PATHINFO_FILENAME)
            );

            $uploads[] = [
                'title' => $sharedTitle,
                'filetype' => 'mp3',
                'filedata' => file_get_contents($file['tmp_name'])
            ];
        }

        if ($lyricsInput !== '') {
            if ($titleInput === '' && $sharedTitle === '') {
                logEvent($this->logger, "error", 'upload', 'Missing title for lyrics.', 400);
                return $this->sendResponse(['status' => 'error', 'message' => 'Insert a title when uploading lyrics.'], 400);
            }

            if ($sharedTitle === '') {
                $sharedTitle = $this->normalizeTitle($titleInput);
            }

            $uploads[] = [
                'title' => $sharedTitle,
                'filetype' => 'txt',
                'filedata' => $lyricsInput
            ];
        }

        if (count($uploads) === 0) {
            return $this->sendResponse(['status' => 'error', 'message' => 'Insert an MP3, lyrics, or both.'], 400);
        }

        $user_id = $_SESSION['user_id'];
        $selectedVisibility = $trackVisibility === 'pro' ? 1 : 0;

        $query = 'INSERT INTO files (title, filetype, filedata, user_id, visibility) VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            logEvent($this->logger, "error", 'upload', 'Query preparation failed.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Query preparation failed.'], 500);
        }

        $this->conn->begin_transaction();
        try {
            foreach ($uploads as $upload) {
                $title = $upload['title'];
                $filetype = $upload['filetype'];
                $filedata = $upload['filedata'];
                $stmt->bind_param("sssii", $title, $filetype, $filedata, $user_id, $selectedVisibility);
                if (!$stmt->execute()) {
                    throw new Exception('Content upload has failed');
                }
            }

            $this->conn->commit();
            $stmt->close();
            logEvent($this->logger, "info", 'upload', 'Content uploaded successfully', 201);
            return $this->sendResponse(['status' => 'success', 'message' => 'Content uploaded successfully'], 201);
        } catch (Exception $e) {
            $this->conn->rollback();
            $stmt->close();
            logEvent($this->logger, "error", 'upload', 'Content upload has failed', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Content upload has failed'], 500);
        }
    }

    private function normalizeTitle(string $title): string
    {
        $normalizedTitle = preg_replace('/[^\w\-\. ]/', '_', $title);
        return substr(trim($normalizedTitle ?? ''), 0, 255);
    }
    

    private function getUserVisibility()
    {
        $user_id = $_SESSION['user_id'];
        $stmt = $this->conn->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($role);
        $stmt->fetch();
        $stmt->close();

        $visibility = $role === 'free' ? 0 : 1;
        return $visibility;
    }
        
    public function downloadFile()
    {   
        if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
            logEvent($this->logger, "error", 'downloadFile', 'File ID not provided', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Download failed'], 400);
        }

        $fileId = $_POST['file_id'];
        $stmt = $this->conn->prepare("
            SELECT 
                files.title, 
                files.filetype, 
                files.filedata, 
                users.username,
                files.visibility
            FROM 
                files 
            INNER JOIN
                users 
            ON 
                files.user_id = users.id 
            WHERE 
                files.id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $stmt->bind_result($title, $filetype, $filedata, $author, $visibility);
        $stmt->fetch();
        $stmt->close();

        $userVisibility = $this->getUserVisibility();

        if($visibility > $userVisibility){
            logEvent($this->logger, "error", 'downloadFile', 'Missing download file permissions.', 403);
            return $this->sendResponse(['status' => 'error', 'message' => 'Missing download file permissions.'], 403);
        }

        if (!$title || !$filedata) {
            logEvent($this->logger, "error", 'downloadFile', 'File not found.', 404);
            return $this->sendResponse(['status' => 'error', 'message' => 'Download failed.'], 404);
        }

        logEvent($this->logger, "info", 'downloadFile', 'File downloaded successfully.', 200);
        $response = [
            'status' => 'success',
            'title' => $title,
            'filetype' => $filetype,
            'author' => $author,
            'filedata' => $filetype === 'txt' ? $filedata : base64_encode($filedata)
        ];

        return $this->sendResponse($response);
    }

    public function showFiles()
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) :1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) :10;
        $file_type = isset($_GET['file_type']) && is_string($_GET['file_type']) ? $_GET['file_type'] : 'both';
        $userId = $_SESSION['user_id'];

        if(!in_array($file_type, ['txt', 'mp3', 'both'])){
            $file_type = 'both';
        }

        if($page < 1){
            $page = 1; // FIXME: Determine the maximum page that can be returned.
        }
        if($limit < 1 || $limit > 6){
            $limit = 6;
        }

        $offset = ($page - 1) * $limit;
        $userVisibility = $this->getUserVisibility();
        
        $query = '
            SELECT f.id, f.title, f.filetype, u.id AS user_id, u.username, f.uploaded_at, f.visibility,
                   CASE WHEN f.user_id = ? THEN 1 ELSE 0 END AS is_owner
            FROM files f INNER JOIN users u ON f.user_id = u.id
            WHERE ? >= f.visibility
            ORDER BY f.uploaded_at DESC
        ';

        $stmt = $this->conn->prepare( $query );
        $stmt->bind_param('ii', $userId, $userVisibility);

        $stmt->execute();
        $result = $stmt->get_result();

        $groupedFiles = [];

        if( $result->num_rows > 0){
            while( $row = $result->fetch_assoc() ){
                $groupKey = $row['user_id'] . '|' . $row['title'] . '|' . $row['visibility'];
                if (!isset($groupedFiles[$groupKey])) {
                    $groupedFiles[$groupKey] = [
                        'title' => $row['title'],
                        'username' => $row['username'],
                        'visibility' => (int) $row['visibility'],
                        'is_owner' => (int) $row['is_owner'],
                        'uploaded_at' => $row['uploaded_at'],
                        'mp3_id' => null,
                        'lyrics_id' => null,
                        'has_mp3' => false,
                        'has_lyrics' => false
                    ];
                }

                if ($row['uploaded_at'] > $groupedFiles[$groupKey]['uploaded_at']) {
                    $groupedFiles[$groupKey]['uploaded_at'] = $row['uploaded_at'];
                }

                if ($row['filetype'] === 'mp3') {
                    $groupedFiles[$groupKey]['mp3_id'] = (int) $row['id'];
                    $groupedFiles[$groupKey]['has_mp3'] = true;
                } elseif ($row['filetype'] === 'txt') {
                    $groupedFiles[$groupKey]['lyrics_id'] = (int) $row['id'];
                    $groupedFiles[$groupKey]['has_lyrics'] = true;
                }
            }
        }

        $stmt->close();
        $files = array_values($groupedFiles);

        if ($file_type === 'mp3') {
            $files = array_values(array_filter($files, fn($file) => $file['has_mp3']));
        } elseif ($file_type === 'txt') {
            $files = array_values(array_filter($files, fn($file) => $file['has_lyrics']));
        }

        usort($files, fn($a, $b) => strcmp($b['uploaded_at'], $a['uploaded_at']));

        $pagedFiles = array_slice($files, $offset, $limit);
        $isLastPage = ($offset + $limit) >= count($files);

        logEvent($this->logger, "info", 'showFiles', 'Files retrieved successfully.', 200);
        return $this->sendResponse([
            'status'=> 'success',
            'files'=> $pagedFiles, 
            'last-page' => $isLastPage
        ],200);
    }

    public function deleteFile()
    {
        if (!isset($_SESSION['csrf_token'], $_POST['csrf_token']) || !is_string($_POST['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            logEvent($this->logger, "error", 'deleteFile', 'Invalid request.', 401);
            return $this->sendResponse(['status' => 'error', 'message' => 'Delete failed.'], 401);
        }

        $fileIds = [];
        if (isset($_POST['file_ids']) && is_string($_POST['file_ids'])) {
            foreach (explode(',', $_POST['file_ids']) as $id) {
                $trimmedId = trim($id);
                if ($trimmedId !== '' && ctype_digit($trimmedId)) {
                    $fileIds[] = (int) $trimmedId;
                }
            }
        } elseif (isset($_POST['file_id']) && is_numeric($_POST['file_id'])) {
            $fileIds[] = (int) $_POST['file_id'];
        }

        if (count($fileIds) === 0) {
            logEvent($this->logger, "error", 'deleteFile', 'File ID not provided.', 400);
            return $this->sendResponse(['status' => 'error', 'message' => 'Delete failed.'], 400);
        }

        $userId = $_SESSION['user_id'];

        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $types = str_repeat('i', count($fileIds));

        $stmt = $this->conn->prepare("SELECT id, user_id FROM files WHERE id IN ($placeholders)");
        if (!$stmt) {
            logEvent($this->logger, "error", 'deleteFile', 'Query preparation failed.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Delete failed.'], 500);
        }

        $stmt->bind_param($types, ...$fileIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== count($fileIds)) {
            logEvent($this->logger, "error", 'deleteFile', 'File not found.', 404);
            return $this->sendResponse(['status' => 'error', 'message' => 'File not found.'], 404);
        }

        while ($row = $result->fetch_assoc()) {
            if ((int) $row['user_id'] !== $userId) {
                logEvent($this->logger, "error", 'deleteFile', 'Unauthorized delete attempt.', 403);
                return $this->sendResponse(['status' => 'error', 'message' => 'You can only delete your own uploads.'], 403);
            }
        }

        $deletePlaceholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $this->conn->prepare("DELETE FROM files WHERE user_id = ? AND id IN ($deletePlaceholders)");
        if (!$stmt) {
            logEvent($this->logger, "error", 'deleteFile', 'Delete preparation failed.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Delete failed.'], 500);
        }

        $deleteTypes = 'i' . str_repeat('i', count($fileIds));
        $params = array_merge([$userId], $fileIds);
        $stmt->bind_param($deleteTypes, ...$params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows !== count($fileIds)) {
            logEvent($this->logger, "error", 'deleteFile', 'Delete failed.', 500);
            return $this->sendResponse(['status' => 'error', 'message' => 'Delete failed.'], 500);
        }

        logEvent($this->logger, "info", 'deleteFile', 'File deleted successfully.', 200);
        return $this->sendResponse(['status' => 'success', 'message' => 'Upload deleted successfully.'], 200);
    }

    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }
}

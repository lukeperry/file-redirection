<?php
session_start();

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

// Check if the user is logged in (keep your existing session checks)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $uploadDirectory = 'uploads/';
    
    // Create the uploads directory if it doesn't exist
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    $uploadFile = $uploadDirectory . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        $uploadSuccess = "File has been uploaded successfully!";
        
        // Upload to Google Drive using Service Account
        $client = new Client();
        $client->setAuthConfig(__DIR__ . '/service-account.json');  // Use the service account credentials file
        $client->addScope(Drive::DRIVE_FILE);

        // Set the CA certificate file path
        $client->setHttpClient(new \GuzzleHttp\Client([
            'verify' => __DIR__ . '/cacert.pem'
        ]));

        // Create the Drive service instance
        $service = new Drive($client);

        // Specify the parent folder ID (replace 'your-folder-id' with the actual folder ID)
        $parentFolderId = '1bt2xBgvHgjNwK_RXJHLviOgPaZ5aeQMu';

        $fileMetadata = new Drive\DriveFile([
            'name' => basename($file['name']),
            'parents' => [$parentFolderId]
        ]);
        $content = file_get_contents($uploadFile);

        // Upload the file to Google Drive
        try {
            $driveFile = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => mime_content_type($uploadFile),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            if ($driveFile->id) {
                $uploadSuccess .= " File uploaded to Google Drive with ID: " . $driveFile->id;
            } else {
                $uploadError = "Failed to upload file to Google Drive.";
            }
        } catch (Exception $e) {
            $uploadError = "Error uploading to Google Drive: " . $e->getMessage();
        }
    } else {
        $uploadError = "Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e0bbff; /* Light purple background */
        }
        .card {
            max-width: 500px;
            margin: auto;
            margin-top: 50px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #ffffff; /* White background for the card */
            border-radius: 10px; /* Rounded corners */
        }
        .btn-primary {
            background-color: #6f42c1; /* Purple button */
            border-color: #6f42c1;
        }
        .btn-primary:hover {
            background-color: #5a32a3; /* Darker purple on hover */
            border-color: #5a32a3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center">Welcome to the Dashboard</h2>
            <form action="" method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="form-group">
                    <label for="file">Choose a file to upload:</label>
                    <input type="file" id="file" name="file" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Upload</button>
            </form>
            <?php if (isset($uploadSuccess)): ?>
                <div class="alert alert-success mt-4">
                    <?= $uploadSuccess ?>
                </div>
            <?php endif; ?>
            <?php if (isset($uploadError)): ?>
                <div class="alert alert-danger mt-4">
                    <?= $uploadError ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

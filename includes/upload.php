<?php
/**
 * EstateVista India - Secure File Upload Engine
 * Strict MIME type verification via finfo, size enforcement, extension whitelisting, and collision-free names.
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Handle a single secure file upload
 *
 * @param array $file Single $_FILES entry (e.g. $_FILES['cover_image'])
 * @param string $subfolder Directory within uploads/ (e.g. 'properties', 'avatars', 'floorplans')
 * @param int $maxSizeMb Maximum allowed file size in Megabytes
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_secure_file(array $file, string $subfolder = 'properties', int $maxSizeMb = 10): array {
    // 1. Check upload error codes
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file was uploaded.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => "File exceeds maximum size limit of {$maxSizeMb}MB."];
        default:
            return ['success' => false, 'error' => 'Unknown upload error occurred.'];
    }

    // 2. Enforce file size limit
    $maxBytes = $maxSizeMb * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => "File is too large. Maximum allowed size is {$maxSizeMb}MB."];
    }

    // 3. Strict MIME-type verification using Fileinfo (finfo)
    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);

    $validMime = in_array($detectedMime, array_values($allowedMimes), true);
    if (!$validMime) {
        return ['success' => false, 'error' => 'Invalid file format. Only JPG, PNG, and WebP images are allowed.'];
    }

    // 4. Verify file extension matches allowed MIME
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';

    if (!array_key_exists($ext, $allowedMimes) || $allowedMimes[$ext] !== $detectedMime) {
        return ['success' => false, 'error' => 'File extension does not match its detected content type.'];
    }

    // 5. Ensure target upload directory exists and is protected
    $baseUploadDir = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . trim($subfolder, '/\\');
    if (!is_dir($baseUploadDir)) {
        if (!mkdir($baseUploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to initialize upload directory on server.'];
        }
    }

    // 6. Generate cryptographically random, collision-free filename
    $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $baseUploadDir . DIRECTORY_SEPARATOR . $uniqueName;

    // 7. Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file. Check directory permissions.'];
    }

    // Relative path for database storage and web display
    $relativePath = 'uploads/' . trim($subfolder, '/') . '/' . $uniqueName;

    return [
        'success' => true,
        'path' => $relativePath,
        'filename' => $uniqueName
    ];
}

/**
 * Handle multiple file uploads (e.g. $_FILES['gallery_images'])
 *
 * @param array $files Multi-file array from $_FILES
 * @param string $subfolder
 * @param int $maxFiles Max allowed files
 * @return array ['success' => bool, 'paths' => array, 'errors' => array]
 */
function upload_multiple_secure_files(array $files, string $subfolder = 'properties', int $maxFiles = 15): array {
    $uploadedPaths = [];
    $errors = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return ['success' => false, 'paths' => [], 'errors' => ['Invalid multi-file payload.']];
    }

    $fileCount = count($files['name']);
    if ($fileCount > $maxFiles) {
        return ['success' => false, 'paths' => [], 'errors' => ["You can upload a maximum of {$maxFiles} images at once."]];
    }

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue; // Skip empty slots
        }

        $singleFile = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i]
        ];

        $res = upload_secure_file($singleFile, $subfolder);
        if ($res['success']) {
            $uploadedPaths[] = $res['path'];
        } else {
            $errors[] = "File '{$files['name'][$i]}': " . $res['error'];
        }
    }

    return [
        'success' => count($uploadedPaths) > 0,
        'paths'   => $uploadedPaths,
        'errors'  => $errors
    ];
}

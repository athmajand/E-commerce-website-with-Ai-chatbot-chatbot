<?php
/**
 * File Uploader Utility Class
 *
 * Handles file uploads for the Kisan Kart application
 */
class FileUploader {
    // Upload directory
    private $upload_dir;

    // Allowed file types
    private $allowed_types;

    // Maximum file size in bytes (default: 5MB)
    private $max_size;

    // Error message
    private $error;

    /**
     * Constructor
     *
     * @param string $upload_dir Upload directory (relative to document root)
     * @param array $allowed_types Allowed file types (e.g. ['image/jpeg', 'image/png', 'application/pdf'])
     * @param int $max_size Maximum file size in bytes (default: 5MB)
     */
    public function __construct($upload_dir = 'uploads', $allowed_types = [], $max_size = 5242880) {
        // Set upload directory - use project root instead of document root
        $project_root = dirname(dirname(dirname(__FILE__))); // Go up 3 levels from this file
        $this->upload_dir = $project_root . '/' . trim($upload_dir, '/') . '/';

        // Create directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }

        // Set allowed file types
        $this->allowed_types = !empty($allowed_types) ? $allowed_types : [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf'
        ];

        // Set maximum file size
        $this->max_size = $max_size;

        // Initialize error message
        $this->error = '';
    }

    /**
     * Upload file
     *
     * @param array $file File data from $_FILES
     * @param string $subdirectory Optional subdirectory within upload directory
     * @param string $custom_filename Optional custom filename (without extension)
     * @return string|bool File path relative to document root on success, false on failure
     */
    public function upload($file, $subdirectory = '', $custom_filename = '') {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->error = 'No file was uploaded';
            return false;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Check file size
        if ($file['size'] > $this->max_size) {
            $this->error = 'File size exceeds the maximum limit of ' . $this->formatSize($this->max_size);
            return false;
        }

        // Check file type
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $this->allowed_types)) {
            $this->error = 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_types);
            return false;
        }

        // Create subdirectory if provided
        $target_dir = $this->upload_dir;
        if (!empty($subdirectory)) {
            $target_dir .= trim($subdirectory, '/') . '/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
        }

        // Generate filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = !empty($custom_filename) ? $custom_filename : uniqid('file_');
        $filename .= '.' . $file_extension;

        // Set target path
        $target_path = $target_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Return file path relative to project root
            $project_root = dirname(dirname(dirname(__FILE__))); // Go up 3 levels from this file
            $relative_path = str_replace($project_root, '', $target_path);
            // Make sure it starts with a slash
            $relative_path = '/' . ltrim($relative_path, '/');
            return $relative_path;
        } else {
            $this->error = 'Failed to move uploaded file: ' . error_get_last()['message'];
            return false;
        }
    }

    /**
     * Get error message
     *
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Format file size
     *
     * @param int $size File size in bytes
     * @return string Formatted file size
     */
    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
?>

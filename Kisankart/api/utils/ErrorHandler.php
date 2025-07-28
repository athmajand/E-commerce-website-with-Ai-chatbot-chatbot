<?php
/**
 * Error Handler Utility Class
 * 
 * This class provides methods for handling and displaying errors in a user-friendly way.
 */
class ErrorHandler {
    /**
     * Format validation errors for display
     * 
     * @param array $errors Array of error messages
     * @return string HTML formatted error messages
     */
    public static function formatValidationErrors($errors) {
        if (empty($errors)) {
            return '';
        }
        
        $html = '<div class="alert alert-danger">';
        $html .= '<h5 class="alert-heading">Please fix the following errors:</h5>';
        $html .= '<ul class="mb-0">';
        
        foreach ($errors as $error) {
            $html .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format a success message for display
     * 
     * @param string $message Success message
     * @return string HTML formatted success message
     */
    public static function formatSuccessMessage($message) {
        if (empty($message)) {
            return '';
        }
        
        $html = '<div class="alert alert-success">';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format an error message for display
     * 
     * @param string $message Error message
     * @return string HTML formatted error message
     */
    public static function formatErrorMessage($message) {
        if (empty($message)) {
            return '';
        }
        
        $html = '<div class="alert alert-danger">';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format a warning message for display
     * 
     * @param string $message Warning message
     * @return string HTML formatted warning message
     */
    public static function formatWarningMessage($message) {
        if (empty($message)) {
            return '';
        }
        
        $html = '<div class="alert alert-warning">';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format an info message for display
     * 
     * @param string $message Info message
     * @return string HTML formatted info message
     */
    public static function formatInfoMessage($message) {
        if (empty($message)) {
            return '';
        }
        
        $html = '<div class="alert alert-info">';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Form data
     * @param array $requiredFields Array of required field names
     * @return array Array of error messages
     */
    public static function validateRequiredFields($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = ucwords(str_replace('_', ' ', $field));
                $errors[] = "$fieldName is required.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    public static function validatePhone($phone) {
        // Basic validation for 10-digit phone number
        return preg_match('/^\d{10}$/', $phone) === 1;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array Array with 'valid' (bool) and 'message' (string) keys
     */
    public static function validatePassword($password) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        if (strlen($password) < 8) {
            $result['valid'] = false;
            $result['message'] = 'Password must be at least 8 characters long.';
            return $result;
        }
        
        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $result['valid'] = false;
            $result['message'] = 'Password must contain at least one uppercase letter.';
            return $result;
        }
        
        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $result['valid'] = false;
            $result['message'] = 'Password must contain at least one lowercase letter.';
            return $result;
        }
        
        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            $result['valid'] = false;
            $result['message'] = 'Password must contain at least one number.';
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file File upload data ($_FILES array element)
     * @param array $allowedTypes Array of allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array Array with 'valid' (bool) and 'message' (string) keys
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        // Check if file was uploaded
        if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $result['valid'] = false;
            $result['message'] = 'No file was uploaded.';
            return $result;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['valid'] = false;
            
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $result['message'] = 'The uploaded file exceeds the maximum file size.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $result['message'] = 'The file was only partially uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $result['message'] = 'Missing a temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $result['message'] = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $result['message'] = 'A PHP extension stopped the file upload.';
                    break;
                default:
                    $result['message'] = 'Unknown upload error.';
            }
            
            return $result;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $result['valid'] = false;
            $result['message'] = 'The file is too large. Maximum size is ' . self::formatFileSize($maxSize) . '.';
            return $result;
        }
        
        // Check file type if allowed types are specified
        if (!empty($allowedTypes)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            
            if (!in_array($mime, $allowedTypes)) {
                $result['valid'] = false;
                $result['message'] = 'Invalid file type. Allowed types: ' . implode(', ', array_map('self::formatMimeType', $allowedTypes));
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Format MIME type for display
     * 
     * @param string $mime MIME type
     * @return string Formatted MIME type
     */
    private static function formatMimeType($mime) {
        $mimeMap = [
            'image/jpeg' => 'JPEG',
            'image/jpg' => 'JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
            'application/pdf' => 'PDF',
            'application/msword' => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX'
        ];
        
        return isset($mimeMap[$mime]) ? $mimeMap[$mime] : $mime;
    }
}
?>

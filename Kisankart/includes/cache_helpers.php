<?php
/**
 * Cache Helper Functions for Kisan Kart
 * 
 * These functions help with cache busting and proper resource loading
 */

/**
 * Generate a cache-busting URL for a resource
 * 
 * @param string $path Path to the resource (CSS, JS, image)
 * @return string URL with cache-busting parameter
 */
function cache_bust_url($path) {
    // Get the full server path to the file
    $full_path = __DIR__ . '/../' . ltrim($path, '/');
    
    // Check if file exists
    if (file_exists($full_path)) {
        // Use file modification time as version parameter
        $version = filemtime($full_path);
    } else {
        // If file doesn't exist, use current time
        $version = time();
    }
    
    // Add version parameter to URL
    if (strpos($path, '?') !== false) {
        // URL already has query parameters
        return $path . '&v=' . $version;
    } else {
        // URL has no query parameters
        return $path . '?v=' . $version;
    }
}

/**
 * Output a stylesheet link tag with cache busting
 * 
 * @param string $path Path to the CSS file
 * @param string $media Media attribute (default: 'all')
 * @return void Outputs the HTML tag
 */
function css_link($path, $media = 'all') {
    $url = cache_bust_url($path);
    echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '" media="' . htmlspecialchars($media) . '">' . PHP_EOL;
}

/**
 * Output a script tag with cache busting
 * 
 * @param string $path Path to the JavaScript file
 * @param bool $defer Whether to add defer attribute (default: false)
 * @return void Outputs the HTML tag
 */
function js_script($path, $defer = false) {
    $url = cache_bust_url($path);
    $defer_attr = $defer ? ' defer' : '';
    echo '<script src="' . htmlspecialchars($url) . '"' . $defer_attr . '></script>' . PHP_EOL;
}

/**
 * Output an image tag with cache busting
 * 
 * @param string $path Path to the image
 * @param string $alt Alt text for the image
 * @param string $class CSS classes (optional)
 * @param array $attributes Additional attributes (optional)
 * @return void Outputs the HTML tag
 */
function cache_bust_img($path, $alt, $class = '', $attributes = []) {
    $url = cache_bust_url($path);
    $class_attr = !empty($class) ? ' class="' . htmlspecialchars($class) . '"' : '';
    
    $attr_str = '';
    foreach ($attributes as $key => $value) {
        $attr_str .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    echo '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($alt) . '"' . $class_attr . $attr_str . '>' . PHP_EOL;
}
?>

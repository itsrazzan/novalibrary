<?php
/**
 * Helper functions untuk path dan URL management
 * Support untuk multiple environments (localhost:8000, localhost/NOVA-Library, dll)
 */

/**
 * Get base path dari aplikasi dengan mendeteksi root folder
 * Contoh: /NOVA-Library atau / atau /myapp
 */
function getBasePath() {
    static $base_path = null;
    
    if ($base_path !== null) {
        return $base_path;
    }
    
    // Deteksi base path dengan cek REQUEST_URI atau SCRIPT_NAME
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    
    // Jika ada REQUEST_URI, gunakan itu untuk deteksi
    if (!empty($request_uri)) {
        // REQUEST_URI format: /NOVA-Library/controllers/login.php atau /controllers/login.php
        // Cari folder pertama yang bukan public, controllers, models, views, config
        $uri_parts = array_filter(explode('/', trim($request_uri, '/')));
        
        if (!empty($uri_parts)) {
            $first_part = reset($uri_parts);
            // Jika folder pertama bukan reserved folder, itu base path
            $reserved = ['public', 'controllers', 'models', 'views', 'config', 'admin', 'user', 'borrowed-books.html', 'dashboard.html', 'history.html', 'index.html', 'register.html', 'waiting-list.html', 'login.php'];
            
            if (!in_array($first_part, $reserved) && !preg_match('/\.(php|html|css|js)$/', $first_part)) {
                $base_path = '/' . $first_part;
                return $base_path;
            }
        }
    }
    
    // Default: root
    $base_path = '';
    return $base_path;
}

/**
 * Generate full URL untuk redirect
 * @param string $path Path relatif dari root aplikasi (e.g., 'views/login.php')
 * @return string URL untuk header Location
 */
function getRedirectUrl($path) {
    $base_path = getBasePath();
    $path = ltrim($path, '/');
    if (empty($base_path)) {
        return '/' . $path;
    }
    return $base_path . '/' . $path;
}

/**
 * Generate URL untuk asset files (CSS, JS, images)
 * @param string $path Path relatif dari root aplikasi (e.g., 'public/css/style.css')
 * @return string URL untuk href/src attributes
 */
function getAssetUrl($path) {
    return getRedirectUrl($path);
}

/**
 * Get base URL untuk fetch API calls
 * @return string Base URL dari aplikasi
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $base_path = getBasePath();
    $url = $protocol . $_SERVER['HTTP_HOST'] . ($base_path ?: '');
    return rtrim($url, '/');
}
?>



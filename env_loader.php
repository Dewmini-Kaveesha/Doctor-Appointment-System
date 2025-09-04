<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file
 */

function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                $value = substr($value, 1, -1);
            }

            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Get environment variable with optional default value
 */
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key) ?? $default;
    
    // Convert string boolean values
    if (is_string($value)) {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
        }
    }
    
    return $value;
}

// Load the .env file
try {
    loadEnv();
} catch (Exception $e) {
    // If .env file doesn't exist, continue with default values
    error_log("Warning: " . $e->getMessage());
}

<?php
// config/env.php - Environment Variable Loader & Helper

if (!function_exists('loadEnv')) {
    function loadEnv() {
        // Build path to user's home directory for a non-shared, secure environment file
        $homeDir = getenv('USERPROFILE') ?: getenv('HOME');
        $localEnvPath = $homeDir ? rtrim(str_replace('\\', '/', $homeDir), '/') . '/.tripistry_lsn.env' : null;
        
        $envPath = __DIR__ . '/../.env';
        if ($localEnvPath && file_exists($localEnvPath)) {
            $envPath = $localEnvPath;
        }

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = trim($parts[1]);
                    
                    // Strip quotes if they surround the value
                    if ((strpos($val, '"') === 0 && strrpos($val, '"') === strlen($val) - 1) ||
                        (strpos($val, "'") === 0 && strrpos($val, "'") === strlen($val) - 1)) {
                        $val = substr($val, 1, -1);
                    }
                    
                    // Only set if not already set by system environment (so system env takes precedence)
                    if (getenv($key) === false) {
                        putenv("$key=$val");
                    }
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $val;
                    }
                    if (!isset($_SERVER[$key])) {
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
    }
    
    loadEnv();
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }
}

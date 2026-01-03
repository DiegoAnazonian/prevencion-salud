<?php

function logInfo($message) {
    writeLog("INFO", $message);
}

function logError($message) {
    writeLog("ERROR", $message);
}

function writeLog($level, $message) {
    // Solo escribir logs si DEBUG_MODE está activo
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
        return;
    }

    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/app.log';

    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0750, true)) {
            error_log("Failed to create log directory: $logDir");
            return;
        }
    }

    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

    $line = "[$date][$level][$ip] $message\n";

    if (!file_put_contents($logFile, $line, FILE_APPEND)) {
        error_log("Failed to write to log file: $logFile");
    }
}

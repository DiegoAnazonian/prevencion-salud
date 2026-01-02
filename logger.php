<?php

function logInfo($message) {
    writeLog("INFO", $message);
}

function logError($message) {
    writeLog("ERROR", $message);
}

function writeLog($level, $message) {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/app.log';

    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

    $line = "[$date][$level][$ip] $message\n";

    file_put_contents($logFile, $line, FILE_APPEND);
}

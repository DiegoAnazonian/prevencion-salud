<?php
/**
 * SISTEMA DE ENVÍO DE FORMULARIOS
 * Versión robusta con validación, sanitización y seguridad
 */

require_once 'config.php';
require_once 'logger.php';

logInfo("Llego solicitud de form");
// Headers para JSON response
header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Método no permitido');
}

// Iniciar sesión para rate limiting
session_start();
// Prevenir session fixation
session_regenerate_id(true);

// ========================================
// RATE LIMITING
// ========================================

if (!checkRateLimit()) {
    respondError('Demasiadas solicitudes. Por favor intentá más tarde.');
}

// ========================================
// OBTENER Y SANITIZAR DATOS
// ========================================

$tipo = sanitizeInput($_POST['tipo'] ?? '');

// Validar tipo de formulario
if (!in_array($tipo, $GLOBALS['FORM_TYPES'])) {
    respondError('Tipo de formulario inválido');
}

// Honeypot anti-spam
if (ENABLE_HONEYPOT && !empty($_POST['website'])) {
    // Campo honeypot completado = bot
    respondError('Error de validación', false);
}

// ========================================
// VALIDAR Y PROCESAR SEGÚN TIPO
// ========================================

if ($tipo === 'particular') {
    processParticularForm();
} else if ($tipo === 'empresa') {
    processEmpresaForm();
} else {
    respondError('Tipo de formulario no reconocido');
}

// ========================================
// PROCESAR FORMULARIO PARTICULAR
// ========================================

function processParticularForm() {
    global $ALLOWED_PROFILES, $AVAILABLE_PLANS;

    // Obtener y sanitizar datos
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $residencia = sanitizeInput($_POST['residencia'] ?? '');
    $telefono = sanitizePhone($_POST['telefono'] ?? '');
    // Validar edad antes de convertir
    $edadRaw = $_POST['edad'] ?? '';
    if (!is_numeric($edadRaw) || strlen($edadRaw) > 3) {
        $errors[] = 'Edad inválida';
        $edad = 0;
    } else {
        $edad = (int)$edadRaw;
    }
    $perfil = sanitizeInput($_POST['perfil'] ?? '');

    // Array de errores
    $errors = [];

    // Validar nombre
    if (empty($nombre) || strlen($nombre) < MIN_NAME_LENGTH) {
        $errors[] = 'Nombre inválido';
    }

    // Validar residencia
    if (empty($residencia) || strlen($residencia) < 2) {
        $errors[] = 'Residencia inválida';
    }

    // Validar teléfono
    if (strlen($telefono) < MIN_PHONE_LENGTH || strlen($telefono) > MAX_PHONE_LENGTH) {
        $errors[] = 'Teléfono inválido';
    }

    // Validar edad
    if ($edad < MIN_AGE || $edad > MAX_AGE) {
        $errors[] = 'Edad debe estar entre ' . MIN_AGE . ' y ' . MAX_AGE . ' años';
    }

    // Validar perfil
    if (!in_array($perfil, $ALLOWED_PROFILES)) {
        $errors[] = 'Perfil inválido';
    }

    // Si hay errores, retornar
    if (!empty($errors)) {
        respondError(implode(', ', $errors));
    }

    // Construir mensaje de email
    $subject = "Nuevo Lead - Particular - Prevención Salud";

    $message = "
========================================
NUEVA CONSULTA - PARTICULAR
========================================

Fecha: " . date('d/m/Y H:i:s') . "

DATOS PERSONALES:
• Nombre: $nombre
• Residencia: $residencia
• Teléfono: $telefono
• Edad: $edad años
• Perfil: " . ucfirst(str_replace('_', ' ', $perfil)) . "

========================================
Este email fue enviado desde el formulario web
de Prevención Salud
========================================
    ";

    // Enviar email
    if (sendEmail(EMAIL_TO, $subject, $message, '', $nombre)) {
        // Log exitoso (opcional)
        logSubmission('particular', $telefono);

        respondSuccess('Consulta enviada exitosamente');
    } else {
        respondError('Error al enviar el email. Por favor intentá nuevamente.');
    }
}

// ========================================
// PROCESAR FORMULARIO EMPRESA
// ========================================

function processEmpresaForm() {
    // Obtener y sanitizar datos
    $empresa = sanitizeInput($_POST['empresa'] ?? '');
    $contacto = sanitizeInput($_POST['contacto'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $telefono = sanitizePhone($_POST['telefono'] ?? '');
    $empleados = (int)($_POST['empleados'] ?? 0);

    // Array de errores
    $errors = [];

    // Validar empresa
    if (empty($empresa) || strlen($empresa) < MIN_NAME_LENGTH) {
        $errors[] = 'Nombre de empresa inválido';
    }

    // Validar contacto
    if (empty($contacto) || strlen($contacto) < MIN_NAME_LENGTH) {
        $errors[] = 'Nombre de contacto inválido';
    }

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }

    // Validar teléfono
    if (strlen($telefono) < MIN_PHONE_LENGTH || strlen($telefono) > MAX_PHONE_LENGTH) {
        $errors[] = 'Teléfono inválido';
    }

    // Validar empleados
    if ($empleados < 1) {
        $errors[] = 'Cantidad de empleados inválida';
    }

    // Si hay errores, retornar
    if (!empty($errors)) {
        respondError(implode(', ', $errors));
    }

    // Construir mensaje de email
    $subject = "Nuevo Lead - Empresa - Prevención Salud";

    $message = "
========================================
NUEVA CONSULTA - EMPRESA
========================================

Fecha: " . date('d/m/Y H:i:s') . "

DATOS DE LA EMPRESA:
• Empresa: $empresa
• Persona de contacto: $contacto
• Email: $email
• Teléfono: $telefono
• Cantidad de empleados: $empleados

========================================
Este email fue enviado desde el formulario web
de Prevención Salud
========================================
    ";

    // Enviar email
    if (sendEmail(EMAIL_TO, $subject, $message, $email, $contacto)) {
        // Log exitoso (opcional)
        logSubmission('empresa', $email);

        respondSuccess('Solicitud de cotización enviada exitosamente');
    } else {
        respondError('Error al enviar el email. Por favor intentá nuevamente.');
    }
}

// ========================================
// FUNCIONES DE UTILIDAD
// ========================================

/**
 * Sanitizar input general
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Sanitizar email
 */
function sanitizeEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return strtolower($email);
}

/**
 * Sanitizar header de email (prevenir injection)
 */
function sanitizeEmailHeader($header) {
    // Eliminar caracteres peligrosos que permiten inyección
    $header = str_replace(["\r", "\n", "%0a", "%0d"], '', $header);
    $header = trim($header);
    return $header;
}

/**
 * Sanitizar teléfono (solo números)
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Enviar email con protección contra header injection
 */
function sendEmail($to, $subject, $message, $replyTo = '', $replyToName = '') {
    // Sanitizar todos los headers para prevenir injection
    $to = sanitizeEmailHeader($to);
    $subject = sanitizeEmailHeader($subject);
    $replyTo = sanitizeEmailHeader($replyTo);
    $replyToName = sanitizeEmailHeader($replyToName);

    $headers = [];
    $headers[] = 'From: ' . sanitizeEmailHeader(EMAIL_FROM_NAME) . ' <' . sanitizeEmailHeader(EMAIL_FROM) . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    if (!empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyToName . ' <' . $replyTo . '>';
    }

    $headersString = implode("\r\n", $headers);

    $result = @mail($to, $subject, $message, $headersString);

    // Log si falla
    if (!$result) {
        logError("Mail failed to send to: $to");
    }

    return $result;
}

/**
 * Rate limiting mejorado (sesión + IP)
 */
function checkRateLimit() {
    $now = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Rate limiting por sesión
    if (!isset($_SESSION['form_submissions'])) {
        $_SESSION['form_submissions'] = [];
    }

    // Limpiar submissions viejas (> 1 hora)
    $_SESSION['form_submissions'] = array_filter(
        $_SESSION['form_submissions'],
        function($timestamp) use ($now) {
            return ($now - $timestamp) < SESSION_TIMEOUT;
        }
    );

    // Verificar límite por sesión
    if (count($_SESSION['form_submissions']) >= MAX_REQUESTS_PER_HOUR) {
        logError("Rate limit exceeded for session - IP: $ip");
        return false;
    }

    // Rate limiting por IP (archivo temporal)
    $ipFile = sys_get_temp_dir() . '/ratelimit_' . md5($ip) . '.tmp';
    $ipSubmissions = [];

    if (file_exists($ipFile)) {
        $ipSubmissions = json_decode(file_get_contents($ipFile), true) ?: [];
        // Limpiar viejas
        $ipSubmissions = array_filter($ipSubmissions, function($timestamp) use ($now) {
            return ($now - $timestamp) < SESSION_TIMEOUT;
        });
    }

    // Verificar límite por IP
    if (count($ipSubmissions) >= MAX_REQUESTS_PER_HOUR) {
        logError("Rate limit exceeded for IP: $ip");
        return false;
    }

    // Agregar timestamps
    $_SESSION['form_submissions'][] = $now;
    $ipSubmissions[] = $now;
    file_put_contents($ipFile, json_encode($ipSubmissions));

    return true;
}

/**
 * Log de submission (opcional - puede guardarse en DB)
 */
function logSubmission($type, $email) {
    if (!DEBUG_MODE) return;

    $logFile = __DIR__ . '/logs/submissions.log';
    $logDir = dirname($logFile);

    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0750, true)) {
            error_log("Failed to create log directory: $logDir");
            return;
        }
    }

    $logEntry = sprintf(
        "[%s] Type: %s | Email: %s | IP: %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $email,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );

    if (!file_put_contents($logFile, $logEntry, FILE_APPEND)) {
        error_log("Failed to write to log file: $logFile");
    }
}

/**
 * Responder con éxito (JSON)
 */
function respondSuccess($message = 'Operación exitosa') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    // Forzar salida del script
    die();
}

/**
 * Responder con error (JSON)
 */
function respondError($message = 'Error desconocido', $log = true) {
    if ($log) {
        logError("Form Error: $message - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    // Forzar salida del script
    die();
}

?>

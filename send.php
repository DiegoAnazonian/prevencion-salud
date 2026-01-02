<?php
/**
 * SISTEMA DE ENVÍO DE FORMULARIOS
 * Versión robusta con validación, sanitización y seguridad
 */

require_once 'config.php';

// Headers para JSON response
header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Método no permitido');
}

// Iniciar sesión para rate limiting
session_start();

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
    $edad = (int)($_POST['edad'] ?? 0);
    $perfil = sanitizeInput($_POST['perfil'] ?? '');
    $plan = sanitizeInput($_POST['plan'] ?? 'Sin preferencia');

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

    // Validar plan (opcional)
    if (!empty($plan) && $plan !== 'Sin preferencia' && !in_array($plan, $AVAILABLE_PLANS)) {
        $errors[] = 'Plan inválido';
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
• Plan de interés: $plan

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
 * Sanitizar teléfono (solo números)
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Enviar email
 */
function sendEmail($to, $subject, $message, $replyTo = '', $replyToName = '') {
    $headers = [];
    $headers[] = 'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    if (!empty($replyTo)) {
        $headers[] = 'Reply-To: ' . $replyToName . ' <' . $replyTo . '>';
    }

    $headersString = implode("\r\n", $headers);

    return mail($to, $subject, $message, $headersString);
}

/**
 * Rate limiting simple
 */
function checkRateLimit() {
    $now = time();

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

    // Verificar límite
    if (count($_SESSION['form_submissions']) >= MAX_REQUESTS_PER_HOUR) {
        return false;
    }

    // Agregar timestamp actual
    $_SESSION['form_submissions'][] = $now;

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
        @mkdir($logDir, 0755, true);
    }

    $logEntry = sprintf(
        "[%s] Type: %s | Email: %s | IP: %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $email,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );

    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Responder con éxito (JSON)
 */
function respondSuccess($message = 'Operación exitosa') {
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    exit;
}

/**
 * Responder con error (JSON)
 */
function respondError($message = 'Error desconocido', $log = true) {
    if ($log && DEBUG_MODE) {
        error_log("Form Error: $message");
    }

    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

?>

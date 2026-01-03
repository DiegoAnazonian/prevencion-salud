<?php
/**
 * CONFIGURACIÓN CENTRALIZADA
 * Archivo de configuración para el sitio de Prevención Salud
 */

// Configuración de Email
define('EMAIL_TO', 'info@elijosalud.com.ar'); // REEMPLAZAR con email real
define('EMAIL_FROM', 'info@elijosalud.com.ar');
define('EMAIL_FROM_NAME', 'Prevención Salud - Formulario Web');

// Configuración de Respuestas
define('SUCCESS_PAGE', 'gracias.html');
define('ERROR_PAGE', 'index.html?error=1');

// Configuración de Validación
define('MIN_AGE', 18);
define('MAX_AGE', 59);
define('MIN_NAME_LENGTH', 3);
define('MIN_PHONE_LENGTH', 8);
define('MAX_PHONE_LENGTH', 15);

// Perfiles permitidos para particulares
$ALLOWED_PROFILES = [
    'autonomo',
    'relacion',
    'monotributista'
];

// Planes disponibles
$AVAILABLE_PLANS = ['A1', 'A2', 'A4', 'A5'];

// Tipos de formulario
$FORM_TYPES = ['particular', 'empresa'];

// Configuración de Seguridad
define('ENABLE_HONEYPOT', true); // Anti-spam honeypot
define('MAX_REQUESTS_PER_HOUR', 5); // Rate limiting (reducido a 5 por seguridad)
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de Debug
define('DEBUG_MODE', false); // Cambiar a false en producción

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

?>

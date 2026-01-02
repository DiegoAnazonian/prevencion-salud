/* ========================================
   GOOGLE ANALYTICS 4 - TRACKING
   ======================================== */

/**
 * Sistema robusto de tracking de eventos con Google Analytics 4
 * Todos los eventos siguen la nomenclatura de GA4 recommended events
 */

// Configuración global
const GA_CONFIG = {
  measurementId: 'G-XXXXXXXXXX', // Reemplazar con ID real
  debug: false // Cambiar a true para ver eventos en consola
};

// Utilidad para logging en modo debug
function logEvent(eventName, params) {
  if (GA_CONFIG.debug) {
    console.log(`[GA4 Event] ${eventName}`, params);
  }
}

// ========================================
// EVENTOS DE PÁGINA
// ========================================

/**
 * Evento: page_view
 * Se dispara automáticamente por gtag('config')
 * Tracking adicional de tiempo en página
 */
let pageStartTime = Date.now();

window.addEventListener('beforeunload', function() {
  const timeOnPage = Math.round((Date.now() - pageStartTime) / 1000);
  if (typeof gtag === 'function') {
    gtag('event', 'page_engagement', {
      'engagement_time_seconds': timeOnPage,
      'event_category': 'engagement',
      'event_label': window.location.pathname
    });
    logEvent('page_engagement', { time: timeOnPage });
  }
});

// ========================================
// EVENTOS DE FORMULARIO
// ========================================

/**
 * Evento: form_view
 * Se dispara cuando un formulario entra en el viewport
 */
function trackFormView(formId) {
  if (typeof gtag === 'function') {
    gtag('event', 'form_view', {
      'event_category': 'forms',
      'event_label': formId,
      'form_id': formId
    });
    logEvent('form_view', { formId });
  }
}

/**
 * Evento: form_start
 * Se dispara cuando el usuario comienza a llenar el formulario (primer campo)
 */
function trackFormStart(formId, formType) {
  if (typeof gtag === 'function') {
    gtag('event', 'form_start', {
      'event_category': 'forms',
      'event_label': formType,
      'form_id': formId,
      'form_type': formType
    });
    logEvent('form_start', { formId, formType });
  }
}

/**
 * Evento: form_submit
 * Se dispara cuando se envía el formulario
 */
function trackFormSubmit(formId, formType, formData) {
  formData = formData || {};
  if (typeof gtag === 'function') {
    gtag('event', 'form_submit', {
      'event_category': 'forms',
      'event_label': formType,
      'form_id': formId,
      'form_type': formType,
      'perfil': formData.perfil || '',
      'plan': formData.plan || '',
      'edad': formData.edad || ''
    });
    logEvent('form_submit', { formId, formType, formData });
  }
}

/**
 * Evento: form_error
 * Se dispara cuando hay errores de validación
 */
function trackFormError(formId, errorField, errorMessage) {
  if (typeof gtag === 'function') {
    gtag('event', 'form_error', {
      'event_category': 'forms',
      'event_label': formId + '_error',
      'form_id': formId,
      'error_field': errorField,
      'error_message': errorMessage
    });
    logEvent('form_error', { formId, errorField, errorMessage });
  }
}

// ========================================
// EVENTOS DE PLANES
// ========================================

/**
 * Evento: plan_view
 * Se dispara cuando se hace click en una tarjeta de plan
 */
function trackPlanView(planName) {
  if (typeof gtag === 'function') {
    gtag('event', 'plan_view', {
      'event_category': 'plans',
      'event_label': planName,
      'plan_name': planName
    });
    logEvent('plan_view', { planName });
  }
}

/**
 * Evento: plan_select
 * Se dispara cuando se selecciona un plan en el formulario
 */
function trackPlanSelect(planName) {
  if (typeof gtag === 'function') {
    gtag('event', 'plan_select', {
      'event_category': 'plans',
      'event_label': planName,
      'plan_name': planName
    });
    logEvent('plan_select', { planName });
  }
}

// ========================================
// EVENTOS DE SANATORIOS
// ========================================

/**
 * Evento: sanatorio_view
 * Se dispara cuando se hace click en un sanatorio del carrusel
 */
function trackSanatorio(sanatorioName) {
  if (typeof gtag === 'function') {
    gtag('event', 'sanatorio_view', {
      'event_category': 'sanatorios',
      'event_label': sanatorioName,
      'sanatorio_name': sanatorioName
    });
    logEvent('sanatorio_view', { sanatorioName });
  }
}

// ========================================
// EVENTOS DE CTA (Call to Action)
// ========================================

/**
 * Evento: cta_click
 * Se dispara en cualquier botón de llamado a acción
 */
function trackCTA(ctaLocation) {
  if (typeof gtag === 'function') {
    gtag('event', 'cta_click', {
      'event_category': 'engagement',
      'event_label': ctaLocation,
      'cta_location': ctaLocation
    });
    logEvent('cta_click', { ctaLocation });
  }
}

// ========================================
// EVENTO DE CONVERSIÓN PRINCIPAL
// ========================================

/**
 * Evento: generate_lead
 * Se dispara cuando se completa exitosamente un formulario
 * Este es el evento de conversión principal para GA4
 */
function trackConversion(formType, userData) {
  userData = userData || {};
  if (typeof gtag === 'function') {
    gtag('event', 'generate_lead', {
      'event_category': 'conversion',
      'event_label': formType,
      'form_type': formType,
      'value': 1,
      'currency': 'ARS',
      'perfil': userData.perfil || '',
      'plan': userData.plan || ''
    });
    logEvent('generate_lead', { formType, userData });
  }
}

// ========================================
// SCROLL TRACKING
// ========================================

/**
 * Tracking de scroll depth (25%, 50%, 75%, 90%)
 */
let scrollDepths = [25, 50, 75, 90];
let trackedDepths = [];

window.addEventListener('scroll', function() {
  const scrollPercent = Math.round(
    (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100
  );

  scrollDepths.forEach(function(depth) {
    if (scrollPercent >= depth && trackedDepths.indexOf(depth) === -1) {
      trackedDepths.push(depth);
      if (typeof gtag === 'function') {
        gtag('event', 'scroll', {
          'event_category': 'engagement',
          'event_label': depth + '%',
          'scroll_depth': depth
        });
        logEvent('scroll', { depth: depth + '%' });
      }
    }
  });
});

// ========================================
// INTERSECTION OBSERVER - Form Views
// ========================================

/**
 * Detecta cuando los formularios entran en viewport
 */
document.addEventListener('DOMContentLoaded', function() {
  const forms = document.querySelectorAll('.form-card form');

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            const formId = entry.target.id;
            if (formId) {
              trackFormView(formId);
              observer.unobserve(entry.target);
            }
          }
        });
      },
      { threshold: 0.5 }
    );

    forms.forEach(function(form) {
      if (form.id) {
        observer.observe(form);
      }
    });
  }
});

// ========================================
// ENHANCED MEASUREMENT - Link Clicks
// ========================================

/**
 * Tracking de clicks en links externos y navegación
 */
document.addEventListener('click', function(e) {
  let link = e.target.closest('a');
  if (!link) return;

  const href = link.getAttribute('href');
  if (!href) return;

  // Links externos
  if (href.indexOf('http') === 0 && href.indexOf(window.location.hostname) === -1) {
    if (typeof gtag === 'function') {
      gtag('event', 'click', {
        'event_category': 'outbound',
        'event_label': href,
        'link_url': href
      });
      logEvent('outbound_click', { url: href });
    }
  }

  // Links de ancla (navegación interna)
  if (href.indexOf('#') === 0) {
    if (typeof gtag === 'function') {
      gtag('event', 'click', {
        'event_category': 'navigation',
        'event_label': href,
        'link_text': link.textContent.trim()
      });
      logEvent('anchor_click', { anchor: href });
    }
  }
});

// ========================================
// EXPORTAR FUNCIONES GLOBALES
// ========================================

window.trackFormView = trackFormView;
window.trackFormStart = trackFormStart;
window.trackFormSubmit = trackFormSubmit;
window.trackFormError = trackFormError;
window.trackPlanView = trackPlanView;
window.trackPlanSelect = trackPlanSelect;
window.trackSanatorio = trackSanatorio;
window.trackCTA = trackCTA;
window.trackConversion = trackConversion;

console.log('[GA4] Analytics tracking initialized');

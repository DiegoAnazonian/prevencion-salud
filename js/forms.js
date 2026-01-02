/* ========================================
   VALIDACIÓN Y MANEJO DE FORMULARIOS
   ======================================== */

/**
 * Sistema robusto de validación de formularios con tracking de GA4
 */

// Estado del formulario
const formState = {
  particular: { started: false },
  empresa: { started: false }
};

// ========================================
// VALIDACIÓN DE CAMPOS
// ========================================

const validators = {
  // Validar nombre (min 3 caracteres, solo letras y espacios)
  nombre: function(value) {
    if (value.length < 3) {
      return 'El nombre debe tener al menos 3 caracteres';
    }
    if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value)) {
      return 'El nombre solo puede contener letras';
    }
    return '';
  },

  // Validar email
  email: function(value) {
    const emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
    if (!emailRegex.test(value)) {
      return 'Por favor ingresá un email válido';
    }
    return '';
  },

  // Validar teléfono (8-15 dígitos)
  telefono: function(value) {
    const phoneRegex = /^[0-9]{8,15}$/;
    const cleanPhone = value.replace(/[\s\-()]/g, '');
    if (!phoneRegex.test(cleanPhone)) {
      return 'Por favor ingresá un teléfono válido (8-15 dígitos)';
    }
    return '';
  },

  // Validar edad (18-59)
  edad: function(value) {
    const age = parseInt(value);
    if (isNaN(age)) {
      return 'Por favor ingresá una edad válida';
    }
    if (age < 18 || age > 59) {
      return 'La edad debe estar entre 18 y 59 años';
    }
    return '';
  },

  // Validar select (no vacío)
  select: function(value) {
    if (!value || value === '') {
      return 'Por favor seleccioná una opción';
    }
    return '';
  },

  // Validar empresa
  empresa: function(value) {
    if (value.length < 3) {
      return 'El nombre de la empresa debe tener al menos 3 caracteres';
    }
    return '';
  },

  // Validar empleados
  empleados: function(value) {
    const num = parseInt(value);
    if (isNaN(num) || num < 1) {
      return 'Por favor ingresá un número válido de empleados';
    }
    return '';
  },

  // Validar contacto
  contacto: function(value) {
    if (value.length < 3) {
      return 'El nombre de contacto debe tener al menos 3 caracteres';
    }
    return '';
  },

  // Validar residencia
  residencia: function(value) {
    if (value.length < 2) {
      return 'Por favor ingresá tu localidad';
    }
    return '';
  }
};

// ========================================
// VALIDAR CAMPO INDIVIDUAL
// ========================================

function validateField(field) {
  const fieldName = field.name;
  const fieldValue = field.value.trim();
  const fieldType = field.tagName === 'SELECT' ? 'select' : fieldName;

  let errorMessage = '';

  // Aplicar validador correspondiente
  if (validators[fieldType]) {
    errorMessage = validators[fieldType](fieldValue);
  } else if (validators[fieldName]) {
    errorMessage = validators[fieldName](fieldValue);
  }

  // Mostrar/ocultar error
  const formGroup = field.closest('.form-group');
  const errorElement = formGroup.querySelector('.error-message');

  if (errorMessage) {
    formGroup.classList.add('has-error');
    field.classList.add('error');
    errorElement.textContent = errorMessage;

    // Track error
    const form = field.closest('form');
    if (form && form.id && typeof trackFormError === 'function') {
      trackFormError(form.id, fieldName, errorMessage);
    }

    return false;
  } else {
    formGroup.classList.remove('has-error');
    field.classList.remove('error');
    errorElement.textContent = '';
    return true;
  }
}

// ========================================
// VALIDAR FORMULARIO COMPLETO
// ========================================

function validateForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll('[required]');

  requiredFields.forEach(function(field) {
    if (!validateField(field)) {
      isValid = false;
    }
  });

  return isValid;
}

// ========================================
// INICIALIZAR FORMULARIOS
// ========================================

function initializeForms() {
  const forms = document.querySelectorAll('form');

  forms.forEach(function(form) {
    const formId = form.id;
    const formTypeInput = form.querySelector('[name="tipo"]');
    const formType = formTypeInput ? formTypeInput.value : 'unknown';

    // Track form start (primer campo completado)
    const allFields = form.querySelectorAll('input, select, textarea');
    allFields.forEach(function(field) {
      field.addEventListener('input', function() {
        if (!formState[formType] || !formState[formType].started) {
          if (!formState[formType]) {
            formState[formType] = {};
          }
          formState[formType].started = true;
          if (typeof trackFormStart === 'function') {
            trackFormStart(formId, formType);
          }
        }
      }, { once: true });
    });

    // Validación en tiempo real
    allFields.forEach(function(field) {
      // Validar al salir del campo (blur)
      field.addEventListener('blur', function() {
        if (this.value.trim()) {
          validateField(this);
        }
      });

      // Limpiar error al escribir
      field.addEventListener('input', function() {
        if (this.classList.contains('error')) {
          const formGroup = this.closest('.form-group');
          formGroup.classList.remove('has-error');
          this.classList.remove('error');
        }
      });
    });

    // Tracking de selección de plan
    const planSelect = form.querySelector('[name="plan"]');
    if (planSelect) {
      planSelect.addEventListener('change', function() {
        if (this.value && typeof trackPlanSelect === 'function') {
          trackPlanSelect(this.value);
        }
      });
    }

    // Submit handler
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      // Validar formulario
      if (!validateForm(this)) {
        // Scroll al primer error
        const firstError = this.querySelector('.has-error');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
      }

      // Deshabilitar botón para evitar doble submit
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.classList.add('loading');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Enviando...';

      // Recopilar datos para tracking
      const formData = new FormData(this);
      const userData = {
        perfil: formData.get('perfil') || '',
        plan: formData.get('plan') || '',
        edad: formData.get('edad') || ''
      };

      // Track submit
      if (typeof trackFormSubmit === 'function') {
        trackFormSubmit(formId, formType, userData);
      }

      // Enviar formulario
      fetch(this.action, {
        method: 'POST',
        body: formData
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        if (data.success) {
          // Track conversión
          if (typeof trackConversion === 'function') {
            trackConversion(formType, userData);
          }

          // Redirigir a página de agradecimiento
          window.location.href = 'gracias.html';
        } else {
          // Mostrar error
          alert(data.message || 'Hubo un error al enviar el formulario. Por favor intentá nuevamente.');
          submitBtn.disabled = false;
          submitBtn.classList.remove('loading');
          submitBtn.textContent = originalText;
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        alert('Hubo un error al enviar el formulario. Por favor intentá nuevamente.');
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.textContent = originalText;
      });

      return false;
    });
  });
}

// ========================================
// FUNCIÓN GLOBAL PARA SELECCIONAR PLAN
// ========================================

function selectPlan(planName) {
  // Scroll al formulario
  const formSection = document.getElementById('contacto');
  if (formSection) {
    formSection.scrollIntoView({ behavior: 'smooth' });

    // Pre-seleccionar el plan en el formulario de particulares
    setTimeout(function() {
      const planSelect = document.querySelector('#form-particular [name="plan"]');
      if (planSelect) {
        planSelect.value = planName;
        const event = new Event('change');
        planSelect.dispatchEvent(event);
      }
    }, 800);
  }
}

// ========================================
// INICIALIZAR AL CARGAR LA PÁGINA
// ========================================

document.addEventListener('DOMContentLoaded', function() {
  initializeForms();
  console.log('[Forms] Form validation and tracking initialized');
});

// Exportar función global
window.selectPlan = selectPlan;

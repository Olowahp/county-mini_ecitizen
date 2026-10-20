/* Global SweetAlert2 feedback and confirmation support for Murang'a County E-Services. */
(function () {
  'use strict';
  function popup(options) {
    if (window.Swal && typeof window.Swal.fire === 'function') return window.Swal.fire(options);
    window.alert([options.title, options.text].filter(Boolean).join('\n\n'));
    return Promise.resolve({ isConfirmed: true });
  }
  function alertIcon(type) {
    type = String(type || '').toLowerCase();
    if (/success|saved|created|updated|published|complete/.test(type)) return 'success';
    if (/error|danger|failed|invalid/.test(type)) return 'error';
    if (/warning|warn/.test(type)) return 'warning';
    return 'info';
  }
  function showFlash() {
    var flash = document.getElementById('portal-flash-notice');
    if (flash) {
      var message = (flash.dataset.message || '').trim();
      if (message && flash.dataset.sweetShown !== '1') {
        flash.dataset.sweetShown = '1';
        var icon = alertIcon(flash.dataset.type);
        popup({ icon: icon, title: icon === 'success' ? 'Success' : icon === 'error' ? 'Unable to complete' : icon === 'warning' ? 'Please note' : 'Information', text: message, confirmButtonText: 'OK', confirmButtonColor: '#08783e', allowOutsideClick: false });
      }
    }
    document.querySelectorAll('.alert, .flash, .alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function (el) {
      if (el.dataset.sweetAlertHandled === '1') return;
      var cls = (el.className || '').toLowerCase();
      var icon = alertIcon(cls);
      var message = (el.innerText || el.textContent || '').trim();
      if (!message) return;
      el.dataset.sweetAlertHandled = '1';
      popup({ icon: icon, title: icon === 'success' ? 'Success' : icon === 'error' ? 'Please check the form' : icon === 'warning' ? 'Notice' : 'Information', text: message, confirmButtonColor: '#08783e' });
    });
  }
  function initForms() {
    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('invalid', function (event) {
        var field = event.target;
        if (!field || !field.validationMessage || form.dataset.invalidAlertShown === '1') return;
        form.dataset.invalidAlertShown = '1';
        popup({ icon: 'warning', title: 'Check the form', text: field.validationMessage, confirmButtonText: 'Review field', confirmButtonColor: '#08783e' }).then(function () {
          delete form.dataset.invalidAlertShown;
          if (field.focus) field.focus();
        });
      }, true);
      form.addEventListener('submit', function (event) {
        if (form.dataset.sweetConfirmed === '1') {
          delete form.dataset.sweetConfirmed;
          return;
        }
        var submitter = event.submitter;
        var submitText = submitter ? (submitter.innerText || submitter.value || '') : '';
        var explicitConfirm = form.getAttribute('data-confirm') || form.getAttribute('data-swal-confirm');
        var destructive = /\b(delete|remove|reject|cancel|deactivate|disable|revoke)\b/i.test(submitText) || form.matches('[data-destructive="true"]');
        if (explicitConfirm || destructive) {
          event.preventDefault();
          var message = explicitConfirm || 'This action may change or remove information. Do you want to continue?';
          popup({ title: 'Confirm action', text: message, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, continue', cancelButtonText: 'Go back', confirmButtonColor: '#08783e', cancelButtonColor: '#68766f', reverseButtons: true }).then(function (result) {
            if (result.isConfirmed) {
              form.dataset.sweetConfirmed = '1';
              if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
              else form.submit();
            }
          });
          return;
        }
        /* Show a lightweight processing dialog for valid form submissions. The next page
           displays the server's flash message after the operation completes. */
        if (form.dataset.processingShown !== '1' && form.checkValidity()) {
          form.dataset.processingShown = '1';
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({ title: 'Processing your request', text: 'Please wait…', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: function () { window.Swal.showLoading(); } });
          }
        }
      });
    });
  }
  function start() { showFlash(); initForms(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

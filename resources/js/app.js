import './bootstrap';

// Menu móvel e bloqueio de duplo envio dos formulários (assets/app.js do Campus).
document.querySelector('.menu-button')?.addEventListener('click', function () {
  const open = document.body.classList.toggle('menu-open');
  this.setAttribute('aria-expanded', String(open));
});
document.querySelectorAll('form[method="post"]').forEach(form => {
  form.addEventListener('submit', event => {
    const submitter = event.submitter;
    if (submitter?.name) {
      const field = document.createElement('input');
      field.type = 'hidden'; field.name = submitter.name; field.value = submitter.value;
      field.dataset.submitAction = '1'; form.appendChild(field);
    }
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
      button.dataset.originalText = button.textContent;
      button.disabled = true; button.textContent = 'Aguarde…';
    });
  });
});
window.addEventListener('pageshow', () => {
  document.querySelectorAll('form button').forEach(button => {
    if (button.dataset.originalText) { button.disabled = false; button.textContent = button.dataset.originalText; }
  });
  document.querySelectorAll('[data-submit-action]').forEach(field => field.remove());
});

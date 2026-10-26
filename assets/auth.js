'use strict';
function switchTab(which) {
  const selected = which === 'registro' ? 'registro' : 'entrar';
  for (const name of ['entrar', 'registro']) {
    const active = name === selected;
    const panel = document.getElementById('p-' + name);
    const tab = document.getElementById('t-' + name);
    if (!panel || !tab) continue;
    panel.hidden = !active;
    tab.classList.toggle('active', active);
    tab.setAttribute('aria-selected', active ? 'true' : 'false');
    tab.tabIndex = active ? 0 : -1;
  }
}
document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-toggle-password]');
  if (!button) return;
  const input = document.getElementById(button.dataset.togglePassword);
  if (!input) return;
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  button.setAttribute('aria-pressed', show ? 'true' : 'false');
  button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
  const icon = button.querySelector('i');
  if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});

(function () {
  'use strict';
  var body = document.body;
  var toggleButtons = document.querySelectorAll('[data-toggle-sidebar]');
  var topToggle = document.getElementById('sidebarToggle');
  var backdrop = document.getElementById('sidebarBackdrop');
  var mobile = window.matchMedia('(max-width: 760px)');
  function stored(key) { try { return window.localStorage.getItem(key); } catch (_) { return null; } }
  function save(key, value) { try { window.localStorage.setItem(key, value); } catch (_) {} }
  function sync() {
    var visible = mobile.matches ? body.classList.contains('mobile-nav-open') : !body.classList.contains('nav-collapsed');
    if (topToggle) {
      topToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
      topToggle.setAttribute('aria-label', visible ? 'Ocultar menú lateral' : 'Mostrar menú lateral');
      topToggle.setAttribute('title', visible ? 'Ocultar menú' : 'Mostrar menú');
    }
  }
  function closeMobile() { body.classList.remove('mobile-nav-open'); sync(); }
  function toggleSidebar() {
    if (mobile.matches) body.classList.toggle('mobile-nav-open');
    else { body.classList.toggle('nav-collapsed'); save('inac-sidebar-collapsed', body.classList.contains('nav-collapsed') ? '1' : '0'); }
    sync();
  }
  if (stored('inac-sidebar-collapsed') === '1') body.classList.add('nav-collapsed');
  toggleButtons.forEach(function (btn) { btn.addEventListener('click', toggleSidebar); });
  if (backdrop) backdrop.addEventListener('click', closeMobile);
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeMobile(); });
  document.querySelectorAll('.sidebar-group[data-group]').forEach(function (group) {
    var key = 'inac-nav-group-' + group.dataset.group;
    if (stored(key) === 'closed') group.open = false;
    group.addEventListener('toggle', function () { save(key, group.open ? 'open' : 'closed'); });
  });
  if (typeof mobile.addEventListener === 'function') mobile.addEventListener('change', function () { closeMobile(); });
  sync();
}());

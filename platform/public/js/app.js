// Mobile sidebar toggle
document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-toggle-side]');
  var side = document.querySelector('.side');
  if (t && side) { side.classList.toggle('open'); e.preventDefault(); return; }
  if (side && side.classList.contains('open') && !e.target.closest('.side')) side.classList.remove('open');
});
// Confirm dangerous actions
document.addEventListener('submit', function (e) {
  var msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});
// Show a "uploading…" state on forms with files (uploads can take minutes on slow connections)
document.addEventListener('submit', function (e) {
  var f = e.target;
  if (e.defaultPrevented || !f.querySelector('input[type=file]')) return;
  var b = f.querySelector('button[type=submit]');
  if (b) { b.disabled = true; b.textContent = 'Uploading… please keep this page open'; }
});

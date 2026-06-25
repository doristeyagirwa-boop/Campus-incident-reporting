// CampusGuard — app.js
// Client-side enhancements. The site works without JS (all logic is PHP).

document.addEventListener('DOMContentLoaded', function () {

  // ---- Auto-dismiss flash messages after 5 seconds ----
  const flashes = document.querySelectorAll('.form-error, .form-success');
  flashes.forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 500);
    }, 5000);
  });

  // ---- File upload zone label ----
  const uploadInput = document.getElementById('attachment');
  const uploadZone  = document.getElementById('upload-zone');
  if (uploadInput && uploadZone) {
    uploadInput.addEventListener('change', function () {
      const name = uploadInput.files[0] ? uploadInput.files[0].name : 'Click to choose a file';
      uploadZone.querySelector('.upload-label').textContent = name;
    });
    uploadZone.addEventListener('click', function () { uploadInput.click(); });
  }

  // ---- Confirm before destructive actions ----
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) { e.preventDefault(); }
    });
  });

  // ---- Live search filter (client-side, for smaller lists) ----
  const searchBox = document.getElementById('live-search');
  if (searchBox) {
    searchBox.addEventListener('input', function () {
      const q = searchBox.value.toLowerCase();
      document.querySelectorAll('.incident-row[data-title]').forEach(function (row) {
        const match = row.dataset.title.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
      });
    });
  }

});

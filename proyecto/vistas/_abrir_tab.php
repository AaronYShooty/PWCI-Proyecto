<?php // Abre la pestaña indicada en ?tab= (por ejemplo después de filtrar o de una acción) ?>
<script>
window.addEventListener('load', function () {
  var tab = new URLSearchParams(location.search).get('tab');
  var enlace = tab ? document.querySelector('.dash-nav a[data-tab="' + tab.replace(/[^a-z-]/g, '') + '"]') : null;
  if (enlace) enlace.click();
});
</script>
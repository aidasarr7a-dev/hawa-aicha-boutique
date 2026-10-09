// Hawa Aïcha Boutique — petits scripts

document.addEventListener('DOMContentLoaded', function () {
  // Boutons + / - sur les quantités
  document.querySelectorAll('[data-qty-step]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var step = parseInt(btn.dataset.qtyStep, 10);
      var min = parseInt(input.min || '1', 10);
      var max = parseInt(input.max || '999', 10);
      var val = (parseInt(input.value, 10) || min) + step;
      input.value = Math.max(min, Math.min(max, val));
      if (input.dataset.autosubmit === '1' && input.form) input.form.submit();
    });
  });

  // Formulaire de contact : ouvre WhatsApp avec le message pré-rempli
  var waForm = document.getElementById('wa-form');
  if (waForm) {
    waForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var nom = waForm.nom.value.trim();
      var produit = waForm.produit.value.trim();
      var msg = waForm.message.value.trim();
      var texte = 'Bonjour Hawa Aïcha Boutique' + (nom ? ', je suis ' + nom : '') + '.\n' +
        (produit ? 'Produit concerné : ' + produit + '\n' : '') + msg;
      window.open('https://wa.me/' + waForm.dataset.number + '?text=' + encodeURIComponent(texte), '_blank', 'noopener');
    });
  }

  // Disparition automatique des alertes
  document.querySelectorAll('.alert-success').forEach(function (a) {
    setTimeout(function () {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close();
    }, 5000);
  });
});

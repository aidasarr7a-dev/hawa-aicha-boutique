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

  // Formulaires à envoi unique : le bouton se désactive après le premier clic
  document.querySelectorAll('form[data-envoi-unique]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button:not([type="button"])');
      if (!btn) return;
      setTimeout(function () { btn.disabled = true; }, 0);
      if (btn.dataset.texteEnvoi) btn.textContent = btn.dataset.texteEnvoi;
    });
  });

  // Commande : l'adresse de livraison n'est demandée que pour la livraison à domicile
  var blocAdresse = document.querySelector('[data-bloc-adresse]');
  if (blocAdresse) {
    var radios = document.querySelectorAll('input[name="mode_livraison"]');
    var majAdresse = function () {
      var domicile = radios[0] && radios[0].checked;
      blocAdresse.hidden = !domicile;
      blocAdresse.querySelector('textarea').required = domicile;
    };
    radios.forEach(function (r) { r.addEventListener('change', majAdresse); });
    majAdresse();
  }

  // Commande : afficher les consignes du mode de paiement choisi (numéro, montant, #144#…)
  var radiosPaiement = document.querySelectorAll('input[name="paiement"]');
  if (radiosPaiement.length) {
    var majPaiement = function () {
      var choisi = document.querySelector('input[name="paiement"]:checked');
      document.querySelectorAll('[data-infos-paiement]').forEach(function (bloc) {
        bloc.hidden = !choisi || bloc.dataset.infosPaiement !== choisi.value;
      });
    };
    radiosPaiement.forEach(function (r) { r.addEventListener('change', majPaiement); });
    majPaiement();
  }

  // Paiement : copier le numéro Wave / Orange Money
  document.querySelectorAll('[data-copier]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var texte = btn.dataset.copier;
      var ok = function () { btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié'; };
      if (navigator.clipboard) {
        navigator.clipboard.writeText(texte).then(ok, function () { window.prompt('Numéro à copier :', texte); });
      } else {
        window.prompt('Numéro à copier :', texte);
      }
    });
  });

  // Disparition automatique des alertes
  document.querySelectorAll('.alert-success').forEach(function (a) {
    setTimeout(function () {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close();
    }, 5000);
  });
});

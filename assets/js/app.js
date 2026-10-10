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
    var bouton = document.getElementById('bouton-commande');
    var majPaiement = function () {
      var choisi = document.querySelector('input[name="paiement"]:checked');
      document.querySelectorAll('[data-infos-paiement]').forEach(function (bloc) {
        bloc.hidden = !choisi || bloc.dataset.infosPaiement !== choisi.value;
      });
      // Le bouton indique clairement l'action : commander et payer avec le mode choisi
      if (bouton && choisi) {
        var libelle = choisi.value === 'Espèces'
          ? 'Commander · payer en espèces'
          : 'Commander et payer avec ' + choisi.value;
        bouton.querySelector('span').textContent = libelle + ' (' + bouton.dataset.total + ')';
      }
    };
    radiosPaiement.forEach(function (r) { r.addEventListener('change', majPaiement); });
    majPaiement();
  }

  // Cartes de choix : classe « choisi » (affichage correct même sur les navigateurs anciens)
  document.querySelectorAll('.choix-carte input[type="radio"]').forEach(function (radio) {
    var maj = function () {
      document.querySelectorAll('input[name="' + radio.name + '"]').forEach(function (r) {
        r.closest('.choix-carte').classList.toggle('choisi', r.checked);
      });
    };
    radio.addEventListener('change', maj);
    if (radio.checked) maj();
  });

  // Bouton « Ouvrir l'application Wave » : application sur Android, App Store sur iPhone
  var ua = navigator.userAgent || '';
  var estAndroid = /Android/i.test(ua);
  var estIOS = /iPhone|iPad|iPod/i.test(ua);
  var estMobile = estAndroid || estIOS;
  document.querySelectorAll('[data-ouvrir-wave]').forEach(function (lien) {
    if (estAndroid) {
      lien.href = 'intent://#Intent;action=android.intent.action.MAIN;category=android.intent.category.LAUNCHER;package=com.wave.personal;'
        + 'S.browser_fallback_url=' + encodeURIComponent('https://play.google.com/store/apps/details?id=com.wave.personal') + ';end';
    } else if (estIOS) {
      lien.href = 'https://apps.apple.com/app/id1523884528';
    }
  });

  // Sur ordinateur : QR code à scanner avec le téléphone (compose #144# pour Orange Money)
  document.querySelectorAll('[data-qr]').forEach(function (zone) {
    if (estMobile) { zone.hidden = true; return; }
    zone.hidden = false;
    var cible = zone.querySelector('.qr-code');
    if (window.QRCode && cible && !cible.hasChildNodes()) {
      new QRCode(cible, { text: zone.dataset.qr, width: 110, height: 110, colorDark: '#2b1b23', colorLight: '#ffffff' });
    }
  });
  document.querySelectorAll('[data-mobile-seulement]').forEach(function (el) {
    if (!estMobile) el.classList.add('sur-ordinateur');
  });

  // Paiement : copier le numéro Wave / Orange Money
  document.querySelectorAll('[data-copier]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var texte = btn.dataset.copier;
      var ok = function () { btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié'; };
      var secours = function () {
        // Copie compatible avec tous les navigateurs (y compris sans HTTPS)
        var zone = document.createElement('textarea');
        zone.value = texte; zone.setAttribute('readonly', ''); zone.style.position = 'fixed'; zone.style.opacity = '0';
        document.body.appendChild(zone); zone.select();
        var reussi = false;
        try { reussi = document.execCommand('copy'); } catch (err) {}
        document.body.removeChild(zone);
        if (reussi) { ok(); } else { window.prompt('Numéro à copier :', texte); }
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texte).then(ok, secours);
      } else {
        secours();
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

// Hawa Aïcha Boutique — espace administrateur : notifications « Toast » et fenêtre de confirmation

(function () {
  'use strict';

  var TYPES = {
    success: ['fa-circle-check', 'Succès'],
    danger: ['fa-circle-exclamation', 'Erreur'],
    warning: ['fa-triangle-exclamation', 'Attention'],
    info: ['fa-circle-info', 'Information']
  };

  // Affiche un toast déjà présent dans la page (rendu par PHP) : disparition automatique + barre de temps
  function montrer(el) {
    var delai = parseInt(el.dataset.bsDelay || '5000', 10);
    var barre = el.querySelector('.admin-toast-temps');
    if (barre) barre.style.animationDuration = delai + 'ms';
    // La barre et le minuteur se mettent en pause au survol, pour laisser le temps de lire
    el.addEventListener('mouseenter', function () { if (barre) barre.style.animationPlayState = 'paused'; });
    el.addEventListener('mouseleave', function () { if (barre) barre.style.animationPlayState = 'running'; });
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    bootstrap.Toast.getOrCreateInstance(el, { delay: delai, autohide: true }).show();
  }

  // Crée un toast depuis JavaScript : adminToast('success', 'Message')
  window.adminToast = function (type, message) {
    var t = TYPES[type] ? type : 'info';
    var zone = document.getElementById('admin-toasts');
    if (!zone) return;
    var el = document.createElement('div');
    el.className = 'toast admin-toast toast-' + t;
    el.setAttribute('role', t === 'danger' ? 'alert' : 'status');
    el.dataset.bsDelay = t === 'danger' ? '8000' : '5000';
    el.innerHTML =
      '<div class="admin-toast-corps">' +
        '<span class="admin-toast-ic"><i class="fa-solid ' + TYPES[t][0] + '"></i></span>' +
        '<div class="flex-grow-1 min-w-0"><strong class="d-block"></strong><div class="admin-toast-msg"></div></div>' +
        '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>' +
      '</div><span class="admin-toast-temps"></span>';
    el.querySelector('strong').textContent = TYPES[t][1];
    el.querySelector('.admin-toast-msg').textContent = message;
    zone.appendChild(el);
    montrer(el);
  };

  // Fenêtre de confirmation personnalisée (remplace confirm() du navigateur)
  var modale, modaleBs, formEnAttente, envoyeurEnAttente;

  function creerModale() {
    modale = document.createElement('div');
    modale.className = 'modal fade';
    modale.id = 'admin-confirmation';
    modale.tabIndex = -1;
    modale.setAttribute('aria-labelledby', 'admin-confirmation-titre');
    modale.setAttribute('aria-hidden', 'true');
    modale.innerHTML =
      '<div class="modal-dialog modal-dialog-centered">' +
        '<div class="modal-content admin-confirm">' +
          '<div class="modal-body">' +
            '<span class="admin-confirm-ic"><i class="fa-solid fa-triangle-exclamation"></i></span>' +
            '<h2 class="h5" id="admin-confirmation-titre"></h2>' +
            '<p class="admin-confirm-msg mb-0"></p>' +
          '</div>' +
          '<div class="admin-confirm-boutons">' +
            '<button type="button" class="btn btn-outline-rose" data-bs-dismiss="modal">Annuler</button>' +
            '<button type="button" class="btn" data-confirm-valider></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modale);
    modaleBs = new bootstrap.Modal(modale);

    modale.querySelector('[data-confirm-valider]').addEventListener('click', function () {
      var form = formEnAttente, envoyeur = envoyeurEnAttente;
      formEnAttente = envoyeurEnAttente = null;
      modaleBs.hide();
      if (!form) return;
      form.dataset.confirme = '1';
      if (form.requestSubmit) {
        form.requestSubmit(envoyeur || undefined);
      } else {
        form.submit();
      }
    });
    modale.addEventListener('hidden.bs.modal', function () { formEnAttente = envoyeurEnAttente = null; });
  }

  function demanderConfirmation(form, envoyeur) {
    if (!modale) creerModale();
    var danger = (form.dataset.confirmType || 'danger') === 'danger';
    modale.querySelector('.admin-confirm').classList.toggle('admin-confirm-rose', !danger);
    modale.querySelector('.admin-confirm-ic i').className = 'fa-solid ' + (danger ? 'fa-triangle-exclamation' : 'fa-circle-question');
    modale.querySelector('#admin-confirmation-titre').textContent = form.dataset.confirmTitre || 'Confirmer cette action';
    modale.querySelector('.admin-confirm-msg').textContent = form.dataset.confirm;
    var valider = modale.querySelector('[data-confirm-valider]');
    valider.textContent = form.dataset.confirmOk || 'Confirmer';
    valider.className = 'btn ' + (danger ? 'btn-danger-doux' : 'btn-rose');
    formEnAttente = form;
    envoyeurEnAttente = envoyeur;
    modaleBs.show();
    modale.addEventListener('shown.bs.modal', function focus() {
      valider.focus();
      modale.removeEventListener('shown.bs.modal', focus);
    });
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form.dataset || !form.dataset.confirm) return;
    if (form.dataset.confirme === '1') { delete form.dataset.confirme; return; }
    // Confirmation conditionnelle : seulement si un champ a une valeur précise (ex. statut = annulee)
    if (form.dataset.confirmSiChamp) {
      var champ = form.elements[form.dataset.confirmSiChamp];
      if (!champ || champ.value !== form.dataset.confirmSiValeur) return;
    }
    ev.preventDefault();
    demanderConfirmation(form, ev.submitter || null);
  });

  // Bouton « afficher / masquer » des champs mot de passe
  document.addEventListener('click', function (ev) {
    var bouton = ev.target.closest('[data-voir-mdp]');
    if (!bouton) return;
    var champ = document.getElementById(bouton.dataset.voirMdp);
    if (!champ) return;
    var visible = champ.type === 'password';
    champ.type = visible ? 'text' : 'password';
    bouton.querySelector('i').className = visible ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    var libelle = visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
    bouton.setAttribute('aria-label', libelle);
    bouton.title = libelle;
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#admin-toasts .toast').forEach(montrer);

    // Fenêtre à rouvrir automatiquement (modification demandée ou formulaire renvoyé avec des erreurs)
    var aOuvrir = document.querySelector('[data-ouvrir-modale]');
    if (aOuvrir) {
      var modaleAuto = document.querySelector(aOuvrir.dataset.ouvrirModale);
      if (modaleAuto) bootstrap.Modal.getOrCreateInstance(modaleAuto).show();
    }
    // Fermer la fenêtre de modification retire « ?modifier=… » de l'adresse
    var modif = document.getElementById('modale-modif');
    if (modif && window.history.replaceState) {
      modif.addEventListener('hidden.bs.modal', function () {
        window.history.replaceState(null, '', window.location.pathname);
      });
    }
  });
})();

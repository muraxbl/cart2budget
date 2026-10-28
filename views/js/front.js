/**
 * CartToBudget - front office (save cart, restore / delete saved carts, copy share link)
 * Configuration and texts come from the "carttobudget" JS variable (Media::addJsDef).
 */
(function () {
  'use strict';

  var cfg = window.carttobudget || {};
  var t = cfg.i18n || {};

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) {
      node.className = className;
    }
    if (text !== undefined && text !== null) {
      node.textContent = text;
    }
    return node;
  }

  var ICONS = { success: '✓', error: '✗', warning: '⚠' };

  function popup(type, title, message, details) {
    var container = document.querySelector('.ctb-popups');
    if (!container) {
      container = el('div', 'ctb-popups');
      document.body.appendChild(container);
    }
    var box = el('div', 'ctb-popup ctb-popup-' + type);
    var body = el('div', 'ctb-popup-body');
    var text = el('div', 'ctb-popup-text');
    var close = el('button', 'ctb-popup-close', '×');
    close.type = 'button';

    body.appendChild(el('div', 'ctb-popup-icon', ICONS[type]));
    text.appendChild(el('div', 'ctb-popup-title', title));
    if (message) {
      text.appendChild(el('div', 'ctb-popup-message', message));
    }
    if (details) {
      text.appendChild(el('div', 'ctb-popup-details', details));
    }
    body.appendChild(text);
    body.appendChild(close);
    box.appendChild(body);
    box.appendChild(el('div', 'ctb-popup-progress'));
    container.appendChild(box);

    var remove = function () {
      box.classList.remove('ctb-show');
      setTimeout(function () { box.remove(); }, 300);
    };
    setTimeout(function () { box.classList.add('ctb-show'); }, 20);
    var timer = setTimeout(remove, 4000);
    close.addEventListener('click', function () {
      clearTimeout(timer);
      remove();
    });
  }

  function confirmDialog(options) {
    var overlay = el('div', 'ctb-dialog-overlay');
    var dialog = el('div', 'ctb-dialog ctb-dialog-' + (options.type || 'warning'));
    var buttons = el('div', 'ctb-dialog-buttons');
    var cancel = el('button', 'btn btn-secondary', t.cancel);
    var ok = el('button', 'btn ' + (options.type === 'error' ? 'btn-danger' : 'btn-primary'), options.confirmText || t.confirm);
    cancel.type = ok.type = 'button';

    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.appendChild(el('div', 'ctb-dialog-icon', ICONS[options.type || 'warning']));
    dialog.appendChild(el('p', 'ctb-dialog-message', options.message));
    if (options.details) {
      dialog.appendChild(el('div', 'ctb-dialog-details', options.details));
    }
    buttons.appendChild(cancel);
    buttons.appendChild(ok);
    dialog.appendChild(buttons);
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    setTimeout(function () { overlay.classList.add('ctb-show'); }, 10);
    ok.focus();

    var close = function () {
      overlay.classList.remove('ctb-show');
      document.removeEventListener('keydown', onKey);
      setTimeout(function () { overlay.remove(); }, 300);
    };
    var onKey = function (e) {
      if (e.key === 'Escape') {
        close();
      }
    };
    document.addEventListener('keydown', onKey);
    cancel.addEventListener('click', close);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        close();
      }
    });
    ok.addEventListener('click', function () {
      close();
      if (options.onConfirm) {
        options.onConfirm();
      }
    });
  }

  function post(params) {
    var body = new URLSearchParams();
    body.append('token', cfg.token || '');
    Object.keys(params).forEach(function (key) {
      body.append(key, params[key]);
    });

    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: body.toString()
    }).then(function (response) {
      return response.json().catch(function () {
        throw new Error('invalid json');
      });
    });
  }

  function busy(button, label) {
    var original = button.innerHTML;
    button.disabled = true;
    button.textContent = label;
    return function () {
      button.disabled = false;
      button.innerHTML = original;
    };
  }

  function initSaveButton() {
    var button = document.getElementById('ctb-save-cart-btn');
    var input = document.getElementById('ctb-cart-name');
    if (!button || button.getAttribute('data-ctb-bound')) {
      return;
    }
    button.setAttribute('data-ctb-bound', '1');

    button.addEventListener('click', function () {
      var restore = busy(button, t.saving);
      post({ action: 'save_cart', cart_name: input ? input.value.trim() : '' })
        .then(function (data) {
          if (data.success) {
            popup('success', t.success, data.message, t.successDetails);
            if (input) {
              input.value = '';
            }
          } else if (data.limit_reached) {
            confirmDialog({
              type: 'warning',
              message: data.message,
              details: data.active_carts + ' / ' + data.max_carts,
              confirmText: t.viewCarts,
              onConfirm: function () { window.location.href = cfg.listUrl; }
            });
          } else {
            popup('error', t.error, data.message);
          }
        })
        .catch(function () { popup('error', t.error, t.connectionError); })
        .then(restore);
    });

    if (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          button.click();
        }
      });
    }
  }

  function initSavedCarts() {
    document.addEventListener('click', function (e) {
      var restoreBtn = e.target.closest('.ctb-btn-restore');
      var deleteBtn = e.target.closest('.ctb-btn-delete');
      var copyBtn = e.target.closest('.ctb-copy-link');

      if (restoreBtn) {
        confirmDialog({
          type: 'warning',
          message: t.restoreMessage,
          details: t.restoreDetails,
          confirmText: t.restore,
          onConfirm: function () {
            var done = busy(restoreBtn, t.restoring);
            post({ action: 'restore_cart', id: restoreBtn.getAttribute('data-id') })
              .then(function (data) {
                if (!data.success) {
                  popup('error', t.error, data.message);
                  done();
                  return;
                }
                popup(data.skipped && data.skipped.length ? 'warning' : 'success', t.restored, t.redirecting, (data.skipped || []).join(', '));
                setTimeout(function () { window.location.href = data.redirect_url || cfg.cartUrl; }, 1500);
              })
              .catch(function () {
                popup('error', t.error, t.connectionError);
                done();
              });
          }
        });
      }

      if (deleteBtn) {
        confirmDialog({
          type: 'error',
          message: t.deleteMessage,
          details: t.deleteDetails,
          confirmText: t['delete'],
          onConfirm: function () {
            var done = busy(deleteBtn, t.deleting);
            post({ action: 'delete_cart', id: deleteBtn.getAttribute('data-id') })
              .then(function (data) {
                if (!data.success) {
                  popup('error', t.error, data.message);
                  done();
                  return;
                }
                popup('success', t.deleted, data.message);
                var card = deleteBtn.closest('.ctb-cart');
                if (card) {
                  card.remove();
                }
                if (!document.querySelector('.ctb-cart')) {
                  setTimeout(function () { window.location.reload(); }, 1000);
                }
              })
              .catch(function () {
                popup('error', t.error, t.connectionError);
                done();
              });
          }
        });
      }

      if (copyBtn) {
        var field = copyBtn.parentNode.querySelector('.ctb-share-url');
        field.select();
        var copied = function () { popup('success', t.linkCopied, ''); };
        if (navigator.clipboard) {
          navigator.clipboard.writeText(field.value).then(copied);
        } else {
          document.execCommand('copy');
          copied();
        }
      }
    });
  }

  function init() {
    initSaveButton();
    initSavedCarts();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // The classic theme re-renders the cart summary through AJAX: re-bind the save button.
  if (window.prestashop && typeof window.prestashop.on === 'function') {
    window.prestashop.on('updatedCart', initSaveButton);
  }
})();

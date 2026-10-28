/**
 * CartToBudget - back office editor (product / customer search, reorder, copy link)
 */
(function () {
  'use strict';

  function debounce(fn, wait) {
    var timer;
    return function () {
      var args = arguments;
      var ctx = this;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function autocomplete(options) {
    var input = document.getElementById(options.input);
    var box = document.getElementById(options.results);
    if (!input || !box) {
      return;
    }
    var items = [];
    var active = -1;

    function close() {
      box.classList.remove('open');
      box.innerHTML = '';
      active = -1;
    }

    function render(results) {
      items = results;
      active = -1;
      if (!results.length) {
        box.innerHTML = '<div class="ctb-result"><em>' + escapeHtml(options.emptyText || '-') + '</em></div>';
        box.classList.add('open');
        return;
      }
      box.innerHTML = results.map(function (item, index) {
        return '<a class="ctb-result' + (item.active === false ? ' inactive' : '') + '" data-index="' + index + '">' + options.renderItem(item) + '</a>';
      }).join('');
      box.classList.add('open');
    }

    var search = debounce(function () {
      var q = input.value.trim();
      if (q.length < 2) {
        close();
        return;
      }
      var url = options.url() + '&q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) { render(data.results || []); })
        .catch(close);
    }, 250);

    input.addEventListener('input', search);
    input.addEventListener('keydown', function (e) {
      var links = box.querySelectorAll('a.ctb-result');
      if (!links.length) {
        return;
      }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
        links.forEach(function (l, i) { l.classList.toggle('active', i === active); });
      } else if (e.key === 'Enter' && active >= 0) {
        e.preventDefault();
        options.onSelect(items[active]);
        close();
      } else if (e.key === 'Escape') {
        close();
      }
    });
    box.addEventListener('mousedown', function (e) {
      var link = e.target.closest('a.ctb-result');
      if (link) {
        e.preventDefault();
        options.onSelect(items[parseInt(link.getAttribute('data-index'), 10)]);
        close();
      }
    });
    input.addEventListener('blur', function () { setTimeout(close, 200); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var editor = document.getElementById('ctb-editor');
    if (!editor) {
      return;
    }
    var ajaxUrl = editor.getAttribute('data-ajax-url');
    var customerField = document.getElementById('ctb-id-customer');

    // Customer search
    autocomplete({
      input: 'ctb-customer-search',
      results: 'ctb-customer-results',
      url: function () { return ajaxUrl + '&action=searchCustomers'; },
      renderItem: function (c) {
        return '<strong>' + escapeHtml(c.name) + '</strong> <small>' + escapeHtml(c.email) + (c.company ? ' - ' + escapeHtml(c.company) : '') + '</small>';
      },
      onSelect: function (c) {
        customerField.value = c.id;
        var selected = document.getElementById('ctb-customer-selected');
        selected.querySelector('.ctb-selected-label').textContent = c.name + ' <' + c.email + '>';
        selected.classList.remove('hidden');
        document.getElementById('ctb-customer-search').value = '';
        // Pre rellenamos los campos con los datos del cliente seleccionado
        var fill = { recipient_name: c.name, recipient_company: c.company, recipient_email: c.email, recipient_vat: c.vat, recipient_address: c.address };
        Object.keys(fill).forEach(function (name) {
          var field = editor.querySelector('[name="' + name + '"]');
          if (field && !field.value.trim() && fill[name]) {
            field.value = fill[name];
          }
        });
      }
    });

    var clear = editor.querySelector('.ctb-clear-customer');
    if (clear) {
      clear.addEventListener('click', function (e) {
        e.preventDefault();
        customerField.value = 0;
        document.getElementById('ctb-customer-selected').classList.add('hidden');
      });
    }

    // Product search
    autocomplete({
      input: 'ctb-product-search',
      results: 'ctb-product-results',
      url: function () {
        return ajaxUrl + '&action=searchProducts&id_customer=' + encodeURIComponent(customerField ? customerField.value : 0);
      },
      renderItem: function (p) {
        return '<span class="ctb-result-price">' + escapeHtml(p.price_formatted) + '</span>'
          + '<strong>' + escapeHtml(p.name) + '</strong>'
          + (p.attributes ? ' <small>' + escapeHtml(p.attributes) + '</small>' : '')
          + '<br><small>' + (p.reference ? escapeHtml(p.reference) + ' &middot; ' : '') + 'Stock: ' + escapeHtml(p.stock) + '</small>';
      },
      onSelect: function (p) {
        document.getElementById('ctb-add-id-product').value = p.id_product;
        document.getElementById('ctb-add-id-product-attribute').value = p.id_product_attribute;
        var selected = document.getElementById('ctb-product-selected');
        selected.querySelector('.ctb-selected-label').textContent = p.name + (p.attributes ? ' - ' + p.attributes : '') + (p.reference ? ' (' + p.reference + ')' : '');
        selected.classList.remove('hidden');
        document.getElementById('ctb-add-price').placeholder = p.price;
        document.getElementById('ctb-product-search').value = '';
        document.getElementById('ctb-add-product-btn').disabled = false;
      }
    });

    // Reorder lines
    editor.addEventListener('click', function (e) {
      var up = e.target.closest('.ctb-move-up');
      var down = e.target.closest('.ctb-move-down');
      if (!up && !down) {
        return;
      }
      e.preventDefault();
      var row = e.target.closest('tr');
      var tbody = row.parentNode;
      if (up && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
      } else if (down && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
      }
      tbody.querySelectorAll('.ctb-position').forEach(function (input, index) {
        input.value = index + 1;
      });
    });

    // Copy share link
    editor.querySelectorAll('.ctb-copy').forEach(function (button) {
      button.addEventListener('click', function () {
        var field = document.querySelector(button.getAttribute('data-target'));
        field.select();
        var done = function () {
          var original = button.innerHTML;
          button.textContent = button.getAttribute('data-copied');
          setTimeout(function () { button.innerHTML = original; }, 1500);
        };
        if (navigator.clipboard) {
          navigator.clipboard.writeText(field.value).then(done);
        } else {
          document.execCommand('copy');
          done();
        }
      });
    });
  });
})();

/**
 * Badge style form: live preview (desktop / mobile) and WCAG contrast warning.
 * A translucent background is checked as mixed with white (typical product image background).
 */
(function () {
  'use strict';

  function field(name) {
    var input = document.querySelector('[name="' + name + '"]');
    return input ? input : null;
  }

  function value(name, fallback) {
    var input = field(name);
    return input && input.value !== '' ? input.value : fallback;
  }

  function checked(name) {
    var input = document.querySelector('[name="' + name + '"]:checked');
    return input ? input.value === '1' : false;
  }

  function isHex(color) {
    return /^#[0-9a-fA-F]{6}$/.test(color);
  }

  function luminance(hex) {
    var rgb = [1, 3, 5].map(function (i) {
      var c = parseInt(hex.substr(i, 2), 16) / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
  }

  function contrast(a, b) {
    var l1 = luminance(a);
    var l2 = luminance(b);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }

  function opacity() {
    var number = parseInt(value('bg_opacity', '100'), 10);
    return isNaN(number) ? 100 : Math.max(0, Math.min(100, number));
  }

  function channels(hex) {
    return [1, 3, 5].map(function (i) {
      return parseInt(hex.substr(i, 2), 16);
    });
  }

  function background(hex, percent) {
    if (percent >= 100) {
      return hex;
    }
    if (percent === 0) {
      return 'transparent';
    }
    return 'rgba(' + channels(hex).join(',') + ',' + percent / 100 + ')';
  }

  // Background color as seen over white.
  function overWhite(hex, percent) {
    return '#' + channels(hex).map(function (c) {
      var mixed = Math.round(c * percent / 100 + 255 * (1 - percent / 100));
      return ('0' + mixed.toString(16)).slice(-2);
    }).join('');
  }

  function px(name, fallback) {
    var number = parseInt(value(name, fallback), 10);
    return (isNaN(number) ? fallback : number) + 'px';
  }

  function update() {
    var bg = value('bg_color', '#222222');
    var color = value('text_color', '#ffffff');
    var borderColor = value('border_color', '#000000');
    var borderWidth = parseInt(value('border_width', '0'), 10) || 0;
    var alpha = opacity();
    var common = {
      background: background(isHex(bg) ? bg : '#222222', alpha),
      color: isHex(color) ? color : '#ffffff',
      fontWeight: value('font_weight', '600'),
      textTransform: checked('uppercase') ? 'uppercase' : 'none',
      borderRadius: px('border_radius', 3),
      border: borderWidth > 0 ? borderWidth + 'px solid ' + (isHex(borderColor) ? borderColor : '#000000') : 'none'
    };

    document.querySelectorAll('#ib-style-live .ib-style-sample').forEach(function (sample) {
      var device = sample.getAttribute('data-device');
      Object.keys(common).forEach(function (key) {
        sample.style[key] = common[key];
      });
      sample.style.fontSize = px('font_size_' + device, device === 'mobile' ? 11 : 13);
      sample.style.padding = px('padding_y_' + device, 4) + ' ' + px('padding_x_' + device, 8);
    });

    var warning = document.querySelector('#ib-style-live .ib-contrast-warning');
    if (warning) {
      warning.style.display = isHex(bg) && isHex(color) && contrast(overWhite(bg, alpha), color) < 4.5 ? '' : 'none';
    }
    var note = document.querySelector('#ib-style-live .ib-translucent-note');
    if (note) {
      note.style.display = alpha < 100 ? '' : 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ib-style-live');
    if (!form) {
      return;
    }
    var container = form.closest('form');
    if (container) {
      container.addEventListener('input', update);
      container.addEventListener('change', update);
    }
    // The color picker changes values without input events.
    setInterval(update, 500);
    update();
  });
})();

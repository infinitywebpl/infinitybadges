/**
 * Badge rule form: field toggling by badge type.
 *
 * Demo version: options of the full version (image badge, text modes, hidden values, hiding system flags)
 * are disabled. The criteria editor is in the full version.
 */
(function () {
  'use strict';

  /*
   * Badge type: feature-only fields and the text field.
   */
  function initTypeToggle() {
    var typeSelect = document.querySelector('select[name="type"]');
    var modeSelect = document.querySelector('select[name="text_mode"]');
    if (!typeSelect) {
      return;
    }

    function show(selector, visible) {
      document.querySelectorAll(selector).forEach(function (group) {
        group.style.display = visible ? '' : 'none';
      });
    }

    function toggle() {
      var isFeature = typeSelect.value === 'feature';
      var isImage = typeSelect.value === 'image';
      // Static text, image alternative text, or custom text of a feature badge.
      var showText = !isFeature || (modeSelect && modeSelect.value === 'custom');
      show('.ib-feature-only', isFeature);
      show('.ib-image-only', isImage);
      show('.ib-not-image', !isImage);
      show('.ib-text-field', showText);
    }

    typeSelect.addEventListener('change', toggle);
    if (modeSelect) {
      modeSelect.addEventListener('change', toggle);
    }
    toggle();
  }

  /*
   * Demo: options of the full version are disabled.
   */
  function initDemo() {
    function disableOption(selectName, value) {
      var option = document.querySelector('select[name="' + selectName + '"] option[value="' + value + '"]');
      if (option) {
        option.disabled = true;
      }
    }

    disableOption('type', 'image');
    disableOption('text_mode', 'feature_name');
    disableOption('text_mode', 'custom');

    document.querySelectorAll('textarea[name="hide_values"], input[name^="hide_flag_"]').forEach(function (field) {
      field.disabled = true;
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initDemo();
    initTypeToggle();
  });
})();

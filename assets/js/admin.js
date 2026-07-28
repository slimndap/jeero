"use strict";

(function () {
  var $fields, $form, $nav_tabs, $tab_fields, $tabs, get_active_tab_index, get_fields, get_form, get_nav_tabs, get_tab_fields, get_tab_subfields, get_tabs, show_tab;
  $form = null;
  $fields = null;
  $tab_fields = null;
  $nav_tabs = null;
  $tabs = null;
  get_form = function () {
    if ($form != null) {
      return $form;
    }
    return $form = jQuery('.jeero-form');
  };
  get_fields = function () {
    if ($fields != null) {
      return $fields;
    }
    return $fields = get_form().find('.jeero-field');
  };
  get_tab_fields = function () {
    if ($tab_fields != null) {
      return $tab_fields;
    }
    return $tab_fields = get_fields().filter('.jeero-field-tab');
  };
  get_tab_subfields = function (tab_index) {
    return get_tab_fields().eq(tab_index).nextUntil('.jeero-field-tab');
  };
  get_nav_tabs = function () {
    if ($nav_tabs != null) {
      return $nav_tabs;
    }
    return $nav_tabs = jQuery('#jeero-nav-tabs');
  };
  get_tabs = function () {
    if ($tabs != null) {
      return $tabs;
    }
    return $tabs = get_nav_tabs().find('.nav-tab');
  };
  get_active_tab_index = function () {
    var tab_index;
    tab_index = parseInt(get_form().find('input[name="jeero_tab"]').val(), 10);
    if (isNaN(tab_index)) {
      return 0;
    } else {
      return tab_index;
    }
  };
  show_tab = function (tab_index) {
    tab_index = Math.max(0, Math.min(tab_index, get_tabs().length - 1));
    get_form().find('input[name="jeero_tab"]').val(tab_index);

    // Hide all fields.
    get_fields().hide();

    // Activate tab nav.
    get_tabs().removeClass('nav-tab-active').eq(tab_index).addClass('nav-tab-active');

    // Show fields of active tab.
    return get_tab_subfields(tab_index).show();
  };
  jQuery(function () {
    $tab_fields = get_tab_fields();
    if ($tab_fields.length) {
      get_form().prepend('<nav class="nav-tab-wrapper wp-clearfix" id="jeero-nav-tabs"></nav>');
      $tab_fields.hide();
      $tab_fields.each(function (index) {
        return get_nav_tabs().append(`<button class=\"nav-tab\" data-tab_index=\"${index}\" type=\"button\">${jQuery(this).text()}</button>`);
      });
      get_tabs().click(function () {
        return show_tab(jQuery(this).data('tab_index'));
      });
      show_tab(get_active_tab_index());
    }
    return get_fields().find('input').on('invalid', function () {
      var $input;
      $input = jQuery(this);
      return get_tab_fields().each(function (index) {
        if (get_tab_subfields(index).has($input).length) {
          return show_tab(index);
        }
      });
    });
  });
}).call(void 0);

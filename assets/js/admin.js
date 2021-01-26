"use strict";

(function () {
  var $fields, $form, $nav_tabs, $tab_fields, $tabs, get_fields, get_form, get_nav_tabs, get_tab_fields, get_tab_subfields, get_tabs, show_tab;
  $form = null;
  $fields = null;
  $tab_fields = null;
  $nav_tabs = null;
  $tabs = null;

  get_form = function get_form() {
    if ($form != null) {
      return $form;
    }

    return $form = jQuery('.jeero-form');
  };

  get_fields = function get_fields() {
    if ($fields != null) {
      return $fields;
    }

    return $fields = get_form().find('.jeero-field');
  };

  get_tab_fields = function get_tab_fields() {
    if ($tab_fields != null) {
      return $tab_fields;
    }

    return $tab_fields = get_fields().filter('.jeero-field-tab');
  };

  get_tab_subfields = function get_tab_subfields(tab_index) {
    return get_tab_fields().eq(tab_index).nextUntil('.jeero-field-tab');
  };

  get_nav_tabs = function get_nav_tabs() {
    if ($nav_tabs != null) {
      return $nav_tabs;
    }

    return $nav_tabs = jQuery('#jeero-nav-tabs');
  };

  get_tabs = function get_tabs() {
    if ($tabs != null) {
      return $tabs;
    }

    return $tabs = get_nav_tabs().find('.nav-tab');
  };

  show_tab = function show_tab(tab_index) {
    // Hide all fields.
    get_fields().hide(); // Activate tab nav.

    get_tabs().removeClass('nav-tab-active').eq(tab_index).addClass('nav-tab-active'); // Show fields of active tab.

    return get_tab_subfields(tab_index).show();
  };

  jQuery(function () {
    $tab_fields = get_tab_fields();

    if ($tab_fields.length) {
      get_form().prepend('<nav class="nav-tab-wrapper wp-clearfix" id="jeero-nav-tabs"></nav>');
      $tab_fields.hide();
      $tab_fields.each(function (index) {
        return get_nav_tabs().append("<button class=\"nav-tab\" data-tab_index=\"".concat(index, "\" type=\"button\">").concat(jQuery(this).text(), "</button>"));
      });
      get_tabs().click(function () {
        return show_tab(jQuery(this).data('tab_index'));
      });
      show_tab(0);
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

//# sourceMappingURL=admin.js.map

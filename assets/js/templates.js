"use strict";

(function () {
  var add_custom_field;
  add_custom_field = function ($custom_field_table) {
    let name = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
    let template = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    var $row, index, name_field_name, row_id, table_id, template_field_name;
    index = $custom_field_table.data('index');
    if (index == null) {
      index = 0;
    }
    table_id = $custom_field_table.attr('id');
    row_id = `${table_id}-${index}`;
    name_field_name = `${table_id}[${index}][name]`;
    template_field_name = `${table_id}[${index}][template]`;
    $custom_field_table.append(`<tr id='${row_id}'> <td class='name'> <input type='text' class='regular-text' name='${name_field_name}' value='${name}'> </td> <td class='template'> <textarea name='${template_field_name}'>${template}</textarea> </td> <td class='actions'> <button type='button' class='button delete-custom-field'>${jeero_templates.translations.delete}</button> </td> </tr>`);
    $row = jQuery("#" + row_id);
    wp.codeEditor.initialize($row.find('textarea'), jeero_templates.settings);
    $row.find('.delete-custom-field').click(function () {
      return $row.remove();
    });
    return $custom_field_table.data('index', index + 1);
  };
  jQuery(function () {
    var $custom_fields_fields, $template_fields;
    $template_fields = jQuery('.jeero-field-template textarea, .jeero-field-post_fields textarea, .jeero-field-custom_fields textarea');
    $template_fields.each(function () {
      return wp.codeEditor.initialize(jQuery(this), jeero_templates.settings);
    });
    $custom_fields_fields = jQuery('.jeero-field-custom_fields');
    return $custom_fields_fields.each(function () {
      var $field, $field_id, $field_table, custom_field, i, len, ref;
      $field = jQuery(this);
      $field_table = $field.find('table');
      $field_id = $field_table.attr('id');
      if (jeero_templates.custom_fields[$field_id] != null) {
        ref = jeero_templates.custom_fields[$field_id];
        for (i = 0, len = ref.length; i < len; i++) {
          custom_field = ref[i];
          add_custom_field($field_table, custom_field.name, custom_field.template);
        }
      }
      return $field.find('.add-custom-field').click(function () {
        return add_custom_field($field_table);
      });
    });
  });
}).call(void 0);

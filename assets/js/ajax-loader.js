jQuery(document).ready(function ($) {
  var data = {
    'action': ajax_loader_object.action_name,
    'fields': ajax_loader_object.fields,
    'nonce': ajax_loader_object.nonce,
  };

  var fields = ajax_loader_object.fields;
  var f_id, field_obj, result;

  for (var field in fields) {
    field_obj = fields[field].field;
    f_id = '#' + field_obj.id;

    if (field_obj.type === 'text_input') {
      $(f_id).val(field_obj.loading_text);
    }
    else {
      $(f_id).html(field_obj.loading_text);
    }
  }

  // We can also pass the url value separately from ajaxurl for front end AJAX implementations
  $.post(ajax_loader_object.ajax_url, data, function (response) {
    response = $.parseJSON(response);

    for (var field in fields) {
      result = response[field].value;
      field_obj = fields[field].field;

      f_id = '#' + field_obj.id;

      $(f_id).replaceWith(result);
    }
  });
});

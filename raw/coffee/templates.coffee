add_custom_field = ( $custom_field_table, name = '', template = '' ) ->

	index = $custom_field_table.data 'index'
	if not index?
		index = 0

	table_id = $custom_field_table.attr 'id'
	row_id = "#{ table_id }-#{ index }"
	name_field_name = "#{ table_id }[#{ index }][name]"
	template_field_name = "#{ table_id }[#{ index }][template]"

	$custom_field_table.append "
		<tr id='#{ row_id }'>
			<td class='name'>
				<input type='text' class='regular-text' name='#{ name_field_name }' value='#{ name }'>
			</td>
			<td class='template'>
				<textarea name='#{ template_field_name }'>#{ template }</textarea>
			</td>
			<td class='actions'>
				<button type='button' class='button delete-custom-field'>#{ jeero_templates.translations.delete }</button>
			</td>
		</tr>
	"
	
	$row = jQuery "#" + row_id
	
	wp.codeEditor.initialize $row.find( 'textarea' ), jeero_templates.settings
	
	$row.find( '.delete-custom-field' ).click ->
		$row.remove()
	
	$custom_field_table.data 'index', index + 1
		

jQuery ->

	$template_fields = jQuery '.jeero-field-template textarea, .jeero-field-custom_fields textarea'

	$template_fields.each ->
	
		wp.codeEditor.initialize jQuery( @ ), jeero_templates.settings


	$custom_fields_fields = jQuery '.jeero-field-custom_fields'
	$custom_fields_fields.each ->
	
		$field = jQuery @
		$field_table = $field.find 'table'
		$field_id = $field_table.attr 'id'
		
		if jeero_templates.custom_fields[ $field_id ]?
	
			for custom_field in jeero_templates.custom_fields[ $field_id ]
			
				add_custom_field $field_table, custom_field.name, custom_field.template
		
		$field.find('.add-custom-field').click ->
		
			add_custom_field $field_table
	

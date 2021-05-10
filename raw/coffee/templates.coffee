jQuery ->

	$template_fields = jQuery '.jeero-field-template textarea'

	$template_fields.each ->
	
		wp.codeEditor.initialize jQuery( @ ), cm_settings


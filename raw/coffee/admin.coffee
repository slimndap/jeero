$form = null
$fields = null
$tab_fields = null
$nav_tabs = null
$tabs = null

get_form = () ->
	return $form if $form?
	$form = jQuery '.jeero-form'
	
get_fields = () ->
	return $fields if $fields?
	$fields = get_form().find '.jeero-field'

get_tab_fields = () ->
	return $tab_fields if $tab_fields?
	$tab_fields = get_fields().filter '.jeero-field-tab'

get_tab_subfields = ( tab_index ) ->
	return get_tab_fields().eq( tab_index ).nextUntil '.jeero-field-tab'

get_nav_tabs = () ->
	return $nav_tabs if $nav_tabs?
	$nav_tabs = jQuery '#jeero-nav-tabs'
	
get_tabs = () ->
	return $tabs if $tabs?
	$tabs = get_nav_tabs().find '.nav-tab'


show_tab = ( tab_index ) ->

	# Hide all fields.
	get_fields().hide()
	
	# Activate tab nav.
	get_tabs().removeClass( 'nav-tab-active' ).eq( tab_index ).addClass 'nav-tab-active'
	
	# Show fields of active tab.
	get_tab_subfields( tab_index ).show()

jQuery ->

	$tab_fields = get_tab_fields()
		
	if $tab_fields.length
	
		get_form().prepend '<nav class="nav-tab-wrapper wp-clearfix" id="jeero-nav-tabs"></nav>'

		$tab_fields.hide()
		
		$tab_fields.each ( index )->		
			get_nav_tabs().append "<button class=\"nav-tab\" data-tab_index=\"#{ index }\" type=\"button\">#{ jQuery( @ ).text() }</button>"
			
		get_tabs().click ->
		
			show_tab jQuery( @ ).data 'tab_index' 

		show_tab 0
	
	get_fields().find( 'input' ).on 'invalid', ->
		
		$input = jQuery @
	
		get_tab_fields().each ( index ) ->
		
			if get_tab_subfields( index ).has( $input ).length
			
				show_tab index
		

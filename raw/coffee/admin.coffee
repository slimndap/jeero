jQuery ->

	$form = jQuery '.jeero-form'	
	if $form

		$fields = jQuery '.jeero-field'

		$tab_fields = $fields.filter '.jeero-field-tab'	
			
		if $tab_fields
		
			$tab_fields.hide()

			$form.prepend '<nav class="nav-tab-wrapper wp-clearfix" id="jeero-nav-tab"></nav>'
			
			$nav_tab = jQuery '#jeero-nav-tab'
			
			$tab_fields.each ->
			
				$tab_field = jQuery @
				
				$nav_tab.append "<a href=\"#kkk\" class=\"nav-tab\">#{ $tab_field.text() }</a>"
				
				$nav_tab_tab = $nav_tab.find '.nav-tab'
				$nav_tab_tab.click ->
				
					$fields.hide()

					$active_fields = $tab_field.nextUntil '.jeero-field-tab'
					$active_fields.show()
				

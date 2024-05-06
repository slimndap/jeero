jQuery ->

	scroll_to_bottom = ->
	
		$debug_logs.each ->
		
			$debug_log = jQuery @
			$debug_log.scrollTop $debug_log[0].scrollHeight
	

	# The textareas that contain the debug logs.
	$debug_logs = jQuery '.jeero_debug_log'
	scroll_to_bottom()
	
	jQuery( document ).on 'heartbeat-tick', ( event, data ) ->
	
		$debug_logs.each ->
	
			$debug_log = jQuery @
			debug_log_slug = 'jeero_debug_log_' + $debug_log.data 'debug_log_slug'
			
			return if not data[ debug_log_slug ]?
			
			# Refresh the debug log with the fresh debug log coming from the Heartbeat API.
			$debug_log.text data[ debug_log_slug ]

			scroll_to_bottom()


	jQuery( document ).on 'heartbeat-send', ( event, data ) ->

		# Request a fresh debug log using the Heartbeat API.
		data.jeero_heartbeat = 'get_debug_log'
	
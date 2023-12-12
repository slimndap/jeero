jQuery ->

	# The textarea that contains the debug log.
	$debug_log = jQuery '#jeero_debug_log'
	
	if $debug_log.length > 0 
	
		# Auto-scroll to the bottom of the debug log.
		$debug_log.scrollTop $debug_log[0].scrollHeight

		jQuery( document ).on 'heartbeat-send', ( event, data ) ->
		
			# Request a fresh debug log using the Heartbeat API.
			data.jeero_heartbeat = 'get_debug_log'

		jQuery( document ).on 'heartbeat-tick', ( event, data ) ->
		
			return if not data.jeero_debug_log?
			
			# Refresh the debug log with the fresh debug log coming from the Heartbeat API.
			$debug_log.text data.jeero_debug_log

			# Auto-scroll to the bottom of the debug log.
			$debug_log.scrollTop $debug_log[0].scrollHeight

format_His = (date) ->
    hours = date.getHours().toString().padStart(2, '0')
    minutes = date.getMinutes().toString().padStart(2, '0')
    seconds = date.getSeconds().toString().padStart(2, '0')
    "#{hours}:#{minutes}:#{seconds}"

get_processed_stats = ( stats ) ->

	labels = []

	data = 
		'items_pickup': []
		'items_processed': []
		'duration_processed': []
	
	stats.forEach ( stat ) ->
		stat_time = new Date( stat.time )

		if isNaN stat_time.getTime()
			console.warn 'Invalid date:', stat.time
			return
			
		stat_time_str = format_His stat_time

		if labels.indexOf( stat_time_str ) == -1
			labels.push stat_time_str
		
		data[ stat.name ].push 
			x: stat_time_str
			y: stat.value

	return
		labels: labels
		data: data
		
get_chart_args = ( stats )->

	{
		type: 'line'
		data:
			labels: stats.labels
			datasets: [
				{
					label: 'Items pickup'
					data: stats.data.items_pickup
					borderColor: 'rgba(75, 192, 192, 1)'
					borderWidth: 1
					fill: false
				},
				{
					label: 'Items processed'
					data: stats.data.items_processed
					borderColor: 'rgba(153, 102, 255, 1)'
					borderWidth: 1
					fill: false
				},
				{
					label: 'Duration (s)'
					data: stats.data.duration_processed
					borderColor: 'rgba(255, 99, 132, 1)'
					borderWidth: 1
					fill: false
				}
			]
		options:
			scales:
				x:
					type: 'category'
					
			animation: false
	}

update_stats = ( stats, chart ) ->

	processed_stats = get_processed_stats stats
	args = get_chart_args processed_stats

	if not chart?
		$debug_stats = jQuery '#jeero_debug_stats'	

		return if $debug_stats.length < 1
		
		return new Chart $debug_stats[0].getContext('2d'), args	
	
	chart.data = args.data
	chart.update()
	
	chart

		
jQuery ->

	scroll_to_bottom = ->
	
		$debug_logs.each ->
		
			$debug_log = jQuery @
			$debug_log.scrollTop $debug_log[0].scrollHeight
	

	# The textareas that contain the debug logs.
	$debug_logs = jQuery '.jeero_debug_log'
	scroll_to_bottom()
	
	stats_chart = update_stats jeero_debug.stats, null
	
	$debug_settings = jQuery '#jeero_debug_settings'
	if $debug_settings.length > 0
		wp.codeEditor.initialize $debug_settings, jeero_debug.settings
	
	jQuery( document ).on 'heartbeat-tick', ( event, data ) ->
	
		$debug_logs.each ->
	
			$debug_log = jQuery @
			debug_log_slug = 'jeero_debug_log_' + $debug_log.data 'debug_log_slug'
			
			return if not data[ debug_log_slug ]?
			
			# Refresh the debug log with the fresh debug log coming from the Heartbeat API.
			$debug_log.text data[ debug_log_slug ]

			scroll_to_bottom()

		if data[ 'jeero_debug_stats' ]?
			
			stats_chart = update_stats data[ 'jeero_debug_stats' ], stats_chart
			



	jQuery( document ).on 'heartbeat-send', ( event, data ) ->

		# Request a fresh debug log using the Heartbeat API.
		data.jeero_heartbeat = 'get_debug_log'
	
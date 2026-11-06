(function () {
	'use strict';

	function sessionId() {
		var match = document.cookie.match(/(?:^|;\s*)jeero_tapos_id=([a-f0-9]{32})(?:;|$)/);
		if (match) return match[1];
		var bytes = new Uint8Array(16);
		window.crypto.getRandomValues(bytes);
		var id = Array.from(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
		// A first-party session cookie survives navigation without entering cached HTML.
		document.cookie = 'jeero_tapos_id=' + id + '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		if (document.cookie.indexOf('jeero_tapos_id=' + id) === -1) throw new Error('Session cookies unavailable');
		return id;
	}

	function init() {
		document.querySelectorAll('iframe[data-jeero-tapos-url]').forEach(function (frame) {
			if (frame.dataset.jeeroTaposReady) return;
			try {
				var url = new URL(frame.dataset.jeeroTaposUrl);
				if (url.protocol !== 'https:') return;
				url.searchParams.set('tapos_id', sessionId());
				window.addEventListener('message', function (event) {
					if (event.source !== frame.contentWindow || event.origin !== url.origin) return;
					if (event.data === 'iframe_go_back') {
						window.history.back();
					} else if (event.data === 'iframe_change' || event.data === 'iframe_scroll_to_top_of_page') {
						frame.scrollIntoView({ block: 'start' });
					}
				});
				frame.dataset.jeeroTaposReady = 'true';
				frame.src = url.href;
				window.iFrameResize({ checkOrigin: [url.origin] }, frame);
			} catch (error) {
				// The visible direct-booking link remains usable when cookies or scripts fail.
				frame.hidden = true;
			}
		});
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
}());

"use strict";(function(){jQuery((function(){var e;if((e=jQuery("#jeero_debug_log")).length>0)return e.scrollTop(e[0].scrollHeight),jQuery(document).on("heartbeat-send",(function(e,t){return t.jeero_heartbeat="get_debug_log"})),jQuery(document).on("heartbeat-tick",(function(t,o){if(null!=o.jeero_debug_log)return e.text(o.jeero_debug_log),e.scrollTop(e[0].scrollHeight)}))}))}).call(void 0);
"use strict";(function(){jQuery((function(){var e,t;return t=function(){return e.each((function(){var e;return(e=jQuery(this)).scrollTop(e[0].scrollHeight)}))},e=jQuery(".jeero_debug_log"),t(),jQuery(document).on("heartbeat-tick",(function(r,u){return e.each((function(){var e,r;if(r="jeero_debug_log_"+(e=jQuery(this)).data("debug_log_slug"),null!=u[r])return e.text(u[r]),t()}))})),jQuery(document).on("heartbeat-send",(function(e,t){return t.jeero_heartbeat="get_debug_log"}))}))}).call(void 0);
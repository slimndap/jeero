"use strict";(function(){jQuery((function(){var e,r,a,n;if((r=jQuery(".jeero-form"))&&(n=(e=jQuery(".jeero-field")).filter(".jeero-field-tab")))return n.hide(),r.prepend('<nav class="nav-tab-wrapper wp-clearfix" id="jeero-nav-tab"></nav>'),a=jQuery("#jeero-nav-tab"),n.each((function(){var r;return r=jQuery(this),a.append('<a href="#kkk" class="nav-tab">'.concat(r.text(),"</a>")),a.find(".nav-tab").click((function(){return e.hide(),r.nextUntil(".jeero-field-tab").show()}))}))}))}).call(void 0);
var jeero_theater_widget_activetickets_message_handler, jeero_theater_widget_activetickets_resize_iframe, jeero_theater_widget_activetickets_scroll_iframe, jeero_theater_widget_activetickets_set_login_status;

jeero_theater_widget_activetickets_scroll_iframe = function(position) {
  var iframe, rect, y;
  iframe = document.querySelector('.jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe');
  if (iframe) {
    rect = iframe.getBoundingClientRect();
    y = rect.top + window.scrollY + position;
    return window.scroll({
      top: y,
      behavior: 'smooth'
    });
  }
};

jeero_theater_widget_activetickets_resize_iframe = function(height) {
  var iframe;
  iframe = document.querySelector('.jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe');
  if (iframe) {
    return iframe.style.height = (height + 50) + "px";
  }
};

jeero_theater_widget_activetickets_set_login_status = function(loginStatus) {
  if (loginStatus !== "notLoggedin") {
    return document.body.classList.add("activetickets_logged_in");
  } else {
    return document.body.classList.remove("activetickets_logged_in");
  }
};

jeero_theater_widget_activetickets_message_handler = function(event) {
  var data;
  data = event.data;
  if (typeof data === "object" && typeof data.vendor === "string" && data.vendor === "ActiveTickets") {
    switch (data.messageType) {
      case "ScrollIframe":
        return jeero_theater_widget_activetickets_scroll_iframe(data.position);
      case "ContentHeightChanged":
        return jeero_theater_widget_activetickets_resize_iframe(data.height);
      case "LoginStatus":
        return jeero_theater_widget_activetickets_set_login_status(data.loginStatus);
    }
  }
};

window.onmessage = jeero_theater_widget_activetickets_message_handler;

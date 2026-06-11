jeero_theater_widget_activetickets_scroll_iframe = ( position ) ->
    iframe = document.querySelector('.jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe')
    if iframe
        rect = iframe.getBoundingClientRect()
        y = rect.top + window.scrollY + position
        window.scroll
            top: y
            behavior: 'smooth'

jeero_theater_widget_activetickets_resize_iframe = ( height ) ->
    iframe = document.querySelector('.jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe')
    if iframe
        iframe.style.height = (height + 50) + "px"

jeero_theater_widget_activetickets_set_login_status = ( loginStatus ) ->
    unless loginStatus == "notLoggedin"
        document.body.classList.add "activetickets_logged_in"
    else
        document.body.classList.remove "activetickets_logged_in"

jeero_theater_widget_activetickets_message_handler = ( event ) ->
  data = event.data
  if typeof data == "object" and typeof data.vendor == "string" and
     data.vendor == "ActiveTickets"
    switch data.messageType
      when "ScrollIframe"
        jeero_theater_widget_activetickets_scroll_iframe data.position
      when "ContentHeightChanged"
        jeero_theater_widget_activetickets_resize_iframe data.height
      when "LoginStatus"
        jeero_theater_widget_activetickets_set_login_status data.loginStatus

window.onmessage = jeero_theater_widget_activetickets_message_handler


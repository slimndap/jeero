jeero_theater_widget_activetickets_cart_storage_key = 'jeero.activetickets.cart'
jeero_theater_widget_activetickets_login_storage_key = 'jeero.activetickets.login'
jeero_theater_widget_activetickets_iframe_selector = '.jeero-theater-widget--account-inline iframe, .jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe'

# Read the first available nested value from a list of dot-separated paths.
jeero_theater_widget_activetickets_get_value = ( data, paths ) ->
    for path in paths
        current = data
        parts = path.split '.'
        for part in parts
            if current? and typeof current == 'object' and part of current
                current = current[ part ]
            else
                current = undefined
                break
        if current?
            return current

    undefined

# Scroll the parent page so a target position inside the ActiveTickets iframe is visible.
jeero_theater_widget_activetickets_scroll_iframe = ( position ) ->
    iframe = document.querySelector jeero_theater_widget_activetickets_iframe_selector
    if iframe
        rect = iframe.getBoundingClientRect()
        y = rect.top + window.scrollY + position
        window.scroll
            top: y
            behavior: 'smooth'

# Resize the inline ActiveTickets iframe when the shop reports a new content height.
jeero_theater_widget_activetickets_resize_iframe = ( height ) ->
    iframe = document.querySelector jeero_theater_widget_activetickets_iframe_selector
    if iframe
        iframe.style.height = (height + 50) + "px"

# Reflect the ActiveTickets login state on the document body for theme styling hooks.
jeero_theater_widget_activetickets_set_login_status = ( loginStatus ) ->
    unless loginStatus == "notLoggedin"
        document.body.classList.add "activetickets_logged_in"
    else
        document.body.classList.remove "activetickets_logged_in"

# Update ActiveTickets account indicator bindings with the latest login status.
jeero_theater_widget_activetickets_update_login_bindings = ( login ) ->
    logged_in = login.loginStatus != "notLoggedin"
    elements = document.querySelectorAll '.jeero-theater-widget--account-indicator.jeero-theater-widget--theater-activetickets [data-jeero-bind="account.label"]'
    for element in elements
        label = if logged_in then element.getAttribute 'data-jeero-logged-in-label' else element.getAttribute 'data-jeero-logged-out-label'
        if label?
            element.textContent = label

# Persist the latest ActiveTickets login status and notify listeners.
jeero_theater_widget_activetickets_store_login_status = ( loginStatus ) ->
    login =
        loginStatus: loginStatus
        loggedIn: loginStatus != "notLoggedin"
        updatedAt: new Date().toISOString()

    jeero_theater_widget_activetickets_set_login_status loginStatus
    jeero_theater_widget_activetickets_update_login_bindings login

    return unless window.sessionStorage?

    try
        sessionStorage.setItem jeero_theater_widget_activetickets_login_storage_key, JSON.stringify login

        event = new CustomEvent 'jeero:activetickets:login:update',
            detail: login
        window.dispatchEvent event
    catch error

# Check for the exact cart API messages documented by ActiveTickets.
jeero_theater_widget_activetickets_is_cart_update_message = ( data ) ->
    data.messageType in [
        'CartLoaded'
        'CartChanged'
        'Pay'
    ]

# Get the total number of cart items from an ActiveTickets cart payload.
jeero_theater_widget_activetickets_get_cart_count = ( cart ) ->
    jeero_theater_widget_activetickets_get_value cart, [
        'cart.itemAmount'
        'itemAmount'
    ]

# Reflect whether the ActiveTickets basket has items on the document body.
jeero_theater_widget_activetickets_set_basket_status = ( count ) ->
    if Number( count ) > 0
        document.body.classList.add "activetickets_has_basket_items"
    else
        document.body.classList.remove "activetickets_has_basket_items"

# Update only ActiveTickets cart indicator bindings with the latest cart count.
jeero_theater_widget_activetickets_update_cart_bindings = ( cart ) ->
    count = jeero_theater_widget_activetickets_get_cart_count cart
    return unless count?

    jeero_theater_widget_activetickets_set_basket_status count

    elements = document.querySelectorAll '.jeero-theater-widget--cart-indicator.jeero-theater-widget--theater-activetickets [data-jeero-bind="cart.count"]'
    for element in elements
        element.textContent = count

# Persist the latest ActiveTickets cart payload in sessionStorage and notify listeners.
jeero_theater_widget_activetickets_store_cart = ( data ) ->
    return unless window.sessionStorage?

    cart =
        messageType: data.messageType
        updatedAt: new Date().toISOString()
        cart: if data.cart? and typeof data.cart == 'object' then JSON.parse JSON.stringify data.cart else data
        changes: if data.changes? and typeof data.changes == 'object' then data.changes else undefined

    count = if data.messageType == 'Pay' then 0 else jeero_theater_widget_activetickets_get_cart_count cart
    if count?
        cart.cart.itemAmount = count

    try
        sessionStorage.setItem jeero_theater_widget_activetickets_cart_storage_key, JSON.stringify cart
        jeero_theater_widget_activetickets_update_cart_bindings cart

        event = new CustomEvent 'jeero:activetickets:cart:update',
            detail: cart
        window.dispatchEvent event
    catch error

# Restore the last known cart state from sessionStorage after page navigation or reload.
jeero_theater_widget_activetickets_restore_cart = ->
    return unless window.sessionStorage?

    try
        stored_cart = sessionStorage.getItem jeero_theater_widget_activetickets_cart_storage_key
        return unless stored_cart

        cart = JSON.parse stored_cart
        jeero_theater_widget_activetickets_update_cart_bindings cart
    catch error
        sessionStorage.removeItem jeero_theater_widget_activetickets_cart_storage_key

# Restore the last known login state from sessionStorage after page navigation or reload.
jeero_theater_widget_activetickets_restore_login_status = ->
    return unless window.sessionStorage?

    try
        stored_login = sessionStorage.getItem jeero_theater_widget_activetickets_login_storage_key
        return unless stored_login

        login = JSON.parse stored_login
        return unless login.loginStatus?

        jeero_theater_widget_activetickets_set_login_status login.loginStatus
        jeero_theater_widget_activetickets_update_login_bindings login
    catch error
        sessionStorage.removeItem jeero_theater_widget_activetickets_login_storage_key

# Restore ActiveTickets state once the DOM can be queried safely.
jeero_theater_widget_activetickets_restore_state = ->
    jeero_theater_widget_activetickets_restore_cart()
    jeero_theater_widget_activetickets_restore_login_status()

# Register event handlers and restore ActiveTickets state once the DOM can be queried safely.
jeero_theater_widget_activetickets_init = ->
    window.onmessage = jeero_theater_widget_activetickets_message_handler

    if document.readyState == 'loading'
        document.addEventListener 'DOMContentLoaded', jeero_theater_widget_activetickets_restore_state
    else
        jeero_theater_widget_activetickets_restore_state()

# Route ActiveTickets postMessage events to iframe, login, and cart handlers.
jeero_theater_widget_activetickets_message_handler = ( event ) ->
  data = event.data
  if typeof data == "object" and typeof data.vendor == "string" and
     data.vendor == "ActiveTickets"
    if jeero_theater_widget_activetickets_is_cart_update_message data
      jeero_theater_widget_activetickets_store_cart data

    switch data.messageType
      when "ScrollIframe"
        jeero_theater_widget_activetickets_scroll_iframe data.position
      when "ContentHeightChanged"
        jeero_theater_widget_activetickets_resize_iframe data.height
      when "LoginStatus"
        jeero_theater_widget_activetickets_store_login_status data.loginStatus

jeero_theater_widget_activetickets_init()

var jeero_theater_widget_activetickets_cart_storage_key, jeero_theater_widget_activetickets_get_cart_count, jeero_theater_widget_activetickets_get_value, jeero_theater_widget_activetickets_iframe_selector, jeero_theater_widget_activetickets_init, jeero_theater_widget_activetickets_is_cart_update_message, jeero_theater_widget_activetickets_login_storage_key, jeero_theater_widget_activetickets_message_handler, jeero_theater_widget_activetickets_resize_iframe, jeero_theater_widget_activetickets_restore_cart, jeero_theater_widget_activetickets_restore_login_status, jeero_theater_widget_activetickets_restore_state, jeero_theater_widget_activetickets_scroll_iframe, jeero_theater_widget_activetickets_set_basket_status, jeero_theater_widget_activetickets_set_login_status, jeero_theater_widget_activetickets_store_cart, jeero_theater_widget_activetickets_store_login_status, jeero_theater_widget_activetickets_update_cart_bindings, jeero_theater_widget_activetickets_update_login_bindings;

jeero_theater_widget_activetickets_cart_storage_key = 'jeero.activetickets.cart';

jeero_theater_widget_activetickets_login_storage_key = 'jeero.activetickets.login';

jeero_theater_widget_activetickets_iframe_selector = '.jeero-theater-widget--account-inline iframe, .jeero-theater-widget--cart-inline iframe, .jeero-theater-widget--tickets-inline iframe';

jeero_theater_widget_activetickets_get_value = function(data, paths) {
  var current, i, j, len, len1, part, parts, path;
  for (i = 0, len = paths.length; i < len; i++) {
    path = paths[i];
    current = data;
    parts = path.split('.');
    for (j = 0, len1 = parts.length; j < len1; j++) {
      part = parts[j];
      if ((current != null) && typeof current === 'object' && part in current) {
        current = current[part];
      } else {
        current = void 0;
        break;
      }
    }
    if (current != null) {
      return current;
    }
  }
  return void 0;
};

jeero_theater_widget_activetickets_scroll_iframe = function(position) {
  var iframe, rect, y;
  iframe = document.querySelector(jeero_theater_widget_activetickets_iframe_selector);
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
  iframe = document.querySelector(jeero_theater_widget_activetickets_iframe_selector);
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

jeero_theater_widget_activetickets_update_login_bindings = function(login) {
  var element, elements, i, label, len, logged_in, results;
  logged_in = login.loginStatus !== "notLoggedin";
  elements = document.querySelectorAll('.jeero-theater-widget--account-indicator.jeero-theater-widget--theater-activetickets [data-jeero-bind="account.label"]');
  results = [];
  for (i = 0, len = elements.length; i < len; i++) {
    element = elements[i];
    label = logged_in ? element.getAttribute('data-jeero-logged-in-label') : element.getAttribute('data-jeero-logged-out-label');
    if (label != null) {
      results.push(element.textContent = label);
    } else {
      results.push(void 0);
    }
  }
  return results;
};

jeero_theater_widget_activetickets_store_login_status = function(loginStatus) {
  var error, event, login;
  login = {
    loginStatus: loginStatus,
    loggedIn: loginStatus !== "notLoggedin",
    updatedAt: new Date().toISOString()
  };
  jeero_theater_widget_activetickets_set_login_status(loginStatus);
  jeero_theater_widget_activetickets_update_login_bindings(login);
  if (window.sessionStorage == null) {
    return;
  }
  try {
    sessionStorage.setItem(jeero_theater_widget_activetickets_login_storage_key, JSON.stringify(login));
    event = new CustomEvent('jeero:activetickets:login:update', {
      detail: login
    });
    return window.dispatchEvent(event);
  } catch (error1) {
    error = error1;
  }
};

jeero_theater_widget_activetickets_is_cart_update_message = function(data) {
  return data.messageType === 'CartLoaded' || data.messageType === 'CartChanged' || data.messageType === 'Pay';
};

jeero_theater_widget_activetickets_get_cart_count = function(cart) {
  return jeero_theater_widget_activetickets_get_value(cart, ['cart.itemAmount', 'itemAmount']);
};

jeero_theater_widget_activetickets_set_basket_status = function(count) {
  if (Number(count) > 0) {
    return document.body.classList.add("activetickets_has_basket_items");
  } else {
    return document.body.classList.remove("activetickets_has_basket_items");
  }
};

jeero_theater_widget_activetickets_update_cart_bindings = function(cart) {
  var count, element, elements, i, len, results;
  count = jeero_theater_widget_activetickets_get_cart_count(cart);
  if (count == null) {
    return;
  }
  jeero_theater_widget_activetickets_set_basket_status(count);
  elements = document.querySelectorAll('.jeero-theater-widget--cart-indicator.jeero-theater-widget--theater-activetickets [data-jeero-bind="cart.count"]');
  results = [];
  for (i = 0, len = elements.length; i < len; i++) {
    element = elements[i];
    results.push(element.textContent = count);
  }
  return results;
};

jeero_theater_widget_activetickets_store_cart = function(data) {
  var cart, count, error, event;
  if (window.sessionStorage == null) {
    return;
  }
  cart = {
    messageType: data.messageType,
    updatedAt: new Date().toISOString(),
    cart: (data.cart != null) && typeof data.cart === 'object' ? JSON.parse(JSON.stringify(data.cart)) : data,
    changes: (data.changes != null) && typeof data.changes === 'object' ? data.changes : void 0
  };
  count = data.messageType === 'Pay' ? 0 : jeero_theater_widget_activetickets_get_cart_count(cart);
  if (count != null) {
    cart.cart.itemAmount = count;
  }
  try {
    sessionStorage.setItem(jeero_theater_widget_activetickets_cart_storage_key, JSON.stringify(cart));
    jeero_theater_widget_activetickets_update_cart_bindings(cart);
    event = new CustomEvent('jeero:activetickets:cart:update', {
      detail: cart
    });
    return window.dispatchEvent(event);
  } catch (error1) {
    error = error1;
  }
};

jeero_theater_widget_activetickets_restore_cart = function() {
  var cart, error, stored_cart;
  if (window.sessionStorage == null) {
    return;
  }
  try {
    stored_cart = sessionStorage.getItem(jeero_theater_widget_activetickets_cart_storage_key);
    if (!stored_cart) {
      return;
    }
    cart = JSON.parse(stored_cart);
    return jeero_theater_widget_activetickets_update_cart_bindings(cart);
  } catch (error1) {
    error = error1;
    return sessionStorage.removeItem(jeero_theater_widget_activetickets_cart_storage_key);
  }
};

jeero_theater_widget_activetickets_restore_login_status = function() {
  var error, login, stored_login;
  if (window.sessionStorage == null) {
    return;
  }
  try {
    stored_login = sessionStorage.getItem(jeero_theater_widget_activetickets_login_storage_key);
    if (!stored_login) {
      return;
    }
    login = JSON.parse(stored_login);
    if (login.loginStatus == null) {
      return;
    }
    jeero_theater_widget_activetickets_set_login_status(login.loginStatus);
    return jeero_theater_widget_activetickets_update_login_bindings(login);
  } catch (error1) {
    error = error1;
    return sessionStorage.removeItem(jeero_theater_widget_activetickets_login_storage_key);
  }
};

jeero_theater_widget_activetickets_restore_state = function() {
  jeero_theater_widget_activetickets_restore_cart();
  return jeero_theater_widget_activetickets_restore_login_status();
};

jeero_theater_widget_activetickets_init = function() {
  window.onmessage = jeero_theater_widget_activetickets_message_handler;
  if (document.readyState === 'loading') {
    return document.addEventListener('DOMContentLoaded', jeero_theater_widget_activetickets_restore_state);
  } else {
    return jeero_theater_widget_activetickets_restore_state();
  }
};

jeero_theater_widget_activetickets_message_handler = function(event) {
  var data;
  data = event.data;
  if (typeof data === "object" && typeof data.vendor === "string" && data.vendor === "ActiveTickets") {
    if (jeero_theater_widget_activetickets_is_cart_update_message(data)) {
      jeero_theater_widget_activetickets_store_cart(data);
    }
    switch (data.messageType) {
      case "ScrollIframe":
        return jeero_theater_widget_activetickets_scroll_iframe(data.position);
      case "ContentHeightChanged":
        return jeero_theater_widget_activetickets_resize_iframe(data.height);
      case "LoginStatus":
        return jeero_theater_widget_activetickets_store_login_status(data.loginStatus);
    }
  }
};

jeero_theater_widget_activetickets_init();

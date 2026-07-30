import _ from 'lodash';
import Popper from 'popper.js';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
// jquery-global sets window.$ before bootstrap evaluates (ESM dependency order).
import './jquery-global.js';
import 'bootstrap';

window._ = _;
window.Popper = Popper;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Preserve pre-1.x validateStatus behavior (accept all HTTP status codes)
// to avoid breaking components that handle errors via response status checks
window.axios.defaults.validateStatus = null;

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just a
 * simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error(
        'CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token'
    );
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

window.Pusher = Pusher;

// Vite injects VITE_* via import.meta.env at build time.
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;
// Skip Echo when key is empty — local .env often has blank PUSHER_APP_KEY.
if (pusherKey) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true
    });
}

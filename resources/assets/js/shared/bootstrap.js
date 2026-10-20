import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

// const authEndpoint = window.routes?.broadcastingAuth || '/broadcasting/auth';

// console.log('🔍 Broadcasting authEndpoint:', authEndpoint);

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//     forceTLS: true,
//     authEndpoint: authEndpoint,
//     auth: {
//         headers: {
//             'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.content || '',
//             'Accept': 'application/json',
//             'X-Requested-With': 'XMLHttpRequest'
//         }
//     }
// });

// window.Echo.connector.pusher.connection.bind('connected', () => {
//     console.log('✅ Pusher connected successfully');
// });

// window.Echo.connector.pusher.connection.bind('error', (err) => {
//     console.error('❌ Pusher connection error:', err);
// });
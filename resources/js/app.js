import './bootstrap';



import { createApp } from 'vue';

import stripe from "./components/stripe.vue"


const app = createApp({});
app.component('stripe-vue',stripe)
app.mount("#app");



import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

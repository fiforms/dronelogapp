import axios from 'axios';

window.axios = axios;

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.baseURL = import.meta.env.VITE_APP_URL ?? '';
axios.defaults.headers.common['Accept'] = 'application/json';

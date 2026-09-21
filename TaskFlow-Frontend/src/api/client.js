import axios from 'axios';

// No trailing slash expected: routes below add their own leading slash.
const API_URL = import.meta.env.VITE_API_URL;

const client = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  // The frontend (5173) and backend (8000) are different origins even
  // though they share a hostname — different port means the browser
  // treats this as cross-site. Axios only reads the XSRF-TOKEN cookie and
  // attaches it as X-XSRF-TOKEN automatically for same-origin requests
  // unless this is set, which is exactly the header Laravel's CSRF
  // middleware expects back.
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
});

export default client;

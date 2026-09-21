import client from './client';

/**
 * Laravel Sanctum's SPA cookie flow: this sets the XSRF-TOKEN cookie,
 * which the client is configured to read and echo back as X-XSRF-TOKEN on
 * every subsequent request. Must be called before register/login — those
 * are POSTs, and Laravel's CSRF middleware rejects a POST that arrives
 * without a valid token already in place.
 */
export function getCsrfCookie() {
  return client.get('/sanctum/csrf-cookie');
}

export async function register({ name, email, password, passwordConfirmation }) {
  await getCsrfCookie();
  const { data } = await client.post('/api/register', {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });
  return data;
}

export async function login({ email, password }) {
  await getCsrfCookie();
  const { data } = await client.post('/api/login', { email, password });
  return data;
}

export async function logout() {
  await client.post('/api/logout');
}

export async function fetchUser() {
  const { data } = await client.get('/api/user');
  return data;
}

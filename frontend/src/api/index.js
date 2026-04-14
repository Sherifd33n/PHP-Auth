const BASE = 'http://localhost/Main-Auth/backend/handlers';

async function getCsrfToken() {
  const res  = await fetch(`${BASE}/csrf-token.php`, { credentials: 'include' });
  const data = await res.json();
  return data.token;
}

export async function getMe() {
  const res = await fetch(`${BASE}/me.php`, { credentials: 'include' });
  if (!res.ok) throw new Error('Not authenticated');
  return res.json();
}

export async function login(email, password) {
  const token = await getCsrfToken();
  const body  = new FormData();
  body.append('email',      email);
  body.append('password',   password);
  body.append('csrf_token', token);

  const res = await fetch(`${BASE}/login.php`, {
    method: 'POST', body, credentials: 'include',
  });
  return res.json();
}

export async function register(fullName, email, password, repeatPassword) {
  const token = await getCsrfToken();
  const body  = new FormData();
  body.append('fullName',        fullName);
  body.append('email',           email);
  body.append('password',        password);
  body.append('repeat_password', repeatPassword);
  body.append('csrf_token',      token);

  const res = await fetch(`${BASE}/register.php`, {
    method: 'POST', body, credentials: 'include',
  });
  return res.json();
}

export async function logout() {
  const res = await fetch(`${BASE}/logout.php`, {
    method: 'POST', credentials: 'include',
  });
  return res.json();
}

export async function forgotPassword(email) {
  // Still fetch a CSRF token — prevents bots from spamming our forgot-password endpoint
  const token = await getCsrfToken();
  const body  = new FormData();
  body.append('email',      email);
  body.append('csrf_token', token);

  const res = await fetch(`${BASE}/forgot-password.php`, {
    method: 'POST', body, credentials: 'include',
  });
  return res.json();
}

export async function resetPassword(resetToken, password, repeatPassword) {
  // No CSRF token needed here — the reset token in the URL IS the security proof
  const body = new FormData();
  body.append('token',           resetToken);
  body.append('password',        password);
  body.append('repeat_password', repeatPassword);

  const res = await fetch(`${BASE}/reset-password.php`, {
    method: 'POST', body, credentials: 'include',
  });
  return res.json();
}

export async function deactivateAccount() {
  // No body needed — the backend reads the user ID directly from the session cookie
  const res = await fetch(`${BASE}/deactivate.php`, {
    method: 'POST', credentials: 'include',
  });
  return res.json();
}

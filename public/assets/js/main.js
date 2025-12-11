export function getToken() {
  return localStorage.getItem('rm_token');
}

export function setToken(token) {
  localStorage.setItem('rm_token', token);
  // keep cookie in sync by calling logout/login endpoints as needed
}

export async function apiFetch(url, options = {}) {
  const headers = options.headers || {};
  headers['Content-Type'] = headers['Content-Type'] || 'application/json';
  headers['Accept'] = headers['Accept'] || 'application/json';
  const token = getToken();
  if (token) headers['Authorization'] = 'Bearer ' + token;
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) headers['X-CSRF'] = meta.content;
  const res = await fetch(url, { ...options, headers });
  if (res.status === 401) {
    window.location.href = '/login';
    return;
  }
  const contentType = res.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
  }
  if (!res.ok) throw new Error('Request failed');
  return null;
}

// Guard UI pages (excluding /login) to ensure token is present
const currentPath = window.location.pathname;
if (!currentPath.startsWith('/login') && !getToken()) {
  window.location.href = '/login';
}

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', () => {
    fetch('/api/v1/auth/logout', { method: 'POST', headers: { 'Authorization': 'Bearer ' + getToken() } })
      .catch(() => {})
      .finally(() => {
        localStorage.removeItem('rm_token');
        window.location.href = '/login';
      });
  });
}

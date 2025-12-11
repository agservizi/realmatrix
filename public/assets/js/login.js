import { setToken } from './main.js';

const form = document.getElementById('login-form');
const help = document.getElementById('login-help');

form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(form));
   if (!data.email || !data.password) {
     help.textContent = 'Inserire email e password';
     return;
   }
  const res = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const json = await res.json();
  if (!res.ok) {
    help.textContent = json.error || 'Errore di login';
    return;
  }
  setToken(json.token);
  window.location.href = '/dashboard';
});

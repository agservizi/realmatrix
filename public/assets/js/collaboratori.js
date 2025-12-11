import { apiFetch } from './main.js';

export function initCollaboratori() {
  const form = document.getElementById('collab-form');
  const tbody = document.querySelector('#collab-table tbody');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    data.permissions = (data.permissions || '').split(',').map(p => p.trim()).filter(Boolean);
    await apiFetch('/api/v1/collaborators', { method: 'POST', body: JSON.stringify(data) });
    form.reset();
    load();
  });

  async function load() {
    try {
      const items = await apiFetch('/api/v1/collaborators') || [];
      tbody.innerHTML = '';
      items.forEach(u => {
        const tr = document.createElement('tr');
        const perms = Array.isArray(u.permissions) ? u.permissions.join(', ') : u.permissions;
        tr.innerHTML = `<td>${u.name}</td><td>${u.email}</td><td>${u.role}</td><td>${perms}</td>`;
        tbody.appendChild(tr);
      });
    } catch (e) {
      console.error('Load collaborators failed', e);
    }
  }

  load();
}

import { apiFetch } from './main.js';

export function initClienti() {
  const form = document.getElementById('cliente-form');
  const tbody = document.querySelector('#clienti-table tbody');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    await apiFetch('/api/v1/clienti', { method: 'POST', body: JSON.stringify(data) });
    form.reset();
    load();
  });

  async function load() {
    try {
      const items = await apiFetch('/api/v1/clienti') || [];
      tbody.innerHTML = '';
      items.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${c.nome}</td><td>${c.email}</td><td>${c.telefono}</td><td>${c.lead_score}</td>`;
        tbody.appendChild(tr);
      });
    } catch (e) {
      console.error('Load clienti failed', e);
    }
  }

  load();
}

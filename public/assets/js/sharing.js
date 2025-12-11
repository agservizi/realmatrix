import { apiFetch } from './main.js';

export function initSharing() {
  const form = document.getElementById('sharing-form');
  const tbody = document.querySelector('#sharing-table tbody');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    await apiFetch('/api/v1/sharing/immobili', { method: 'POST', body: JSON.stringify(data) });
    form.reset();
    load();
  });

  async function load() {
    try {
      const items = await apiFetch('/api/v1/sharing/immobili') || [];
      tbody.innerHTML = '';
      items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.immobile_id}</td><td>${item.visibilita}</td><td>${item.agency_id}</td>`;
        tbody.appendChild(tr);
      });
    } catch (e) {
      console.error('Load sharing failed', e);
    }
  }

  load();
}

import { apiFetch } from './main.js';

export function initImmobili() {
  const form = document.getElementById('immobile-form');
  const tbody = document.querySelector('#immobili-table tbody');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    const meta = document.querySelector('meta[name="csrf-token"]');
    const res = await fetch('/api/v1/immobili', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('rm_token'),
        ...(meta ? { 'X-CSRF': meta.content } : {})
      },
      body: formData
    });
    if (res.ok) {
      form.reset();
      load();
    }
  });

  async function load() {
    try {
      const items = await apiFetch('/api/v1/immobili') || [];
      tbody.innerHTML = '';
      items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${item.titolo}</td><td>${item.prezzo}</td><td>${item.stato}</td>`;
        tbody.appendChild(tr);
      });
    } catch (e) {
      console.error('Load immobili failed', e);
    }
  }

  load();
}

import { apiFetch } from './main.js';

export async function loadDashboard() {
  let me;
  try {
    me = await apiFetch('/api/v1/auth/me');
    const [immobili, lead, appuntamenti, sharing] = await Promise.all([
      apiFetch('/api/v1/immobili'),
      apiFetch('/api/v1/lead'),
      apiFetch('/api/v1/appuntamenti'),
      apiFetch('/api/v1/sharing/immobili')
    ]);
    const perms = (me && me.permissions) || [];
    const has = (p) => perms.includes(p) || (me && me.role === 'admin');

    setTile('kpi-immobili', immobili, has('immobili'));
    setTile('kpi-lead', lead, has('lead'));
    setTile('kpi-appuntamenti', appuntamenti, has('appuntamenti'));
    setTile('kpi-sharing', sharing, has('home-sharing'));
    renderChart('kpi-chart', [
      { label: 'Immobili', value: (immobili || []).length, color: '#3273dc' },
      { label: 'Lead', value: (lead || []).length, color: '#23d160' },
      { label: 'Appunt.', value: (appuntamenti || []).length, color: '#ffdd57' },
      { label: 'Sharing', value: (sharing || []).length, color: '#ff3860' },
    ]);
    const feed = document.getElementById('activity-feed');
    feed.innerHTML = '<p>Attività caricate.</p>';
  } catch (e) {
    console.error('Dashboard load failed', e);
  }
}

function setTile(id, list, allowed) {
  const el = document.getElementById(id);
  const card = el?.closest('.column');
  if (!allowed) {
    if (card) card.style.display = 'none';
    return;
  }
  if (card) card.style.display = '';
  el.textContent = (list || []).length;
}

function renderChart(canvasId, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const width = canvas.width;
  const height = canvas.height;
  ctx.clearRect(0, 0, width, height);
  const max = Math.max(...data.map(d => d.value), 1);
  const barWidth = width / data.length - 10;
  data.forEach((d, i) => {
    const x = 10 + i * (barWidth + 10);
    const barHeight = (d.value / max) * (height - 20);
    ctx.fillStyle = d.color;
    ctx.fillRect(x, height - barHeight - 10, barWidth, barHeight);
    ctx.fillStyle = '#363636';
    ctx.font = '12px sans-serif';
    ctx.fillText(d.label, x, height - 2);
  });
}

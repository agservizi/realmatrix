const API_BASE = 'http://localhost:8000';
let token = null;
let permissions = [];
let refreshToken = null;

const permList = [
    'immobili_manage','clienti_manage','lead_manage','appuntamenti_manage',
    'contratti_manage','documenti_manage','fatture_manage','homesharing_manage',
    'config_manage','dashboard_full'
];

function renderPermSwitches() {
    const container = document.getElementById('perm-switches');
    container.innerHTML = '';
    permList.forEach((perm) => {
        const col = document.createElement('div');
        col.className = 'column is-half';
        col.innerHTML = `
            <label class="checkbox">
                <input type="checkbox" data-perm="${perm}" /> ${perm}
            </label>`;
        container.appendChild(col);
    });
}

async function login() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const statusEl = document.getElementById('login-status');
    statusEl.textContent = '...';
    const res = await fetch(`${API_BASE}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const body = await res.json();
    if (body.error) {
        statusEl.textContent = body.error;
        statusEl.className = 'tag is-danger';
        return;
    }
    token = body.data.token;
    refreshToken = body.data.refresh_token;
    permissions = body.data.user.permissions;
    statusEl.textContent = 'Ok';
    statusEl.className = 'tag is-success';
    document.getElementById('auth-section').classList.add('is-hidden');
    document.getElementById('dashboard').classList.remove('is-hidden');
    renderWidgets();
    await loadCollaboratori();
    await loadImmobili();
    await loadDocumenti();
    await loadHomeSharing();
}

async function loadCollaboratori() {
    const res = await fetch(`${API_BASE}/agency/collaborators`, {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    const body = await res.json();
    const tbody = document.querySelector('#collaboratori-table tbody');
    tbody.innerHTML = '';
    (body.data || []).forEach((c) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${c.name}</td><td>${c.email}</td><td>${c.role}</td><td>${(c.permissions||[]).join(', ')}</td>`;
        tbody.appendChild(tr);
    });
}

function authHeaders(json = false) {
    const h = { 'Authorization': `Bearer ${token}` };
    if (json) h['Content-Type'] = 'application/json';
    return h;
}

async function createCollaboratore() {
    const selectedPerms = Array.from(document.querySelectorAll('#perm-switches input:checked')).map(el => el.dataset.perm);
    const payload = {
        name: document.getElementById('c-name').value,
        email: document.getElementById('c-email').value,
        password: document.getElementById('c-pass').value,
        role: document.getElementById('c-role').value,
        permissions: selectedPerms
    };
    const res = await fetch(`${API_BASE}/agency/collaborators`, {
        method: 'POST',
        headers: authHeaders(true),
        body: JSON.stringify(payload)
    });
    const body = await res.json();
    document.getElementById('collab-status').textContent = body.error || 'Creato';
    await loadCollaboratori();
}

async function loadImmobili(page = 1) {
    const res = await fetch(`${API_BASE}/immobili?page=${page}&limit=10`, { headers: authHeaders() });
    const body = await res.json();
    const tbody = document.querySelector('#immobili-table tbody');
    tbody.innerHTML = '';
    (body.data.items || body.data || []).forEach((i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${i.titolo}</td><td>${i.prezzo ?? ''}</td><td>${i.stato ?? ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function createImmobile() {
    const payload = {
        titolo: document.getElementById('imm-titolo').value,
        descrizione: document.getElementById('imm-desc').value,
        prezzo: parseFloat(document.getElementById('imm-prezzo').value || '0'),
        stato: document.getElementById('imm-stato').value
    };
    const res = await fetch(`${API_BASE}/immobili`, {
        method: 'POST',
        headers: authHeaders(true),
        body: JSON.stringify(payload)
    });
    const body = await res.json();
    document.getElementById('imm-status').textContent = body.error || 'Creato';
    await loadImmobili();
}

async function loadDocumenti(page = 1) {
    const res = await fetch(`${API_BASE}/documenti?page=${page}&limit=10`, { headers: authHeaders() });
    const body = await res.json();
    const tbody = document.querySelector('#doc-table tbody');
    tbody.innerHTML = '';
    (body.data.items || body.data || []).forEach((d) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${d.titolo}</td><td>${d.file_path}</td>`;
        tbody.appendChild(tr);
    });
}

async function uploadDocumento() {
    const file = document.getElementById('doc-file').files[0];
    if (!file) {
        document.getElementById('doc-status').textContent = 'Seleziona un file';
        return;
    }
    const fd = new FormData();
    fd.append('file', file);
    fd.append('titolo', document.getElementById('doc-titolo').value || file.name);
    const res = await fetch(`${API_BASE}/documenti`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: fd
    });
    const body = await res.json();
    document.getElementById('doc-status').textContent = body.error || 'Caricato';
    await loadDocumenti();
}

async function loadHomeSharing(page = 1) {
    const res = await fetch(`${API_BASE}/homesharing?page=${page}&limit=10`, { headers: authHeaders() });
    const body = await res.json();
    const tbody = document.querySelector('#hs-table tbody');
    tbody.innerHTML = '';
    (body.data.items || body.data || []).forEach((r) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.id}</td><td>${r.from_agency_id}</td><td>${r.to_agency_id}</td><td>${r.immobile_id}</td><td>${r.stato}</td><td>
            <button class="button is-small" data-id="${r.id}" data-status="accepted">Accetta</button>
            <button class="button is-small is-light" data-id="${r.id}" data-status="rejected">Rifiuta</button>
        </td>`;
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll('button[data-id]').forEach(btn => {
        btn.addEventListener('click', () => changeHomeSharing(btn.dataset.id, btn.dataset.status));
    });
}

async function sendHomeSharing() {
    const payload = {
        to_agency_id: parseInt(document.getElementById('hs-to').value, 10),
        immobile_id: parseInt(document.getElementById('hs-imm').value, 10),
        messaggio: document.getElementById('hs-msg').value
    };
    const res = await fetch(`${API_BASE}/homesharing`, {
        method: 'POST',
        headers: authHeaders(true),
        body: JSON.stringify(payload)
    });
    const body = await res.json();
    document.getElementById('hs-status').textContent = body.error || 'Inviata';
    await loadHomeSharing();
}

async function changeHomeSharing(id, stato) {
    const res = await fetch(`${API_BASE}/homesharing/${id}`, {
        method: 'PUT',
        headers: authHeaders(true),
        body: JSON.stringify({ stato })
    });
    const body = await res.json();
    document.getElementById('hs-status').textContent = body.error || 'Aggiornato';
    await loadHomeSharing();
}

function renderWidgets() {
    const widgets = document.querySelectorAll('#widgets [data-perm]');
    widgets.forEach(w => {
        const perm = w.dataset.perm;
        w.style.display = permissions.includes(perm) ? 'block' : 'none';
    });
}

renderPermSwitches();
document.getElementById('login-btn').addEventListener('click', login);
document.getElementById('add-collaboratore').addEventListener('click', createCollaboratore);
document.getElementById('imm-create').addEventListener('click', createImmobile);
document.getElementById('doc-upload').addEventListener('click', uploadDocumento);
document.getElementById('hs-send').addEventListener('click', sendHomeSharing);

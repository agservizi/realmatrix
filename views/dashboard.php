<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3 mb-3" id="kpi-grid">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Immobili</p>
        <p class="h4 mb-0" id="kpi-immobili">0</p>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Lead</p>
        <p class="h4 mb-0" id="kpi-lead">0</p>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Appuntamenti</p>
        <p class="h4 mb-0" id="kpi-appuntamenti">0</p>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card text-center shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Sharing</p>
        <p class="h4 mb-0" id="kpi-sharing">0</p>
      </div>
    </div>
  </div>
</div>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h2 class="h5">KPI Grafico</h2>
    <canvas id="kpi-chart" width="600" height="200"></canvas>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5">Attività recenti</h2>
    <div id="activity-feed"></div>
  </div>
</div>
<script type="module">
import { loadDashboard } from '/public/assets/js/dashboard.js';
loadDashboard();
</script>

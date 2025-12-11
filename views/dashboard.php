<h1 class="title">Dashboard</h1>
<div class="columns is-multiline" id="kpi-grid">
  <div class="column is-one-quarter">
    <div class="box has-text-centered">
      <p class="heading">Immobili</p>
      <p class="title" id="kpi-immobili">0</p>
    </div>
  </div>
  <div class="column is-one-quarter">
    <div class="box has-text-centered">
      <p class="heading">Lead</p>
      <p class="title" id="kpi-lead">0</p>
    </div>
  </div>
  <div class="column is-one-quarter">
    <div class="box has-text-centered">
      <p class="heading">Appuntamenti</p>
      <p class="title" id="kpi-appuntamenti">0</p>
    </div>
  </div>
  <div class="column is-one-quarter">
    <div class="box has-text-centered">
      <p class="heading">Sharing</p>
      <p class="title" id="kpi-sharing">0</p>
    </div>
  </div>
</div>
<div class="box">
  <h2 class="subtitle">KPI Grafico</h2>
  <canvas id="kpi-chart" width="600" height="200"></canvas>
</div>
<div class="box">
  <h2 class="subtitle">Attività recenti</h2>
  <div id="activity-feed"></div>
</div>
<script type="module">
import { loadDashboard } from '/assets/js/dashboard.js';
loadDashboard();
</script>

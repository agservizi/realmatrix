<h1 class="h4 mb-3">Clienti</h1>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form id="cliente-form" class="row g-3">
      <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" type="email"></div>
      <div class="col-md-6"><label class="form-label">Telefono</label><input class="form-control" name="telefono"></div>
      <div class="col-md-6"><label class="form-label">Lead score</label><input class="form-control" name="lead_score" type="number"></div>
      <div class="col-12"><label class="form-label">Note</label><textarea class="form-control" name="note"></textarea></div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Salva</button></div>
    </form>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="clienti-table">
        <thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Lead</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
<script type="module">
import { initClienti } from '/public/assets/js/clienti.js';
initClienti();
</script>

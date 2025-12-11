<h1 class="h4 mb-3">Collaboratori</h1>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form id="collab-form" class="row g-3">
      <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
      <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required></div>
      <div class="col-md-6"><label class="form-label">Ruolo</label><input class="form-control" name="role" placeholder="agente"></div>
      <div class="col-12">
        <label class="form-label">Permessi (separati da virgola)</label>
        <input class="form-control" name="permissions" placeholder="immobili,clienti,lead">
      </div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Crea</button></div>
    </form>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="collab-table">
        <thead><tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Permessi</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
<script type="module">
import { initCollaboratori } from '/public/assets/js/collaboratori.js';
initCollaboratori();
</script>

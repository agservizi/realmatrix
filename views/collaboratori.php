<h1 class="title">Collaboratori</h1>
<div class="box">
  <form id="collab-form" class="columns is-multiline">
    <div class="column is-half"><label class="label">Nome</label><input class="input" name="name" required></div>
    <div class="column is-half"><label class="label">Email</label><input class="input" name="email" type="email" required></div>
    <div class="column is-half"><label class="label">Password</label><input class="input" name="password" type="password" required></div>
    <div class="column is-half"><label class="label">Ruolo</label><input class="input" name="role" placeholder="agente"></div>
    <div class="column is-full">
      <label class="label">Permessi (comma separated)</label>
      <input class="input" name="permissions" placeholder="immobili,clienti,lead">
    </div>
    <div class="column is-full"><button class="button is-primary" type="submit">Crea</button></div>
  </form>
</div>
<table class="table is-fullwidth" id="collab-table">
  <thead><tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Permessi</th></tr></thead>
  <tbody></tbody>
</table>
<script type="module">
import { initCollaboratori } from '/assets/js/collaboratori.js';
initCollaboratori();
</script>

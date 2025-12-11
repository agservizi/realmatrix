<h1 class="title">Clienti</h1>
<div class="box">
  <form id="cliente-form" class="columns is-multiline">
    <div class="column is-half"><label class="label">Nome</label><input class="input" name="nome" required></div>
    <div class="column is-half"><label class="label">Email</label><input class="input" name="email" type="email"></div>
    <div class="column is-half"><label class="label">Telefono</label><input class="input" name="telefono"></div>
    <div class="column is-half"><label class="label">Lead score</label><input class="input" name="lead_score" type="number"></div>
    <div class="column is-full"><label class="label">Note</label><textarea class="textarea" name="note"></textarea></div>
    <div class="column is-full"><button class="button is-primary" type="submit">Salva</button></div>
  </form>
</div>
<table class="table is-fullwidth" id="clienti-table">
  <thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Lead</th></tr></thead>
  <tbody></tbody>
</table>
<script type="module">
import { initClienti } from '/assets/js/clienti.js';
initClienti();
</script>

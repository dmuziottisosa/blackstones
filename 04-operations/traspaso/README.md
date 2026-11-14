# Traspaso de planillas al tablero

Archivos **cifrados** (AES-256) con las fichas de un traspaso puntual al tablero de obras. Sin la clave son ilegibles; la clave no está en el repo.

Sirven solo para el bloque de deploy de ese día: se bajan, se descifran en la máquina del dueño y se suben a `bs-data/tablero/importar.json`. Después de usarlos se borran de acá.

Regla: datos de clientes (nombres, teléfonos, direcciones) **nunca** van a este repo en texto plano, porque es público. Detalle del mecanismo en `04-operations/tablero-obras.md`, sección "Carga masiva".

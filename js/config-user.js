function configuser() {
    var modal = document.getElementById('editarPerfilModal');
    var cerrarBtn = document.getElementById('cerrarModal');
    if (modal) {
        window.abrirModalEditarPerfil = function() {
            modal.style.display = 'block';
        }
    }
    if (cerrarBtn) {
        cerrarBtn.onclick = function() {
            if (modal) {
                modal.style.display = 'none';
            }
        }
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

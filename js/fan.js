function fan() {
    jQuery(function ($) {
        $("#cambiartipodeusuario").click(function () {
            var data = {
                action: "cambiar_tipo_usuario",
                tipo: "fan",
            };

            $.post(ajaxurl, data, function (response) {
                var nuevo_estado =
                    response.trim() === "1" ? "activado" : "desactivado";
                var modo = nuevo_estado === "activado" ? "FAN" : "ARTISTA";
                $("#modo-usuario").text("MODO " + modo);
                alert(
                    "Tu usuario ha cambiado de " +
                        (nuevo_estado === "activado"
                            ? "artista a fan."
                            : "fan a artista.")
                );
                location.reload(); // Recarga la página
            });
        });
    });
}

function selectortipousuario() {
    jQuery(function ($) {
        window.selectUserType = function (type) {
            document.getElementById("tipo_usuario").value = type;
            document
                .getElementById("userTypeArtista")
                .classList.remove("selected");
            document.getElementById("userTypeFan").classList.remove("selected");
            document
                .getElementById(
                    "userType" + (type === "artista" ? "Artista" : "Fan")
                )
                .classList.add("selected");
        };

        window.validarSeleccion = function () {
            if (!document.getElementById("tipo_usuario").value) {
                document.getElementById("errorTipoUsuario").style.display =
                    "block";
                return false;
            }
            return true;
        };
    });
}

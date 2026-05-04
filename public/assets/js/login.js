$(function () {
  var $form = $("#form-login");
  var $usuario = $("#usuario");
  var $clave = $("#clave");
  var $errorUsuario = $("#error-usuario");
  var msgUsuarioVacio = "Ingrese el usuario.";

  /** Quita espacios y saltos de línea del valor y mantiene el cursor lo más cerca posible (pegar, autocompletar, etc.). */
  function quitarEspaciosEnCampo(el) {
    var v = el.value;
    if (!/\s/.test(v)) {
      return;
    }
    var start = el.selectionStart;
    var end = el.selectionEnd;
    var limpio = v.replace(/\s/g, "");
    var nuevoInicio = v.substring(0, start).replace(/\s/g, "").length;
    var nuevoFin = v.substring(0, end).replace(/\s/g, "").length;
    el.value = limpio;
    el.setSelectionRange(nuevoInicio, nuevoFin);
  }

  $usuario.trigger("focus");

  $usuario.add($clave).on("keydown", function (e) {
    if (e.key === " ") {
      e.preventDefault();
    }
  });

  $usuario.add($clave).on("input", function () {
    quitarEspaciosEnCampo(this);
    $(this).removeClass("is-invalid");
    if (this.id === "usuario") {
      $errorUsuario.text(msgUsuarioVacio);
    }
  });

  $form.on("submit", function (e) {
    var usuario = $.trim($usuario.val());
    var clave = $.trim($clave.val());
    var valido = true;

    $usuario.add($clave).removeClass("is-invalid");
    $errorUsuario.text(msgUsuarioVacio);

    if (!usuario) {
      $usuario.addClass("is-invalid");
      valido = false;
    }

    if (!clave) {
      $clave.addClass("is-invalid");
      valido = false;
    }

    if (!valido) {
      e.preventDefault();
      $usuario.add($clave).filter(".is-invalid").first().trigger("focus");
    }
  });
});

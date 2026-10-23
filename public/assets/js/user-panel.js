/* User panel behaviour (Tabler layout). */
(function () {
  "use strict";

  // Copy buttons: <button class="btn-copy" data-clipboard-target="#id">.
  if (window.ClipboardJS) {
    new ClipboardJS(".btn-copy").on("success", function () {
      toastr.success(getConfig.copiedToClipboardSuccess);
    });
  }

  // Ask before submitting forms whose button has .action-confirm.
  document.querySelectorAll(".action-confirm").forEach(function (el) {
    el.addEventListener("click", function (event) {
      if (!window.confirm(getConfig.actionConfirm)) {
        event.preventDefault();
      }
    });
  });

  // Account details: preview the chosen avatar before saving.
  var avatarInput = document.getElementById("change_avatar");
  var avatarPreview = document.getElementById("avatar_preview");
  if (avatarInput && avatarPreview) {
    avatarInput.addEventListener("change", function () {
      var file = avatarInput.files && avatarInput.files[0];
      if (file) {
        avatarPreview.style.backgroundImage = "url('" + URL.createObjectURL(file) + "')";
      }
    });
  }

  // Dashboard: filter the server list by country / city.
  var search = document.getElementById("server-search");
  var table = document.getElementById("server-table");
  if (search && table) {
    var empty = document.getElementById("server-search-empty");
    search.addEventListener("input", function () {
      var term = search.value.trim().toLowerCase();
      var shown = 0;
      table.querySelectorAll("tbody tr").forEach(function (row) {
        var match = !term || row.getAttribute("data-search").indexOf(term) !== -1;
        row.classList.toggle("d-none", !match);
        if (match) {
          shown++;
        }
      });
      table.classList.toggle("d-none", shown === 0);
      if (empty) {
        empty.classList.toggle("d-none", shown !== 0);
      }
    });
  }
})();

/* Light / dark switch shared by the user and admin panels (Tabler layout). */
(function () {
  "use strict";

  // Light / dark switch without a page reload. tabler-theme.js reads the same
  // "tabler-theme" key on the next page load; the links' ?theme= hrefs are the
  // no-JS fallback.
  document.querySelectorAll("[data-theme-switch]").forEach(function (el) {
    el.addEventListener("click", function (event) {
      event.preventDefault();
      var theme = el.getAttribute("data-theme-switch");
      try {
        localStorage.setItem("tabler-theme", theme);
      } catch (e) {}
      if (theme === "dark") {
        document.documentElement.setAttribute("data-bs-theme", "dark");
      } else {
        document.documentElement.removeAttribute("data-bs-theme");
      }
      var tooltip = window.tabler && tabler.Tooltip && tabler.Tooltip.getInstance(el);
      if (tooltip) {
        tooltip.hide();
      }
    });
  });
})();

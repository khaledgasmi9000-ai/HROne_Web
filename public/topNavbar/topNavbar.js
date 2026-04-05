document.addEventListener("DOMContentLoaded", function () {
  var bodyPage = (document.body.getAttribute("data-page") || "").toLowerCase();
  var links = document.querySelectorAll(".nav-link[data-href]");

  links.forEach(function (button) {
    var page = (button.getAttribute("data-page") || "").toLowerCase();
    if (page === bodyPage) {
      button.classList.add("active");
    }

    button.addEventListener("click", function () {
      var href = button.getAttribute("data-href");
      if (href) {
        window.location.href = href;
      }
    });
  });
});

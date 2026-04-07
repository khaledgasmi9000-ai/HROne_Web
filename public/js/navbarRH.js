document.addEventListener("DOMContentLoaded", function () {
  var currentFile = window.location.pathname.split("/").pop() || "navbarRH.html";
  currentFile = currentFile.toLowerCase();

  var links = document.querySelectorAll(".nav-link");
  links.forEach(function (link) {
    var href = (link.getAttribute("href") || "").toLowerCase();
    if (href === currentFile) {
      link.classList.add("active");
    }
  });
});

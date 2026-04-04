document.addEventListener("DOMContentLoaded", function () {
  var currentPath = window.location.pathname.replace(/\/$/, "");
  if (currentPath === "") {
    currentPath = "/";
  }

  var links = document.querySelectorAll(".nav-link");
  links.forEach(function (link) {
    var href = link.getAttribute("href") || "";
    var linkPath = href;

    try {
      linkPath = new URL(href, window.location.origin).pathname;
    } catch (error) {
      linkPath = href;
    }

    linkPath = linkPath.replace(/\/$/, "");
    if (linkPath === "") {
      linkPath = "/";
    }

    if (linkPath === currentPath) {
      link.classList.add("active");
    }
  });
});

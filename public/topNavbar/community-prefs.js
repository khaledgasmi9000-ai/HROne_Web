(function () {
  var K = {
    theme: "hro_comm_theme",
    font: "hro_comm_font",
    filters: "hro_comm_filters",
    dashFilters: "hro_comm_dash_filters",
  };

  function applyTheme() {
    var t = "light";
    try {
      t = localStorage.getItem(K.theme) || "light";
    } catch (e) {}
    document.documentElement.setAttribute("data-theme", t);
    var btn = document.getElementById("btnThemeToggle");
    if (btn) btn.setAttribute("aria-pressed", t === "dark" ? "true" : "false");
  }

  function applyFont() {
    var f = "m";
    try {
      f = localStorage.getItem(K.font) || "m";
    } catch (e) {}
    document.documentElement.setAttribute("data-font-scale", f);
  }

  function cycleTheme() {
    var cur =
      document.documentElement.getAttribute("data-theme") === "dark"
        ? "dark"
        : "light";
    var next = cur === "light" ? "dark" : "light";
    try {
      localStorage.setItem(K.theme, next);
    } catch (e) {}
    applyTheme();
  }

  function cycleFont() {
    var order = ["s", "m", "l"];
    var cur = "m";
    try {
      cur = localStorage.getItem(K.font) || "m";
    } catch (e) {}
    var i = order.indexOf(cur);
    var next = order[(i < 0 ? 1 : i + 1) % order.length];
    try {
      localStorage.setItem(K.font, next);
    } catch (e) {}
    applyFont();
  }

  function saveFeedFilters() {
    var ft = document.getElementById("filterTag");
    var st = document.getElementById("searchTitle");
    var mine = document.getElementById("filterMine");
    if (!ft && !st && !mine) return;
    try {
      localStorage.setItem(
        K.filters,
        JSON.stringify({
          tag: ft && ft.value ? ft.value.trim() : "",
          q: st && st.value ? st.value.trim() : "",
          mine: mine && mine.checked,
        })
      );
    } catch (e) {}
  }

  function restoreFeedFilters() {
    var ft = document.getElementById("filterTag");
    var st = document.getElementById("searchTitle");
    var mine = document.getElementById("filterMine");
    if (!ft && !st && !mine) return;
    try {
      var raw = localStorage.getItem(K.filters);
      if (!raw) return;
      var o = JSON.parse(raw);
      if (ft && o.tag) ft.value = o.tag;
      if (st && o.q) st.value = o.q;
      if (mine && o.mine) mine.checked = !!o.mine;
    } catch (e) {}
  }

  function saveDashFilters() {
    var dt = document.getElementById("dashFilterTag");
    var ds = document.getElementById("dashSearchTitle");
    var dc = document.getElementById("dashSearchComment");
    if (!dt && !ds && !dc) return;
    try {
      localStorage.setItem(
        K.dashFilters,
        JSON.stringify({
          tag: dt && dt.value ? dt.value.trim() : "",
          q: ds && ds.value ? ds.value.trim() : "",
          cq: dc && dc.value ? dc.value.trim() : "",
        })
      );
    } catch (e) {}
  }

  function restoreDashFilters() {
    var dt = document.getElementById("dashFilterTag");
    var ds = document.getElementById("dashSearchTitle");
    var dc = document.getElementById("dashSearchComment");
    if (!dt && !ds && !dc) return;
    try {
      var raw = localStorage.getItem(K.dashFilters);
      if (!raw) return;
      var o = JSON.parse(raw);
      if (dt && o.tag) dt.value = o.tag;
      if (ds && o.q) ds.value = o.q;
      if (dc && o.cq) dc.value = o.cq;
    } catch (e) {}
  }

  document.addEventListener("DOMContentLoaded", function () {
    applyTheme();
    applyFont();

    var bt = document.getElementById("btnThemeToggle");
    if (bt) bt.addEventListener("click", cycleTheme);
    var bf = document.getElementById("btnFontCycle");
    if (bf) bf.addEventListener("click", cycleFont);

    restoreFeedFilters();
    restoreDashFilters();

    var ft = document.getElementById("filterTag");
    var st = document.getElementById("searchTitle");
    var mine = document.getElementById("filterMine");
    if (ft) ft.addEventListener("change", saveFeedFilters);
    if (st) {
      st.addEventListener("change", saveFeedFilters);
      st.addEventListener("blur", saveFeedFilters);
    }
    if (mine) mine.addEventListener("change", saveFeedFilters);

    var btnDash = document.getElementById("btnApplyDashFilters");
    if (btnDash) btnDash.addEventListener("click", saveDashFilters);
    var dt = document.getElementById("dashFilterTag");
    var ds = document.getElementById("dashSearchTitle");
    var dc = document.getElementById("dashSearchComment");
    if (dt) dt.addEventListener("change", saveDashFilters);
    if (ds) {
      ds.addEventListener("change", saveDashFilters);
      ds.addEventListener("blur", saveDashFilters);
    }
    if (dc) {
      dc.addEventListener("change", saveDashFilters);
      dc.addEventListener("blur", saveDashFilters);
    }
  });
})();

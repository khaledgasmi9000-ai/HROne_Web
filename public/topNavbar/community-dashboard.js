(function () {
  var USER_KEY = "hro_community_user_id";

  function getUserIdInput() {
    var el = document.getElementById("currentUserId");
    if (!el) return 1;
    var v = parseInt(el.value, 10);
    if (!isFinite(v) || v < 1) return 1;
    return v;
  }

  function loadUserId() {
    var el = document.getElementById("currentUserId");
    if (!el) return;
    try {
      var s = localStorage.getItem(USER_KEY);
      if (s) {
        var n = parseInt(s, 10);
        if (isFinite(n) && n >= 1) el.value = String(n);
      }
    } catch (e) {}
  }

  function saveUserId() {
    var el = document.getElementById("currentUserId");
    if (el) {
      try {
        localStorage.setItem(USER_KEY, String(getUserIdInput()));
      } catch (e) {}
    }
  }

  function setSessionStatus(msg, isErr) {
    var el = document.getElementById("sessionStatus");
    if (!el) return;
    el.textContent = msg || "";
    el.style.color = isErr ? "#b91c1c" : "";
  }

  function setGlobalError(msg) {
    var el = document.getElementById("dashboardGlobalError");
    if (!el) return;
    if (msg) {
      el.textContent = msg;
      el.hidden = false;
    } else {
      el.textContent = "";
      el.hidden = true;
    }
  }

  function humanizeApiError(text, status) {
    var t = (text || "").trim();
    if (
      t.indexOf("<!DOCTYPE") === 0 ||
      t.indexOf("<html") === 0 ||
      t.indexOf("<!--") === 0
    ) {
      return (
        "Erreur serveur (" +
        (status || "?") +
        "). Vérifiez la base et le mapping Doctrine."
      );
    }
    if (t.length > 240) return t.slice(0, 240) + "…";
    return t || "Erreur " + (status || "");
  }

  function api(path, options) {
    options = options || {};
    var headers = options.headers || {};
    if (
      options.body &&
      typeof options.body === "string" &&
      !headers["Content-Type"]
    ) {
      headers["Content-Type"] = "application/json";
    }
    return fetch(path, {
      method: options.method || "GET",
      headers: headers,
      body: options.body,
      credentials: "same-origin",
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        if (text) {
          try {
            data = JSON.parse(text);
          } catch (e) {
            data = { message: humanizeApiError(text, res.status) };
          }
        }
        if (!res.ok) {
          var rawMsg =
            data && typeof data.message === "string" ? data.message : text;
          var msg = humanizeApiError(rawMsg, res.status);
          var err = new Error(msg);
          err.status = res.status;
          err.data = data;
          throw err;
        }
        return data;
      });
    });
  }

  function postSession(userId) {
    return api("/api/community/session", {
      method: "POST",
      body: JSON.stringify({ user_id: userId }),
    });
  }

  function esc(s) {
    if (s == null) return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function ui() {
    return typeof window !== "undefined" && window.__COMMUNITY_UI__
      ? window.__COMMUNITY_UI__
      : {};
  }

  function str(name) {
    var s = ui().strings || {};
    return s[name] || name;
  }

  function fmtDate(iso) {
    if (!iso) return "—";
    try {
      var d = new Date(iso);
      if (isNaN(d.getTime())) return iso;
      var loc = ui().locale || "fr";
      var tag = loc === "ar" ? "ar-TN" : loc === "en" ? "en-GB" : "fr-FR";
      return d.toLocaleString(tag);
    } catch (e) {
      return iso;
    }
  }

  var modalBackdrop = document.getElementById("modalBackdrop");
  var modalPost = document.getElementById("modalPost");
  var modalComment = document.getElementById("modalComment");

  function openModal(panel) {
    if (modalBackdrop) modalBackdrop.hidden = false;
    if (panel) panel.hidden = false;
  }

  function closeModals() {
    if (modalBackdrop) modalBackdrop.hidden = true;
    if (modalPost) modalPost.hidden = true;
    if (modalComment) modalComment.hidden = true;
  }

  function renderStats(g) {
    var grid = document.getElementById("statGrid");
    if (!grid || !g) return;

    var L = ui().statLabels || {};
    var cards = [
      { key: "posts_total", value: g.posts_total },
      { key: "posts_active_public", value: g.posts_active_public },
      { key: "posts_inactive", value: g.posts_inactive },
      { key: "posts_distinct_authors", value: g.posts_distinct_authors },
      { key: "comments_total", value: g.comments_total },
      { key: "comments_active_public", value: g.comments_active_public },
      { key: "comments_inactive", value: g.comments_inactive },
      { key: "comments_roots", value: g.comments_roots },
      { key: "comments_replies", value: g.comments_replies },
      { key: "votes_posts_total", value: g.votes_posts_total },
      { key: "votes_comments_total", value: g.votes_comments_total },
    ];

    grid.innerHTML = cards
      .map(function (c, i) {
        var label = L[c.key] || c.key;
        var tone = i % 6;
        return (
          '<div class="stat-card stat-tone-' +
          tone +
          '"><div class="stat-value">' +
          esc(c.value) +
          '</div><div class="stat-label">' +
          esc(label) +
          "</div></div>"
        );
      })
      .join("");

    var tagsPanel = document.getElementById("tagsPanel");
    var tagsList = document.getElementById("tagsBarList");
    var tags = g.tags_top || [];
    if (tagsPanel && tagsList && tags.length) {
      tagsPanel.hidden = false;
      var maxC = Math.max.apply(
        null,
        tags.map(function (t) {
          return t.count;
        })
      );
      tagsList.innerHTML = tags
        .map(function (t) {
          var w = maxC > 0 ? Math.round((100 * t.count) / maxC) : 0;
          return (
            "<li><span class=\"tag-name\" title=\"" +
            esc(t.tag) +
            '">' +
            esc(t.tag) +
            '</span><span class="tag-bar-wrap"><span class="tag-bar" style="width:' +
            w +
            '%"></span></span><span class="tag-num">' +
            esc(t.count) +
            "</span></li>"
          );
        })
        .join("");
    } else if (tagsPanel) {
      tagsPanel.hidden = true;
    }
  }

  function renderPostsTable(posts) {
    var tbody = document.querySelector("#tableMyPosts tbody");
    var empty = document.getElementById("emptyPosts");
    var badge = document.getElementById("countPostsBadge");
    var y = ui().labelYes || "Oui";
    var n = ui().labelNo || "Non";
    if (badge) badge.textContent = String((posts && posts.length) || 0);
    if (!tbody) return;
    tbody.innerHTML = "";
    if (!posts || !posts.length) {
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;
    posts.forEach(function (p) {
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" +
        esc(p.id) +
        "</td><td>" +
        esc(p.title) +
        "</td><td>" +
        esc(p.tag || "") +
        "</td><td>" +
        (p.is_active !== false ? esc(y) : esc(n)) +
        "</td><td>" +
        esc(p.score != null ? p.score : 0) +
        "</td><td>" +
        esc(fmtDate(p.created_at)) +
        '</td><td class="actions"><button type="button" class="btn-table" data-edit-post="' +
        esc(p.id) +
        '">Modifier</button><button type="button" class="btn-table danger" data-del-post="' +
        esc(p.id) +
        '">Supprimer</button></td>';
      tbody.appendChild(tr);
    });
  }

  function renderCommentsTable(comments) {
    var tbody = document.querySelector("#tableMyComments tbody");
    var empty = document.getElementById("emptyComments");
    var badge = document.getElementById("countCommentsBadge");
    var y = ui().labelYes || "Oui";
    var n = ui().labelNo || "Non";
    if (badge) badge.textContent = String((comments && comments.length) || 0);
    if (!tbody) return;
    tbody.innerHTML = "";
    if (!comments || !comments.length) {
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;
    comments.forEach(function (c) {
      var excerpt =
        (c.content || "").replace(/\s+/g, " ").trim().slice(0, 80) +
        ((c.content || "").length > 80 ? "…" : "");
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" +
        esc(c.id) +
        "</td><td>" +
        esc(c.post_id) +
        '</td><td class="cell-excerpt" title="' +
        esc(c.content || "") +
        '">' +
        esc(excerpt) +
        "</td><td>" +
        (c.is_active !== false ? esc(y) : esc(n)) +
        "</td><td>" +
        esc(c.score != null ? c.score : 0) +
        "</td><td>" +
        esc(fmtDate(c.created_at)) +
        '</td><td class="actions"><button type="button" class="btn-table" data-edit-comment="' +
        esc(c.id) +
        '">Modifier</button><button type="button" class="btn-table danger" data-del-comment="' +
        esc(c.id) +
        '">Supprimer</button></td>';
      tbody.appendChild(tr);
    });
  }

  var postsCache = [];
  var commentsCache = [];

  function buildDashPostsQuery() {
    var p = [];
    var tagEl = document.getElementById("dashFilterTag");
    var qEl = document.getElementById("dashSearchTitle");
    var tag = tagEl && tagEl.value ? tagEl.value.trim() : "";
    var qt = qEl && qEl.value ? qEl.value.trim() : "";
    if (tag.length > 80) {
      alert(str("validationSearch"));
      tag = tag.slice(0, 80);
      tagEl.value = tag;
    }
    if (qt.length > 120) {
      alert(str("validationSearch"));
      qt = qt.slice(0, 120);
      qEl.value = qt;
    }
    if (tag) p.push("tag=" + encodeURIComponent(tag));
    if (qt) p.push("q=" + encodeURIComponent(qt));
    return p.length ? "?" + p.join("&") : "";
  }

  function buildDashCommentsQuery() {
    var p = [];
    var qEl = document.getElementById("dashSearchComment");
    var qt = qEl && qEl.value ? qEl.value.trim() : "";
    if (qt.length > 120) {
      alert(str("validationSearch"));
      qt = qt.slice(0, 120);
      qEl.value = qt;
    }
    if (qt) p.push("q=" + encodeURIComponent(qt));
    return p.length ? "?" + p.join("&") : "";
  }

  function findPost(id) {
    id = parseInt(id, 10);
    for (var i = 0; i < postsCache.length; i++) {
      if (postsCache[i].id === id) return postsCache[i];
    }
    return null;
  }

  function findComment(id) {
    id = parseInt(id, 10);
    for (var i = 0; i < commentsCache.length; i++) {
      if (commentsCache[i].id === id) return commentsCache[i];
    }
    return null;
  }

  function loadDashboard() {
    setGlobalError("");
    return api("/api/community/dashboard/stats")
      .then(function (data) {
        if (data.global) renderStats(data.global);
        if (data.session_user) {
          var su = data.session_user;
          setSessionStatus(
            "Connecté : " + (su.name || "") + " (ID " + su.user_id + ")",
            false
          );
          var nameEl = document.getElementById("dashProfileName");
          var metaEl = document.getElementById("dashProfileMeta");
          var hintEl = document.getElementById("dashProfileHint");
          var idEl = document.getElementById("dashProfileId");
          var avEl = document.getElementById("dashProfileAvatar");
          if (nameEl) {
            nameEl.textContent = su.name || "";
            nameEl.hidden = false;
          }
          if (idEl) idEl.textContent = String(su.user_id);
          if (metaEl) metaEl.hidden = false;
          if (hintEl) hintEl.hidden = true;
          if (avEl && su.name) {
            avEl.textContent = String(su.name).charAt(0).toUpperCase();
          }
        }
        return api("/api/community/dashboard/my-posts" + buildDashPostsQuery());
      })
      .then(function (data) {
        postsCache = data.posts || [];
        renderPostsTable(postsCache);
        return api("/api/community/dashboard/my-comments" + buildDashCommentsQuery());
      })
      .then(function (data) {
        commentsCache = data.comments || [];
        renderCommentsTable(commentsCache);
      })
      .catch(function (e) {
        setGlobalError(e.message || String(e));
        setSessionStatus("", false);
      });
  }

  function applySession() {
    var uid = getUserIdInput();
    saveUserId();
    setSessionStatus("Connexion…", false);
    return postSession(uid)
      .then(function () {
        setSessionStatus("Session OK (ID " + uid + ").", false);
        return loadDashboard();
      })
      .catch(function (e) {
        setSessionStatus(e.message || "Erreur session", true);
      });
  }

  document.getElementById("btnSessionApply")?.addEventListener("click", function () {
    applySession();
  });
  document.getElementById("btnDashboardRefresh")?.addEventListener("click", function () {
    loadDashboard();
  });
  document.getElementById("btnApplyDashFilters")?.addEventListener("click", function () {
    loadDashboard();
  });

  document.body.addEventListener("click", function (ev) {
    var t = ev.target;
    if (t && t.getAttribute && t.getAttribute("data-close-modal") != null) {
      closeModals();
    }
    if (t === modalBackdrop) closeModals();

    var ep = t.closest && t.closest("[data-edit-post]");
    if (ep) {
      var pid = parseInt(ep.getAttribute("data-edit-post"), 10);
      var p = findPost(pid);
      if (p && modalPost) {
        var f = document.getElementById("formEditPost");
        if (f) {
          f.elements.entity_id.value = String(p.id);
          f.title.value = p.title || "";
          f.description.value = p.description || "";
          f.tag.value = p.tag || "";
          f.image_url.value = p.image_url || "";
          f.is_active.checked = p.is_active !== false;
        }
        openModal(modalPost);
      }
    }

    var ec = t.closest && t.closest("[data-edit-comment]");
    if (ec) {
      var cid = parseInt(ec.getAttribute("data-edit-comment"), 10);
      var c = findComment(cid);
      if (c && modalComment) {
        var fc = document.getElementById("formEditComment");
        if (fc) {
          fc.elements.entity_id.value = String(c.id);
          fc.content.value = c.content || "";
          fc.is_active.checked = c.is_active !== false;
        }
        openModal(modalComment);
      }
    }

    var dp = t.closest && t.closest("[data-del-post]");
    if (dp) {
      var delPid = parseInt(dp.getAttribute("data-del-post"), 10);
      if (confirm(str("confirmDeletePost") + " (#" + delPid + ")")) {
        api("/api/posts/" + delPid, { method: "DELETE" })
          .then(function () {
            return loadDashboard();
          })
          .catch(function (e) {
            alert(e.message);
          });
      }
    }

    var dc = t.closest && t.closest("[data-del-comment]");
    if (dc) {
      var delCid = parseInt(dc.getAttribute("data-del-comment"), 10);
      if (confirm(str("confirmDeleteComment") + " (#" + delCid + ")")) {
        api("/api/comments/" + delCid, { method: "DELETE" })
          .then(function () {
            return loadDashboard();
          })
          .catch(function (e) {
            alert(e.message);
          });
      }
    }
  });

  function showFormError(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || "";
    el.hidden = !msg;
  }

  document.getElementById("formEditPost")?.addEventListener("submit", function (ev) {
    ev.preventDefault();
    showFormError("formEditPostError", "");
    var f = ev.target;
    var id = parseInt(f.elements.entity_id.value, 10);
    var title = f.title.value.trim();
    if (!title || title.length > 255) {
      showFormError("formEditPostError", str("validationTitle"));
      return;
    }
    var tag = f.tag.value.trim();
    if (tag.length > 80) {
      showFormError("formEditPostError", str("validationTag"));
      return;
    }
    var imageUrl = f.image_url.value.trim();
    if (imageUrl) {
      try {
        new URL(imageUrl);
      } catch (e) {
        showFormError("formEditPostError", str("validationUrl"));
        return;
      }
      if (imageUrl.length > 2048) {
        showFormError("formEditPostError", str("validationUrl"));
        return;
      }
    }
    var desc = f.description.value;
    if (desc && desc.length > 10000) {
      showFormError("formEditPostError", str("validationComment"));
      return;
    }
    var body = {
      title: title,
      description: desc || null,
      tag: tag || "General",
      image_url: imageUrl || null,
      is_active: f.is_active.checked,
    };
    api("/api/posts/" + id, {
      method: "PATCH",
      body: JSON.stringify(body),
    })
      .then(function () {
        closeModals();
        return loadDashboard();
      })
      .catch(function (e) {
        alert(e.message);
      });
  });

  document.getElementById("formEditComment")?.addEventListener("submit", function (ev) {
    ev.preventDefault();
    showFormError("formEditCommentError", "");
    var f = ev.target;
    var id = parseInt(f.elements.entity_id.value, 10);
    var content = f.content.value.trim();
    if (!content || content.length > 8000) {
      showFormError("formEditCommentError", str("validationComment"));
      return;
    }
    var body = {
      content: content,
      is_active: f.is_active.checked,
    };
    api("/api/comments/" + id, {
      method: "PATCH",
      body: JSON.stringify(body),
    })
      .then(function () {
        closeModals();
        return loadDashboard();
      })
      .catch(function (e) {
        alert(e.message);
      });
  });

  loadUserId();
  postSession(getUserIdInput())
    .then(function () {
      return loadDashboard();
    })
    .catch(function () {
      setSessionStatus(
        "Connectez-vous avec votre ID puis cliquez « Connecter la session ».",
        false
      );
    });
})();

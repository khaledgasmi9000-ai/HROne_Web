(function () {
  var USER_KEY = "hro_community_user_id";

  function getUserIdInput() {
    var el = document.getElementById("currentUserId");
    if (!el) return 1;
    var v = parseInt(el.value, 10);
    if (!isFinite(v) || v < 1) return 1;
    return v;
  }

  function saveUserId() {
    var el = document.getElementById("currentUserId");
    if (el) {
      try {
        localStorage.setItem(USER_KEY, String(getUserIdInput()));
      } catch (e) {}
    }
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

  function setSessionStatus(msg, isErr) {
    var el = document.getElementById("sessionStatus");
    if (!el) return;
    el.textContent = msg || "";
    el.style.color = isErr ? "#b91c1c" : "";
  }

  /** Évite d’afficher toute la page HTML Symfony (500) dans un span. */
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
        "). Ouvrez l’onglet Réseau (F12) pour le détail, ou vérifiez le mapping Doctrine / la base."
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

  /** Ré-enregistre la session avant chaque mutation (évite la course au chargement). */
  function withCommunitySession(fn) {
    return postSession(getUserIdInput()).then(function () {
      return fn();
    });
  }

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
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

  function showFormErr(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || "";
    el.hidden = !msg;
  }

  function currentMeUserId(me) {
    return me && me.user_id ? me.user_id : null;
  }

  function commentDepth(id, byId, cache) {
    if (cache[id] !== undefined) return cache[id];
    var d = 0;
    var cur = byId[id];
    var seen = {};
    while (cur && cur.parent_comment_id) {
      if (seen[cur.id]) break;
      seen[cur.id] = true;
      d++;
      if (d > 50) break;
      cur = byId[cur.parent_comment_id];
    }
    cache[id] = d;
    return d;
  }

  function sortCommentsForDisplay(comments) {
    var byId = {};
    comments.forEach(function (c) {
      byId[c.id] = c;
    });
    var cache = {};
    return comments.slice().sort(function (a, b) {
      var da = commentDepth(a.id, byId, cache);
      var db = commentDepth(b.id, byId, cache);
      if (da !== db) return da - db;
      return (a.id || 0) - (b.id || 0);
    });
  }

  function renderCommentsBlock(card, list, postId, me) {
    var listEl = card.querySelector("[data-role=comments]");
    if (!listEl) return;
    var uid = currentMeUserId(me);
    if (!list || !list.length) {
      listEl.innerHTML = '<p class="post-meta">Pas encore de commentaire.</p>';
      return;
    }
    var sorted = sortCommentsForDisplay(list);
    var byId = {};
    sorted.forEach(function (c) {
      byId[c.id] = c;
    });
    var depthCache = {};
    listEl.innerHTML = sorted
      .map(function (c) {
        var depth = commentDepth(c.id, byId, depthCache);
        var pad = Math.min(depth * 16, 120);
        var voteRow =
          '<span class="vote-inline">' +
          '<button type="button" class="btn-vote btn-vote-up" data-vote-comment="' +
          esc(c.id) +
          '" data-vt="up" title="Pour (like)">▲ ' +
          esc(c.votes_up || 0) +
          "</button>" +
          '<button type="button" class="btn-vote btn-vote-down" data-vote-comment="' +
          esc(c.id) +
          '" data-vt="down" title="Contre (dislike)">▼ ' +
          esc(c.votes_down || 0) +
          "</button>" +
          '<span class="score">(' +
          esc(c.score != null ? c.score : 0) +
          ")</span></span>";
        var del =
          uid && c.user_id === uid
            ? '<button type="button" class="btn-ghost" data-del-comment="' +
              esc(c.id) +
              '">Supprimer</button>'
            : "";
        var reply =
          '<button type="button" class="btn-ghost btn-reply" data-reply-to="' +
          esc(c.id) +
          '">Répondre</button>';
        return (
          '<div class="comment-line" style="margin-left:' +
          pad +
          'px" data-cid="' +
          esc(c.id) +
          '">' +
          "<strong>" +
          esc(c.author_name || "Utilisateur") +
          "</strong> · #" +
          esc(c.id) +
          " · " +
          esc(c.created_at || "") +
          "<br/>" +
          esc(c.content) +
          '<div class="comment-actions">' +
          voteRow +
          reply +
          del +
          "</div></div>"
        );
      })
      .join("");

    listEl.querySelectorAll("[data-del-comment]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-del-comment");
        if (!cid || !confirm(str("confirmDeleteComment"))) return;
        withCommunitySession(function () {
          return api("/api/comments/" + cid, { method: "DELETE" });
        })
          .then(function () {
            return loadCommentsForCard(card, postId, me);
          })
          .catch(function (e) {
            alert(e.message || String(e));
          });
      });
    });

    listEl.querySelectorAll("[data-vote-comment]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-vote-comment");
        var vt = btn.getAttribute("data-vt");
        if (!cid || !vt) return;
        withCommunitySession(function () {
          return api("/api/comments/" + cid + "/vote", {
            method: "POST",
            body: JSON.stringify({ vote_type: vt }),
          });
        })
          .then(function () {
            return loadCommentsForCard(card, postId, me);
          })
          .catch(function (e) {
            alert(e.message || String(e));
          });
      });
    });

    listEl.querySelectorAll("[data-reply-to]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var pid = btn.getAttribute("data-reply-to");
        var form = card.querySelector("form[data-role=comment-form]");
        if (!form || !pid) return;
        var hid = form.querySelector('input[name="parent_comment_id"]');
        if (!hid) {
          hid = document.createElement("input");
          hid.type = "hidden";
          hid.name = "parent_comment_id";
          form.appendChild(hid);
        }
        hid.value = pid;
        var ta = form.querySelector("textarea[name=content]");
        if (ta) {
          ta.focus();
          ta.placeholder = "Réponse au commentaire #" + pid + "…";
        }
      });
    });
  }

  function loadCommentsForCard(card, postId, me) {
    var list = card.querySelector("[data-role=comments]");
    if (!list) return Promise.resolve();
    return api("/api/posts/" + postId)
      .then(function (data) {
        var comments = (data && data.comments) || [];
        renderCommentsBlock(card, comments, postId, me);
      })
      .catch(function () {
        list.innerHTML =
          '<p class="feed-error">Erreur chargement commentaires.</p>';
      });
  }

  function renderPosts(container, posts, me) {
    container.innerHTML = "";
    var uid = currentMeUserId(me);
    if (!posts || !posts.length) {
      container.innerHTML =
        '<p class="post-meta">Aucun post pour le moment.</p>';
      return;
    }
    posts.forEach(function (post) {
      var card = document.createElement("article");
      card.className = "post-card";
      card.dataset.postId = String(post.id);
      var author = post.author_name ? esc(post.author_name) : "user " + esc(post.user_id);
      var tag = post.tag ? " · " + esc(post.tag) : "";
      var when = post.created_at ? esc(post.created_at) : "";
      var desc = post.description ? esc(post.description) : "";
      var isOwner = uid && post.user_id === uid;
      var editBtn = isOwner
        ? '<button type="button" class="btn-ghost" data-action="edit-post">Modifier</button>'
        : "";
      var delBtn = isOwner
        ? '<button type="button" class="btn-danger" data-action="delete-post">Supprimer</button>'
        : "";
      var votes =
        '<div class="vote-row">' +
        '<button type="button" class="btn-vote btn-vote-up" data-vote-post="' +
        esc(post.id) +
        '" data-vt="up" title="Pour (like)">▲ ' +
        esc(post.votes_up != null ? post.votes_up : 0) +
        "</button>" +
        '<button type="button" class="btn-vote btn-vote-down" data-vote-post="' +
        esc(post.id) +
        '" data-vt="down" title="Contre (dislike)">▼ ' +
        esc(post.votes_down != null ? post.votes_down : 0) +
        "</button>" +
        '<span class="score">Score ' +
        esc(post.score != null ? post.score : 0) +
        "</span>" +
        '<span class="post-meta"> · ' +
        esc(post.comments_count != null ? post.comments_count : 0) +
        " commentaire(s)</span></div>";

      card.innerHTML =
        "<h3>" +
        esc(post.title) +
        "</h3>" +
        '<div class="post-meta">#' +
        esc(post.id) +
        " · " +
        author +
        tag +
        (when ? " · " + when : "") +
        "</div>" +
        votes +
        (desc ? '<p class="post-body">' + desc + "</p>" : "") +
        (post.image_url
          ? '<p class="post-meta"><a href="' +
            esc(post.image_url) +
            '" target="_blank" rel="noopener">Image</a></p>'
          : "") +
        '<div class="post-actions">' +
        editBtn +
        delBtn +
        "</div>" +
        '<div class="comments-block">' +
        "<h4>Commentaires</h4>" +
        '<div class="comments-list" data-role="comments"></div>' +
        '<form class="comment-form" data-role="comment-form">' +
        '<input type="hidden" name="parent_comment_id" value="" />' +
        "<label>Nouveau commentaire" +
        '<textarea name="content" required maxlength="8000" placeholder="Votre message"></textarea></label>' +
        '<button type="submit" class="btn-primary">Commenter</button>' +
        "</form>" +
        "</div>";

      container.appendChild(card);

      var hidParent = card.querySelector('input[name="parent_comment_id"]');
      if (hidParent) hidParent.value = "";

      loadCommentsForCard(card, post.id, me);

      card.querySelectorAll("[data-vote-post]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var pid = btn.getAttribute("data-vote-post");
          var vt = btn.getAttribute("data-vt");
          withCommunitySession(function () {
            return api("/api/posts/" + pid + "/vote", {
              method: "POST",
              body: JSON.stringify({ vote_type: vt }),
            });
          })
            .then(function () {
              return refreshFeed();
            })
            .catch(function (e) {
              alert(e.message || String(e));
            });
        });
      });

      var del = card.querySelector("[data-action=delete-post]");
      if (del) {
        del.addEventListener("click", function () {
          if (!confirm(str("confirmDeletePost"))) return;
          withCommunitySession(function () {
            return api("/api/posts/" + post.id, { method: "DELETE" });
          })
            .then(function () {
              return refreshFeed();
            })
            .catch(function (e) {
              alert(e.message || String(e));
            });
        });
      }

      var ed = card.querySelector("[data-action=edit-post]");
      if (ed) {
        ed.addEventListener("click", function () {
          var t = prompt("Nouveau titre", post.title || "");
          if (t === null) return;
          t = t.trim();
          if (!t) {
            alert("Titre vide.");
            return;
          }
          var d = prompt("Description (laisser vide pour ne pas changer)", post.description || "");
          var payload = { title: t };
          if (d !== null) payload.description = d;
          withCommunitySession(function () {
            return api("/api/posts/" + post.id, {
              method: "PATCH",
              body: JSON.stringify(payload),
            });
          })
            .then(function () {
              return refreshFeed();
            })
            .catch(function (e) {
              alert(e.message || String(e));
            });
        });
      }

      card.querySelector("form[data-role=comment-form]").addEventListener(
        "submit",
        function (ev) {
          ev.preventDefault();
          var form = ev.target;
          var ta = form.querySelector("textarea[name=content]");
          var content = (ta && ta.value) || "";
          content = content.trim();
          if (!content) return;
          if (content.length > 8000) {
            alert(str("validationComment"));
            return;
          }
          var hid = form.querySelector('input[name="parent_comment_id"]');
          var parentId = hid && hid.value ? parseInt(hid.value, 10) : null;
          var body = { content: content };
          if (parentId && isFinite(parentId) && parentId > 0) {
            body.parent_comment_id = parentId;
          }
          withCommunitySession(function () {
            return api("/api/posts/" + post.id + "/comments", {
              method: "POST",
              body: JSON.stringify(body),
            });
          })
            .then(function () {
              ta.value = "";
              if (hid) hid.value = "";
              if (ta) ta.placeholder = "Votre message";
              return loadCommentsForCard(card, post.id, me);
            })
            .catch(function (e) {
              alert(e.message || String(e));
            });
        }
      );
    });
  }

  function buildFeedQuery() {
    var q = [];
    var tagEl = document.getElementById("filterTag");
    var tag = tagEl && tagEl.value ? tagEl.value.trim() : "";
    if (tag.length > 80) {
      alert(str("validationSearch"));
      tag = tag.slice(0, 80);
      tagEl.value = tag;
    }
    if (tag) q.push("tag=" + encodeURIComponent(tag));
    var st = document.getElementById("searchTitle");
    var sq = st && st.value ? st.value.trim() : "";
    if (sq.length > 120) {
      alert(str("validationSearch"));
      sq = sq.slice(0, 120);
      st.value = sq;
    }
    if (sq) q.push("q=" + encodeURIComponent(sq));
    var mine = document.getElementById("filterMine");
    if (mine && mine.checked) {
      var u = getUserIdInput();
      q.push("user_id=" + encodeURIComponent(String(u)));
    }
    return q.length ? "?" + q.join("&") : "";
  }

  function loadWeatherWidget() {
    var el = document.getElementById("weatherBody");
    var wrap = document.getElementById("weatherWidget");
    if (!el || !wrap) return;
    var errTxt = wrap.getAttribute("data-weather-err") || "—";
    api("/api/community/weather")
      .then(function (data) {
        var w = data && data.weather;
        if (!w || w.temperature_c == null) {
          el.textContent = errTxt;
          return;
        }
        el.innerHTML =
          '<span class="weather-metric">' +
          esc(w.temperature_c) +
          " °C</span> · " +
          esc(w.humidity_pct != null ? w.humidity_pct + " %" : "—") +
          " · " +
          esc(w.wind_kmh != null ? w.wind_kmh + " km/h" : "—");
      })
      .catch(function () {
        el.textContent = errTxt;
      });
  }

  function appendChatLine(cls, textHtml) {
    var log = document.getElementById("chatLog");
    if (!log) return;
    var div = document.createElement("div");
    div.className = "chat-msg " + cls;
    div.innerHTML = textHtml;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  function wireChatbot() {
    var form = document.getElementById("chatForm");
    if (!form) return;
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var ta = document.getElementById("chatInput");
      var msg = ta && ta.value ? ta.value.trim() : "";
      if (!msg) return;
      appendChatLine("chat-msg-user", esc(msg).replace(/\n/g, "<br/>"));
      ta.value = "";
      api("/api/community/assistant", {
        method: "POST",
        body: JSON.stringify({ message: msg }),
      })
        .then(function (data) {
          var r = (data && data.reply) || "";
          var block = esc(r).replace(/\n/g, "<br/>");
          if (data.suggestions && data.suggestions.length) {
            block +=
              '<div class="chat-suggestions"><small>' +
              esc(data.suggestions.join(" · ")) +
              "</small></div>";
          }
          appendChatLine("chat-msg-bot", block);
        })
        .catch(function (e) {
          appendChatLine("chat-msg-bot", esc(e.message || String(e)));
        });
    });
  }

  function refreshFeed() {
    var feed = document.getElementById("postsFeed");
    var errEl = document.getElementById("feedError");
    if (!feed) return Promise.resolve();
    if (errEl) {
      errEl.hidden = true;
      errEl.textContent = "";
    }
    return api("/api/community/me")
      .then(function (meData) {
        var me = meData && meData.user;
        return api("/api/posts" + buildFeedQuery()).then(function (data) {
          renderPosts(feed, (data && data.posts) || [], me);
        });
      })
      .catch(function (e) {
        if (errEl) {
          errEl.hidden = false;
          errEl.textContent =
            e.message ||
            "Impossible de charger les posts (session, base ou serveur).";
        }
      });
  }

  function applySessionFromInput() {
    var id = getUserIdInput();
    setSessionStatus("Connexion…", false);
    return postSession(id)
      .then(function (u) {
        saveUserId();
        setSessionStatus(
          "Connecté : " +
            (u && u.name ? u.name + " (#" + u.user_id + ")" : "#" + id),
          false
        );
        return refreshFeed();
      })
      .catch(function (e) {
        setSessionStatus(e.message || "Échec session", true);
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadUserId();

    var uid = document.getElementById("currentUserId");
    if (uid) {
      uid.addEventListener("change", saveUserId);
      uid.addEventListener("blur", saveUserId);
    }

    var btnSess = document.getElementById("btnSessionApply");
    if (btnSess) btnSess.addEventListener("click", applySessionFromInput);

    var btnRef = document.getElementById("btnRefreshFeed");
    if (btnRef) btnRef.addEventListener("click", refreshFeed);
    var ft = document.getElementById("filterTag");
    if (ft) ft.addEventListener("change", refreshFeed);
    var fm = document.getElementById("filterMine");
    if (fm) fm.addEventListener("change", refreshFeed);
    var st = document.getElementById("searchTitle");
    if (st) {
      st.addEventListener("change", refreshFeed);
      st.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          refreshFeed();
        }
      });
    }

    loadWeatherWidget();
    wireChatbot();

    var form = document.getElementById("formNewPost");
    if (form) {
      form.addEventListener("submit", function (ev) {
        ev.preventDefault();
        showFormErr("formNewPostError", "");
        var fd = new FormData(form);
        var title = (fd.get("title") || "").toString().trim();
        if (!title || title.length > 255) {
          showFormErr("formNewPostError", str("validationTitle"));
          return;
        }
        var tag = (fd.get("tag") || "").toString().trim();
        if (tag.length > 80) {
          showFormErr("formNewPostError", str("validationTag"));
          return;
        }
        var imageUrl = (fd.get("image_url") || "").toString().trim();
        if (imageUrl) {
          try {
            new URL(imageUrl);
          } catch (e) {
            showFormErr("formNewPostError", str("validationUrl"));
            return;
          }
          if (imageUrl.length > 2048) {
            showFormErr("formNewPostError", str("validationUrl"));
            return;
          }
        }
        var desc = (fd.get("description") || "").toString();
        if (desc.length > 10000) {
          showFormErr("formNewPostError", str("validationComment"));
          return;
        }
        var payload = {
          title: title,
          description: desc || null,
          tag: tag || null,
          image_url: imageUrl || null,
        };
        withCommunitySession(function () {
          return api("/api/posts", {
            method: "POST",
            body: JSON.stringify(payload),
          });
        })
          .then(function () {
            form.reset();
            return refreshFeed();
          })
          .catch(function (e) {
            alert(e.message || String(e));
          });
      });
    }

    applySessionFromInput();
  });
})();

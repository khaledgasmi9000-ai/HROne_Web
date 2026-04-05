(function () {
  var USER_KEY = "hro_community_user_id";
  var BOOKMARK_KEY = "hro_comm_bookmarks";
  var FAV_TAGS_KEY = "hro_comm_fav_tags";
  var FILTER_KEY = "hro_comm_filters";
  var THEME_KEY = "hro_comm_theme";
  var FONT_KEY = "hro_comm_font";
  var HIDDEN_USERS_KEY = "hro_comm_hidden_users";
  var PINNED_COMMENTS_KEY = "hro_comm_pinned_comments";
  var REPORTS_KEY = "hro_comm_reports";
  var NOTIFS_KEY = "hro_comm_notifications";
  var COMMENT_BADGES_KEY = "hro_comm_comment_badges";
  var COLLEAGUE_HISTORY_KEY = "hro_comm_colleague_history";
  var COLLEAGUE_EDIT_KEY = "hro_comm_colleague_edit_history";
  var CONTENT_EDIT_KEY = "hro_comm_content_edit_history";

  var __feedEditCommentCtx = null;
  var __communityState = {
    me: null,
    posts: [],
    commentsByPost: {},
    colleagueChannel: null,
    typingTimer: null,
  };

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
        "). Ouvrez l’onglet Réseau (F12) pour le détail, ou vérifiez la base de données."
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

  function readStore(key, fallback) {
    try {
      var raw = localStorage.getItem(key);
      if (!raw) return fallback;
      return JSON.parse(raw);
    } catch (e) {
      return fallback;
    }
  }

  function writeStore(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {}
  }

  function normalizeHandle(value) {
    return String(value || "")
      .toLowerCase()
      .replace(/[^a-z0-9_]+/g, "");
  }

  function currentUserHandles() {
    var me = __communityState.me || {};
    var list = [];
    if (me.name) list.push(normalizeHandle(me.name));
    if (me.user_id) {
      list.push("user" + String(me.user_id));
      list.push(String(me.user_id));
    }
    return list.filter(Boolean);
  }

  function textMentionsCurrentUser(text) {
    var content = String(text || "").toLowerCase();
    return currentUserHandles().some(function (handle) {
      return handle && content.indexOf("@" + handle) >= 0;
    });
  }

  function pushNotification(entry) {
    var list = readStore(NOTIFS_KEY, []);
    list.unshift({
      id: Date.now() + "-" + Math.random().toString(36).slice(2, 7),
      ts: new Date().toISOString(),
      type: entry.type || "info",
      title: entry.title || "Notification",
      body: entry.body || "",
    });
    writeStore(NOTIFS_KEY, list.slice(0, 60));
    renderNotificationHistory();
  }

  function canUseDesktopNotifications() {
    return typeof window !== "undefined" && "Notification" in window;
  }

  function notifyDesktop(title, body) {
    if (!canUseDesktopNotifications()) return;
    if (Notification.permission !== "granted") return;
    try {
      new Notification(title, { body: body || "" });
    } catch (e) {}
  }

  function requestDesktopNotifications() {
    if (!canUseDesktopNotifications()) {
      alert(str("noNotificationApi"));
      return;
    }
    Notification.requestPermission().then(function (permission) {
      pushNotification({
        type: "system",
        title: str("notifPermission"),
        body: permission,
      });
      renderNotificationHistory();
    });
  }

  function applyThemePreference() {
    var theme = "light";
    try {
      theme = localStorage.getItem(THEME_KEY) || "light";
    } catch (e) {}
    document.documentElement.setAttribute("data-theme", theme);
    var btn = document.getElementById("btnThemeToggle");
    if (btn) btn.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
  }

  function cycleThemePreference() {
    var current =
      document.documentElement.getAttribute("data-theme") === "dark"
        ? "dark"
        : "light";
    try {
      localStorage.setItem(THEME_KEY, current === "dark" ? "light" : "dark");
    } catch (e) {}
    applyThemePreference();
  }

  function applyFontPreference() {
    var scale = "m";
    try {
      scale = localStorage.getItem(FONT_KEY) || "m";
    } catch (e) {}
    document.documentElement.setAttribute("data-font-scale", scale);
  }

  function cycleFontPreference() {
    var order = ["s", "m", "l"];
    var current = "m";
    try {
      current = localStorage.getItem(FONT_KEY) || "m";
    } catch (e) {}
    var next = order[(Math.max(order.indexOf(current), 0) + 1) % order.length];
    try {
      localStorage.setItem(FONT_KEY, next);
    } catch (e) {}
    applyFontPreference();
  }

  function fallbackStrings() {
    var locale = (ui().locale || "fr").toLowerCase();
    if (locale === "en") {
      return {
        voteLike: "Like",
        voteDislike: "Dislike",
        noComments: "No comments yet.",
        noPosts: "No posts yet.",
        delete: "Delete",
        edit: "Edit",
        reply: "Reply",
        bookmarksEmpty: "No saved posts.",
        validationTagChars: "Tag contains invalid characters.",
        commentRequired: "Comment is required.",
        commentIsReply: "Reply to comment",
        scoreLabel: "Score",
        commentsLabel: "comments",
        bookmarkTitle: "Save",
        share: "Share",
        shareCopy: "Copy link",
        shareTwitter: "Share on X",
        shareLinkedin: "Share on LinkedIn",
        shareNative: "Share",
        shareClose: "Close",
        shareCopied: "Link copied.",
        imageLink: "Image",
        commentsHeading: "Comments",
        newCommentLabel: "New comment",
        commentPlaceholder: "Write your message",
        commentSubmit: "Comment",
        replyPlaceholder: "Reply to comment #",
        commentsLoadError: "Unable to load comments.",
        promptNewTitle: "New title",
        promptNewDesc: "New description",
        promptNewTag: "New tag",
        promptNewImage: "New image URL",
        promptVisibility: "Visible? type yes or no",
        modalSave: "Save",
        modalCancel: "Cancel",
        postVisible: "Visible post",
        commentVisible: "Visible comment",
        modalContent: "Content",
        emptyTitle: "Title cannot be empty.",
        colleagueTitle: "Colleague room",
        colleagueHint: "Local discussion synced between your browser tabs.",
        colleaguePlaceholder: "Write to your colleagues...",
        colleagueEmpty: "No messages yet in the room.",
        pin: "Pin",
        unpin: "Unpin",
        pinned: "Pinned",
        hideAuthor: "Hide this author",
        confirmHideUser: "Hide all comments from this user on this device?",
        report: "Report",
        reportReason: "Reason for report",
        reportThanks: "Report saved locally.",
        reportRecorded: "Report saved",
        notifBookmarkAdded: "Post saved",
        notifBookmarkRemoved: "Post removed from saved items",
        notifMention: "You were mentioned",
        notifPermission: "Notification permission",
        noNotificationApi: "Notifications are unavailable in this browser.",
        typing: "A colleague is typing...",
        topPostsTitle: "Top 5 posts",
        topCommentsTitle: "Top 5 recent comments",
        moderationTitle: "Moderation queue",
        moderationEmpty: "No reports pending.",
        mergeDiscussion: "Merge",
        editHistory: "History",
      };
    }
    if (locale === "ar") {
      return {
        voteLike: "إعجاب",
        voteDislike: "عدم إعجاب",
        noComments: "لا توجد تعليقات بعد.",
        noPosts: "لا توجد منشورات حالياً.",
        delete: "حذف",
        edit: "تعديل",
        reply: "رد",
        bookmarksEmpty: "لا توجد منشورات محفوظة.",
        validationTagChars: "الوسم يحتوي على أحرف غير صالحة.",
        commentRequired: "التعليق مطلوب.",
        commentIsReply: "رد على التعليق",
        scoreLabel: "النقاط",
        commentsLabel: "تعليقات",
        bookmarkTitle: "حفظ",
        share: "مشاركة",
        shareCopy: "نسخ الرابط",
        shareTwitter: "مشاركة على X",
        shareLinkedin: "مشاركة على LinkedIn",
        shareNative: "مشاركة",
        shareClose: "إغلاق",
        shareCopied: "تم نسخ الرابط.",
        imageLink: "صورة",
      commentsHeading: "التعليقات",
      newCommentLabel: "تعليق جديد",
      commentPlaceholder: "اكتب رسالتك",
      commentSubmit: "إرسال",
      replyPlaceholder: "الرد على التعليق #",
      commentsLoadError: "تعذر تحميل التعليقات.",
      promptNewTitle: "عنوان جديد",
      promptNewDesc: "وصف جديد",
      promptNewTag: "وسم جديد",
      promptNewImage: "رابط صورة جديد",
      promptVisibility: "مرئي؟ اكتب نعم أو لا",
      modalSave: "حفظ",
      modalCancel: "إلغاء",
      postVisible: "المنشور مرئي",
      commentVisible: "التعليق مرئي",
      modalContent: "المحتوى",
      emptyTitle: "العنوان لا يمكن أن يكون فارغاً.",
      colleagueTitle: "غرفة الزملاء",
        colleagueHint: "نقاش محلي متزامن بين علامات تبويب المتصفح.",
        colleaguePlaceholder: "اكتب لزملائك...",
        colleagueEmpty: "لا توجد رسائل بعد.",
        pin: "تثبيت",
        unpin: "إلغاء التثبيت",
        pinned: "مثبت",
        hideAuthor: "إخفاء هذا المستخدم",
        confirmHideUser: "إخفاء جميع تعليقات هذا المستخدم على هذا الجهاز؟",
        report: "إبلاغ",
        reportReason: "سبب البلاغ",
        reportThanks: "تم حفظ البلاغ محلياً.",
        reportRecorded: "تم حفظ البلاغ",
        notifBookmarkAdded: "تم حفظ المنشور",
        notifBookmarkRemoved: "تمت إزالة المنشور من المحفوظات",
        notifMention: "تمت الإشارة إليك",
        notifPermission: "إذن الإشعارات",
        noNotificationApi: "الإشعارات غير متوفرة في هذا المتصفح.",
        typing: "أحد الزملاء يكتب الآن...",
        topPostsTitle: "أفضل 5 منشورات",
        topCommentsTitle: "آخر 5 تعليقات",
        moderationTitle: "قائمة الإشراف",
        moderationEmpty: "لا توجد بلاغات معلقة.",
        mergeDiscussion: "دمج",
        editHistory: "السجل",
      };
    }
    return {
      voteLike: "J'aime",
      voteDislike: "Je n'aime pas",
      noComments: "Pas encore de commentaire.",
      noPosts: "Aucun post pour le moment.",
      delete: "Supprimer",
      edit: "Modifier",
      reply: "Répondre",
      bookmarksEmpty: "Aucun post enregistré.",
      validationTagChars: "Le tag contient des caractères invalides.",
      commentRequired: "Le commentaire est obligatoire.",
      commentIsReply: "Réponse au commentaire",
      scoreLabel: "Score",
      commentsLabel: "commentaires",
      bookmarkTitle: "Enregistrer",
      share: "Partager",
      shareCopy: "Copier le lien",
      shareTwitter: "Partager sur X",
      shareLinkedin: "Partager sur LinkedIn",
      shareNative: "Partager",
      shareClose: "Fermer",
      shareCopied: "Lien copié.",
      imageLink: "Image",
      commentsHeading: "Commentaires",
      newCommentLabel: "Nouveau commentaire",
      commentPlaceholder: "Votre message",
      commentSubmit: "Commenter",
      replyPlaceholder: "Réponse au commentaire #",
      commentsLoadError: "Erreur chargement commentaires.",
      promptNewTitle: "Nouveau titre",
      promptNewDesc: "Nouvelle description",
      promptNewTag: "Nouveau tag",
      promptNewImage: "Nouvelle URL image",
      promptVisibility: "Visible ? tapez oui ou non",
      modalSave: "Enregistrer",
      modalCancel: "Annuler",
      postVisible: "Post visible",
      commentVisible: "Commentaire visible",
      modalContent: "Contenu",
      emptyTitle: "Le titre ne peut pas être vide.",
      colleagueTitle: "Salon collègues",
      colleagueHint: "Discussion locale synchronisée entre vos onglets.",
      colleaguePlaceholder: "Écrire aux collègues...",
      colleagueEmpty: "Aucun message pour le moment.",
      pin: "Épingler",
      unpin: "Désépingler",
      pinned: "Épinglé",
      hideAuthor: "Masquer cet auteur",
      confirmHideUser: "Masquer tous les commentaires de cet utilisateur sur votre appareil ?",
      report: "Signaler",
      reportReason: "Motif du signalement",
      reportThanks: "Signalement enregistré localement.",
      reportRecorded: "Signalement enregistré",
      notifBookmarkAdded: "Post ajouté aux signets",
      notifBookmarkRemoved: "Post retiré des signets",
      notifMention: "Vous avez été mentionné",
      notifPermission: "Permission notifications",
      noNotificationApi: "Les notifications ne sont pas disponibles dans ce navigateur.",
      typing: "Un collègue est en train d’écrire…",
      topPostsTitle: "Top 5 posts",
      topCommentsTitle: "Top 5 commentaires récents",
      moderationTitle: "File de modération",
      moderationEmpty: "Aucun signalement en attente.",
      mergeDiscussion: "Fusionner",
      editHistory: "Historique",
    };
  }

  function str(name) {
    var s = ui().strings || {};
    var f = fallbackStrings();
    return s[name] || f[name] || name;
  }

  function parseVisibilityInput(value, currentValue) {
    if (value == null) return currentValue;
    var v = String(value).trim().toLowerCase();
    if (v === "") return currentValue;
    if (
      v === "yes" ||
      v === "y" ||
      v === "oui" ||
      v === "o" ||
      v === "true" ||
      v === "1" ||
      v === "visible" ||
      v === "oui." ||
      v === "نعم"
    ) {
      return true;
    }
    if (
      v === "no" ||
      v === "n" ||
      v === "non" ||
      v === "false" ||
      v === "0" ||
      v === "hidden" ||
      v === "hide" ||
      v === "masqué" ||
      v === "masque" ||
      v === "لا"
    ) {
      return false;
    }
    return currentValue;
  }

  function showFormErr(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || "";
    el.hidden = !msg;
  }

  function isHttpsUrl(value) {
    if (!value) return true;
    try {
      var parsed = new URL(value);
      return parsed.protocol === "https:";
    } catch (e) {
      return false;
    }
  }

  function ensureFieldInlineError(field) {
    if (!field || !field.parentNode) return null;
    var next = field.nextElementSibling;
    if (next && next.classList && next.classList.contains("field-inline-error")) {
      return next;
    }
    var el = document.createElement("small");
    el.className = "field-inline-error";
    el.hidden = true;
    field.insertAdjacentElement("afterend", el);
    return el;
  }

  function showFieldInlineError(field, msg) {
    var box = ensureFieldInlineError(field);
    if (!box) return;
    box.textContent = msg || "";
    box.hidden = !msg;
    if (msg) field.classList.add("is-invalid");
    else field.classList.remove("is-invalid");
  }

  function fieldValidationMessage(field) {
    if (!field) return "Valeur invalide.";
    var v = field.validity || {};
    var name = (field.name || field.id || "").toLowerCase();
    if (v.valueMissing) return "Ce champ est obligatoire.";
    if (v.tooShort || v.tooLong) {
      if (name.indexOf("title") >= 0) return str("validationTitle");
      if (name.indexOf("tag") >= 0) return str("validationTag");
      if (name.indexOf("content") >= 0 || name.indexOf("description") >= 0) {
        return str("validationComment");
      }
      return "La longueur saisie n'est pas valide.";
    }
    if (name.indexOf("image_url") >= 0 && !isHttpsUrl(field.value.trim())) {
      return str("validationUrl");
    }
    if (v.typeMismatch && name.indexOf("image_url") >= 0) return str("validationUrl");
    if (v.patternMismatch) {
      if (name.indexOf("tag") >= 0 || field.id === "favTagsInput") {
        return str("validationTagChars");
      }
      if (name.indexOf("image_url") >= 0) return str("validationUrl");
      return "Le format saisi n'est pas correct.";
    }
    if (field.id === "currentUserId" && (!field.value || Number(field.value) < 1)) {
      return "Saisissez un identifiant utilisateur valide.";
    }
    return "";
  }

  function validateFieldInline(field) {
    if (!field || field.disabled) return true;
    var msg = fieldValidationMessage(field);
    if (msg) {
      showFieldInlineError(field, msg);
      return false;
    }
    showFieldInlineError(field, "");
    return true;
  }

  function validateFormInline(form) {
    if (!form) return true;
    var fields = form.querySelectorAll("input, textarea, select");
    var firstInvalid = null;
    Array.prototype.forEach.call(fields, function (field) {
      var ok = validateFieldInline(field);
      if (!ok && !firstInvalid) firstInvalid = field;
    });
    if (firstInvalid) firstInvalid.focus();
    return !firstInvalid;
  }

  function bindInlineValidation(root) {
    if (!root) return;
    var forms = root.matches && root.matches("form") ? [root] : root.querySelectorAll("form");
    Array.prototype.forEach.call(forms, function (form) {
      form.noValidate = true;
      form.addEventListener(
        "invalid",
        function (ev) {
          ev.preventDefault();
          validateFieldInline(ev.target);
        },
        true
      );
      form.addEventListener(
        "input",
        function (ev) {
          var target = ev.target;
          if (target && /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName)) {
            validateFieldInline(target);
          }
        },
        true
      );
      form.addEventListener(
        "blur",
        function (ev) {
          var target = ev.target;
          if (target && /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName)) {
            validateFieldInline(target);
          }
        },
        true
      );
    });
  }

  function currentMeUserId(me) {
    return me && me.user_id ? me.user_id : null;
  }

  function svgDataUri(svg) {
    return "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg);
  }

  function artworkDataUri(kind, title, accentA, accentB, seed) {
    var label = esc(title || kind || "Card");
    var motif = String((seed || 1) % 9);
    var svg =
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 540" role="img" aria-label="' +
      label +
      '">' +
      '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="' +
      accentA +
      '"/><stop offset="100%" stop-color="' +
      accentB +
      '"/></linearGradient></defs>' +
      '<rect width="960" height="540" fill="url(#g)"/>' +
      '<circle cx="760" cy="112" r="120" fill="rgba(255,255,255,0.18)"/>' +
      '<circle cx="174" cy="430" r="150" fill="rgba(255,255,255,0.12)"/>' +
      '<path d="M0 430 C140 360 260 470 390 430 S650 360 960 455 L960 540 L0 540 Z" fill="rgba(255,255,255,0.18)"/>' +
      '<rect x="64" y="66" width="188" height="28" rx="14" fill="rgba(255,255,255,0.26)"/>' +
      '<rect x="64" y="108" width="270" height="18" rx="9" fill="rgba(255,255,255,0.2)"/>' +
      '<rect x="64" y="344" width="248" height="18" rx="9" fill="rgba(255,255,255,0.22)"/>' +
      '<g fill="rgba(255,255,255,0.92)">' +
      (kind === "weather"
        ? '<circle cx="722" cy="244" r="58"/><circle cx="784" cy="222" r="48"/><circle cx="825" cy="252" r="42"/><rect x="678" y="252" width="190" height="54" rx="27"/>'
        : kind === "chatbot"
        ? '<rect x="678" y="168" width="182" height="166" rx="34"/><circle cx="734" cy="228" r="12" fill="' +
          accentA +
          '"/><circle cx="804" cy="228" r="12" fill="' +
          accentA +
          '"/><rect x="726" y="274" width="88" height="16" rx="8" fill="' +
          accentA +
          '"/><rect x="744" y="132" width="52" height="44" rx="18" fill="rgba(255,255,255,0.82)"/>'
        : kind === "colleague"
        ? '<circle cx="718" cy="222" r="38"/><circle cx="786" cy="206" r="34"/><circle cx="836" cy="236" r="28"/><rect x="666" y="268" width="106" height="74" rx="28"/><rect x="754" y="248" width="98" height="94" rx="30"/>'
        : '<circle cx="780" cy="214" r="72"/><rect x="700" y="290" width="160" height="100" rx="40"/><circle cx="300" cy="230" r="58" fill="rgba(255,255,255,0.16)"/>') +
      "</g>" +
      '<text x="64" y="252" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="54" font-weight="700">' +
      label +
      "</text>" +
      '<text x="64" y="292" fill="rgba(255,255,255,0.92)" font-family="Segoe UI, Arial, sans-serif" font-size="24">HROne community visual ' +
      motif +
      "</text></svg>";
    return svgDataUri(svg);
  }

  function defaultPostImage(post) {
    var tag = post && post.tag ? String(post.tag) : "General";
    var title = post && post.title ? String(post.title) : "Post";
    return artworkDataUri(
      "post",
      tag + " • " + title.slice(0, 20),
      "#2563eb",
      "#22c55e",
      (post && post.id) || 1
    );
  }

  function avatarHtml(url, initials, cls, alt) {
    if (url) {
      return (
        '<img class="' +
        esc(cls) +
        '" src="' +
        esc(url) +
        '" alt="' +
        esc(alt || "") +
        '" />'
      );
    }
    return (
      '<span class="' +
      esc(cls) +
      ' avatar-fallback" aria-hidden="true">' +
      esc(initials || "?") +
      "</span>"
    );
  }

  function syncProfileVisual(photoId, avatarId, url, initials, alt) {
    var photo = document.getElementById(photoId);
    var avatar = document.getElementById(avatarId);
    if (photo) {
      if (url) {
        photo.src = url;
        photo.alt = alt || "";
        photo.hidden = false;
      } else {
        photo.hidden = true;
      }
    }
    if (avatar) {
      avatar.textContent = initials || "?";
      avatar.hidden = !!url;
    }
  }

  function updateFeedIdentity(me) {
    var name = (me && me.name) || "Utilisateur";
    var meta = me && me.user_id ? "ID #" + me.user_id : "";
    var initials = (me && me.initials) || name.slice(0, 1).toUpperCase();
    var avatarUrl = me && me.avatar_url ? me.avatar_url : "";
    var nameEl = document.getElementById("feedProfileName");
    var metaEl = document.getElementById("feedProfileMeta");
    if (nameEl) nameEl.textContent = name;
    if (metaEl) metaEl.textContent = meta;
    syncProfileVisual(
      "feedProfilePhoto",
      "feedProfileAvatar",
      avatarUrl,
      initials,
      name
    );
    syncProfileVisual(
      "composerProfilePhoto",
      "composerProfileAvatar",
      avatarUrl,
      initials,
      name
    );
  }

  function ensureProfileCard(me) {
    var main = document.querySelector(".community-main");
    if (!main || document.getElementById("dynamicProfileCard")) return;
    var sessionCard = document.querySelector(".session-card");
    if (!sessionCard) return;
    var name = (me && me.name) || "Utilisateur";
    var avatarUrl =
      (me && me.avatar_url) ||
      artworkDataUri("profile", name, "#7c3aed", "#2563eb", getUserIdInput());
    var card = document.createElement("section");
    card.className = "card dynamic-profile-card";
    card.id = "dynamicProfileCard";
    card.innerHTML =
      '<div class="dynamic-profile-cover"></div><div class="dynamic-profile-body"><img class="dynamic-profile-photo" src="' +
      esc(avatarUrl) +
      '" alt="' +
      esc(name) +
      '" /><div class="dynamic-profile-copy"><p class="dynamic-profile-kicker">Profil</p><h2 class="dynamic-profile-name">' +
      esc(name) +
      '</h2><p class="dynamic-profile-meta">' +
      esc((me && me.user_id ? "ID #" + me.user_id : "Compte actif") + " · Communauté HROne") +
      "</p></div></div>";
    main.insertBefore(card, sessionCard);
  }

  function ensureWidgetPhoto(cardId, kind, title, colors) {
    var card = document.getElementById(cardId);
    if (!card || card.querySelector(".widget-photo")) return;
    var img = document.createElement("img");
    img.className = "widget-photo";
    img.alt = title;
    img.src = artworkDataUri(kind, title, colors[0], colors[1], getUserIdInput());
    card.insertBefore(img, card.firstChild);
  }

  function ensureFeatureToolbar() {
    var main = document.querySelector(".community-main");
    var filters = document.querySelector(".filters-bar");
    if (!main || !filters || document.getElementById("communityFeatureBar")) return;

    var wrap = document.createElement("section");
    wrap.className = "community-feature-stack";
    wrap.innerHTML =
      '<div class="community-feature-bar card" id="communityFeatureBar" role="toolbar">' +
      '<div class="feature-bar-inner">' +
      '<button type="button" class="feat-btn" id="btnThemeToggle" aria-pressed="false"><span class="feat-ico feat-ico-theme" aria-hidden="true"></span><span class="feat-label">Thème</span></button>' +
      '<button type="button" class="feat-btn" id="btnFontCycle"><span class="feat-ico feat-ico-font" aria-hidden="true"></span><span class="feat-label">Texte</span></button>' +
      '<button type="button" class="feat-btn" id="btnBookmarksToggle"><span class="feat-ico feat-ico-bookmark" aria-hidden="true"></span><span class="feat-label">Signets</span></button>' +
      '<button type="button" class="feat-btn" id="btnDesktopNotif"><span class="feat-ico feat-ico-bell" aria-hidden="true"></span><span class="feat-label">Notifications</span></button>' +
      '<button type="button" class="feat-btn" id="btnNotifHistoryToggle"><span class="feat-ico feat-ico-history" aria-hidden="true"></span><span class="feat-label">Historique</span></button>' +
      "</div>" +
      '<div class="fav-tags-row">' +
      '<label class="fav-tags-label"><span class="feat-ico feat-ico-tag-inline" aria-hidden="true"></span>Tags favoris' +
      '<input type="text" id="favTagsInput" class="fav-tags-input" maxlength="200" placeholder="RH, General…" autocomplete="off" /></label>' +
      '<button type="button" class="btn-secondary btn-fav-apply" id="btnApplyFavTag">Appliquer</button>' +
      "</div></div>" +
      '<div class="card slide-panel" id="bookmarksPanel" hidden><h3 class="slide-panel-title">Signets</h3><p class="muted slide-panel-hint">Les posts enregistrés apparaissent ici.</p><ul class="bookmarks-list" id="bookmarksList"></ul></div>' +
      '<div class="card slide-panel" id="notifHistoryPanel" hidden><h3 class="slide-panel-title">Historique des notifications</h3><ul class="notif-history-list" id="notifHistoryList"></ul></div>';
    main.insertBefore(wrap, filters);
  }

  function ensureInsightsWidgets() {
    var aside = document.querySelector(".community-aside");
    if (!aside) return;
    if (!document.getElementById("topPostsWidget")) {
      var topPosts = document.createElement("div");
      topPosts.className = "card";
      topPosts.id = "topPostsWidget";
      topPosts.innerHTML =
        "<h2 class=\"aside-title\">" +
        esc(str("topPostsTitle")) +
        '</h2><div class="top-ranking-list" id="topPostsList"></div>';
      aside.appendChild(topPosts);
    }
    if (!document.getElementById("topCommentsWidget")) {
      var topComments = document.createElement("div");
      topComments.className = "card";
      topComments.id = "topCommentsWidget";
      topComments.innerHTML =
        "<h2 class=\"aside-title\">" +
        esc(str("topCommentsTitle")) +
        '</h2><div class="top-comments-list" id="topCommentsList"></div>';
      aside.appendChild(topComments);
    }
    if (!document.getElementById("moderationWidget")) {
      var moderation = document.createElement("div");
      moderation.className = "card";
      moderation.id = "moderationWidget";
      moderation.innerHTML =
        "<h2 class=\"aside-title\">" +
        esc(str("moderationTitle")) +
        '</h2><div id="moderationList" class="moderation-list"></div>';
      aside.appendChild(moderation);
    }
  }

  function getBookmarks() {
    try {
      var raw = localStorage.getItem(BOOKMARK_KEY);
      if (!raw) return [];
      var a = JSON.parse(raw);
      return Array.isArray(a) ? a : [];
    } catch (e) {
      return [];
    }
  }

  function saveBookmarks(list) {
    try {
      localStorage.setItem(BOOKMARK_KEY, JSON.stringify(list.slice(0, 50)));
    } catch (e) {}
  }

  function getHiddenUsers() {
    return readStore(HIDDEN_USERS_KEY, []);
  }

  function saveHiddenUsers(list) {
    writeStore(HIDDEN_USERS_KEY, list.slice(0, 100));
  }

  function getPinnedComments() {
    return readStore(PINNED_COMMENTS_KEY, {});
  }

  function setPinnedComment(postId, commentId) {
    var map = getPinnedComments();
    if (commentId) map[String(postId)] = Number(commentId);
    else delete map[String(postId)];
    writeStore(PINNED_COMMENTS_KEY, map);
  }

  function saveReport(entry) {
    var list = readStore(REPORTS_KEY, []);
    list.unshift(entry);
    writeStore(REPORTS_KEY, list.slice(0, 80));
    renderModerationQueue();
  }

  function saveCommentBadge(postId, count) {
    var map = readStore(COMMENT_BADGES_KEY, {});
    map[String(postId)] = count;
    writeStore(COMMENT_BADGES_KEY, map);
  }

  function getCommentBadge(postId) {
    var map = readStore(COMMENT_BADGES_KEY, {});
    return Number(map[String(postId)] || 0);
  }

  function isBookmarked(postId) {
    return getBookmarks().some(function (b) {
      return Number(b.post_id) === Number(postId);
    });
  }

  function toggleBookmark(postId, title) {
    var list = getBookmarks();
    var idn = Number(postId);
    var i = list.findIndex(function (b) {
      return Number(b.post_id) === idn;
    });
    if (i >= 0) list.splice(i, 1);
    else
      list.unshift({
        post_id: idn,
        title: title || "Post #" + idn,
        ts: Date.now(),
      });
    saveBookmarks(list);
  }

  function renderBookmarksPanel() {
    var ul = document.getElementById("bookmarksList");
    if (!ul) return;
    var list = getBookmarks();
    if (!list.length) {
      ul.innerHTML =
        "<li class=\"muted\">" + esc(str("bookmarksEmpty")) + "</li>";
      return;
    }
    ul.innerHTML = list
      .map(function (b) {
        return (
          "<li><a href=\"#post-" +
          esc(b.post_id) +
          "\">" +
          esc(b.title || "Post") +
          "</a></li>"
        );
      })
      .join("");
  }

  function renderNotificationHistory() {
    var listEl = document.getElementById("notifHistoryList");
    if (!listEl) return;
    var list = readStore(NOTIFS_KEY, []);
    if (!list.length) {
      listEl.innerHTML = '<li class="muted">Aucune entrée pour l’instant.</li>';
      return;
    }
    listEl.innerHTML = list
      .map(function (item) {
        return (
          "<li><strong>" +
          esc(item.title || "Notification") +
          "</strong>" +
          (item.body ? "<br/>" + esc(item.body) : "") +
          '<br/><time datetime="' +
          esc(item.ts || "") +
          '">' +
          esc(new Date(item.ts || Date.now()).toLocaleString()) +
          "</time></li>"
        );
      })
      .join("");
  }

  function renderModerationQueue() {
    var box = document.getElementById("moderationList");
    if (!box) return;
    var list = readStore(REPORTS_KEY, []);
    if (!list.length) {
      box.innerHTML = '<p class="muted">' + esc(str("moderationEmpty")) + "</p>";
      return;
    }
    box.innerHTML = list
      .map(function (item, index) {
        return (
          '<div class="moderation-item" data-report-index="' +
          esc(index) +
          '"><div class="moderation-copy"><strong>' +
          esc(item.target || "Élément") +
          "</strong><p>" +
          esc(item.reason || "Sans motif") +
          '</p></div><div class="moderation-actions"><button type="button" class="btn-secondary" data-report-approve="' +
          esc(index) +
          '">Approuver</button><button type="button" class="btn-danger" data-report-reject="' +
          esc(index) +
          '">Rejeter</button></div></div>'
        );
      })
      .join("");
  }

  function formatRichText(raw) {
    if (raw == null || raw === "") return "";
    var s = esc(String(raw));
    s = s.replace(/#([a-zA-ZÀ-ÿ0-9_]+)/g, function (_, tag) {
      return (
        '<a href="#" class="hashtag-link" data-tag="' +
        esc(tag) +
        '">#' +
        esc(tag) +
        "</a>"
      );
    });
    s = s.replace(/@([a-zA-ZÀ-ÿ0-9_]+)/g, function (_, name) {
      return '<span class="mention-chip">@' + esc(name) + "</span>";
    });
    return s.replace(/\n/g, "<br/>");
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

  function recordContentEdit(kind, id, snapshot) {
    var list = readStore(CONTENT_EDIT_KEY, []);
    list.unshift({
      kind: kind,
      id: id,
      ts: new Date().toISOString(),
      snapshot: snapshot,
    });
    writeStore(CONTENT_EDIT_KEY, list.slice(0, 120));
  }

  function topRecentComments() {
    var all = [];
    Object.keys(__communityState.commentsByPost || {}).forEach(function (postId) {
      (__communityState.commentsByPost[postId] || []).forEach(function (comment) {
        all.push(comment);
      });
    });
    return all
      .sort(function (a, b) {
        return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
      })
      .slice(0, 5);
  }

  function renderTopWidgets() {
    ensureInsightsWidgets();
    var postEl = document.getElementById("topPostsList");
    var commentEl = document.getElementById("topCommentsList");
    if (postEl) {
      var posts = (__communityState.posts || [])
        .slice()
        .sort(function (a, b) {
          return Number(b.score || 0) - Number(a.score || 0);
        })
        .slice(0, 5);
      postEl.innerHTML = posts.length
        ? posts
            .map(function (post, index) {
              return (
                '<a class="rank-row" href="#post-' +
                esc(post.id) +
                '"><span class="rank-circle rank-tone-' +
                esc(index % 5) +
                '">' +
                esc(index + 1) +
                '</span><span class="rank-copy"><strong>' +
                esc(post.title || "Post") +
                "</strong><small>Score " +
                esc(post.score != null ? post.score : 0) +
                "</small></span></a>"
              );
            })
            .join("")
        : '<p class="muted">Aucun post chargé.</p>';
    }
    if (commentEl) {
      var comments = topRecentComments();
      commentEl.innerHTML = comments.length
        ? comments
            .map(function (comment, index) {
              return (
                '<div class="rank-row"><span class="rank-circle rank-tone-' +
                esc(index % 5) +
                '">' +
                esc((comment.author_name || "?").slice(0, 1).toUpperCase()) +
                '</span><span class="rank-copy"><strong>' +
                esc(comment.author_name || "Utilisateur") +
                "</strong><small>" +
                esc(String(comment.content || "").slice(0, 90)) +
                "</small></span></div>"
              );
            })
            .join("")
        : '<p class="muted">Aucun commentaire chargé.</p>';
    }
    renderModerationQueue();
  }

  function voteThumbButtons(idAttr, counts, userVote) {
    var up = counts.up != null ? counts.up : 0;
    var down = counts.down != null ? counts.down : 0;
    var su = userVote === "up" ? " is-active" : "";
    var sd = userVote === "down" ? " is-active" : "";
    return (
      '<span class="vote-inline">' +
      '<button type="button" class="btn-vote btn-vote-up' +
      su +
      '" ' +
      idAttr +
      ' data-vt="up" title="' +
      esc(str("voteLike")) +
      '"><span class="vote-thumb" aria-hidden="true">👍</span><span class="vote-count">' +
      esc(up) +
      "</span></button>" +
      '<button type="button" class="btn-vote btn-vote-down' +
      sd +
      '" ' +
      idAttr +
      ' data-vt="down" title="' +
      esc(str("voteDislike")) +
      '"><span class="vote-thumb" aria-hidden="true">👎</span><span class="vote-count">' +
      esc(down) +
      "</span></button></span>"
    );
  }

  function renderCommentsBlock(card, list, postId, me) {
    var listEl = card.querySelector("[data-role=comments]");
    if (!listEl) return;
    var uid = currentMeUserId(me);
    var hiddenUsers = getHiddenUsers();
    var pinnedId = Number(getPinnedComments()[String(postId)] || 0);
    list = (list || []).filter(function (comment) {
      return hiddenUsers.indexOf(Number(comment.user_id)) < 0;
    });

    if (!list || !list.length) {
      listEl.innerHTML = '<p class="post-meta">' + esc(str("noComments")) + "</p>";
      return;
    }

    var sorted = sortCommentsForDisplay(list).sort(function (a, b) {
      if (Number(a.id) === pinnedId) return -1;
      if (Number(b.id) === pinnedId) return 1;
      return 0;
    });
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
          voteThumbButtons(
            'data-vote-comment="' + esc(c.id) + '"',
            { up: c.votes_up, down: c.votes_down },
            c.user_vote
          ) +
          '<span class="score">(' +
          esc(c.score != null ? c.score : 0) +
          ")</span>";
        var editC =
          uid && c.user_id === uid
            ? '<button type="button" class="btn-ghost" data-edit-comment="' +
              esc(c.id) +
              '">' +
              esc(str("edit")) +
              "</button>"
            : "";
        var del =
          uid && c.user_id === uid
            ? '<button type="button" class="btn-ghost" data-del-comment="' +
              esc(c.id) +
              '">' +
              esc(str("delete")) +
              "</button>"
            : "";
        var reply =
          '<button type="button" class="btn-ghost btn-reply" data-reply-to="' +
          esc(c.id) +
          '">' +
          esc(str("reply")) +
          "</button>";
        var pinBtn =
          '<button type="button" class="btn-ghost" data-pin-comment="' +
          esc(c.id) +
          '">' +
          esc(Number(c.id) === pinnedId ? str("unpin") : str("pin")) +
          "</button>";
        var hideBtn =
          uid && c.user_id !== uid
            ? '<button type="button" class="btn-ghost" data-hide-comment-user="' +
              esc(c.user_id) +
              '">' +
              esc(str("hideAuthor")) +
              "</button>"
            : "";
        var reportBtn =
          '<button type="button" class="btn-ghost" data-report-comment="' +
          esc(c.id) +
          '">' +
          esc(str("report")) +
          "</button>";
        return (
          '<div class="comment-line comment-card' +
          (Number(c.id) === pinnedId ? " is-pinned" : "") +
          '" style="margin-left:' +
          pad +
          'px" data-cid="' +
          esc(c.id) +
          '">' +
          (Number(c.id) === pinnedId
            ? '<span class="pin-badge">' + esc(str("pinned")) + "</span>"
            : "") +
          "<strong>" +
          esc(c.author_name || "Utilisateur") +
          "</strong> · #" +
          esc(c.id) +
          " · " +
          esc(c.created_at || "") +
          "<br/>" +
          formatRichText(c.content) +
          '<div class="comment-actions">' +
          voteRow +
          reply +
          pinBtn +
          hideBtn +
          reportBtn +
          editC +
          del +
          "</div></div>"
        );
      })
      .join("");

    listEl.querySelectorAll("[data-edit-comment]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-edit-comment");
        if (!cid) return;
        openFeedEditComment(cid, postId, card, me);
      });
    });

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

    listEl.querySelectorAll("[data-pin-comment]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = Number(btn.getAttribute("data-pin-comment"));
        var currentPinned = Number(getPinnedComments()[String(postId)] || 0);
        setPinnedComment(postId, currentPinned === cid ? null : cid);
        renderCommentsBlock(card, list, postId, me);
      });
    });

    listEl.querySelectorAll("[data-hide-comment-user]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var userId = Number(btn.getAttribute("data-hide-comment-user"));
        if (!userId || !confirm(str("confirmHideUser"))) return;
        var items = getHiddenUsers();
        if (items.indexOf(userId) < 0) items.push(userId);
        saveHiddenUsers(items);
        renderCommentsBlock(card, list, postId, me);
      });
    });

    listEl.querySelectorAll("[data-report-comment]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = Number(btn.getAttribute("data-report-comment"));
        var reason = prompt(str("reportReason"), "");
        saveReport({
          ts: new Date().toISOString(),
          target: "Commentaire #" + cid,
          reason: reason || "Sans motif",
          comment_id: cid,
          post_id: postId,
        });
        pushNotification({
          type: "report",
          title: str("reportRecorded"),
          body: "Commentaire #" + cid,
        });
        alert(str("reportThanks"));
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
          ta.placeholder = str("replyPlaceholder") + pid + "…";
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
        __communityState.commentsByPost[String(postId)] = comments;
        saveCommentBadge(postId, comments.length);
        renderCommentsBlock(card, comments, postId, me);
        renderTopWidgets();
      })
      .catch(function () {
        list.innerHTML =
          '<p class="feed-error">' + esc(str("commentsLoadError")) + "</p>";
      });
  }

  function renderPosts(container, posts, me) {
    container.innerHTML = "";
    __communityState.me = me || null;
    __communityState.posts = posts || [];
    var uid = currentMeUserId(me);
    if (!posts || !posts.length) {
      container.innerHTML =
        '<p class="post-meta">' + esc(str("noPosts")) + "</p>";
      renderTopWidgets();
      return;
    }
    posts.forEach(function (post) {
      var card = document.createElement("article");
      card.className = "post-card social-post-card";
      card.id = "post-" + post.id;
      card.dataset.postId = String(post.id);
      card.dataset.postTitle = post.title || "";

      var author = post.author_name
        ? esc(post.author_name)
        : "user " + esc(post.user_id);
      var tag = post.tag ? " · " + esc(post.tag) : "";
      var when = post.created_at ? esc(post.created_at) : "";
      var desc = post.description ? formatRichText(post.description) : "";
      var authorVisual = avatarHtml(
        post.author_avatar_url,
        post.author_initials,
        "post-author-avatar",
        post.author_name || "Utilisateur"
      );
      var isOwner = uid && post.user_id === uid;
      var bmk = isBookmarked(post.id);
      var knownComments = getCommentBadge(post.id);
      var unreadComments = Math.max(Number(post.comments_count || 0) - knownComments, 0);
      var editBtn = isOwner
        ? '<button type="button" class="btn-ghost" data-action="edit-post">' +
          esc(str("edit")) +
          "</button>"
        : "";
      var delBtn = isOwner
        ? '<button type="button" class="btn-danger" data-action="delete-post">' +
          esc(str("delete")) +
          "</button>"
        : "";
      var voteRow =
        '<div class="vote-row">' +
        voteThumbButtons(
          'data-vote-post="' + esc(post.id) + '"',
          { up: post.votes_up, down: post.votes_down },
          post.user_vote
        ) +
        '<span class="score">' +
        esc(str("scoreLabel")) +
        " " +
        esc(post.score != null ? post.score : 0) +
        '</span><span class="post-meta"> · ' +
        esc(post.comments_count != null ? post.comments_count : 0) +
        " " +
        esc(str("commentsLabel")) +
        "</span></div>";

      var bookmarkBtn =
        '<button type="button" class="btn-ghost btn-bookmark' +
        (bmk ? " is-bookmarked" : "") +
        '" data-bookmark-post="' +
        esc(post.id) +
        '" title="' +
        esc(str("bookmarkTitle")) +
        '"><span class="bookmark-star" aria-hidden="true">' +
        (bmk ? "★" : "☆") +
        "</span></button>";
      var shareBtn =
        '<button type="button" class="btn-ghost" data-share-post="' +
        esc(post.id) +
        '" title="' +
        esc(str("share")) +
        '">' +
        esc(str("share")) +
        "</button>";

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
        voteRow +
        (desc ? '<p class="post-body">' + desc + "</p>" : "") +
        '<div class="post-media"><img class="post-media-image" src="' +
        esc(post.image_url || defaultPostImage(post)) +
        '" alt="' +
        esc(post.title || str("imageLink")) +
        '" /></div>' +
        '<div class="post-actions">' +
        bookmarkBtn +
        shareBtn +
        editBtn +
        delBtn +
        "</div>" +
        '<div class="comments-block">' +
        "<h4>" +
        esc(str("commentsHeading")) +
        (unreadComments
          ? ' <span class="comments-badge">+' + esc(unreadComments) + "</span>"
          : "") +
        "</h4>" +
        '<div class="comments-list" data-role="comments"></div>' +
        '<form class="comment-form" data-role="comment-form">' +
        '<input type="hidden" name="parent_comment_id" value="" />' +
        "<label>" +
        esc(str("newCommentLabel")) +
        '<textarea name="content" required maxlength="8000" minlength="1" title="' +
        esc(str("commentRequired")) +
        '" placeholder="' +
        esc(str("commentPlaceholder")) +
        '"></textarea></label>' +
        '<button type="submit" class="btn-primary">' +
        esc(str("commentSubmit")) +
        "</button>" +
        "</form>" +
        "</div>";

      container.appendChild(card);

      var titleNode = card.querySelector("h3");
      var metaNode = card.querySelector(".post-meta");
      if (titleNode && metaNode) {
        var head = document.createElement("div");
        head.className = "post-head";
        head.innerHTML =
          avatarHtml(
            post.author_avatar_url,
            post.author_initials,
            "post-author-avatar",
            post.author_name || "Utilisateur"
          ) +
          '<div class="post-head-copy"><div class="post-head-top"><strong class="post-author-name">' +
          author +
          '</strong><span class="post-tag-chip">' +
          esc(post.tag || "General") +
          "</span></div></div>";
        metaNode.classList.add("post-meta-inline");
        head.querySelector(".post-head-copy").appendChild(metaNode);
        card.insertBefore(head, titleNode);
        titleNode.classList.add("post-title");
      }

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

      var bbtn = card.querySelector("[data-bookmark-post]");
      if (bbtn) {
        bbtn.addEventListener("click", function () {
          var wasBookmarked = isBookmarked(post.id);
          toggleBookmark(post.id, post.title);
          pushNotification({
            type: "bookmark",
            title: wasBookmarked ? str("notifBookmarkRemoved") : str("notifBookmarkAdded"),
            body: post.title || "Post #" + post.id,
          });
          refreshFeed();
          renderBookmarksPanel();
        });
      }

      var sbtn = card.querySelector("[data-share-post]");
      if (sbtn) {
        sbtn.addEventListener("click", function () {
          openSharePopover(post.id, post.title || "");
        });
      }

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
          openFeedEditPost(post.id);
        });
      }

      var cform = card.querySelector("form[data-role=comment-form]");
      if (cform) {
        bindInlineValidation(cform);
        cform.addEventListener("submit", function (ev) {
          ev.preventDefault();
          if (!validateFormInline(cform)) {
            return;
          }
          var ta = cform.querySelector("textarea[name=content]");
          var content = (ta && ta.value) || "";
          content = content.trim();
          if (!content) return;
          if (content.length > 8000) {
            alert(str("validationComment"));
            return;
          }
          var hid = cform.querySelector('input[name="parent_comment_id"]');
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
              if (ta) ta.placeholder = str("commentPlaceholder");
              if (textMentionsCurrentUser(content)) {
                pushNotification({
                  type: "mention",
                  title: str("notifMention"),
                  body: content.slice(0, 120),
                });
                notifyDesktop(str("notifMention"), content.slice(0, 120));
              }
              return loadCommentsForCard(card, post.id, me);
            })
            .catch(function (e) {
              alert(e.message || String(e));
            });
        });
      }
    });

    renderBookmarksPanel();
    renderTopWidgets();
  }

  var sharePopoverEl = null;

  function ensureSharePopover() {
    if (sharePopoverEl) return sharePopoverEl;
    var el = document.getElementById("sharePopover");
    if (el) {
      sharePopoverEl = el;
    } else {
      el = document.createElement("div");
      el.id = "sharePopover";
      el.className = "share-popover card";
      el.setAttribute("hidden", "");
      el.innerHTML =
        '<p class="share-pop-title"></p>' +
        '<div class="share-pop-actions">' +
        '<button type="button" class="btn-secondary" data-share-act="copy"></button>' +
        '<a class="btn-ghost" data-share-act="twitter" target="_blank" rel="noopener"></a>' +
        '<a class="btn-ghost" data-share-act="linkedin" target="_blank" rel="noopener"></a>' +
        '<button type="button" class="btn-ghost" data-share-act="native"></button>' +
        '<button type="button" class="btn-ghost" data-share-act="close"></button></div>';
      document.body.appendChild(el);
      sharePopoverEl = el;
    }
    el.querySelectorAll("[data-share-act]").forEach(function (node) {
      var act = node.getAttribute("data-share-act");
      if (act === "copy") node.textContent = str("shareCopy");
      else if (act === "twitter") node.textContent = str("shareTwitter");
      else if (act === "linkedin") node.textContent = str("shareLinkedin");
      else if (act === "native") node.textContent = str("shareNative");
      else if (act === "close") node.textContent = str("shareClose");
    });
    if (!el.dataset.shareWired) {
      el.dataset.shareWired = "1";
      el.addEventListener("click", function (ev) {
        var t = ev.target.closest("[data-share-act]");
        if (!t) return;
        var act = t.getAttribute("data-share-act");
        if (act === "copy") {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareState.url).then(function () {
              alert(str("shareCopied"));
            });
          } else {
            prompt(str("shareCopy"), shareState.url);
          }
          ev.preventDefault();
        } else if (act === "native" && navigator.share) {
          ev.preventDefault();
          navigator
            .share({ title: shareState.title, url: shareState.url })
            .catch(function () {});
        } else if (act === "close") {
          ev.preventDefault();
          el.hidden = true;
        }
      });
    }
    return el;
  }

  var shareState = { url: "", title: "" };

  function openSharePopover(postId, title) {
    var base =
      window.location.origin +
      window.location.pathname +
      window.location.search;
    shareState.url = base.split("#")[0] + "#post-" + postId;
    shareState.title = title || "Post";
    var el = ensureSharePopover();
    var pt = el.querySelector(".share-pop-title");
    if (pt) pt.textContent = title || "Post #" + postId;
    el.querySelectorAll("[data-share-act]").forEach(function (node) {
      var act = node.getAttribute("data-share-act");
      if (act === "twitter") {
        node.href =
          "https://twitter.com/intent/tweet?url=" +
          encodeURIComponent(shareState.url) +
          "&text=" +
          encodeURIComponent(shareState.title);
      } else if (act === "linkedin") {
        node.href =
          "https://www.linkedin.com/sharing/share-offsite/?url=" +
          encodeURIComponent(shareState.url);
      }
    });
    el.hidden = false;
  }

  function wireToolbar() {
    var btnB = document.getElementById("btnBookmarksToggle");
    if (btnB) {
      btnB.addEventListener("click", function () {
        renderBookmarksPanel();
        var p = document.getElementById("bookmarksPanel");
        if (!p) return;
        p.hidden = !p.hidden;
      });
    }
    var btnDesktop = document.getElementById("btnDesktopNotif");
    if (btnDesktop) {
      btnDesktop.addEventListener("click", requestDesktopNotifications);
    }
    var btnHistory = document.getElementById("btnNotifHistoryToggle");
    if (btnHistory) {
      btnHistory.addEventListener("click", function () {
        renderNotificationHistory();
        var panel = document.getElementById("notifHistoryPanel");
        if (!panel) return;
        panel.hidden = !panel.hidden;
      });
    }
    var btnFav = document.getElementById("btnApplyFavTag");
    if (btnFav) {
      btnFav.addEventListener("click", function () {
        var inp = document.getElementById("favTagsInput");
        var ft = document.getElementById("filterTag");
        var raw = inp && inp.value ? inp.value : "";
        var first = raw.split(",")[0];
        first = first ? first.trim() : "";
        if (first && ft) {
          ft.value = first;
          try {
            localStorage.setItem(FAV_TAGS_KEY, raw);
          } catch (e) {}
          refreshFeed();
        }
      });
    }
    try {
      var fav = localStorage.getItem(FAV_TAGS_KEY);
      var fi = document.getElementById("favTagsInput");
      if (fav && fi) fi.value = fav;
    } catch (e) {}

    var moderation = document.getElementById("moderationWidget");
    if (moderation && !moderation.dataset.wired) {
      moderation.dataset.wired = "1";
      moderation.addEventListener("click", function (ev) {
        var approve = ev.target.closest("[data-report-approve]");
        var reject = ev.target.closest("[data-report-reject]");
        if (!approve && !reject) return;
        var index = Number((approve || reject).getAttribute(approve ? "data-report-approve" : "data-report-reject"));
        var reports = readStore(REPORTS_KEY, []);
        if (!(index >= 0 && index < reports.length)) return;
        var item = reports[index];
        reports.splice(index, 1);
        writeStore(REPORTS_KEY, reports);
        pushNotification({
          type: approve ? "moderation-approved" : "moderation-rejected",
          title: approve ? "Signalement classé" : "Signalement rejeté",
          body: item && item.target ? item.target : "",
        });
        renderModerationQueue();
      });
    }
  }

  function wireFeedHashtagDelegation() {
    var feed = document.getElementById("postsFeed");
    if (!feed) return;
    feed.addEventListener("click", function (ev) {
      var a = ev.target.closest(".hashtag-link");
      if (!a) return;
      ev.preventDefault();
      var tag = a.getAttribute("data-tag");
      var ft = document.getElementById("filterTag");
      if (tag && ft) {
        ft.value = tag;
        try {
          localStorage.setItem(
            "hro_comm_filters",
            JSON.stringify({
              tag: tag,
              q:
                (document.getElementById("searchTitle") || {}).value || "",
              mine: !!(document.getElementById("filterMine") || {}).checked,
            })
          );
        } catch (e) {}
        refreshFeed();
      }
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

  function saveFeedFilters() {
    writeStore(FILTER_KEY, {
      tag: ((document.getElementById("filterTag") || {}).value || "").trim(),
      q: ((document.getElementById("searchTitle") || {}).value || "").trim(),
      mine: !!((document.getElementById("filterMine") || {}).checked),
    });
  }

  function restoreFeedFilters() {
    var filters = readStore(FILTER_KEY, {});
    var tag = document.getElementById("filterTag");
    var q = document.getElementById("searchTitle");
    var mine = document.getElementById("filterMine");
    if (tag && filters.tag) tag.value = filters.tag;
    if (q && filters.q) q.value = filters.q;
    if (mine) mine.checked = !!filters.mine;
  }

  function loadWeatherWidget() {
    var el = document.getElementById("weatherBody");
    var wrap = document.getElementById("weatherWidget");
    if (!el || !wrap) return;
    ensureWidgetPhoto("weatherWidget", "weather", "Météo", ["#0284c7", "#2563eb"]);
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

  function appendColleagueLine(payload) {
    var log = document.getElementById("colleagueLog");
    if (!log || !payload) return;
    var empty = log.querySelector(".colleague-empty");
    if (empty) empty.remove();
    var selector = payload.id ? '[data-colleague-id="' + payload.id + '"]' : null;
    var div = selector ? log.querySelector(selector) : null;
    if (!div) {
      div = document.createElement("div");
      div.className = "colleague-msg";
      if (payload.id) div.setAttribute("data-colleague-id", payload.id);
    }
    div.innerHTML =
      '<div class="colleague-head"><strong class="colleague-author">' +
      esc(payload.author || "User") +
      '</strong><span class="colleague-time">' +
      esc(payload.time || "") +
      '</span></div><p>' +
      formatRichText(payload.text || "") +
      "</p>" +
      (payload.edited ? '<small class="colleague-edited">(modifié)</small>' : "") +
      (payload.id
        ? '<div class="colleague-msg-actions"><button type="button" class="btn-ghost" data-colleague-edit="' +
          esc(payload.id) +
          '">' +
          esc(str("edit")) +
          '</button><button type="button" class="btn-ghost" data-colleague-history="' +
          esc(payload.id) +
          '">' +
          esc(str("editHistory")) +
          "</button></div>"
        : "");
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  function renderColleagueHistory() {
    var log = document.getElementById("colleagueLog");
    if (!log) return;
    var history = readStore(COLLEAGUE_HISTORY_KEY, []);
    log.innerHTML = "";
    if (!history.length) {
      log.innerHTML =
        '<p class="muted colleague-empty">' + esc(str("colleagueEmpty")) + "</p>";
      return;
    }
    history.forEach(function (item) {
      appendColleagueLine(item);
    });
  }

  function saveColleagueMessage(payload) {
    var history = readStore(COLLEAGUE_HISTORY_KEY, []);
    var idx = history.findIndex(function (item) {
      return item.id === payload.id;
    });
    if (idx >= 0) history[idx] = payload;
    else history.push(payload);
    writeStore(COLLEAGUE_HISTORY_KEY, history.slice(-80));
  }

  function saveColleagueEditHistory(payload) {
    var history = readStore(COLLEAGUE_EDIT_KEY, {});
    var key = String(payload.id || "");
    history[key] = history[key] || [];
    history[key].unshift({
      text: payload.text || "",
      ts: new Date().toISOString(),
    });
    history[key] = history[key].slice(0, 10);
    writeStore(COLLEAGUE_EDIT_KEY, history);
  }

  function showColleagueHistory(messageId) {
    var history = readStore(COLLEAGUE_EDIT_KEY, {});
    var items = history[String(messageId)] || [];
    if (!items.length) {
      alert("Aucun historique pour ce message.");
      return;
    }
    alert(
      items
        .map(function (entry) {
          return new Date(entry.ts).toLocaleString() + "\n" + entry.text;
        })
        .join("\n\n---\n\n")
    );
  }

  function ensureColleagueWidget() {
    var aside = document.querySelector(".community-aside");
    if (!aside || document.getElementById("colleagueWidget")) return;
    var card = document.createElement("div");
    card.className = "card colleague-card";
    card.id = "colleagueWidget";
    card.innerHTML =
      '<h2 class="aside-title">' +
      esc(str("colleagueTitle")) +
      '</h2><p class="form-hint">' +
      esc(str("colleagueHint")) +
      '</p><p class="typing-line" id="colleagueTyping" aria-live="polite"></p><div class="colleague-log" id="colleagueLog" aria-live="polite"><p class="muted colleague-empty">' +
      esc(str("colleagueEmpty")) +
      '</p></div><form id="colleagueForm" class="chat-form colleague-form"><textarea id="colleagueInput" rows="2" maxlength="500" placeholder="' +
      esc(str("colleaguePlaceholder")) +
      '" minlength="1" title="' +
      esc(str("validationComment")) +
      '" required></textarea><button type="submit" class="btn-primary">Envoyer</button></form>';
    aside.appendChild(card);
    ensureWidgetPhoto(
      "colleagueWidget",
      "colleague",
      str("colleagueTitle"),
      ["#0f766e", "#14b8a6"]
    );
  }

  function wireColleagueChat() {
    ensureColleagueWidget();
    var form = document.getElementById("colleagueForm");
    var input = document.getElementById("colleagueInput");
    var typing = document.getElementById("colleagueTyping");
    if (!form || !input) return;
    bindInlineValidation(form);

    var channel = null;
    try {
      if (typeof BroadcastChannel !== "undefined") {
        channel = new BroadcastChannel("hrone-community-colleagues");
      }
    } catch (e) {}

    __communityState.colleagueChannel = channel;
    renderColleagueHistory();

    if (channel) {
      channel.onmessage = function (event) {
        var data = event.data || {};
        if (data.type === "typing") {
          if (typing) typing.textContent = str("typing");
          clearTimeout(__communityState.typingTimer);
          __communityState.typingTimer = setTimeout(function () {
            if (typing) typing.textContent = "";
          }, 1600);
          return;
        }
        if (data.type === "message") {
          saveColleagueMessage(data.payload);
          appendColleagueLine(data.payload);
          if (textMentionsCurrentUser(data.payload.text || "")) {
            pushNotification({
              type: "mention",
              title: str("notifMention"),
              body: data.payload.text || "",
            });
            notifyDesktop(str("notifMention"), data.payload.text || "");
          }
          return;
        }
        if (data.type === "edit") {
          saveColleagueEditHistory(data.previous || {});
          saveColleagueMessage(data.payload);
          appendColleagueLine(data.payload);
        }
      };
    }

    input.addEventListener("input", function () {
      if (channel) {
        channel.postMessage({ type: "typing", user_id: getUserIdInput() });
      }
    });

    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var text = (input.value || "").trim();
      if (!text) return;
      var payload = {
        author: "Collègue #" + getUserIdInput(),
        text: text,
        time: new Date().toLocaleTimeString(),
      };
      payload.id = "msg-" + Date.now() + "-" + Math.random().toString(36).slice(2, 6);
      payload.edited = false;
      saveColleagueMessage(payload);
      appendColleagueLine(payload);
      if (channel) {
        channel.postMessage({ type: "message", payload: payload });
      }
      input.value = "";
      if (typing) typing.textContent = "";
    });

    var log = document.getElementById("colleagueLog");
    if (log && !log.dataset.wired) {
      log.dataset.wired = "1";
      log.addEventListener("click", function (ev) {
        var histBtn = ev.target.closest("[data-colleague-history]");
        if (histBtn) {
          showColleagueHistory(histBtn.getAttribute("data-colleague-history"));
          return;
        }
        var editBtn = ev.target.closest("[data-colleague-edit]");
        if (!editBtn) return;
        var messageId = editBtn.getAttribute("data-colleague-edit");
        var history = readStore(COLLEAGUE_HISTORY_KEY, []);
        var index = history.findIndex(function (item) {
          return item.id === messageId;
        });
        if (index < 0) return;
        var nextText = prompt(str("edit"), history[index].text || "");
        if (nextText == null) return;
        var previous = Object.assign({}, history[index]);
        history[index].text = nextText.trim();
        history[index].edited = true;
        history[index].time = new Date().toLocaleTimeString();
        writeStore(COLLEAGUE_HISTORY_KEY, history);
        saveColleagueEditHistory(previous);
        appendColleagueLine(history[index]);
        if (channel) {
          channel.postMessage({
            type: "edit",
            previous: previous,
            payload: history[index],
          });
        }
      });
    }
  }

  function wireChatbot() {
    var form = document.getElementById("chatForm");
    if (!form) return;
    var chatbotCard = document.querySelector(".chatbot-card");
    if (chatbotCard && !chatbotCard.id) chatbotCard.id = "chatbotWidget";
    ensureWidgetPhoto("chatbotWidget", "chatbot", "Chatbot", ["#7c3aed", "#2563eb"]);
    bindInlineValidation(form);
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      if (!validateFormInline(form)) {
        return;
      }
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
        ensureProfileCard(me);
        updateFeedIdentity(me);
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

  function closeFeedModals() {
    var bd = document.getElementById("feedModalBackdrop");
    var mp = document.getElementById("feedModalPost");
    var mc = document.getElementById("feedModalComment");
    if (bd) bd.hidden = true;
    if (mp) mp.hidden = true;
    if (mc) mc.hidden = true;
  }

  function ensureFeedCrudModals() {
    if (!document.getElementById("feedModalBackdrop")) {
      var backdrop = document.createElement("div");
      backdrop.className = "modal-backdrop";
      backdrop.id = "feedModalBackdrop";
      backdrop.hidden = true;
      backdrop.setAttribute("aria-hidden", "true");
      document.body.appendChild(backdrop);
    }
    if (!document.getElementById("feedModalPost")) {
      var postModal = document.createElement("div");
      postModal.className = "modal-panel";
      postModal.id = "feedModalPost";
      postModal.hidden = true;
      postModal.setAttribute("role", "dialog");
      postModal.setAttribute("aria-labelledby", "feedModalPostTitle");
      postModal.innerHTML =
        '<h3 id="feedModalPostTitle">' +
        esc(str("edit")) +
        '</h3><form id="formFeedEditPost" class="stack-form"><input type="hidden" name="post_id" /><label>' +
        esc(str("promptNewTitle")) +
        '<input type="text" name="title" required minlength="1" maxlength="255" title="' +
        esc(str("validationTitle")) +
        '" /></label><label>' +
        esc(str("promptNewDesc")) +
        '<textarea name="description" rows="3" maxlength="10000" title="' +
        esc(str("validationComment")) +
        '"></textarea></label><label>' +
        esc(str("promptNewTag")) +
        '<input type="text" name="tag" maxlength="80" pattern="^[a-zA-ZÀ-ÿ0-9_\\s\\-]*$" title="' +
        esc(str("validationTagChars")) +
        '" /></label><label>' +
        esc(str("promptNewImage")) +
        '<input type="url" name="image_url" maxlength="2048" pattern="https://.*" title="' +
        esc(str("validationUrl")) +
        '" /></label><label class="field-check"><input type="checkbox" name="is_active" value="1" /> <span>' +
        esc(str("postVisible")) +
        '</span></label><p class="field-error" id="formFeedEditPostError" hidden></p><div class="modal-actions"><button type="button" class="btn-ghost" data-feed-close-modal>' +
        esc(str("modalCancel")) +
        '</button><button type="submit" class="btn-primary">' +
        esc(str("modalSave")) +
        "</button></div></form>";
      document.body.appendChild(postModal);
    }
    if (!document.getElementById("feedModalComment")) {
      var commentModal = document.createElement("div");
      commentModal.className = "modal-panel";
      commentModal.id = "feedModalComment";
      commentModal.hidden = true;
      commentModal.setAttribute("role", "dialog");
      commentModal.setAttribute("aria-labelledby", "feedModalCommentTitle");
      commentModal.innerHTML =
        '<h3 id="feedModalCommentTitle">' +
        esc(str("edit")) +
        '</h3><p class="form-hint" id="feedEditCommentMeta" hidden></p><form id="formFeedEditComment" class="stack-form"><input type="hidden" name="comment_id" /><input type="hidden" name="post_id" /><label>' +
        esc(str("modalContent")) +
        '<textarea name="content" rows="5" required minlength="1" maxlength="8000" title="' +
        esc(str("commentRequired")) +
        '"></textarea></label><label class="field-check"><input type="checkbox" name="is_active" value="1" /> <span>' +
        esc(str("commentVisible")) +
        '</span></label><p class="field-error" id="formFeedEditCommentError" hidden></p><div class="modal-actions"><button type="button" class="btn-ghost" data-feed-close-modal>' +
        esc(str("modalCancel")) +
        '</button><button type="submit" class="btn-primary">' +
        esc(str("modalSave")) +
        "</button></div></form>";
      document.body.appendChild(commentModal);
    }
  }

  function showFeedFormErr(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || "";
    el.hidden = !msg;
  }

  function openFeedEditPost(postId) {
    ensureFeedCrudModals();
    var bd = document.getElementById("feedModalBackdrop");
    var mp = document.getElementById("feedModalPost");
    var f = document.getElementById("formFeedEditPost");
    showFeedFormErr("formFeedEditPostError", "");
    api("/api/posts/" + postId)
      .then(function (d) {
        var p = d && d.post;
        if (!p) throw new Error("Post introuvable.");
        f.elements.post_id.value = String(p.id);
        f.title.value = p.title || "";
        f.description.value = p.description || "";
        f.tag.value = p.tag || "";
        f.image_url.value = p.image_url || "";
        f.is_active.checked = p.is_active !== false;
        bd.hidden = false;
        mp.hidden = false;
      })
      .catch(function (e) {
        alert(e.message || String(e));
      });
  }

  function openFeedEditComment(commentId, postId, card, me) {
    ensureFeedCrudModals();
    var bd = document.getElementById("feedModalBackdrop");
    var mc = document.getElementById("feedModalComment");
    var f = document.getElementById("formFeedEditComment");
    var meta = document.getElementById("feedEditCommentMeta");
    __feedEditCommentCtx = { postId: postId, card: card, me: me };
    showFeedFormErr("formFeedEditCommentError", "");
    api("/api/comments/" + commentId)
      .then(function (d) {
        var c = d && d.comment;
        if (!c) throw new Error("Commentaire introuvable.");
        f.elements.comment_id.value = String(c.id);
        f.elements.post_id.value = String(postId);
        f.content.value = c.content || "";
        f.is_active.checked = c.is_active !== false;
        if (meta) {
          if (c.parent_comment_id) {
            meta.textContent =
              str("commentIsReply") + " #" + c.parent_comment_id;
            meta.hidden = false;
          } else {
            meta.textContent = "";
            meta.hidden = true;
          }
        }
        bd.hidden = false;
        mc.hidden = false;
      })
      .catch(function (e) {
        alert(e.message || String(e));
      });
  }

  function wireFeedCrudModals() {
    ensureFeedCrudModals();
    var bd = document.getElementById("feedModalBackdrop");
    var mp = document.getElementById("feedModalPost");
    var mc = document.getElementById("feedModalComment");
    if (!bd) return;

    document.body.addEventListener("click", function (ev) {
      var t = ev.target;
      if (t && t.getAttribute && t.getAttribute("data-feed-close-modal") != null) {
        closeFeedModals();
      }
    });
    bd.addEventListener("click", function (ev) {
      if (ev.target === bd) closeFeedModals();
    });

    var fp = document.getElementById("formFeedEditPost");
    if (fp) {
      bindInlineValidation(fp);
      fp.addEventListener("submit", function (ev) {
        ev.preventDefault();
        showFeedFormErr("formFeedEditPostError", "");
        if (!validateFormInline(fp)) {
          return;
        }
        var id = parseInt(fp.elements.post_id.value, 10);
        var title = fp.title.value.trim();
        if (!title || title.length > 255) {
          showFeedFormErr("formFeedEditPostError", str("validationTitle"));
          return;
        }
        var tag = fp.tag.value.trim();
        if (tag.length > 80) {
          showFeedFormErr("formFeedEditPostError", str("validationTag"));
          return;
        }
        if (tag && !/^[a-zA-ZÀ-ÿ0-9_\s\-]+$/i.test(tag)) {
          showFeedFormErr("formFeedEditPostError", str("validationTagChars"));
          return;
        }
        var imageUrl = fp.image_url.value.trim();
        if (imageUrl) {
          if (!isHttpsUrl(imageUrl) || imageUrl.length > 2048) {
            showFeedFormErr("formFeedEditPostError", str("validationUrl"));
            return;
          }
        }
        var desc = fp.description.value;
        if (desc.length > 10000) {
          showFeedFormErr("formFeedEditPostError", str("validationComment"));
          return;
        }
        var body = {
          title: title,
          description: desc || null,
          tag: tag || "General",
          image_url: imageUrl || null,
          is_active: fp.is_active.checked,
        };
        recordContentEdit("post", id, {
          title: title,
          description: desc || null,
          tag: tag || "General",
          image_url: imageUrl || null,
          is_active: fp.is_active.checked,
        });
        withCommunitySession(function () {
          return api("/api/posts/" + id, {
            method: "PATCH",
            body: JSON.stringify(body),
          });
        })
          .then(function () {
            closeFeedModals();
            return refreshFeed();
          })
          .catch(function (e) {
            showFeedFormErr("formFeedEditPostError", e.message || String(e));
          });
      });
    }

    var fc = document.getElementById("formFeedEditComment");
    if (fc) {
      bindInlineValidation(fc);
      fc.addEventListener("submit", function (ev) {
        ev.preventDefault();
        showFeedFormErr("formFeedEditCommentError", "");
        if (!validateFormInline(fc)) {
          return;
        }
        var cid = parseInt(fc.elements.comment_id.value, 10);
        var content = fc.content.value.trim();
        if (!content) {
          showFeedFormErr("formFeedEditCommentError", str("commentRequired"));
          return;
        }
        if (content.length > 8000) {
          showFeedFormErr("formFeedEditCommentError", str("validationComment"));
          return;
        }
        var body = {
          content: content,
          is_active: fc.is_active.checked,
        };
        recordContentEdit("comment", cid, {
          content: content,
          is_active: fc.is_active.checked,
        });
        withCommunitySession(function () {
          return api("/api/comments/" + cid, {
            method: "PATCH",
            body: JSON.stringify(body),
          });
        })
          .then(function () {
            closeFeedModals();
            var ctx = __feedEditCommentCtx;
            if (ctx && ctx.card) {
              return loadCommentsForCard(ctx.card, ctx.postId, ctx.me);
            }
            return refreshFeed();
          })
          .catch(function (e) {
            showFeedFormErr(
              "formFeedEditCommentError",
              e.message || String(e)
            );
          });
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadUserId();
    ensureFeatureToolbar();
    ensureInsightsWidgets();
    renderNotificationHistory();
    renderModerationQueue();
    bindInlineValidation(document);

    var uidEl = document.getElementById("currentUserId");
    if (uidEl) {
      uidEl.addEventListener("change", saveUserId);
      uidEl.addEventListener("blur", saveUserId);
    }

    var btnSess = document.getElementById("btnSessionApply");
    if (btnSess) btnSess.addEventListener("click", applySessionFromInput);

    var btnRef = document.getElementById("btnRefreshFeed");
    if (btnRef) btnRef.addEventListener("click", refreshFeed);
    var ft = document.getElementById("filterTag");
    if (ft) ft.addEventListener("change", function () {
      saveFeedFilters();
      refreshFeed();
    });
    var fm = document.getElementById("filterMine");
    if (fm) fm.addEventListener("change", function () {
      saveFeedFilters();
      refreshFeed();
    });
    var st = document.getElementById("searchTitle");
    if (st) {
      st.addEventListener("change", function () {
        saveFeedFilters();
        refreshFeed();
      });
      st.addEventListener("blur", saveFeedFilters);
      st.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          saveFeedFilters();
          refreshFeed();
        }
      });
    }

    loadWeatherWidget();
    wireChatbot();
    wireColleagueChat();
    wireToolbar();
    wireFeedHashtagDelegation();
    wireFeedCrudModals();
    renderBookmarksPanel();

    var form = document.getElementById("formNewPost");
    if (form) {
      bindInlineValidation(form);
      form.addEventListener("submit", function (ev) {
        ev.preventDefault();
        showFormErr("formNewPostError", "");
        if (!validateFormInline(form)) {
          return;
        }
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
        if (tag && !/^[a-zA-ZÀ-ÿ0-9_\s\-]+$/i.test(tag)) {
          showFormErr("formNewPostError", str("validationTagChars"));
          return;
        }
        var imageUrl = (fd.get("image_url") || "").toString().trim();
        if (imageUrl) {
          if (!isHttpsUrl(imageUrl) || imageUrl.length > 2048) {
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

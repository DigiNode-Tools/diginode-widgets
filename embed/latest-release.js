/*!
 * DigiNode Widgets: latest DigiNode Tools release banner.
 * https://github.com/DigiNode-Tools/diginode-widgets  (MIT licence)
 *
 * Add this where you want the banner:
 *   <div class="diginode-latest-release"></div>
 *   <script src="https://cdn.jsdelivr.net/gh/DigiNode-Tools/diginode-widgets@1/embed/latest-release.js" async></script>
 *
 * Options (as attributes on the div):
 *   data-lines="1"           Everything on one line (default: 2 lines)
 *   data-next="no"           Don't show the next release date. (By default it's shown only when there are changes
 *                            waiting to be released - so it never announces a release when there's nothing to release.)
 *   data-logo="yes"/"no"     Show the DigiNode logo on the left (default: yes, except on diginode.tools)
 *   data-label="..."         The label text (default: "Latest release"; data-label="" hides it)
 *   data-link="..."          Where "See what's new" goes (default: the DigiNode Tools release notes)
 *   data-accent="#0066cc"    The accent colour
 *
 * The banner is drawn in its own shadow DOM, so your site's styles can't break it (and it can't affect your site).
 * It uses your site's font and text colour, so it suits light and dark pages. Nothing is shown if the release
 * information can't be loaded.
 */
(function () {
  "use strict";

  var DATA_URL = "https://raw.githubusercontent.com/DigiNode-Tools/diginode-tools/main/docs/latest-release.json";
  var RELEASE_NOTES = "https://diginode.tools/release-notes/";
  var ICON = "https://raw.githubusercontent.com/DigiNode-Tools/diginode-media/main/brand/diginode-icon-96.png";

  var CSS =
    ":host{display:block}" +
    ".b{--a:#0066cc;max-width:760px;margin:1em auto;padding:.7em 1em;border-radius:10px;text-align:center;" +
    "font:inherit;line-height:1.5;color:inherit;border:1px solid rgba(0,102,204,.4);background:rgba(0,102,204,.08);" +
    "border-color:color-mix(in srgb,var(--a) 40%,transparent);background:color-mix(in srgb,var(--a) 8%,transparent)}" +
    ".b.lg{display:flex;align-items:center;gap:.8em;text-align:left}" +
    ".lg img{width:2.6em;height:2.6em;flex:none}" +
    "p{margin:0}" +
    ".l{display:inline-block;margin-right:.35em;padding:.1em .55em;border-radius:999px;background:var(--a);color:#fff;" +
    "font-size:.75em;font-weight:600;letter-spacing:.04em;text-transform:uppercase;vertical-align:.1em}" +
    ".m{opacity:.75}.n{white-space:nowrap}" +
    "a{color:inherit;text-decoration:underline;text-underline-offset:2px;font-weight:600;white-space:nowrap}" +
    ".one p{display:inline}.one .s::before{content:' \\2014  '}";

  function formatDate(ymd) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd || "")) return "";
    try {
      return new Date(ymd + "T12:00:00Z").toLocaleDateString(undefined,
        { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" });
    } catch (e) {
      return ymd;
    }
  }

  // The next release Tuesday from today (first_release_day, then every every_days days)
  function nextReleaseDay(schedule) {
    if (!schedule || !/^\d{4}-\d{2}-\d{2}$/.test(schedule.first_release_day || "") || !(schedule.every_days > 0)) return "";
    var day = 86400000, first = Date.parse(schedule.first_release_day + "T00:00:00Z");
    var now = new Date(), today = Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate());
    var next = first;
    if (today > first) next = first + Math.ceil((today - first) / day / schedule.every_days) * schedule.every_days * day;
    return new Date(next).toISOString().slice(0, 10);
  }

  // Are there release notes waiting to be released? (Notes under "Unreleased" on develop)
  function hasUnreleasedNotes(text) {
    var lines = (text || "").split("\n"), on = false, n = 0;
    for (var i = 0; i < lines.length; i++) {
      if (/^### DigiNode Tools - Unreleased/.test(lines[i])) { on = true; continue; }
      if (on && /^### DigiNode Tools v/.test(lines[i])) break;
      if (on && /^- /.test(lines[i])) n++;
    }
    return n > 0;
  }

  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text) e.textContent = text;
    return e;
  }

  function render(host, d, nextDay) {
    // Only fill empty placeholders (the WordPress plugin's banners use the same class, and already have content)
    if (host.shadowRoot || host.children.length || !d || !/^\d+\.\d+\.\d+$/.test(d.version || "")) return;
    var root = host.attachShadow({ mode: "open" });
    var style = el("style");
    style.textContent = CSS;
    root.appendChild(style);

    var onDigiNodeSite = /(^|\.)diginode\.tools$/.test(location.hostname);
    var logoOpt = host.getAttribute("data-logo");
    var showLogo = logoOpt === "yes" || (logoOpt !== "no" && !onDigiNodeSite);

    var box = el("div", "b" + (showLogo ? " lg" : "") + (host.getAttribute("data-lines") === "1" ? " one" : ""));
    var accent = host.getAttribute("data-accent");
    if (accent && /^#[0-9a-fA-F]{3,8}$/.test(accent)) box.style.setProperty("--a", accent);

    if (showLogo) {
      var img = el("img");
      img.src = ICON;
      img.alt = "DigiNode Tools";
      box.appendChild(img);
    }
    var text = el("div");

    var top = el("p");
    var label = host.hasAttribute("data-label") ? host.getAttribute("data-label") : "Latest release";
    if (label) {
      top.appendChild(el("span", "l", label));
      top.appendChild(document.createTextNode(" "));
    }
    top.appendChild(el("strong", "", "DigiNode Tools v" + d.version));
    var date = formatDate(d.date);
    if (date) top.appendChild(el("span", "m", " · " + date));
    text.appendChild(top);

    var summary = el("p", "s");
    if (d.summary) summary.appendChild(document.createTextNode(d.summary + " "));
    var link = el("a", "", "See what’s new →");
    var href = host.getAttribute("data-link") || RELEASE_NOTES;
    link.href = /^https?:\/\//.test(href) ? href : RELEASE_NOTES;
    summary.appendChild(link);
    if (nextDay && host.getAttribute("data-next") !== "no") {
      summary.appendChild(document.createTextNode(" "));
      summary.appendChild(el("span", "m n", "Next release: " + formatDate(nextDay)));
    }
    text.appendChild(summary);
    box.appendChild(text);
    root.appendChild(box);
  }

  function start() {
    var hosts = document.querySelectorAll(".diginode-latest-release");
    if (!hosts.length || !window.fetch) return;
    var wantNext = false;
    for (var i = 0; i < hosts.length; i++) if (hosts[i].getAttribute("data-next") !== "no") wantNext = true;

    fetch(DATA_URL)
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) return;
        // Only show the next release date if there's something waiting to be released
        var check = (wantNext && d.unreleased_notes_url && d.schedule)
          ? fetch(d.unreleased_notes_url).then(function (r) { return r.ok ? r.text() : ""; })
              .then(function (t) { return hasUnreleasedNotes(t) ? nextReleaseDay(d.schedule) : ""; })
              .catch(function () { return ""; })
          : Promise.resolve("");
        return check.then(function (nextDay) {
          for (var j = 0; j < hosts.length; j++) render(hosts[j], d, nextDay);
        });
      })
      .catch(function () { /* Show nothing if the release information can't be loaded */ });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();

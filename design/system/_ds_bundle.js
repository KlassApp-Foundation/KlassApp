/* @ds-bundle: {"format":4,"namespace":"KlassAppDesignSystem_df5836","components":[{"name":"Button","sourcePath":"components/actions/Button.jsx"},{"name":"GoogleDriveMark","sourcePath":"components/brand/GoogleDriveMark.jsx"},{"name":"SlackMark","sourcePath":"components/brand/SlackMark.jsx"},{"name":"WhatsAppMark","sourcePath":"components/brand/WhatsAppMark.jsx"},{"name":"ICONS","sourcePath":"components/core/Icon.jsx"},{"name":"Icon","sourcePath":"components/core/Icon.jsx"},{"name":"KpiCard","sourcePath":"components/data-display/KpiCard.jsx"},{"name":"Table","sourcePath":"components/data-display/Table.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"FormGroup","sourcePath":"components/forms/FormGroup.jsx"},{"name":"Badge","sourcePath":"components/surfaces/Badge.jsx"},{"name":"Card","sourcePath":"components/surfaces/Card.jsx"}],"sourceHashes":{"components/actions/Button.jsx":"1f711d38fc8c","components/brand/GoogleDriveMark.jsx":"3e572ab24ef1","components/brand/SlackMark.jsx":"22e2c222b3c3","components/brand/WhatsAppMark.jsx":"d4dd34ab2dbf","components/core/Icon.jsx":"cadc7fa3ceb6","components/data-display/KpiCard.jsx":"b260c440c5be","components/data-display/Table.jsx":"2925884a8caa","components/forms/Checkbox.jsx":"b71c969210d6","components/forms/FormGroup.jsx":"57e88e2f0aa1","components/surfaces/Badge.jsx":"a76dfb7cec49","components/surfaces/Card.jsx":"1ab75301fdfd","concepts/admin-mvp/data.js":"b10615874675","concepts/admin-mvp/screens-a.js":"8d35ec220219","concepts/admin-mvp/screens-b.js":"49fddadf8c33","concepts/admin-mvp/screens-c.js":"c968c8b51c53","concepts/admin-mvp/screens-d.js":"70c008690bef","concepts/quickstart/doc-page.js":"f52ae9c02fca","klassapp-handoff-2026-09-29-docs/design-system/concepts/quickstart/doc-page.js":"f52ae9c02fca","klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/deck-stage.js":"f3d3d0a662c0","klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/ds-base.js":"2c57557645f7","klassapp-handoff-2026-10-01-documents/template/doc-page.js":"f52ae9c02fca","klassapp-handoff-2026-10-01-documents/template/ds-base.js":"7464c543d081","klassapp-handoff-2026-10-08-admin-mvp/concept/data.js":"b10615874675","klassapp-handoff-2026-10-08-admin-mvp/concept/screens-a.js":"6ec0a918ef3e","klassapp-handoff-2026-10-08-admin-mvp/concept/screens-b.js":"49fddadf8c33","klassapp-handoff-2026-10-08-admin-mvp/concept/screens-c.js":"c968c8b51c53","klassapp-handoff-2026-10-08-admin-mvp/concept/screens-d.js":"70c008690bef","ui_kits/onboarding-wizard/WizardApp.jsx":"d8c38074461f","ui_kits/onboarding-wizard/WizardSteps.jsx":"56c713039862","ui_kits/school-dashboard/App.jsx":"431b72105531","ui_kits/school-dashboard/DashboardHome.jsx":"f9a515595fc4","ui_kits/school-dashboard/ExamsScreen.jsx":"168e298afe6c","ui_kits/school-dashboard/FeesScreen.jsx":"14efc1873b61","ui_kits/school-dashboard/Sidebar.jsx":"d5e3ba9349fa","ui_kits/school-dashboard/StudentsScreen.jsx":"dedb96ace81c","ui_kits/toshi-assistant/ToshiApp.jsx":"9b70c3f6b06d","ui_kits/toshi-assistant/ToshiPanel.jsx":"a46aa72e95b5"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.KlassAppDesignSystem_df5836 = window.KlassAppDesignSystem_df5836 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/actions/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const VARIANTS = {
  primary: 'ds-btn-primary',
  success: 'ds-btn-primary',
  // RETIRED: white on #22C55E fails AA; the only passing green is primary
  danger: 'ds-btn-danger',
  warning: 'ds-btn-warning',
  outline: 'ds-btn-outline',
  ghost: 'ds-btn-ghost'
};
const SIZES = {
  sm: 'ds-btn-sm',
  md: 'ds-btn-md',
  lg: 'ds-btn-lg'
};
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function Button({
  variant = 'primary',
  size = 'md',
  href,
  type = 'button',
  disabled = false,
  loading = false,
  v2 = false,
  className = '',
  children,
  onClick
}) {
  const classes = cx('ds-btn', VARIANTS[variant] ?? VARIANTS.primary, SIZES[size] ?? SIZES.md, v2 && 'v2', loading && 'is-loading', className);
  const content = loading ? [/*#__PURE__*/React.createElement("span", {
    key: "s",
    className: "ds-btn-spinner",
    "aria-hidden": "true"
  }), /*#__PURE__*/React.createElement("span", {
    key: "l"
  }, children)] : children;
  if (href) {
    return /*#__PURE__*/React.createElement("a", _extends({
      href: href,
      className: classes,
      onClick: onClick,
      "aria-busy": loading || undefined
    }, disabled ? {
      'aria-disabled': true,
      tabIndex: -1
    } : {}), content);
  }
  return /*#__PURE__*/React.createElement("button", {
    type: type,
    className: classes,
    disabled: disabled,
    "aria-busy": loading || undefined,
    onClick: onClick
  }, content);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/actions/Button.jsx", error: String((e && e.message) || e) }); }

// components/brand/GoogleDriveMark.jsx
try { (() => {
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function GoogleDriveMark({
  decorative = true,
  className = '',
  style
}) {
  return /*#__PURE__*/React.createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 87.3 78",
    role: "img",
    focusable: "false",
    "aria-hidden": decorative ? 'true' : 'false',
    className: cx('brand-mark', 'brand-mark--drive', className),
    style: style
  }, decorative ? null : /*#__PURE__*/React.createElement("title", null, "Google Drive"), /*#__PURE__*/React.createElement("path", {
    fill: "#0066da",
    d: "m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#00ac47",
    d: "m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#ea4335",
    d: "m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#00832d",
    d: "m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#2684fc",
    d: "m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#ffba00",
    d: "m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z"
  }));
}
Object.assign(__ds_scope, { GoogleDriveMark });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/brand/GoogleDriveMark.jsx", error: String((e && e.message) || e) }); }

// components/brand/SlackMark.jsx
try { (() => {
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function SlackMark({
  decorative = true,
  className = '',
  style
}) {
  return /*#__PURE__*/React.createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 127 127",
    role: "img",
    focusable: "false",
    "aria-hidden": decorative ? 'true' : 'false',
    className: cx('brand-mark', 'brand-mark--slack', className),
    style: style
  }, decorative ? null : /*#__PURE__*/React.createElement("title", null, "Slack"), /*#__PURE__*/React.createElement("path", {
    fill: "#E01E5A",
    d: "M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#36C5F0",
    d: "M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#2EB67D",
    d: "M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#ECB22E",
    d: "M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z"
  }));
}
Object.assign(__ds_scope, { SlackMark });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/brand/SlackMark.jsx", error: String((e && e.message) || e) }); }

// components/brand/WhatsAppMark.jsx
try { (() => {
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function WhatsAppMark({
  decorative = true,
  className = '',
  style
}) {
  return /*#__PURE__*/React.createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 175.216 175.552",
    role: "img",
    focusable: "false",
    "aria-hidden": decorative ? 'true' : 'false',
    className: cx('brand-mark', 'brand-mark--whatsapp', className),
    style: style
  }, decorative ? null : /*#__PURE__*/React.createElement("title", null, "WhatsApp"), /*#__PURE__*/React.createElement("path", {
    fill: "#25D366",
    d: "M87.184 25.227c-33.733 0-61.166 27.423-61.178 61.13a60.98 60.98 0 0 0 9.349 32.535l1.455 2.313-6.179 22.558 23.146-6.069 2.235 1.324c9.387 5.571 20.15 8.517 31.126 8.523h.023c33.707 0 61.14-27.426 61.153-61.135a60.75 60.75 0 0 0-17.895-43.251 60.75 60.75 0 0 0-43.235-17.928z"
  }), /*#__PURE__*/React.createElement("path", {
    fill: "#fff",
    fillRule: "evenodd",
    d: "M68.772 55.603c-1.378-3.061-2.828-3.123-4.137-3.176l-3.524-.043c-1.226 0-3.218.46-4.902 2.3s-6.435 6.287-6.435 15.332 6.588 17.785 7.506 19.013 12.718 20.381 31.405 27.75c15.529 6.124 18.689 4.906 22.061 4.6s10.877-4.447 12.408-8.74 1.532-7.971 1.073-8.74-1.685-1.226-3.525-2.146-10.877-5.367-12.562-5.981-2.91-.919-4.137.921-4.746 5.979-5.819 7.206-2.144 1.381-3.984.462-7.76-2.861-14.784-9.124c-5.465-4.873-9.154-10.891-10.228-12.73s-.114-2.835.808-3.751c.825-.824 1.838-2.147 2.759-3.22s1.224-1.84 1.836-3.065.307-2.301-.153-3.22-4.032-10.011-5.666-13.647"
  }));
}
Object.assign(__ds_scope, { WhatsAppMark });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/brand/WhatsAppMark.jsx", error: String((e && e.message) || e) }); }

// components/core/Icon.jsx
try { (() => {
/* Heroicons v1 "outline" path data — the icon family the app already uses
   (KpiCard's glyph table is verbatim Heroicons v1). The eight KpiCard keys
   below are copied from the source bundle; the navigation glyphs are the
   matching Heroicons v1 outline members, added so app chrome doesn't need
   hand-drawn SVG. 24×24 viewBox, stroke 2, round caps, currentColor. */
const ICONS = {
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
  classes: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  exam: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  whatsapp: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
  book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  dollar: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  check: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  upload: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
  logout: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
  search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
  plus: 'M12 4v16m8-8H4',
  menu: 'M4 6h16M4 12h16M4 18h16',
  close: 'M6 18L18 6M6 6l12 12',
  chevronDown: 'M19 9l-7 7-7-7',
  chevronLeft: 'M15 19l-7-7 7-7',
  chevronRight: 'M9 5l7 7-7 7',
  tick: 'M5 13l4 4L19 7',
  fallback: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
};
const ALIASES = {
  door: 'classes',
  calendar: 'exam',
  message: 'whatsapp',
  library: 'book',
  notice: 'bell',
  money: 'dollar',
  tasks: 'check'
};
function Icon({
  name = 'fallback',
  size = 20,
  strokeWidth = 2,
  className = '',
  style,
  title
}) {
  const path = ICONS[ALIASES[name] ?? name] ?? ICONS.fallback;
  return /*#__PURE__*/React.createElement("svg", {
    width: size,
    height: size,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: strokeWidth,
    strokeLinecap: "round",
    strokeLinejoin: "round",
    className: className,
    style: style,
    role: "img",
    "aria-hidden": title ? 'false' : 'true',
    focusable: "false"
  }, title ? /*#__PURE__*/React.createElement("title", null, title) : null, /*#__PURE__*/React.createElement("path", {
    d: path
  }));
}
Object.assign(__ds_scope, { ICONS, Icon });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Icon.jsx", error: String((e && e.message) || e) }); }

// components/data-display/KpiCard.jsx
try { (() => {
const COLORS = {
  blue: {
    bg: 'rgba(30,111,217,0.10)',
    text: 'var(--d-blue)'
  },
  green: {
    bg: 'rgba(22,163,74,0.10)',
    text: '#16A34A'
  },
  amber: {
    bg: 'rgba(217,119,6,0.10)',
    text: 'var(--d-amber)'
  },
  red: {
    bg: 'rgba(220,38,38,0.10)',
    text: 'var(--d-red)'
  },
  purple: {
    bg: 'rgba(139,92,246,0.10)',
    text: '#8B5CF6'
  }
};
const ICON_PATHS = {
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
  classes: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  exam: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  whatsapp: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
  book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  dollar: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  check: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
  fallback: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
};
const ICON_ALIASES = {
  door: 'classes',
  calendar: 'exam',
  message: 'whatsapp',
  library: 'book',
  notice: 'bell',
  money: 'dollar',
  tasks: 'check'
};
function KpiCard({
  icon = '',
  value = '\u2014',
  label = '',
  color = 'blue',
  link = ''
}) {
  const tint = COLORS[color] ?? COLORS.blue;
  const pathKey = ICON_ALIASES[icon] ?? icon;
  const path = ICON_PATHS[pathKey] ?? ICON_PATHS.fallback;
  const body = /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    className: "ds-kpi-icon-wrap",
    style: {
      background: tint.bg,
      color: tint.text
    }
  }, /*#__PURE__*/React.createElement("svg", {
    width: "24",
    height: "24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 2,
    viewBox: "0 0 24 24"
  }, /*#__PURE__*/React.createElement("path", {
    d: path
  }))), /*#__PURE__*/React.createElement("p", {
    className: "ds-kpi-value"
  }, value), /*#__PURE__*/React.createElement("p", {
    className: "ds-kpi-label"
  }, label));
  if (link) return /*#__PURE__*/React.createElement("a", {
    href: link,
    className: "ds-kpi-card group"
  }, body);
  return /*#__PURE__*/React.createElement("div", {
    className: "ds-kpi-card group"
  }, body);
}
Object.assign(__ds_scope, { KpiCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data-display/KpiCard.jsx", error: String((e && e.message) || e) }); }

// components/data-display/Table.jsx
try { (() => {
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function Table({
  headers = [],
  density = 'comfortable',
  selectable = false,
  sortable = false,
  cardMobile = true,
  className = '',
  children
}) {
  const classes = cx('ds-table-ledger', density === 'compact' ? 'dt-compact' : 'dt-comfortable', cardMobile ? 'ds-table-card-mobile' : '', className);
  return /*#__PURE__*/React.createElement("div", {
    className: "ds-table-wrap"
  }, /*#__PURE__*/React.createElement("table", {
    className: classes
  }, headers.length > 0 ? /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", null, selectable ? /*#__PURE__*/React.createElement("th", {
    className: "dt-cell-check",
    style: {
      cursor: 'default'
    }
  }, /*#__PURE__*/React.createElement("input", {
    type: "checkbox",
    className: "dt-checkbox",
    id: "select-all"
  })) : null, headers.map(header => /*#__PURE__*/React.createElement("th", {
    key: header
  }, header, sortable ? /*#__PURE__*/React.createElement("span", {
    className: "dt-sort-arrow"
  }, "\u25B4") : null)))) : null, /*#__PURE__*/React.createElement("tbody", null, children)));
}
Object.assign(__ds_scope, { Table });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data-display/Table.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function Checkbox({
  label,
  name,
  value,
  type = 'checkbox',
  checked,
  defaultChecked,
  disabled = false,
  className = '',
  onChange
}) {
  const cls = ['ds-check', disabled && 'is-disabled', className].filter(Boolean).join(' ');
  return /*#__PURE__*/React.createElement("label", {
    className: cls
  }, /*#__PURE__*/React.createElement("input", {
    type: type,
    name: name,
    value: value,
    checked: checked,
    defaultChecked: defaultChecked,
    disabled: disabled,
    onChange: onChange
  }), /*#__PURE__*/React.createElement("span", null, label));
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/FormGroup.jsx
try { (() => {
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function slug(input) {
  return String(input).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}
function FormGroup({
  label,
  name = '',
  type = 'text',
  value,
  error,
  required = false,
  placeholder,
  help,
  options = {},
  className = '',
  children,
  onChange
}) {
  const labelClass = cx('ds-form-label', required ? 'ds-form-label-required' : '');
  const inputClass = cx('ds-form-input', error ? 'ds-form-input-error' : '', type === 'select' ? 'ds-form-select' : '', type === 'textarea' ? 'ds-form-textarea' : '');
  const inputId = name || slug(label ?? '');
  return /*#__PURE__*/React.createElement("div", {
    className: cx('ds-form-group', className)
  }, label ? /*#__PURE__*/React.createElement("label", {
    htmlFor: inputId,
    className: labelClass
  }, label) : null, type === 'select' ? /*#__PURE__*/React.createElement("select", {
    name: name,
    id: inputId,
    className: inputClass,
    required: required,
    defaultValue: value,
    onChange: onChange
  }, placeholder ? /*#__PURE__*/React.createElement("option", {
    value: ""
  }, placeholder) : null, Object.entries(options).map(([key, optionLabel]) => /*#__PURE__*/React.createElement("option", {
    key: key,
    value: key
  }, optionLabel))) : type === 'textarea' ? /*#__PURE__*/React.createElement("textarea", {
    name: name,
    id: inputId,
    className: inputClass,
    placeholder: placeholder,
    required: required,
    defaultValue: value,
    onChange: onChange
  }) : /*#__PURE__*/React.createElement("input", {
    type: type,
    name: name,
    id: inputId,
    className: inputClass,
    placeholder: placeholder,
    required: required,
    defaultValue: value,
    onChange: onChange
  }), error ? /*#__PURE__*/React.createElement("p", {
    className: "ds-form-error"
  }, error) : null, help ? /*#__PURE__*/React.createElement("p", {
    className: "ds-form-help"
  }, help) : null, children);
}
Object.assign(__ds_scope, { FormGroup });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/FormGroup.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/Badge.jsx
try { (() => {
const KNOWN_VARIANTS = ['pending', 'approved', 'rejected', 'paid', 'unpaid', 'active', 'inactive', 'warning', 'info'];
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function Badge({
  variant = 'info',
  size = 'sm',
  className = '',
  children
}) {
  const safeVariant = KNOWN_VARIANTS.includes(variant) ? variant : 'info';
  return /*#__PURE__*/React.createElement("span", {
    className: cx('ds-badge', `ds-badge-${safeVariant}`, `ds-badge-${size}`, className)
  }, children);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/Badge.jsx", error: String((e && e.message) || e) }); }

// components/surfaces/Card.jsx
try { (() => {
const PADDINGS = {
  default: 'ds-card-padding-default',
  sm: 'ds-card-padding-sm',
  none: 'ds-card-padding-none',
  lg: 'ds-card-padding-lg'
};
const SHADOWS = {
  sm: 'ds-card-shadow-sm',
  md: 'ds-card-shadow-md',
  lg: 'ds-card-shadow-lg',
  none: ''
};
function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
function Card({
  padding = 'default',
  shadow = 'sm',
  hover = false,
  title,
  className = '',
  children,
  style
}) {
  const classes = cx('ds-card', PADDINGS[padding] ?? PADDINGS.default, SHADOWS[shadow] ?? SHADOWS.sm, hover ? 'ds-card-hover' : '', className);
  return /*#__PURE__*/React.createElement("div", {
    className: classes,
    style: style
  }, title ? /*#__PURE__*/React.createElement("h3", {
    className: "ds-card-title"
  }, title) : null, children);
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/surfaces/Card.jsx", error: String((e && e.message) || e) }); }

// concepts/admin-mvp/data.js
try { (() => {
// Demo data — Demo Junior School (nursery and primary). Demo only; no real people.
window.KD = (() => {
  const PAL = ['#1E6FD9', '#15803D', '#B45309', '#1E293B'];
  const SCHOOL = {
    name: 'Demo Junior School',
    no: '007',
    currency: 'UGX',
    year: '2026',
    term: 'Term 3'
  }; // currency comes from school settings
  const FIRST = ['Amara', 'Liam', 'Sofia', 'Noah', 'Aisha', 'Mateo', 'Grace', 'Yusuf', 'Priya', 'Ethan', 'Zara', 'Kofi', 'Mei', 'Omar', 'Lucía', 'Daniel', 'Nia', 'Arjun', 'Hana', 'Samuel', 'Leila', 'Tomás', 'Imani', 'Ravi', 'Elena', 'Musa', 'Chloe', 'Ibrahim', 'Ana', 'Joseph', 'Fatima', 'Lucas'];
  const LAST = ['Okafor', 'Chen', 'Haddad', 'Mensah', 'Rahman', 'García', 'Wanjiru', 'Demir', 'Nair', 'Brooks', 'Ali', 'Asante', 'Tanaka', 'Farouk', 'Morales', 'Kim', 'Ndlovu', 'Patel', 'Sato', 'Okello', 'Karimi', 'Silva', 'Mwangi', 'Iyer', 'Petrova', 'Bello', 'Martin', 'Hassan', 'Costa', 'Achieng', 'Yilmaz', 'Rossi'];
  const CLASSES = [{
    id: 'n1',
    name: 'Nursery',
    streams: ['Sunflower'],
    n: 22,
    ct: 't7',
    avg: null,
    att: 95
  }, {
    id: 'rc',
    name: 'Reception',
    streams: ['Sunflower'],
    n: 24,
    ct: 't8',
    avg: null,
    att: 94
  }, {
    id: 'p1',
    name: 'Primary 1',
    streams: ['Blue', 'Red'],
    n: 31,
    ct: 't2',
    avg: 74,
    att: 96
  }, {
    id: 'p2',
    name: 'Primary 2',
    streams: ['Blue', 'Red'],
    n: 29,
    ct: 't3',
    avg: 71,
    att: 93
  }, {
    id: 'p3',
    name: 'Primary 3',
    streams: ['Blue'],
    n: 28,
    ct: 't4',
    avg: 69,
    att: 92
  }, {
    id: 'p4',
    name: 'Primary 4',
    streams: ['Blue'],
    n: 30,
    ct: 't5',
    avg: 66,
    att: 91
  }, {
    id: 'p5',
    name: 'Primary 5',
    streams: ['Blue', 'Red'],
    n: 32,
    ct: 't1',
    avg: 68,
    att: 94
  }, {
    id: 'p6',
    name: 'Primary 6',
    streams: ['Blue'],
    n: 27,
    ct: 't6',
    avg: 63,
    att: 90
  }];
  const T = [{
    id: 't1',
    fn: 'Sarah',
    ln: 'Nakato',
    email: 's.nakato@demojunior.school',
    ph: '+000 700 100 101',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 5 · Blue', 'Mathematics, Science'], ['Primary 6 · Blue', 'Mathematics']],
    ctOf: 'Primary 5 · Blue',
    lessons: 24,
    pendAtt: 0,
    pendMarks: ['Science · Primary 5 Blue'],
    last: '2 hours ago'
  }, {
    id: 't2',
    fn: 'Daniel',
    ln: 'Mensah',
    email: 'd.mensah@demojunior.school',
    ph: '+000 700 100 102',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 1 · Blue', 'English, Literacy']],
    ctOf: 'Primary 1 · Blue',
    lessons: 20,
    pendAtt: 1,
    pendMarks: [],
    last: 'Yesterday'
  }, {
    id: 't3',
    fn: 'Mei',
    ln: 'Tanaka',
    email: 'm.tanaka@demojunior.school',
    ph: '+000 700 100 103',
    role: 'Teacher',
    invite: 'invited',
    cls: [['Primary 2 · Blue', 'English']],
    ctOf: 'Primary 2 · Blue',
    lessons: 18,
    pendAtt: 0,
    pendMarks: ['English · Primary 2 Blue'],
    last: ''
  }, {
    id: 't4',
    fn: 'Omar',
    ln: 'Farouk',
    email: '',
    ph: '+000 700 100 104',
    role: 'Teacher',
    invite: 'none',
    cls: [['Primary 3 · Blue', 'Social Studies']],
    ctOf: 'Primary 3 · Blue',
    lessons: 16,
    pendAtt: 1,
    pendMarks: [],
    last: ''
  }, {
    id: 't5',
    fn: 'Elena',
    ln: 'Petrova',
    email: 'e.petrova@demojunior.school',
    ph: '+000 700 100 105',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 4 · Blue', 'Mathematics']],
    ctOf: 'Primary 4 · Blue',
    lessons: 22,
    pendAtt: 0,
    pendMarks: [],
    last: '3 days ago'
  }, {
    id: 't6',
    fn: 'Samuel',
    ln: 'Asante',
    email: 's.asante@demojunior.school',
    ph: '+000 700 100 106',
    role: 'Head teacher',
    invite: 'accepted',
    cls: [['Primary 6 · Blue', 'English']],
    ctOf: 'Primary 6 · Blue',
    lessons: 10,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }, {
    id: 't7',
    fn: 'Hana',
    ln: 'Sato',
    email: 'h.sato@demojunior.school',
    ph: '+000 700 100 107',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Nursery · Sunflower', 'All areas']],
    ctOf: 'Nursery · Sunflower',
    lessons: 25,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }, {
    id: 't8',
    fn: 'Ravi',
    ln: 'Iyer',
    email: 'r.iyer@demojunior.school',
    ph: '+000 700 100 108',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Reception · Sunflower', 'All areas']],
    ctOf: 'Reception · Sunflower',
    lessons: 25,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }].map((t, i) => ({
    ...t,
    n: i + 11,
    status: 'active'
  }));
  const S = Array.from({
    length: 32
  }, (_, i) => {
    const fn = FIRST[i],
      ln = LAST[i * 7 % 32],
      g = i % 2 ? 'Male' : 'Female';
    const bal = [0, 180000, 0, 95000, 0, 0, 240000, 0][i % 8];
    return {
      id: 1040 + i,
      fn,
      ln,
      sex: i === 9 ? '' : g,
      kls: 'KLS007' + String(1 + i).padStart(4, '0'),
      cls: i === 5 ? '' : 'Primary 5 · ' + (i % 3 ? 'Blue' : 'Red'),
      status: i === 12 ? 'inactive' : 'active',
      parent: i === 7 ? null : {
        fn: ['Grace', 'Peter', 'Ana', 'Musa'][i % 4],
        ln,
        ph: '+000 772 418 2' + String(10 + i).padStart(2, '0')
      },
      att: 88 + i * 3 % 12,
      avg: 58 + i * 11 % 34,
      bal,
      dob: '14 Mar 2015'
    };
  });
  S[0] = {
    ...S[0],
    fn: 'Amara',
    ln: 'Okafor',
    avg: 74,
    att: 91,
    bal: 180000,
    pos: 6
  };
  const P = [{
    id: 2210,
    fn: 'Grace',
    ln: 'Okafor',
    ph: '+000 772 418 205',
    email: 'grace.okafor@example.com',
    wa: 'in',
    waDate: '12 Sep 2026',
    last: 'Today, 07:42',
    kids: [S[0], {
      id: 1090,
      fn: 'Tobi',
      ln: 'Okafor',
      kls: 'KLS0070033',
      cls: 'Primary 2 · Blue',
      bal: 0,
      att: 97,
      avg: 81
    }]
  }, {
    id: 2211,
    fn: 'Peter',
    ln: 'Chen',
    ph: '+000 701 552 930',
    email: '',
    wa: 'none',
    last: 'Never',
    kids: [S[1]]
  }, {
    id: 2212,
    fn: 'Ana',
    ln: 'Haddad',
    ph: '+000 755 003 118',
    email: 'ana.h@example.com',
    wa: 'in',
    waDate: '2 Oct 2026',
    last: '3 days ago',
    kids: [S[2]]
  }, {
    id: 2213,
    fn: 'Musa',
    ln: 'Mensah',
    ph: '+000 782 660 471',
    email: '',
    wa: 'pending',
    last: 'Never',
    kids: [S[3], S[11]]
  }];
  const ini = (f, l) => {
    f = (f || '').trim().split(/\s+/)[0] || '';
    l = (l || '').trim();
    return (([...f][0] || '') + ([...l][0] || '')).toLocaleUpperCase();
  };
  const av = (p, s = 40) => {
    const i = ini(p.fn, p.ln);
    const r = s <= 32 ? 8 : 12;
    return `<span class="av" style="width:${s}px;height:${s}px;font-size:${Math.round(s * .4)}px;border-radius:${r}px;background:${i ? PAL[p.id ? (typeof p.id === 'number' ? p.id : p.n || 0) % 4 : 0] : '#64748B'}" aria-hidden="true">${i}</span>`;
  };
  const money = v => SCHOOL.currency + ' ' + Number(v).toLocaleString('en');
  const grade = a => a >= 80 ? 'A' : a >= 70 ? 'B' : a >= 60 ? 'C' : a >= 50 ? 'D' : 'E';
  const ic = (n, c = 'ic') => `<i data-lucide="${n}" class="${c}" aria-hidden="true"></i>`;
  return {
    SCHOOL,
    CLASSES,
    T,
    S,
    P,
    av,
    ini,
    money,
    grade,
    ic,
    PAL
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/admin-mvp/data.js", error: String((e && e.message) || e) }); }

// concepts/admin-mvp/screens-a.js
try { (() => {
// Shell, dashboard, people list, classes — admin MVP concept
(() => {
  const {
    SCHOOL,
    CLASSES,
    T,
    S,
    P,
    av,
    money,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  // Groups follow dashboard v2 (handoff-2026-09-30-profiles Part B)
  const NAV = [['', [['dashboard', 'layout-dashboard', 'Dashboard']]], ['People', [['students', 'graduation-cap', 'Students'], ['teachers', 'presentation', 'Teachers and staff'], ['parents', 'users', 'Parents']]], ['Academics', [['classes', 'school', 'Classes and streams'], ['subjects', 'book-open', 'Subjects'], ['attendance', 'calendar-check', 'Attendance'], ['exams', 'clipboard-list', 'Exams and marks'], ['reports', 'file-text', 'Report cards']]], ['Money', [['fees', 'wallet', 'Fees']]], ['Messages', [['messages', 'message-circle', 'WhatsApp']]], ['School', [['settings', 'settings', 'Settings'], ['help', 'life-buoy', 'Help']]]];
  const ME = {
    id: 3300,
    fn: 'Mucunguzi',
    ln: 'Moses',
    email: 'mucunguzi.moses.admin@demojunior.school'
  };
  function acctPop(mob) {
    return `<div class="pop" role="menu" aria-label="Account" id="acct-pop">
  <div class="who">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span></div>
  <a class="mi" role="menuitem" href="#" data-go="me">${ic('user-round')}Edit profile</a>
  <a class="mi" role="menuitem" href="#">${ic('key-round')}Change password</a>
  <a class="mi" role="menuitem" href="#">${ic('settings')}Settings</a>
  <div class="sep" role="separator"></div>
  <button class="mi" role="menuitem" type="button">${ic('log-out')}Log out</button></div>`;
  }
  window.shell = (active, title, body) => {
    const nav = NAV.map(([g, items]) => `${g ? `<div class="grp">${g}</div>` : ''}<ul class="nav">${items.map(([k, i, l]) => `<li><a href="#" data-go="${k}" ${k === active ? 'aria-current="page"' : ''}>${ic(i)}${l}</a></li>`).join('')}</ul>`).join('');
    return `<div class="shell">
  <aside class="side ${ST.drawer ? 'open' : ''}" aria-label="Main menu" id="side">
    <div class="brand"><img src="../../assets/brand/klassapp-horizontal-light.svg" alt="KlassApp"></div>
    <nav aria-label="Main">${nav}</nav>
    <div class="grow"></div>
    ${ST.hideSetup && ST.state !== 'data' ? `<a class="schip" href="#" aria-label="Finish setup, ${ST.state === 'new' ? 1 : 4} of 7 steps done"><span><b>Finish setup</b><span class="pill">${ST.state === 'new' ? 1 : 4}/7</span></span><small>Next: ${ST.state === 'new' ? 'Add your students' : 'Set up fees for Term 3'}</small><span class="bar"><i style="width:${(ST.state === 'new' ? 1 : 4) / 7 * 100}%"></i></span></a>` : ''}
    <div class="soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant · coming soon</div>
    <div class="acct">${ST.acct && !ST.mob ? acctPop() : ''}
      <button class="acct-btn" type="button" data-act="acct" aria-haspopup="menu" aria-expanded="${ST.acct && !ST.mob}" aria-controls="acct-pop">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span>${ic('chevrons-up-down', 'ic ic-sm')}</button></div>
  </aside>
  <div class="mainw">
    <header class="topbar"><button class="btn icon ghost" type="button" data-act="drawer" aria-label="Open menu" aria-expanded="${ST.drawer}" aria-controls="side">${ic('menu')}</button><span class="t">${title}</span>
      <div class="acct"><button class="btn icon ghost" type="button" data-act="acct" aria-label="Account" aria-haspopup="menu" aria-expanded="${ST.acct && ST.mob}">${av(ME, 32)}</button>${ST.acct && ST.mob ? acctPop(1) : ''}</div></header>
    <div class="panel"><main class="page" id="main">${body}</main></div>
  </div></div>${ST.dlg ? dialog(ST.dlg) : ''}`;
  };
  function dialog(d) {
    return `<div class="dlg-bg" data-act="dlg-x"><div class="dlg" role="alertdialog" aria-modal="true" aria-labelledby="dlg-t" aria-describedby="dlg-d" data-stop>
  <h2 id="dlg-t">${d.t}</h2><p id="dlg-d">${d.p}</p><div class="row-acts"><button class="btn" type="button" data-act="dlg-x">Cancel</button><button class="btn danger" type="button" data-act="dlg-x">${d.b}</button></div></div></div>`;
  }
  const ayPick = () => `<label class="ay">Academic year <select class="sel" aria-label="Academic year"><option selected>2026</option><option>2025</option></select></label>`;
  /* ---------- 1. Dashboard ---------- */
  const bars = (rows, max = 100) => `<div class="hbars">${rows.map(r => `<div class="hb"><span>${r[0]}</span><span class="t" role="img" aria-label="${r[0]}: ${r[1]}%"><i style="width:${r[1] / max * 100}%;${r[2] ? 'background:' + r[2] : ''}"></i></span><b>${r[1]}%</b></div>`).join('')}</div>`;
  function line(pts, w = 520, h = 160) {
    const mn = 80,
      mx = 100,
      x = i => 36 + i * (w - 48) / (pts.length - 1),
      y = v => 12 + (mx - v) / (mx - mn) * (h - 40);
    const d = pts.map((p, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(p[1]).toFixed(1)).join(' ');
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Attendance trend: ${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">
  ${[80, 90, 100].map(v => `<line x1="36" x2="${w - 12}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="30" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}
  <path d="${d}" fill="none" stroke="#15803D" stroke-width="2.5"/>${pts.map((p, i) => `<circle cx="${x(i)}" cy="${y(p[1])}" r="3.5" fill="#15803D"/><text x="${x(i)}" y="${h - 8}" text-anchor="middle" font-size="11" fill="#475569">${p[0]}</text>`).join('')}</svg>`;
  }
  function cols(vals, exp, w = 520, h = 170) {
    const mx = Math.max(...exp),
      bw = (w - 60) / vals.length,
      y = v => h - 34 - v / mx * (h - 50);
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Fees collected by month: ${vals.map(v => v[0] + ' ' + money(v[1])).join(', ')}">
  ${vals.map((v, i) => {
      const x = 40 + i * bw;
      return `<rect x="${x + 8}" y="${y(exp[i])}" width="${bw - 16}" height="${h - 34 - y(exp[i])}" fill="none" stroke="#94A3B8" stroke-dasharray="4 3" rx="4"/><rect x="${x + 8}" y="${y(v[1])}" width="${bw - 16}" height="${h - 34 - y(v[1])}" fill="#15803D" rx="4"/><text x="${x + bw / 2}" y="${h - 14}" text-anchor="middle" font-size="11" fill="#475569">${v[0]}</text><text x="${x + bw / 2}" y="${y(v[1]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${Math.round(v[1] / 1e6 * 10) / 10}M</text>`;
    }).join('')}</svg>`;
  }
  function kpi(icon, l, v, s, cls = '', meter) {
    return `<div class="kpi"><span class="l">${ic(icon, 'ic ic-sm')}${l}</span><span class="v">${v}</span>${meter != null ? `<span class="meter" role="img" aria-label="${meter}%"><i style="width:${meter}%"></i></span>` : ''}<span class="s ${cls}">${s}</span></div>`;
  }
  const gender = (g, b, u) => `<div class="stack" role="img" aria-label="Girls ${g}%, boys ${b}%, not specified ${u}%"><i style="width:${g}%;background:#B45309"></i><i style="width:${b}%;background:#1E6FD9"></i><i style="width:${u}%;background:#64748B"></i></div><div class="legend"><span><i style="background:#B45309"></i>Girls ${g}%</span><span><i style="background:#1E6FD9"></i>Boys ${b}%</span><span><i style="background:#64748B"></i>Not specified ${u}%</span></div>`;
  window.scrDashboard = () => {
    const st = ST.state;
    const steps = st === 'new' ? 1 : st === 'mid' ? 4 : 7;
    const next = st === 'new' ? 'Add your students' : 'Set up fees for Term 3';
    const setup = steps < 7 && !ST.hideSetup ? `<div class="setup" role="region" aria-label="School setup">${ic('list-checks')}<b>Setup ${steps} of 7 done</b><span class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="7" aria-valuenow="${steps}" aria-label="Setup progress"><i style="width:${steps / 7 * 100}%"></i></span><span class="nx">Next: ${next}</span><a class="btn pri" href="#">Continue setup</a><button class="btn icon ghost" type="button" data-act="hideSetup" aria-label="Hide setup bar. Progress stays in the sidebar.">${ic('x')}</button></div>` : '';
    const QA = [['user-plus', 'Add students', 'One by one or from a spreadsheet', ''], ['clipboard-list', 'Enter marks', st === 'new' ? 'Needs students and an exam' : 'Mid-term exams are open', st === 'new'], ['file-text', 'Generate report cards', st === 'data' ? '140 ready to generate' : 'Needs marks for an exam', st !== 'data'], ['message-circle', 'Send report cards on WhatsApp', st === 'data' ? '142 sent last term' : 'Needs report cards', st !== 'data'], ['wallet', 'Fees', st === 'data' ? 'Record payments and send reminders' : 'Set up Term 3 fees first', st !== 'data']];
    const qaRow = `<nav class="qa" aria-label="Quick actions">${QA.map(q => `<a class="btn" href="#">${ic(q[0])}${q[1]}</a>`).join('')}</nav>`;
    const qaTiles = `<section aria-label="Quick actions"><h2 class="sh">Quick actions</h2><div class="qat">${QA.map(q => `<a class="qt" href="#"><span class="ib">${ic(q[0])}</span><span><b>${q[1]}</b><span class="${q[3] ? 'pre' : ''}">${q[2]}</span></span></a>`).join('')}</div></section>`;
    const head = `<div class="ph"><div><h1>${greet()}, ${ME.fn}</h1><p>${SCHOOL.name} · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts">${ayPick()}</div></div>`;
    if (st === 'new') return head + setup + qaTiles + `<div class="kpis">${kpi('graduation-cap', 'Students', '0', 'None added yet')}${kpi('presentation', 'Staff', '1', 'Just you')}${kpi('calendar-check', 'Attendance this week', '–', 'Starts after students are added')}${kpi('wallet', 'Fees collected', '–', 'No fee structure yet')}${kpi('file-text', 'Report cards ready', '–', 'After the first exam')}</div>
  <div class="empty"><b>Add your students to get started</b><p>Your dashboard fills in as you add students, take attendance and enter marks. You can add students one by one or import a spreadsheet.</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}Add student</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>
  <p class="toshi-soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
    const mid = st === 'mid';
    return head + setup + (mid ? '' : qaRow) + `<div class="kpis">
  ${kpi('graduation-cap', 'Students', '248', '+12 this term', 'up')}${kpi('presentation', 'Staff', '18', '16 teachers · 2 admin')}
  ${kpi('calendar-check', 'Attendance this week', '93.4%', '▲ 1.2 pts on last week', 'up')}
  ${mid ? kpi('wallet', 'Fees collected', '–', 'Set up Term 3 fees first', 'warn') : kpi('wallet', 'Fees collected', '62%', money(46500000) + ' of ' + money(75000000), '', 62)}
  ${kpi('file-text', 'Report cards ready', mid ? '0' : '140', mid ? 'No exam closed yet' : 'of 248 · Mid-term exams', mid ? '' : '', mid ? null : 56)}</div>
  ${mid ? qaTiles : ''}<div class="grid2">
   <div class="card"><div class="hd"><h2>Performance by class</h2><small>${mid ? 'No exam yet' : 'Mid-term exams · average mark'}</small></div>${mid ? `<div class="empty in"><b>No marks entered yet</b><p>Averages appear when teachers enter marks for an exam.</p><a class="btn" href="#">Go to exams</a></div>` : bars(CLASSES.filter(c => c.avg).map(c => [c.name, c.avg]))}</div>
   <div class="card"><div class="hd"><h2>Attendance trend</h2><small>Last 8 weeks · whole school</small></div>${line([['W1', 91], ['W2', 92], ['W3', 90], ['W4', 93], ['W5', 94], ['W6', 92], ['W7', 92.2], ['W8', 93.4]])}</div>
   <div class="card"><div class="hd"><h2>Students by gender</h2><small>248 students</small></div>${gender(51, 48, 1)}</div>
   <div class="card"><div class="hd"><h2>Fees collection</h2><small>Collected against expected, by month</small></div>${mid ? `<div class="empty in"><b>No fee structure for Term 3</b><p>Set the term's fees to start recording payments.</p><a class="btn pri" href="#">Set up fees</a></div>` : cols([['Sep', 24100000], ['Oct', 14900000], ['Nov', 7500000]], [30000000, 25000000, 20000000]) + `<div class="legend"><span><i style="background:#15803D"></i>Collected</span><span><i style="border:1px dashed #94A3B8"></i>Expected</span></div>`}</div>
  </div>
  <div class="card"><div class="hd"><h2>Recent activity</h2><a href="#">See all</a></div><ul class="act">
   <li><span class="ib">${ic('calendar-check', 'ic ic-sm')}</span><span>Sarah Nakato took attendance for <b>Primary 5 · Blue</b> (30 of 32 present)</span><time>08:12</time></li>
   <li><span class="ib">${ic('wallet', 'ic ic-sm')}</span><span>Payment of ${money(270000)} recorded for <b>Amara Okafor</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('clipboard-list', 'ic ic-sm')}</span><span>Mathematics marks entered for <b>Primary 6 · Blue</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('message-circle', 'ic ic-sm')}</span><span>142 report cards sent to parents on WhatsApp</span><time>Mon</time></li>
   <li><span class="ib">${ic('user-plus', 'ic ic-sm')}</span><span>3 students added to <b>Reception · Sunflower</b></span><time>Mon</time></li></ul></div>
  <p class="toshi-soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
  };
  function greet() {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  }
  /* ---------- 2. People list (students / teachers / parents) ---------- */
  const CFG = {
    students: {
      t: 'Students',
      one: 'student',
      add: 'Add student',
      rows: () => S.slice(0, 10),
      chips: [['all', 'All', 248], ['cls', 'Class: All', null, 'chevron-down'], ['active', 'Active', 241], ['inactive', 'Inactive', 7], ['nocls', 'No class', 3], ['nopar', 'No parent', 5]],
      cols: ['Student', 'KLS number', 'Class', 'Parent or guardian', 'Status'],
      cell: s => [`<span class="who">${av(s, 36)}<span style="min-width:0"><a href="#" data-go="student">${s.fn} ${s.ln}</a></span></span>`, `<span class="mono">${s.kls}</span>`, s.cls || '<span class="badge b-warn">No class</span>', s.parent ? `${s.parent.fn} ${s.parent.ln}` : '<span class="badge b-warn">No parent</span>', st(s.status)],
      card: s => [`${s.fn} ${s.ln}`, `<span class="mono">${s.kls}</span><span>${s.cls || '<span class="badge b-warn">No class</span>'}</span>${s.status !== 'active' ? st(s.status) : ''}`],
      menu: ['View profile', 'Edit', 'Move to class', 'Message parent'],
      bulk: ['Message parents', 'Move to class', 'Export'],
      go: 'student'
    },
    teachers: {
      t: 'Teachers',
      one: 'teacher',
      add: 'Add teacher',
      rows: () => T,
      chips: [['all', 'All', 18], ['active', 'Active', 17], ['inv', 'Not yet invited', 1], ['pend', 'Invite pending', 1], ['ct', 'Class teachers', 8]],
      cols: ['Teacher', 'Teaches', 'Class teacher of', 'Invite', 'Status'],
      cell: t => [`<span class="who">${av(t, 36)}<span style="min-width:0"><a href="#" data-go="teacher">${t.fn} ${t.ln}</a><span class="sub trunc">${t.role}</span></span></span>`, t.cls.map(c => c[1]).join(', '), t.ctOf || '–', inv(t.invite), st(t.status)],
      card: t => [`${t.fn} ${t.ln}`, `<span>${t.ctOf ? 'Class teacher · ' + t.ctOf : t.role}</span>${t.invite !== 'accepted' ? inv(t.invite) : ''}`],
      menu: ['View profile', 'Edit', 'Send invite', 'Assign classes'],
      bulk: ['Send invites', 'Export'],
      go: 'teacher'
    },
    parents: {
      t: 'Parents',
      one: 'parent',
      add: 'Add parent',
      rows: () => P.concat(P.map(p => ({
        ...p,
        id: p.id + 10,
        fn: p.fn === 'Grace' ? 'Joy' : p.fn === 'Peter' ? 'Ruth' : p.fn === 'Ana' ? 'Ade' : 'Lina'
      }))),
      chips: [['all', 'All', 211], ['wa', 'On WhatsApp', 188], ['nowa', 'Not opted in', 23], ['never', 'Never logged in', 41]],
      cols: ['Parent or guardian', 'Children', 'Phone', 'WhatsApp', 'Last login'],
      cell: p => [`<span class="who">${av(p, 36)}<span style="min-width:0"><a href="#" data-go="parent">${p.fn} ${p.ln}</a></span></span>`, p.kids.map(k => k.fn).join(', '), `<span class="mono">${p.ph}</span>`, wa(p.wa), p.last],
      card: p => [`${p.fn} ${p.ln}`, `<span>${p.kids.map(k => k.fn).join(', ')}</span>${wa(p.wa)}`],
      menu: ['View profile', 'Edit', 'Link a child', 'Send WhatsApp opt-in'],
      bulk: ['Send WhatsApp opt-in', 'Export'],
      go: 'parent'
    }
  };
  const st = s => s === 'active' ? '<span class="badge b-ok">Active</span>' : '<span class="badge b-off">Inactive</span>';
  const inv = s => s === 'accepted' ? '<span class="badge b-ok">Joined</span>' : s === 'invited' ? '<span class="badge b-info">Invited</span>' : '<span class="badge b-warn">Not invited</span>';
  const wa = s => s === 'in' ? '<span class="badge b-ok">Opted in</span>' : s === 'pending' ? '<span class="badge b-info">Asked</span>' : '<span class="badge b-off">Not opted in</span>';
  window.peopleList = (kind, opts = {}) => {
    const c = CFG[kind],
      rows = opts.rows || c.rows(),
      state = opts.state || ST.state;
    const chipList = opts.chips || c.chips.filter(x => !(opts.inClass && x[0] === 'cls'));
    const tools = `<div class="lt"><label class="search"><span class="sr">Search ${c.t.toLowerCase()}</span>${ic('search')}<input type="search" placeholder="Search by name${kind === 'students' ? ', KLS number' : kind === 'parents' ? ', phone' : ', email'}"></label>
   <div class="chips" role="group" aria-label="Filters">${chipList.map((x, i) => `<button class="chip" type="button" aria-pressed="${i === 0}">${x[1]}${x[2] != null ? ` <span class="n">${x[2]}</span>` : ''}${x[3] ? ic(x[3], 'ic ic-sm') : ''}</button>`).join('')}</div></div>`;
    const sel = ST.sel.size;
    const bulk = sel ? `<div class="bulk" role="region" aria-label="Bulk actions"><b>${sel} selected</b>${c.bulk.map(b => `<button class="btn" type="button">${b}</button>`).join('')}<button class="btn" type="button" data-act="clr">Clear</button></div>` : '';
    if (state === 'empty') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} yet</b><p>${kind === 'students' ? 'Add students one by one, or import a spreadsheet with names and classes.' : kind === 'teachers' ? 'Add your teaching staff, then send each one an invite to join.' : 'Parents are added with their children, or you can add them here and link them.'}</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>`;
    if (state === 'nomatch') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} match “Zed”</b><p>Check the spelling or clear the filters to search all ${c.t.toLowerCase()}.</p><button class="btn" type="button">Clear filters</button></div>`;
    const loading = state === 'loading';
    const menu = r => `<button class="btn icon ghost" type="button" data-act="rmenu" data-id="${r.id}" aria-haspopup="menu" aria-expanded="${ST.menu == r.id}" aria-label="Actions for ${r.fn} ${r.ln}">${ic('ellipsis-vertical')}</button>${ST.menu == r.id ? `<div class="rmenu" role="menu">${c.menu.map((m, i) => `<a class="mi" role="menuitem" href="#" ${i === 0 ? `data-go="${c.go}"` : ''}>${m}</a>`).join('')}</div>` : ''}`;
    const ck = r => `<label class="ckb"><input type="checkbox" data-act="ck" data-id="${r.id}" ${ST.sel.has(String(r.id)) ? 'checked' : ''} aria-label="Select ${r.fn} ${r.ln}"></label>`;
    const head = `<thead><tr><th class="ck"><label class="ckb"><input type="checkbox" data-act="ckall" ${sel === rows.length ? 'checked' : ''} aria-label="Select all on this page"></label></th>${c.cols.map(h => `<th scope="col">${h}</th>`).join('')}<th class="menu"><span class="sr">Actions</span></th></tr></thead>`;
    const sk = '<span class="skel" style="width:70%"></span>';
    const body = loading ? Array.from({
      length: 6
    }, () => `<tr aria-hidden="true"><td class="ck"></td>${c.cols.map((_, i) => `<td>${i ? sk : '<span class="who"><span class="skel" style="width:36px;height:36px;border-radius:12px"></span><span class="skel" style="width:140px"></span></span>'}</td>`).join('')}<td></td></tr>`).join('') : rows.map(r => `<tr class="${ST.sel.has(String(r.id)) ? 'sel' : ''}"><td class="ck">${ck(r)}</td>${c.cell(r).map(x => `<td>${x}</td>`).join('')}<td class="menu">${menu(r)}</td></tr>`).join('');
    const cards = loading ? Array.from({
      length: 5
    }, () => `<div class="pc" aria-hidden="true"><span></span><span class="skel" style="width:40px;height:40px;border-radius:12px"></span><span><span class="skel" style="width:60%"></span><span class="skel" style="width:40%;margin-top:6px"></span></span><span></span></div>`).join('') : rows.map(r => {
      const [n, l2] = c.card(r);
      return `<div class="pc ${ST.sel.has(String(r.id)) ? 'sel' : ''}">${ck(r)}${av(r, 40)}<div style="min-width:0"><a class="nm trunc" href="#" data-go="${c.go}">${n}</a><div class="ln2">${l2}</div></div><div style="position:relative">${menu(r)}</div></div>`;
    }).join('');
    return tools + bulk + `<div class="tbl" ${loading ? 'aria-busy="true"' : ''}>${loading ? '<span class="sr" role="status">Loading ' + c.t.toLowerCase() + '…</span>' : ''}<table class="pl">${head}<tbody>${body}</tbody></table><div class="cards">${cards}</div>
  <div class="pager"><span>${loading ? '&nbsp;' : `Showing 1–${rows.length} of ${opts.total || c.chips[0][2]}`}</span><span class="row-acts"><button class="btn icon" type="button" aria-label="Previous page" disabled>${ic('chevron-left')}</button><button class="btn icon" type="button" aria-label="Next page">${ic('chevron-right')}</button></span></div></div>`;
  };
  window.scrList = kind => {
    const c = CFG[kind];
    return `<div class="ph"><div><h1>${c.t}</h1><p>${c.chips[0][2]} ${c.t.toLowerCase()} at ${SCHOOL.name}</p></div><div class="row-acts"><a class="btn" href="#">${ic('upload')}Import</a><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a></div></div>` + peopleList(kind);
  };
  /* ---------- 6. Classes (class teacher per stream, class-level default) ---------- */
  const streamTeacher = (c, i) => i === 0 || c.streams.length === 1 ? {
    t: T.find(x => x.id === c.ct),
    own: true
  } : {
    t: T.find(x => x.id === c.ct),
    own: false
  };
  const lvl = c => c.id === 'n1' || c.id === 'rc' ? 'Nursery' : 'Primary';
  window.scrClasses = () => {
    if (ST.state === 'empty') return `<div class="ph"><div><h1>Classes and streams</h1></div></div><div class="empty"><b>No classes yet</b><p>Add the classes your school teaches, from nursery to the final year. Add streams if a class is split into groups.</p><div class="row-acts"><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>`;
    return `<div class="ph"><div><h1>Classes and streams</h1><p>${CLASSES.length} classes · ${CLASSES.reduce((a, c) => a + c.streams.length, 0)} streams · Mid-term exams</p></div><div class="row-acts"><a class="btn" href="#">${ic('plus')}Add stream</a><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>
  <div class="lt"><label class="search"><span class="sr">Find a class</span>${ic('search')}<input type="search" placeholder="Find a class"></label><div class="chips" role="group" aria-label="Level"><button class="chip" type="button" aria-pressed="true">All <span class="n">8</span></button><button class="chip" type="button" aria-pressed="false">Nursery <span class="n">2</span></button><button class="chip" type="button" aria-pressed="false">Primary <span class="n">6</span></button></div></div>
  <div class="ccards">${CLASSES.map(c => `<article class="cc"><span class="kick">${lvl(c)}</span><a class="t" href="#" data-go="class">${c.name}</a>
    <ul class="strl">${c.streams.map((s, i) => {
      const x = streamTeacher(c, i);
      return `<li><span>${s}</span><span class="tch">${av(x.t, 24)}<span class="trunc">${x.t.fn} ${x.t.ln}</span>${x.own ? '' : '<span class="badge b-off">Class default</span>'}</span></li>`;
    }).join('')}</ul>
    <div class="st"><span>${c.n} students</span><span>Attendance ${c.att}%</span></div>
    <div class="hint">${c.avg ? `<span>Average <b>${c.avg}%</b> · ${grade(c.avg)}</span><span>${c.id === 'p6' ? '▼ 2 since last exam' : '▲ 3 since last exam'}</span>` : '<span>No exams for this class</span>'}</div></article>`).join('')}</div>`;
  };
  window.scrClass = () => {
    const c = CLASSES.find(x => x.id === 'p5'),
      t = T[0];
    const subj = [['Mathematics', T[0]], ['English', T[2]], ['Science', T[0]], ['Social Studies', T[3]], ['Religious Education', null], ['Creative Arts', T[6]]];
    const dist = [['A', 6, '#15803D'], ['B', 9, '#1E6FD9'], ['C', 10, '#B45309'], ['D', 5, '#1E293B'], ['E', 2, '#B91C1C']];
    const ST2 = [{
      s: 'Blue',
      n: 16,
      avg: 70,
      att: 95,
      own: true
    }, {
      s: 'Red',
      n: 16,
      avg: 66,
      att: 93,
      own: false
    }];
    const blue = S.filter(s => s.cls.endsWith('Blue')).slice(0, 8);
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="classes">Classes</a> <span aria-hidden="true">›</span> Primary 5</nav>
  <div class="ph"><div><h1>Primary 5</h1><p>2 streams · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts"><a class="btn" href="#">${ic('calendar-check')}Take attendance</a><a class="btn" href="#">${ic('clipboard-list')}Enter marks</a><button class="btn icon" type="button" aria-label="More actions">${ic('ellipsis')}</button></div></div>
  <div class="kpis k4">
   <div class="kpi"><span class="l">${ic('user-round-check', 'ic ic-sm')}Class teacher (default)</span><span style="display:flex;align-items:center;gap:10px;min-width:0">${av(t, 32)}<a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a></span><span class="s">For any stream without its own</span></div>
   ${kpi('graduation-cap', 'Students', '32', '17 girls · 15 boys')}${kpi('chart-column', 'Average · Mid-term', '68% · C', '▲ 3 pts since last exam', 'up')}${kpi('calendar-check', 'Attendance this week', '94%', '30 of 32 present today')}</div>
  <div class="card"><div class="hd"><h2>Streams</h2><a href="#">${ic('plus', 'ic ic-sm')} Add stream</a></div><div class="sgrid">${ST2.map(x => `<div class="scard"><b>Primary 5 · ${x.s}</b>
   <div class="tch">${av(t, 32)}<span style="min-width:0"><a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a><span class="sub">${x.own ? 'Class teacher of this stream' : 'Class default · no teacher assigned to this stream'}</span></span></div>
   ${x.own ? '' : '<a class="btn" href="#">Assign a class teacher</a>'}
   <div class="st"><span>${x.n} students</span><span>Average ${x.avg}%</span><span>Attendance ${x.att}%</span></div></div>`).join('')}</div></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Grade distribution</h2><small>Mid-term exams · 32 students</small></div>
   <div class="stack" role="img" aria-label="${dist.map(d => d[0] + ': ' + d[1]).join(', ')}">${dist.map(d => `<i style="width:${d[1] / 32 * 100}%;background:${d[2]}"></i>`).join('')}</div><div class="legend">${dist.map(d => `<span><i style="background:${d[2]}"></i>${d[0]} · ${d[1]}</span>`).join('')}</div>
   <div class="hd" style="margin-top:6px"><h3>Students by gender</h3></div>${gender(53, 47, 0)}</div>
   <div class="card"><div class="hd"><h2>Subjects</h2><small>6</small></div><ul class="subj">${subj.map(([s, tt]) => `<li><span>${s}</span><span class="tch">${tt ? av(tt, 28) + `<span class="trunc">${tt.fn} ${tt.ln}</span>` : '<span class="badge b-warn">No teacher</span>'}</span></li>`).join('')}</ul></div></div>
  <h2 class="sh">Students</h2>` + peopleList('students', {
      inClass: 1,
      rows: blue,
      total: 32,
      chips: [['all', 'All streams', 32], ['b', 'Blue', 16], ['r', 'Red', 16], ['active', 'Active', 31], ['nopar', 'No parent', 2]]
    });
  };
  window.KC = {
    bars,
    line,
    cols,
    kpi,
    gender,
    ayPick
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/admin-mvp/screens-a.js", error: String((e && e.message) || e) }); }

// concepts/admin-mvp/screens-b.js
try { (() => {
// Profiles: student, teacher, parent — admin viewer
(() => {
  const {
    SCHOOL,
    T,
    S,
    P,
    av,
    money,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  const tabs = (list, cur) => `<div class="tabs" role="tablist">${list.map(x => `<button role="tab" type="button" data-act="tab" data-tab="${x}" aria-selected="${x === cur}">${x}</button>`).join('')}</div>`;
  const more = (id, items) => `<span style="position:relative"><button class="btn icon" type="button" data-act="rmenu" data-id="${id}" aria-haspopup="menu" aria-expanded="${ST.menu == id}" aria-label="More actions">${ic('ellipsis')}</button>${ST.menu == id ? `<div class="rmenu" role="menu" style="top:calc(100% + 6px);right:0;width:240px">${items}</div>` : ''}</span>`;
  const empty = (t, p, b) => `<div class="empty"><b>${t}</b><p>${p}</p>${b ? `<a class="btn" href="#">${b}</a>` : ''}</div>`;
  const E = () => ST.state === 'empty';
  /* ---------- 3. Student ---------- */
  window.scrStudent = () => {
    const s = S[0],
      tl = ['Overview', 'Academics', 'Attendance', 'Fees', 'Parents and guardians', 'Health and support', 'Documents', 'Notes'],
      tab = tl.includes(ST.tab) ? ST.tab : 'Overview';
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a><a class="mi" role="menuitem" href="#">${ic('arrow-right-left')}Move to class</a><div class="sep" role="separator"></div>
   <button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deact">${ic('user-x')}Deactivate student</button>`;
    const hd = `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="students">Students</a> <span aria-hidden="true">›</span> ${s.fn} ${s.ln}</nav>
  <div class="phd">${av(s, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${s.fn} ${s.ln}</h1><div class="meta"><a href="#" data-go="class" style="font-weight:700">${s.cls}</a><span><span class="k">KLS</span> <span style="font-variant-numeric:tabular-nums">${s.kls}</span></span><span class="badge b-ok">Active</span></div></div>
  <div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit</a><a class="btn" href="#">${ic('message-circle')}Message parent</a>${more('stu', menu)}</div></div>`;
    const k = E() ? `<div class="kpis k3"><div class="kpi"><span class="l">${ic('calendar-check', 'ic ic-sm')}Attendance this term</span><span class="v">–</span><span class="s">No register taken yet</span></div><div class="kpi"><span class="l">${ic('chart-column', 'ic ic-sm')}Latest exam</span><span class="v">–</span><span class="s">No marks yet</span></div><div class="kpi"><span class="l">${ic('wallet', 'ic ic-sm')}Fees</span><span class="v">–</span><span class="s">No fee structure</span></div></div>` : `<div class="kpis k3"><div class="kpi"><span class="l">${ic('calendar-check', 'ic ic-sm')}Attendance this term</span><span class="v">91%</span><span class="meter" role="img" aria-label="91%"><i style="width:91%"></i></span><span class="s">5 days absent · 2 late</span></div>
     <div class="kpi"><span class="l">${ic('chart-column', 'ic ic-sm')}Latest exam · Mid-term</span><span class="v">74% · B</span><span class="s">6th of 32 in Primary 5 · Blue</span></div>
     <div class="kpi"><span class="l">${ic('wallet', 'ic ic-sm')}Fees · ${SCHOOL.term}</span><span class="kl">Balance</span><span class="v owed">${money(180000)}</span><span class="s"><span class="badge b-warn">Partly paid</span> ${money(270000)} of ${money(450000)}</span></div></div>`;
    let b;
    if (tab === 'Overview') b = `<div class="grid2"><div class="card"><h2>Student</h2><dl class="kv"><dt>Full name</dt><dd>${s.fn} ${s.ln}</dd><dt>KLS number</dt><dd>${s.kls}</dd><dt>Class</dt><dd>${s.cls}</dd><dt>Gender</dt><dd>Female</dd><dt>Date of birth</dt><dd>${E() ? '<span style="color:var(--d-text-secondary)">Not given</span>' : s.dob}</dd><dt>Joined</dt><dd>${E() ? 'This term' : 'Term 1, 2024'}</dd></dl></div>
   <div class="card"><h2>Parents and guardians</h2>${E() ? `<p style="margin:0">No parent linked yet.</p><a class="btn" href="#" style="align-self:flex-start">${ic('link')}Link a parent</a>` : `<div class="kidc">${av(P[0], 40)}<div><a href="#" data-go="parent" style="font-weight:700">${P[0].fn} ${P[0].ln}</a><div class="pc-sub" style="font-size:13.5px;color:var(--d-text-secondary)">Mother · <a href="tel:+000772418205">${P[0].ph}</a> · on WhatsApp</div></div></div>`}</div></div>`;else if (E()) b = {
      Academics: empty('No marks yet', 'Marks appear here once teachers enter them for an exam.'),
      Attendance: empty('No attendance taken yet this term', 'It appears after the class teacher takes the first register.'),
      Fees: empty('No fee structure for Primary 5 this term', 'Set up the term’s fees before recording payments.', 'Set up fees'),
      'Parents and guardians': empty('No parent linked yet', 'Link a parent so they receive report cards and fee messages on WhatsApp.', 'Link a parent'),
      'Health and support': empty('No health or support notes', 'Parents can add allergies, medical conditions and support needs on the admission form, or you can add them here.', 'Add notes'),
      Documents: empty('No documents yet', 'Birth certificates and photos sent with the admission form appear here.', 'Upload document'),
      Notes: empty('No notes', 'Notes are visible to admins only, and each view is logged.', 'Add a note')
    }[tab];else if (tab === 'Academics') {
      const sub = [['English', 70, 72, 76], ['Mathematics', 66, 71, 73], ['Science', 74, 75, 79], ['Social Studies', 62, 66, 68], ['Religious Education', 81, 80, 84], ['Creative Arts', 77, 79, 0]];
      b = `<div class="card"><div class="hd"><h2>Marks by subject</h2><small>${SCHOOL.year} · % per exam</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Subject</th><th class="n" scope="col">Term 1</th><th class="n" scope="col">Term 2</th><th class="n" scope="col">Term 3 mid-term</th><th scope="col">Grade</th></tr></thead><tbody>${sub.map(r => `<tr><td>${r[0]}</td><td class="n">${r[1]}</td><td class="n">${r[2]}</td><td class="n">${r[3] || '<span style="color:var(--d-text-secondary)">Pending</span>'}</td><td>${r[3] ? grade(r[3]) : '–'}</td></tr>`).join('')}<tr><th scope="row">Average</th><td class="n"><b>72</b></td><td class="n"><b>74</b></td><td class="n"><b>74</b></td><td><b>B</b></td></tr></tbody></table></div></div>
    <div class="card"><div class="hd"><h2>This term against the class</h2><small>Amara · class average</small></div><div class="hbars">${sub.filter(r => r[3]).map((r, i) => `<div class="hb"><span>${r[0].split(' ')[0]}</span><span class="t" role="img" aria-label="${r[0]}: ${r[3]}%, class average ${r[3] - 5 + i}%"><i style="width:${r[3]}%"></i></span><b>${r[3]}%</b></div><div class="hb" style="margin-top:-6px"><span></span><span class="t" style="height:6px" aria-hidden="true"><i style="width:${r[3] - 5 + i}%;background:#94A3B8"></i></span><span style="font-size:12px;text-align:right;color:var(--d-text-secondary)">${r[3] - 5 + i}%</span></div>`).join('')}</div><div class="legend"><span><i style="background:#1E6FD9"></i>Amara</span><span><i style="background:#94A3B8"></i>Class average</span></div></div>`;
    } else if (tab === 'Attendance') b = `<div class="card"><div class="hd"><h2>${SCHOOL.term}</h2><small>Marked by Sarah Nakato</small></div><div class="kpis k3"><div class="kpi"><span class="l">Present</span><span class="v">91%</span></div><div class="kpi"><span class="l">Absent</span><span class="v">5 days</span></div><div class="kpi"><span class="l">Late</span><span class="v">2 days</span></div></div><table class="dt"><thead><tr><th scope="col">Date</th><th scope="col">Status</th></tr></thead><tbody><tr><td>Mon 5 Oct</td><td><span class="badge b-bad">Absent</span></td></tr><tr><td>Fri 2 Oct</td><td><span class="badge b-ok">Present</span></td></tr><tr><td>Thu 1 Oct</td><td><span class="badge b-warn">Late</span></td></tr></tbody></table></div>`;else if (tab === 'Fees') b = `<div class="card"><div class="hd"><h2>${SCHOOL.term}, ${SCHOOL.year}</h2><span class="badge b-warn">Partly paid</span></div><dl class="kv"><dt>Expected</dt><dd>${money(450000)}</dd><dt>Paid</dt><dd>${money(270000)}</dd><dt>Balance</dt><dd class="owed"><b>${money(180000)}</b></dd></dl><table class="dt"><thead><tr><th scope="col">Date</th><th scope="col">Method</th><th class="n" scope="col">Amount</th></tr></thead><tbody><tr><td>2 Sep</td><td>Bank transfer</td><td class="n">${money(270000)}</td></tr></tbody></table><div class="row-acts"><a class="btn pri" href="#">Record payment</a><a class="btn" href="#">Send reminder on WhatsApp</a></div></div>`;else if (tab === 'Parents and guardians') b = `<div class="card"><div class="hd"><h2>Parents and guardians</h2><a class="btn" href="#">${ic('link')}Link another</a></div><div class="kidc">${av(P[0], 48)}<div><a href="#" data-go="parent" style="font-weight:700">${P[0].fn} ${P[0].ln}</a><div style="font-size:13.5px;color:var(--d-text-secondary)">Mother · primary contact</div><a class="ctline" href="tel:+000772418205">${ic('phone')}${P[0].ph}</a><span class="badge b-ok">Opted in to WhatsApp</span></div></div></div>`;else if (tab === 'Documents') b = `<div class="card"><div class="hd"><h2>Documents</h2><a class="btn" href="#">${ic('upload')}Upload</a></div><table class="dt"><thead><tr><th scope="col">Document</th><th scope="col">From</th><th scope="col">Added</th></tr></thead><tbody><tr><td>${ic('file-text', 'ic ic-sm')} Birth certificate.pdf</td><td>Admission form</td><td>12 Jan 2024</td></tr><tr><td>${ic('image', 'ic ic-sm')} Passport photo.jpg</td><td>Admission form</td><td>12 Jan 2024</td></tr></tbody></table></div>`;else if (tab === 'Health and support') b = `<div class="card"><div class="hd"><h2>Health and support</h2><span class="badge b-info">${ic('lock-keyhole', 'ic ic-sm')}Admins and head teacher only</span></div><p class="note">${ic('history', 'ic ic-sm')}Opening this tab is logged. Last opened by Mucunguzi Moses today at 09:14.</p><dl class="kv"><dt>Allergies</dt><dd>Peanuts (severe). Carries an adrenaline pen in her school bag.</dd><dt>Medical conditions</dt><dd>Mild asthma. Inhaler kept at the school office.</dd><dt>Support needs</dt><dd>Sits near the front of the class for hearing.</dd><dt>Emergency contact</dt><dd>Grace Okafor (mother) · <a href="tel:+000772418205">${P[0].ph}</a></dd><dt>Last updated</dt><dd>12 Jan 2024 · admission form</dd></dl><div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit notes</a></div></div>`;else b = `<div class="card"><div class="reqrow"><span>Notes are visible to admins only. Opening them is logged.</span><button class="btn" type="button">${ic('lock-keyhole-open')}Show notes</button></div></div>`;
    return hd + k + `<div class="tabw">${tabs(tl, tab)}</div>` + b;
  };
  /* ---------- 4. Teacher ---------- */
  window.scrTeacher = () => {
    const inv = ST.invite || 'accepted',
      t = {
        ...T[0],
        invite: inv
      };
    const badge = inv === 'accepted' ? '<span class="badge b-ok">Joined 3 Sep 2026</span>' : inv === 'invited' ? '<span class="badge b-info">Invited 5 Oct · not accepted yet</span>' : '<span class="badge b-warn">Not invited</span>';
    const invBtn = inv === 'accepted' ? '' : inv === 'invited' ? `<a class="btn" href="#">${ic('send')}Resend invite</a>` : `<a class="btn pri" href="#">${ic('send')}Send invite</a>`;
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('book-open')}Assign classes and subjects</a>${inv === 'accepted' ? `<a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a>` : ''}<div class="sep" role="separator"></div><button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deactT">${ic('user-x')}Deactivate account</button>`;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="teachers">Teachers</a> <span aria-hidden="true">›</span> ${t.fn} ${t.ln}</nav>
  <div class="phd">${av(t, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${t.fn} ${t.ln}</h1><div class="meta"><span class="badge b-info">${t.role}</span><span>Class teacher of <a href="#" data-go="class" style="font-weight:700">${t.ctOf}</a></span>${badge}</div></div>
  <div class="row-acts">${invBtn}<a class="btn" href="#">${ic('pencil')}Edit</a>${more('tch', menu)}</div></div>
  <div class="kpis k4">
   <div class="kpi"><span class="l">${ic('school', 'ic ic-sm')}Classes</span><span class="v">2</span><span class="s">Primary 5 · Blue, Primary 6 · Blue</span></div>
   <div class="kpi"><span class="l">${ic('book-open', 'ic ic-sm')}Subjects</span><span class="v">2</span><span class="s">Mathematics, Science</span></div>
   ${ST.wl === 'as' ? `<div class="kpi"><span class="l">${ic('clock', 'ic ic-sm')}Workload</span><span class="v">3</span><span class="s">class–subject assignments</span><span class="badge b-off" style="align-self:flex-start">No timetable yet</span></div>` : `<div class="kpi"><span class="l">${ic('clock', 'ic ic-sm')}Workload</span><span class="v">${t.lessons}</span><span class="s">lessons a week</span><span class="badge b-ok" style="align-self:flex-start">From the timetable</span></div>`}
   <div class="kpi"><span class="l">${ic('circle-alert', 'ic ic-sm')}Still pending</span><span class="v">1</span><span class="s warn">Science marks · Primary 5 Blue</span></div></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Teaches</h2><small>${SCHOOL.term}</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Class</th><th scope="col">Subject</th><th class="n" scope="col">Students</th><th scope="col">Mid-term marks</th></tr></thead><tbody>
   <tr><td><a href="#" data-go="class">Primary 5 · Blue</a> <span class="badge b-ok">Class teacher</span></td><td>Mathematics</td><td class="n">32</td><td><span class="badge b-ok">Entered</span></td></tr>
   <tr><td><a href="#" data-go="class">Primary 5 · Blue</a></td><td>Science</td><td class="n">32</td><td><span class="badge b-warn">Pending</span></td></tr>
   <tr><td>Primary 6 · Blue</td><td>Mathematics</td><td class="n">27</td><td><span class="badge b-ok">Entered</span></td></tr></tbody></table></div>
   <div class="reqrow"><span>Today's attendance for Primary 5 · Blue</span><span class="badge b-ok">Taken at 08:12</span></div></div>
   <div class="card"><h2>Contact</h2><a class="ctline" href="tel:+000700100101">${ic('phone')}${t.ph}</a><a class="ctline trunc" href="mailto:${t.email}">${ic('mail')}<span class="trunc">${t.email}</span></a><dl class="kv" style="margin-top:4px"><dt>Last login</dt><dd>${inv === 'accepted' ? t.last : 'Never'}</dd><dt>Account</dt><dd>${inv === 'accepted' ? 'Active' : inv === 'invited' ? 'Invite sent, link valid 72 hours' : 'No login yet'}</dd></dl></div></div>`;
  };
  /* ---------- 5. Parent ---------- */
  window.scrParent = () => {
    const p = P[0],
      waSt = ST.wa || 'in';
    const waRow = waSt === 'in' ? `<span class="badge b-ok">Opted in on ${p.waDate}</span>` : waSt === 'pending' ? `<span class="badge b-info">Opt-in request sent, waiting for reply</span>` : `<span class="badge b-off">Not opted in</span><a class="btn" href="#">${ic('message-circle')}Send opt-in request</a>`;
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('link')}Link a child</a><a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a><div class="sep" role="separator"></div><button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deactP">${ic('user-x')}Deactivate account</button>`;
    const kids = E() ? empty('No children linked yet', 'Link this parent to their children so they receive report cards and fee messages on WhatsApp.', 'Link a child') : `<div class="grid2">${p.kids.map(k => `<article class="card"><div class="kidc">${av(k, 48)}<div style="min-width:0"><a href="#" data-go="student" style="font:600 16px var(--d-font-display,'Sora',sans-serif);color:#0F172A">${k.fn} ${k.ln}</a><div style="font-size:13.5px;color:var(--d-text-secondary)">${k.cls} · <span style="font-variant-numeric:tabular-nums">${k.kls}</span></div><div class="meta" style="margin-top:6px;font-size:13.5px"><span>Attendance ${k.att}%</span>${k.bal ? `<span class="owed">${money(k.bal)} due</span>` : '<span class="badge b-ok">Fees cleared</span>'}</div></div>
     <div class="ql"><a class="btn" href="#">${ic('file-text')}Report card</a><a class="btn" href="#">${ic('wallet')}Fees</a></div></div></article>`).join('')}</div>`;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="parents">Parents</a> <span aria-hidden="true">›</span> ${p.fn} ${p.ln}</nav>
  <div class="phd">${av(p, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${p.fn} ${p.ln}</h1><div class="meta"><span class="badge b-info">Parent</span><span>${E() ? 'No children linked' : p.kids.length + ' children at ' + SCHOOL.name}</span></div></div>
  <div class="row-acts"><a class="btn" href="#">${ic('message-circle')}Message on WhatsApp</a><a class="btn" href="#">${ic('pencil')}Edit</a>${more('par', menu)}</div></div>
  <h2 style="font:600 18px var(--d-font-display,'Sora',sans-serif);margin:0;color:#0F172A">Children</h2>${kids}
  <div class="grid2"><div class="card"><h2>Contact</h2><a class="ctline" href="tel:+000772418205">${ic('phone')}${p.ph}</a><a class="ctline" href="mailto:${p.email}">${ic('mail')}<span class="trunc">${p.email}</span></a></div>
  <div class="card"><h2>WhatsApp and access</h2><div class="reqrow">${waRow}</div><dl class="kv"><dt>Last login</dt><dd>${p.last}</dd><dt>Messages this term</dt><dd>12 sent · 2 replies</dd></dl></div></div>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/admin-mvp/screens-b.js", error: String((e && e.message) || e) }); }

// concepts/admin-mvp/screens-c.js
try { (() => {
// Batch two: Subjects, Subject page, Attendance overview, Exams (subject-first)
(() => {
  const {
    SCHOOL,
    T,
    av,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  const {
    bars,
    line,
    kpi
  } = window.KC;
  const tt = id => T.find(t => t.id === id);
  const STREAMS = [['Primary 1', 'Blue', 't2', 16], ['Primary 1', 'Red', 't2', 15], ['Primary 2', 'Blue', 't3', 15], ['Primary 2', 'Red', 't3', 14], ['Primary 3', 'Blue', 't4', 28], ['Primary 4', 'Blue', 't5', 30], ['Primary 5', 'Blue', 't1', 16], ['Primary 5', 'Red', 't1', 16], ['Primary 6', 'Blue', 't6', 27]];
  const SUB = [{
    id: 'eng',
    n: 'English',
    code: 'ENG',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t2', 't3', 't6'],
    avg: 71,
    d: 2,
    ent: 9
  }, {
    id: 'mth',
    n: 'Mathematics',
    code: 'MTH',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t1', 't5'],
    avg: 66,
    d: -1,
    ent: 6
  }, {
    id: 'sci',
    n: 'Science',
    code: 'SCI',
    type: 'Core',
    lv: 'Primary 3–6',
    from: 4,
    tch: ['t1', 't4'],
    avg: 72,
    d: 3,
    ent: 3
  }, {
    id: 'sst',
    n: 'Social Studies',
    code: 'SST',
    type: 'Core',
    lv: 'Primary 3–6',
    from: 4,
    tch: ['t4'],
    avg: 63,
    d: 0,
    ent: 5
  }, {
    id: 're',
    n: 'Religious Education',
    code: 'RE',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t6'],
    avg: 79,
    d: 1,
    ent: 2,
    gap: 1
  }, {
    id: 'ca',
    n: 'Creative Arts',
    code: 'CA',
    type: 'Optional',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t7'],
    avg: null,
    d: 0,
    ent: 0
  }, {
    id: 'lan',
    n: 'Language',
    code: 'LAN',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }, {
    id: 'num',
    n: 'Numbers',
    code: 'NUM',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }, {
    id: 'rdg',
    n: 'Reading',
    code: 'RDG',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }];
  const rowsOf = s => s.nur ? [] : STREAMS.slice(s.from);
  const stu = s => s.nur ? 46 : rowsOf(s).reduce((a, r) => a + r[3], 0);
  const avs = ids => `<span class="avs">${ids.slice(0, 3).map(i => av(tt(i), 28)).join('')}${ids.length > 3 ? `<span class="sub">+${ids.length - 3}</span>` : ''}</span>`;
  const prog = (a, b) => `<span class="prw"><span class="prog" role="progressbar" aria-valuemin="0" aria-valuemax="${b}" aria-valuenow="${a}" aria-label="${a} of ${b} classes"><i style="width:${b ? a / b * 100 : 0}%"></i></span><span class="sub">${a} of ${b}</span></span>`;
  const stat = (a, b) => b === 0 ? '<span class="badge b-off">No exam</span>' : a === b ? '<span class="badge b-ok">Complete</span>' : a === 0 ? '<span class="badge b-off">Not started</span>' : '<span class="badge b-warn">In progress</span>';
  const menu = (id, items) => `<span style="position:relative;display:inline-flex"><button class="btn icon ghost" type="button" data-act="rmenu" data-id="${id}" aria-haspopup="menu" aria-expanded="${ST.menu == id}" aria-label="More actions">${ic('ellipsis-vertical')}</button>${ST.menu == id ? `<div class="rmenu" role="menu" style="top:calc(100% + 4px);right:0">${items.map(m => m === '-' ? '<div class="sep" role="separator"></div>' : `<a class="mi${m.startsWith('!') ? ' danger' : ''}" role="menuitem" href="#" ${m === 'View subject' ? 'data-go="subject"' : ''}>${m.replace('!', '')}</a>`).join('')}</div>` : ''}</span>`;
  const avgCell = s => s.nur ? '<span class="sub">Not examined</span>' : s.avg ? `<b>${s.avg}%</b> · ${grade(s.avg)} <span class="sub" style="display:inline">${s.d > 0 ? '▲ ' + s.d : s.d < 0 ? '▼ ' + -s.d : '–'}</span>` : '<span class="sub">No marks yet</span>';
  /* ---------- Subjects index ---------- */
  window.scrSubjects = () => {
    const head = `<div class="ph"><div><h1>Subjects</h1><p>${SUB.length} subjects · each listed once, across all its classes</p></div><div class="row-acts"><a class="btn pri" href="#" data-go="form-subject">${ic('plus')}Add subject</a></div></div>
  <div class="lt"><label class="search"><span class="sr">Search subjects</span>${ic('search')}<input type="search" placeholder="Search by subject or code"></label><div class="chips" role="group" aria-label="Filters"><button class="chip" type="button" aria-pressed="true">All <span class="n">9</span></button><button class="chip" type="button" aria-pressed="false">Core <span class="n">8</span></button><button class="chip" type="button" aria-pressed="false">Optional <span class="n">1</span></button><button class="chip" type="button" aria-pressed="false">Nursery <span class="n">3</span></button><button class="chip" type="button" aria-pressed="false">Primary <span class="n">6</span></button><button class="chip" type="button" aria-pressed="false">Missing a teacher <span class="n">1</span></button></div></div>`;
    if (ST.state === 'empty') return head + `<div class="empty"><b>No subjects yet</b><p>Add each subject once, then choose the classes that take it and who teaches it in each class.</p><a class="btn pri" href="#">${ic('plus')}Add subject</a></div>`;
    const M = ['View subject', 'Edit', 'Assign teachers', '-', '!Archive subject'];
    return head + `<div class="tbl"><table class="pl"><thead><tr><th scope="col">Subject</th><th scope="col">Type</th><th scope="col">Classes</th><th scope="col">Teachers</th><th scope="col">Average · Mid-term</th><th scope="col">Mid-term marks</th><th class="menu"><span class="sr">Actions</span></th></tr></thead><tbody>
  ${SUB.map(s => `<tr><td><span class="who"><span class="sic" aria-hidden="true">${s.code}</span><span style="min-width:0"><a href="#" data-go="subject">${s.n}</a><span class="sub">${s.code}</span></span></span></td><td>${s.type}</td><td>${s.nur ? 2 : rowsOf(s).length} <span class="sub" style="display:inline">· ${s.lv}</span>${s.gap ? ' <span class="badge b-warn">1 without a teacher</span>' : ''}</td><td>${avs(s.tch)}</td><td>${avgCell(s)}</td><td>${s.nur ? '<span class="badge b-off">No exam</span>' : prog(s.ent, rowsOf(s).length)}</td><td class="menu">${menu('s' + s.id, M)}</td></tr>`).join('')}</tbody></table>
  <div class="cards">${SUB.map(s => `<div class="pc nock"><span class="sic" aria-hidden="true">${s.code}</span><div style="min-width:0"><a class="nm trunc" href="#" data-go="subject">${s.n}</a><div class="ln2"><span>${s.lv}</span>${s.nur ? '' : stat(s.ent, rowsOf(s).length)}${s.gap ? '<span class="badge b-warn">1 without a teacher</span>' : ''}</div></div><div style="position:relative">${menu('m' + s.id, M)}</div></div>`).join('')}</div>
  <div class="pager"><span>Showing all 9</span></div></div>`;
  };
  /* ---------- Subject page ---------- */
  window.scrSubject = () => {
    const s = SUB[1],
      rows = rowsOf(s);
    const st = (i, n) => i < 6 ? [n, n] : i === 6 ? [18, n] : [0, n];
    const EX = [['Beginning of term', '7 Sep', 9, 9, 64, 'Complete'], ['Mid-term', '5–9 Oct', 6, 9, 66, 'In progress'], ['End of term', 'From 24 Nov', 0, 9, null, 'Scheduled']];
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="subjects">Subjects</a> <span aria-hidden="true">›</span> ${s.n}</nav>
  <div class="ph"><div><h1>${s.n}</h1><div class="meta"><span class="badge b-info">${s.type}</span><span><span class="k">Code</span> ${s.code}</span><span>${s.lv}</span></div></div><div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit</a><a class="btn" href="#">${ic('user-round-plus')}Assign teachers</a>${menu('subj', ['Download all marksheets', '-', '!Archive subject'])}</div></div>
  <div class="kpis k4">${kpi('school', 'Classes', String(rows.length), s.lv)}<div class="kpi"><span class="l">${ic('presentation', 'ic ic-sm')}Teachers</span><span class="v">2</span><span class="s">${s.tch.map(i => tt(i).fn + ' ' + tt(i).ln).join(', ')}</span></div>${kpi('graduation-cap', 'Students', String(stu(s)), 'taking ' + s.n)}${kpi('chart-column', 'Average · Mid-term', '66% · C', '▼ 1 pt on Beginning of term', 'warn')}</div>
  <div class="card"><div class="hd"><h2>Exams this term</h2><small>${SCHOOL.term}, ${SCHOOL.year}</small></div>
  <ul class="exl">${EX.map((e, i) => `<li><span><b>${e[0]}</b><span class="sub">${e[1]}</span></span><span class="hm">${e[4] ? `Average <b>${e[4]}%</b>` : '<span class="sub">No marks yet</span>'}</span>${prog(e[2], e[3])}<span class="hm">${e[5] === 'Complete' ? '<span class="badge b-ok">Complete</span>' : e[5] === 'In progress' ? '<span class="badge b-warn">In progress</span>' : '<span class="badge b-off">Scheduled</span>'}</span><span class="row-acts">${i === 1 ? '<a class="btn pri" href="#">Enter marks</a>' : i === 0 ? '<a class="btn" href="#">View marks</a>' : ''}${menu('ex' + i, ['Import from spreadsheet', 'Download marksheet', 'Remind teachers'])}</span></li>`).join('')}</ul></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Classes</h2><small>Mid-term marks and report cards</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Class</th><th scope="col" class="hm">Teacher</th><th class="n hm" scope="col">Average</th><th scope="col">Marks</th><th scope="col" class="hm">Report card</th></tr></thead><tbody>
  ${rows.map((r, i) => {
      const [a, n] = st(i, r[3]);
      const t = tt(i > 5 ? 't1' : i < 4 ? 't5' : 't1');
      return `<tr><td><a href="#" data-go="class">${r[0]} · ${r[1]}</a></td><td class="hm"><span class="tch" style="display:flex;gap:8px;align-items:center">${av(t, 24)}<span class="trunc">${t.fn} ${t.ln}</span></span></td><td class="n hm">${a === n ? 60 + i * 5 % 14 + '%' : '–'}</td><td>${a === n ? `<span class="badge b-ok">Entered ${a}/${n}</span>` : a ? `<span class="badge b-warn">${a} of ${n}</span>` : `<span class="badge b-off">Not started</span>`}</td><td class="hm">${a === n ? '<span class="badge b-ok">Ready</span>' : '<span class="sub">Waiting for marks</span>'}</td></tr>`;
    }).join('')}</tbody></table></div></div>
  <div class="card"><div class="hd"><h2>Average by class</h2><small>Mid-term · entered so far</small></div>${bars(rows.slice(0, 6).map((r, i) => [r[0].replace('Primary ', 'P.') + ' ' + r[1], 60 + i * 5 % 14]))}<p class="sub" style="margin:0">3 classes have no Mid-term marks yet.</p></div></div>`;
  };
  /* ---------- Attendance overview ---------- */
  const vbars = (pts, lo = 80) => {
    const w = 360,
      h = 170,
      bw = (w - 40) / pts.length,
      y = v => 14 + (100 - v) / (100 - lo) * (h - 50);
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">${[80, 90, 100].map(v => `<line x1="30" x2="${w - 6}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="26" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}${pts.map((p, i) => {
      const x = 34 + i * bw;
      return `<rect x="${x + 6}" y="${y(p[1])}" width="${bw - 12}" height="${h - 36 - y(p[1])}" rx="4" fill="${p[2] || '#15803D'}"/><text x="${x + bw / 2}" y="${y(p[1]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${p[1]}%</text><text x="${x + bw / 2}" y="${h - 18}" text-anchor="middle" font-size="11" fill="#475569">${p[0]}</text>`;
    }).join('')}</svg>`;
  };
  window.scrAttendance = () => {
    const head = `<div class="ph"><div><h1>Attendance</h1><p>Today · Thursday 8 October 2026</p></div><div class="row-acts"><label class="ay">Period <select class="sel" aria-label="Period"><option selected>This week</option><option>This term</option><option>Last 8 weeks</option></select></label><a class="btn" href="#">${ic('download')}Export</a></div></div>`;
    if (ST.state === 'empty') return head + `<div class="empty"><b>No attendance taken yet this term</b><p>Class teachers take the register each morning. Patterns by class and by day appear after the first week.</p><a class="btn pri" href="#">${ic('bell')}Remind class teachers</a></div>`;
    const REG = STREAMS.map((r, i) => ({
      c: r[0] + ' · ' + r[1],
      t: tt(r[2]),
      n: r[3],
      ok: i !== 3 && i !== 5,
      at: ['07:52', '08:05', '08:20', '', '08:12', '', '08:12', '08:31', '07:58'][i]
    })).concat([{
      c: 'Nursery · Sunflower',
      t: tt('t7'),
      n: 22,
      ok: true,
      at: '08:02'
    }, {
      c: 'Reception · Sunflower',
      t: tt('t8'),
      n: 24,
      ok: true,
      at: '08:09'
    }]);
    REG.sort((a, b) => a.ok - b.ok);
    return head + `<div class="kpis k4">${kpi('user-round-check', 'Present today', '93.1%', '231 of 248 · 17 absent', '', 93.1)}<div class="kpi"><span class="l">${ic('clipboard-check', 'ic ic-sm')}Registers taken today</span><span class="v">9 of 11</span><span class="meter" role="img" aria-label="9 of 11"><i style="width:82%"></i></span><span class="s warn">2 not taken yet</span></div>${kpi('calendar-check', 'This week', '93.4%', '▲ 1.2 pts on last week', 'up')}${kpi('calendar-range', 'This term', '92.6%', 'Since 7 September')}</div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Who has taken today</h2><small>Not taken first</small></div><ul class="reg">${REG.map(r => `<li><span style="min-width:0"><b class="trunc">${r.c}</b><span class="tch">${av(r.t, 24)}<span class="trunc sub" style="display:inline">${r.t.fn} ${r.t.ln}</span></span></span>${r.ok ? `<span class="badge b-ok">Taken ${r.at}</span>` : `<span class="row-acts"><span class="badge b-warn">Not yet</span><a class="btn" href="#">${ic('bell')}Remind</a></span>`}</li>`).join('')}</ul></div>
  <div class="card"><div class="hd"><h2>By day of the week</h2><small>This term</small></div>${vbars([['Mon', 94], ['Tue', 95], ['Wed', 94], ['Thu', 93], ['Fri', 89, '#B45309']])}<p class="note">${ic('info', 'ic ic-sm')}Fridays average 5 points below the rest of the week.</p></div></div>
  <div class="grid2"><div class="card"><div class="hd"><h2>By class</h2><small>This week</small></div>${bars(STREAMS.map((r, i) => [r[0].replace('Primary ', 'P.') + ' ' + r[1], [96, 95, 93, 92, 92, 91, 95, 93, 90][i]]))}</div>
  <div class="card"><div class="hd"><h2>Trend</h2><small>Last 8 weeks · whole school</small></div>${line([['W1', 91], ['W2', 92], ['W3', 90], ['W4', 93], ['W5', 94], ['W6', 92], ['W7', 92.2], ['W8', 93.4]])}</div></div>`;
  };
  /* ---------- Exams, subject first ---------- */
  window.scrExams = () => {
    const ex = SUB.filter(s => !s.nur);
    const tot = ex.reduce((a, s) => a + rowsOf(s).length, 0),
      done = ex.reduce((a, s) => a + s.ent, 0);
    const open = ST.open || 'mth';
    const head = `<div class="ph"><div><h1>Exams and marks</h1><p>${SCHOOL.term}, ${SCHOOL.year} · by subject</p></div><div class="row-acts"><label class="ay">Term <select class="sel" aria-label="Term"><option selected>Term 3, 2026</option><option>Term 2, 2026</option></select></label><a class="btn pri" href="#">${ic('plus')}Add exam</a></div></div>
  <div class="chips" role="group" aria-label="Exam"><button class="chip" type="button" aria-pressed="false">Beginning of term <span class="n">· Complete</span></button><button class="chip" type="button" aria-pressed="true">Mid-term <span class="n">· In progress</span></button><button class="chip" type="button" aria-pressed="false">End of term <span class="n">· From 24 Nov</span></button></div>`;
    if (ST.state === 'empty') return head.replace(/<div class="chips"[\s\S]*$/, '') + `<div class="empty"><b>No exams this term</b><p>Add an exam, such as a mid-term or end-of-term exam, then choose its subjects and classes. Teachers enter marks per subject.</p><a class="btn pri" href="#">${ic('plus')}Add exam</a></div>`;
    return head + `<div class="card exsum"><div style="min-width:0;flex:1 1 280px"><h2>Mid-term exams · 5–9 October</h2><p style="margin:4px 0 8px">Marks entered for <b>${done} of ${tot}</b> subject classes</p><span class="prog" style="height:8px" role="progressbar" aria-valuemin="0" aria-valuemax="${tot}" aria-valuenow="${done}" aria-label="Marks entered"><i style="width:${done / tot * 100}%"></i></span></div>
   <div class="exrc"><span><b>Report cards</b><span class="sub">4 of 11 classes have every mark</span></span><span class="row-acts"><a class="btn" href="#">${ic('bell')}Remind teachers</a><a class="btn pri" href="#">${ic('file-text')}Generate for 4 classes</a></span></div></div>
  <div class="lt"><label class="search"><span class="sr">Search subjects</span>${ic('search')}<input type="search" placeholder="Search subjects"></label><div class="chips" role="group" aria-label="Status"><button class="chip" type="button" aria-pressed="true">All <span class="n">6</span></button><button class="chip" type="button" aria-pressed="false">Not started <span class="n">1</span></button><button class="chip" type="button" aria-pressed="false">In progress <span class="n">3</span></button><button class="chip" type="button" aria-pressed="false">Complete <span class="n">2</span></button></div></div>
  <ul class="xl">${ex.map(s => {
      const rows = rowsOf(s),
        n = rows.length,
        o = open === s.id;
      return `<li class="${o ? 'open' : ''}"><div class="xh"><button class="btn icon ghost" type="button" data-act="tog" data-id="${s.id}" aria-expanded="${o}" aria-controls="x-${s.id}" aria-label="${o ? 'Hide' : 'Show'} classes for ${s.n}">${ic(o ? 'chevron-down' : 'chevron-right')}</button>
   <span class="xn"><a href="#" data-go="subject"><b>${s.n}</b></a><span class="tch">${avs(s.tch)}<span class="sub" style="display:inline">${n} classes</span></span></span>${prog(s.ent, n)}<span class="hm">${stat(s.ent, n)}</span><span class="hm xa">${s.avg && s.ent ? `Avg <b>${s.avg}%</b>` : '<span class="sub">–</span>'}</span>${menu('x' + s.id, ['Download all marksheets', 'Import from spreadsheet', 'Remind teachers'])}</div>
   ${o ? `<ul class="xc" id="x-${s.id}">${rows.map((r, i) => {
        const a = i < s.ent ? r[3] : i === s.ent ? Math.round(r[3] / 2) : 0;
        const t = tt(s.tch[i % s.tch.length]);
        return `<li><span style="min-width:0"><b class="trunc">${r[0]} · ${r[1]}</b><span class="tch">${av(t, 24)}<span class="sub trunc" style="display:inline">${t.fn} ${t.ln}</span></span></span>${a === r[3] ? `<span class="badge b-ok">Entered ${a}/${r[3]}</span>` : a ? `<span class="badge b-warn">${a} of ${r[3]}</span>` : '<span class="badge b-off">Not started</span>'}<span class="row-acts"><a class="btn${a === r[3] ? '' : ' pri'}" href="#">${a === r[3] ? 'View' : 'Enter marks'}</a>${menu('c' + s.id + i, ['Import from spreadsheet', 'Download marksheet', 'Remind teacher'])}</span></li>`;
      }).join('')}</ul>` : ''}</li>`;
    }).join('')}</ul>
  <p class="sub" style="margin:0">Language, Numbers and Reading (Nursery and Reception) have no Mid-term exam.</p>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/admin-mvp/screens-c.js", error: String((e && e.message) || e) }); }

// concepts/admin-mvp/screens-d.js
try { (() => {
// Batch two: shared add / edit person form (student, teacher, parent, staff)
(() => {
  const {
    SCHOOL,
    T,
    S,
    P,
    av,
    ic
  } = KD;
  const ST = window.ST;
  const f = (id, label, o = {}) => {
    const err = ST.state === 'error' && o.err;
    const v = ST.mode === 'edit' && o.v != null ? o.v : '';
    const ctl = o.type === 'select' ? `<select class="inp sel" id="${id}" ${err ? `aria-invalid="true" aria-describedby="${id}-e"` : ''}>${(o.opts || []).map((x, i) => `<option ${ST.mode === 'edit' && x === o.v || !i && o.ph ? 'selected' : ''}>${x}</option>`).join('')}</select>` : o.type === 'textarea' ? `<textarea class="inp" id="${id}" rows="3" ${o.hint ? `aria-describedby="${id}-h"` : ''}>${v}</textarea>` : `<input class="inp" id="${id}" type="${o.type || 'text'}" value="${err ? '' : v}" ${o.ac ? `autocomplete="${o.ac}"` : ''} ${o.ph2 ? `placeholder="${o.ph2}"` : ''} ${err ? `aria-invalid="true" aria-describedby="${id}-e"` : o.hint ? `aria-describedby="${id}-h"` : ''}>`;
    return `<div class="f${o.full ? ' full' : ''}${err ? ' err' : ''}"><label for="${id}">${label}${o.opt ? '<span class="opt">Optional</span>' : ''}</label>${ctl}${err ? `<span class="emsg" id="${id}-e">${ic('circle-alert', 'ic ic-sm')}${o.err}</span>` : o.hint ? `<span class="hint" id="${id}-h">${o.hint}</span>` : ''}</div>`;
  };
  const radios = (name, label, opts, o = {}) => `<fieldset class="f${o.full ? ' full' : ''}"><legend>${label}${o.opt ? '<span class="opt">Optional</span>' : ''}</legend><div class="radios">${opts.map((x, i) => `<label class="rc"><input type="radio" name="${name}" ${o.sel != null && i === o.sel ? 'checked' : ''}>${x}</label>`).join('')}</div>${o.hint ? `<span class="hint">${o.hint}</span>` : ''}</fieldset>`;
  const check = (label, on, hint) => `<label class="rc ck full"><input type="checkbox" ${on ? 'checked' : ''}><span>${label}${hint ? `<span class="hint" style="display:block">${hint}</span>` : ''}</span></label>`;
  const sec = (t, p, body) => `<section class="fsec"><h2>${t}</h2>${p ? `<p class="hint" style="margin:-8px 0 0">${p}</p>` : ''}<div class="fg">${body}</div></section>`;
  const nameRow = (p, err) => f('fn', 'First name', {
    ac: 'given-name',
    v: p && p.fn
  }) + f('ln', 'Last name', {
    ac: 'family-name',
    v: p && p.ln,
    err: err ? 'Enter a last name' : null
  });
  const photo = p => `<div class="f full"><span class="lab">Photo<span class="opt">Optional</span></span><div class="phrow">${p ? av(p, 64) : '<span class="av" style="width:64px;height:64px;background:#F1F5F9;color:#475569;border:1px dashed #94A3B8" aria-hidden="true">' + ic('camera') + '</span>'}<span style="display:flex;flex-direction:column;gap:4px"><a class="btn" href="#">${ic('upload')}${p ? 'Change photo' : 'Upload photo'}</a><span class="hint">Until there's a photo, KlassApp shows initials.</span></span></div></div>`;
  function invite(kind, edit) {
    if (edit) {
      const st = kind === 'parent' ? 'Opted in to WhatsApp on 12 Sep 2026' : 'Joined on 3 Sep 2026';
      return sec(kind === 'parent' ? 'WhatsApp and login' : 'Login', '', `<div class="f full"><div class="reqrow"><span class="badge b-ok">${st}</span>${kind === 'parent' ? '<a class="btn" href="#">Send a login invite by email</a>' : '<a class="btn" href="#">Reset password</a>'}</div></div>`);
    }
    if (kind === 'parent') return sec('Invite', 'Parents get report cards, fee balances and attendance on WhatsApp once they reply to the opt-in message.', check('Send a WhatsApp opt-in message after saving', true, 'Sent to the phone number above. They reply YES to start receiving messages.') + check('Also send a login invite by email', false, 'For parents who also want to use the web app. Needs an email address.'));
    return sec('Invite', 'They choose their own password. The invite link works for 72 hours.', radios('inv', 'Send an invite', ['By email', 'By WhatsApp', 'Not yet'], {
      sel: 0,
      full: 1,
      hint: 'You can send or resend it later from their profile.'
    }));
  }
  const SPEC = {
    student: {
      t: 'student',
      p: () => S[0],
      body: (p, e) => [sec('Student', '', nameRow(p, e) + radios('gender', 'Gender', ['Female', 'Male', 'Not specified'], {
        sel: ST.mode === 'edit' ? 0 : 2
      }) + f('dob', 'Date of birth', {
        type: 'date',
        opt: 1,
        v: '2015-03-14'
      }) + photo(ST.mode === 'edit' ? p : null)), sec('Class', '', f('cls', 'Class', {
        type: 'select',
        opts: ['Choose a class', 'Nursery', 'Reception', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'],
        ph: 1,
        v: 'Primary 5'
      }) + f('str', 'Stream', {
        type: 'select',
        opts: ['Choose a stream', 'Blue', 'Red'],
        ph: 1,
        v: 'Blue',
        hint: 'Shown only for classes with streams.'
      }) + f('jd', 'Joining date', {
        type: 'date',
        v: '2024-01-12',
        hint: ST.mode === 'edit' ? '' : 'Defaults to today.'
      }) + `<div class="f"><span class="lab">KLS number</span><span class="ro">${ST.mode === 'edit' ? p.kls : 'Created when you save'}</span><span class="hint">KLS + your school's number (${SCHOOL.no}) + a 4-digit sequence. It never changes.</span></div>`), ST.mode === 'edit' ? sec('Parents and guardians', '', `<div class="f full"><div class="reqrow"><span class="tch" style="display:flex;gap:10px;align-items:center">${av(P[0], 40)}<span><b>${P[0].fn} ${P[0].ln}</b><span class="sub">Mother · ${P[0].ph}</span></span></span><a class="btn" href="#">${ic('link')}Link another</a></div></div>`) : sec('Parent or guardian', 'Link an existing parent, or add a new one. Parents receive updates on WhatsApp.', `<div class="f full"><label for="ps">Find a parent already in KlassApp<span class="opt">Optional</span></label><span class="search" style="flex:none">${ic('search')}<input class="inp" id="ps" type="search" placeholder="Search by name or phone" style="padding-left:40px"></span></div><p class="or full">Or add a new parent</p>` + f('pfn', 'First name') + f('pln', 'Last name') + f('rel', 'Relationship to the student', {
        type: 'select',
        opts: ['Choose', 'Mother', 'Father', 'Guardian', 'Other'],
        ph: 1
      }) + f('pph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        err: e ? 'Enter a phone number so the parent can get WhatsApp updates' : null,
        hint: 'Include the country code.'
      }) + check('This number is on WhatsApp', true) + check('Send a WhatsApp opt-in message after saving', true)), sec('Health and support', 'Only admins and the head teacher can see this. Each view is logged.', f('al', 'Allergies or medical conditions', {
        type: 'textarea',
        opt: 1,
        full: 1,
        v: 'Peanuts (severe). Mild asthma.'
      }) + f('sn', 'Support needs', {
        type: 'textarea',
        opt: 1,
        full: 1,
        v: 'Sits near the front for hearing.'
      }))]
    },
    teacher: {
      t: 'teacher',
      p: () => T[0],
      body: (p, e) => [sec('Teacher', '', nameRow(p, e) + f('role', 'Role', {
        type: 'select',
        opts: ['Teacher', 'Head teacher', 'Deputy head teacher'],
        v: 'Teacher'
      }) + f('sno', 'Staff number', {
        opt: 1,
        hint: 'If your school uses one.'
      }) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph,
        hint: 'Include the country code.'
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        v: p.email,
        err: e ? 'Enter an email or a phone number to send the invite' : null
      }) + photo(ST.mode === 'edit' ? p : null)), sec('Teaching', 'You can also do this later from the class or subject page.', `<div class="f full"><span class="lab">Classes and subjects<span class="opt">Optional</span></span><ul class="assign">${(ST.mode === 'edit' ? [['Primary 5 · Blue', 'Mathematics'], ['Primary 5 · Blue', 'Science'], ['Primary 6 · Blue', 'Mathematics']] : [['Primary 5 · Blue', 'Mathematics']]).map((r, i) => `<li><select class="inp sel" aria-label="Class ${i + 1}"><option>${r[0]}</option></select><select class="inp sel" aria-label="Subject ${i + 1}"><option>${r[1]}</option></select><button class="btn icon" type="button" aria-label="Remove ${r[1]}, ${r[0]}">${ic('x')}</button></li>`).join('')}</ul><a class="btn" href="#" style="align-self:flex-start">${ic('plus')}Add a class and subject</a></div>` + f('ct', 'Class teacher of', {
        type: 'select',
        opt: 1,
        opts: ['None', 'Primary 5 · Blue', 'Primary 5 · Red', 'Primary 6 · Blue'],
        v: 'Primary 5 · Blue'
      })), invite('teacher', ST.mode === 'edit')]
    },
    parent: {
      t: 'parent',
      p: () => P[0],
      body: (p, e) => [sec('Parent or guardian', '', nameRow(p, e) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph,
        hint: 'Include the country code.'
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        opt: 1,
        v: p.email
      }) + check('This number is on WhatsApp', true) + photo(ST.mode === 'edit' ? p : null)), sec('Children', '', `<div class="f full"><label for="ks">Link a child</label><span class="search" style="flex:none">${ic('search')}<input class="inp" id="ks" type="search" placeholder="Search by name or KLS number" style="padding-left:40px"></span>${ST.state === 'error' ? `<span class="emsg">${ic('circle-alert', 'ic ic-sm')}Link at least one child</span>` : ''}<ul class="kids">${(ST.mode === 'edit' || ST.state !== 'error' ? p.kids : []).map(k => `<li>${av(k, 32)}<span style="min-width:0"><b>${k.fn} ${k.ln}</b><span class="sub">${k.cls} · ${k.kls}</span></span><select class="inp sel" aria-label="Relationship to ${k.fn}"><option>Mother</option><option>Father</option><option>Guardian</option><option>Other</option></select><button class="btn icon" type="button" aria-label="Unlink ${k.fn}">${ic('x')}</button></li>`).join('')}</ul></div>`), invite('parent', ST.mode === 'edit')]
    },
    staff: {
      t: 'staff member',
      p: () => ({
        id: 3310,
        fn: 'Ruth',
        ln: 'Kim',
        ph: '+000 700 100 120',
        email: 'r.kim@demojunior.school'
      }),
      body: (p, e) => [sec('Staff member', 'For non-teaching staff, such as the bursar or librarian. Their role decides what they can see.', nameRow(p, e) + f('role', 'Role', {
        type: 'select',
        opts: ['Choose a role', 'Bursar', 'Librarian', 'School admin', 'Office staff'],
        ph: 1,
        v: 'Bursar'
      }) + f('sno', 'Staff number', {
        opt: 1
      }) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        v: p.email
      }) + photo(ST.mode === 'edit' ? p : null))]
    }
  };
  window.scrForm = kind => {
    const k = SPEC[kind],
      p = k.p(),
      edit = ST.mode === 'edit',
      e = ST.state === 'error';
    const back = {
      student: 'students',
      teacher: 'teachers',
      parent: 'parents',
      staff: 'teachers'
    }[kind];
    const title = edit ? `Edit ${p.fn} ${p.ln}` : `Add ${k.t}`;
    const errs = e ? {
      student: ['Last name', 'Parent phone'],
      teacher: ['Last name', 'Email'],
      parent: ['Last name', 'Children'],
      staff: ['Last name']
    }[kind] : null;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="${back}">${back === 'teachers' ? 'Teachers and staff' : back[0].toUpperCase() + back.slice(1)}</a> <span aria-hidden="true">›</span> ${edit ? p.fn + ' ' + p.ln : 'Add'}</nav>
  <div class="ph"><div><h1>${title}</h1><p>${edit ? 'Changes save when you press Save.' : 'Fields are required unless marked Optional.'}</p></div></div>
  <form class="form" novalidate onsubmit="return false">${errs ? `<div class="errsum" role="alert" tabindex="-1"><b>${errs.length} things need fixing</b><ul>${errs.map(x => `<li><a href="#">${x}</a></li>`).join('')}</ul></div>` : ''}
  ${k.body(p, e).join('')}
  <div class="fbar"><a class="btn" href="#" data-go="${back}">Cancel</a>${edit ? '' : '<button class="btn" type="button">Save and add another</button>'}<button class="btn pri" type="submit">${edit ? 'Save changes' : kind === 'student' ? 'Add student' : `Save${kind === 'parent' ? ' and send opt-in' : kind === 'teacher' ? ' and send invite' : ''}`}</button></div></form>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/admin-mvp/screens-d.js", error: String((e && e.message) || e) }); }

// concepts/quickstart/doc-page.js
try { (() => {
// @ds-adherence-ignore -- omelette starter scaffold (raw elements/hex/px by design)
// Copied omelette starter. Re-running copy_starter_component with this kind overwrites this file with the latest version (page content is unaffected).
/* BEGIN USAGE */
/**
 * <doc-page> — paged-document shell for printable HTML.
 *
 * FIRST, decide how the document paginates — up front, before building:
 *
 * - FLOWING document (the default): write the whole document as one
 *   normal HTML flow inside <doc-page>; the browser's print engine
 *   splits it onto pages at export. Use for long-form documents with a
 *   single text flow: reports, memos, letters, essays.
 * - EXPLICIT pagination: a fixed set of pre-paginated pages, one
 *   <section class="page"> child per page. Use when the user asks for a
 *   specific page count, or the design implies one: a one-page resume, a
 *   two-sided flier, a poster, a certificate, a brochure — any richly
 *   laid-out document without a single text flow.
 * - If in doubt, ask the user as part of the build.
 *
 * PAGE SIZING — paper differs by country (letter vs A4), so the printed
 * sheet is not one fixed truth:
 * - FLOWING documents pin NO paper size: the print engine paginates
 *   onto the user's real paper, and the content reflows to it.
 * - EXPLICITLY PAGINATED documents print each page at a FIXED page box
 *   with overflow hidden — letter by default, size="a4" for a clearly
 *   metric user, the user's chosen paper when they export. Design each
 *   page to FILL that box, fitting letter and A4 alike without overlap.
 * - width/height pin an explicit fixed size, ONLY when the user gives
 *   one.
 * Never write your own @page rule or hard-code paper dimensions in the
 * content.
 *
 * Sizing modes (attributes):
 *   (none)                      — portrait: flowing docs use the user's
 *           paper; explicitly paginated pages use the named size box
 *           (letter unless size="a4")
 *   orientation="landscape"     — the same, landscape
 *   width / height              — explicit fixed size, ONLY when the user
 *           gives one (e.g. width="22in" height="30in" for a 22×30
 *           poster): the page IS the design's size, printed at true
 *           dimensions (or scaled onto the user's paper at print time).
 *           Any absolute CSS length: px/in/mm/cm/pt/pc.
 * The component announces the chosen mode to the host app at runtime (a
 * meta tag it injects), so the print path can inject the user's true
 * paper size.
 *
 * On screen the document renders on a desk background: a flowing
 * document as one tall scrolling sheet (Google Docs' pageless view);
 * explicitly paginated documents as one card per page.
 *
 * EXPLICIT pagination usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page>
 *     <section class="page" id="p1">…one page's design…</section>
 *     <section class="page" id="p2">…</section>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * How the page box works, concretely: each .page prints as ONE full-bleed
 * sheet at a FIXED physical size — letter by default (set size="a4" for
 * a clearly metric user), the user's chosen paper when they export —
 * with overflow hidden. Nothing scrolls and nothing reflows onto a next
 * sheet: content that misses the box is CLIPPED. Design each page to
 * FILL that page box, and to fit it — letter and A4 alike — without
 * overlap. Each page is a size container; don't size anything in
 * viewport units (they track the window, not the page), and never set
 * width or height on the .page section itself (the component sizes the
 * page box; an authored height like 100% is meaningless at print and is
 * overridden). The component owns the page box, the screen card chrome,
 * and the page breaks (never add your own break-before/after). Don't mix
 * .page sections with flowing content or header/footer slots in the same
 * document.
 *
 * FLOWING usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page margin="0.75in">
 *     <h1>Title</h1>
 *     <p>…body…</p>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * There is no manual page-splitting — the browser's print engine
 * paginates at export. Standard break-hygiene rules (`break-inside:
 * avoid` on figures, code blocks, images and table rows; `orphans/
 * widows: 3`) are applied so paragraphs and groups split cleanly. On
 * screen and at print, headings default to `text-wrap: balance` and
 * body text to `text-wrap: pretty`; the defaults have zero specificity,
 * so any text-wrap you declare wins.
 *
 * Other attributes:
 *   size    — letter | a4 | legal (default letter). Flowing documents:
 *           preview proportion only — it does NOT pin their printed
 *           paper (the print dialog's paper governs); leave it alone
 *           there. Explicitly paginated documents: it sets the page box
 *           the cards and the pinned @page share (the export dialog's
 *           choice overrides both at print) — set size="a4" for a
 *           clearly metric user. Scaled-fit: names the sheet the fit is
 *           computed against, same a4-for-metric-users advice.
 *   content-width / content-height — the design's own fixed dimensions
 *           (CSS lengths), for scaling a fixed-size design ONTO the
 *           named sheet: content lays out at exactly this size, and the
 *           component scales it to fit that sheet's printable area
 *           (centered horizontally, top-aligned; the export dialog
 *           re-fits to the user's actual paper choice where available).
 *           Both must be set; they do not change the page box. For pages
 *           WITHOUT running header/footer slots.
 *   margin  — printable inset on every page of a FLOWING document
 *           (default 0.75in); margin="0" makes pages full-bleed.
 *           Explicitly paginated pages are always full-bleed.
 *
 * Running header/footer (flowing documents only): give an element
 * `slot="header"` or `slot="footer"` and it repeats on every printed
 * page via `position: fixed`. To keep body text from sliding under it,
 * the component prints inside a single-cell table whose <thead>/<tfoot>
 * are spacers sized to the header/footer height — browsers repeat
 * thead/tfoot on every page, so each sheet's content starts below the
 * header and ends above the footer. On screen the header/footer render
 * once at the top/bottom of the sheet.
 *
 * At print the component injects `@page { margin: 0 }` (which leaves
 * Chrome no margin box to draw its date/URL/page-count header in) and
 * moves the visual margin onto the sheet's own padding. It also marks
 * the document as owning its print CSS (a
 * `meta[name="omelette-owns-print"]` it injects at runtime), so the
 * PDF export never injects page-geometry CSS of its own on top.
 *
 * Print best practices for the content you author:
 * - Multi-column text: use CSS columns (`column-count` +
 *   `column-gap`), never side-by-side flex/grid columns — only real
 *   CSS columns flow and break across pages. `column-span: all` lets
 *   a heading span the columns; `hyphens: auto` (needs `lang` on
 *   the html element) keeps narrow columns readable.
 * - Page breaks in flowing documents: `break-before: page` on an
 *   element that must start a new page (a chapter, an appendix). Add
 *   your own kept-together blocks (callouts, stat tiles, cards) to a
 *   `break-inside: avoid` rule, and keep each one shorter than a page.
 * - Extend `orphans: 3; widows: 3` to any custom text blocks you add
 *   (p and li are covered by default).
 * - Give long tables a <thead> — browsers repeat it on every printed
 *   page.
 * - No `position: fixed`/`sticky` and no viewport units in content:
 *   fixed elements stamp every printed page (running headers/footers go
 *   in the component's slots) and `100vh` mis-sizes at print.
 *
 * Author content as static HTML so the user can click-to-edit any text
 * directly. Do not set width/padding/background on the document body —
 * the component owns the sheet box.
 */
/* END USAGE */

(() => {
  const PAPER = {
    letter: ['8.5in', '11in'],
    a4: ['210mm', '297mm'],
    legal: ['8.5in', '14in']
  };
  const CSS_LENGTH = /^\d+(\.\d+)?(px|in|mm|cm|pt|pc)$/;
  // Unitless "0" is a valid CSS length and the natural way to write
  // margin="0"; normalise it to 0px so max()/calc() (which reject a bare
  // number) keep working.
  const safeLen = (v, fb) => {
    v = (v || '').trim();
    return v === '0' ? '0px' : CSS_LENGTH.test(v) ? v : fb;
  };
  // WebKit (Safari and every iOS browser shell) never repeats a table's
  // thead/tfoot on printed pages (WebKit bug 17205), so the spacer-borne
  // vertical margins of a FLOWING document reach only the first page
  // there. Engine check, not browser check: vendor is 'Apple Computer,
  // Inc.' exactly for WebKit and 'Google Inc.' for Blink.
  const WK_PRINT = /apple/i.test(navigator.vendor || '');
  // CSS length → px number (CSS absolute units are exact: 1in = 96px).
  // Returns NaN for anything safeLen would reject — callers gate on it.
  const PX_PER = {
    px: 1,
    in: 96,
    mm: 96 / 25.4,
    cm: 96 / 2.54,
    pt: 96 / 72,
    pc: 16
  };
  const toPx = v => {
    const m = /^(\d+(?:\.\d+)?)(px|in|mm|cm|pt|pc)$/.exec((v || '').trim());
    return m ? parseFloat(m[1]) * PX_PER[m[2]] : NaN;
  };
  const stylesheet = `
    :host {
      position: relative;
      display: block;
      /* When the viewport is narrower than the page, grow to wrap the
       * sheet (plus this padding) instead of staying viewport-width, so
       * the desk background and right margin reach the sheet's far edge
       * in the horizontal scroll. */
      min-width: max-content;
      min-height: 100vh;
      background: #f5f5f4;
      padding: 48px 24px;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
      --doc-page-w: 8.5in;
      --doc-page-h: 11in;
      --doc-page-margin: 0.75in;
      --doc-hdr-h: 0px;
      --doc-ftr-h: 0px;
      --doc-hdr-pad: 0px;
      --doc-ftr-pad: 0px;
    }
    .sheet {
      width: var(--doc-page-w);
      margin: 0 auto;
      background: #fff;
      box-shadow: 0 2px 10px rgba(20, 20, 19, 0.12);
      border-radius: 7px;
      box-sizing: border-box;
      padding: var(--doc-page-margin);
    }
    .frame { width: 100%; border-collapse: collapse; }
    /* Scaled-fit mode (content-width/content-height): the inner .fit box
     * lays the content out at its authored fixed size and scales it onto
     * the printable area; .fit-box reserves the scaled footprint in flow
     * (transforms don't affect layout) and centers it. Without the mode,
     * both divs are unstyled block pass-throughs. */
    /* Explicit pagination: direct .page children are the pages. The sheet
     * becomes a transparent stack and each page carries the card look on
     * screen; at print each page is exactly one full-bleed sheet. The
     * ::slotted defaults are deliberately weak (document CSS wins), so
     * authored page styling can override any of this. */
    .sheet.paginated {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      padding: 0;
    }
    .paginated ::slotted(.page) {
      position: relative;
      display: block;
      width: 100%;
      aspect-ratio: var(--doc-page-ar);
      container-type: size;
      overflow: hidden;
      box-sizing: border-box;
      background: #fff;
      border-radius: 7px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
      print-color-adjust: exact;
      -webkit-print-color-adjust: exact;
      break-inside: avoid;
    }
    .paginated ::slotted(.page:not(:first-child)) { margin-top: 1rem; }
    @media print {
      .sheet.paginated { padding: 0; }
      /* The flowing-document vertical inset lives on the repeating
       * thead/tfoot spacers, not the sheet padding — they must go too,
       * or each full-sheet .page is pushed ~margin down and spills onto
       * a second sheet. Paginated pages are full-bleed by definition
       * (content owns its insets). */
      .sheet.paginated .hdr-space,
      .sheet.paginated .ftr-space { height: 0; }
      .paginated ::slotted(.page) {
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        /* Physical page-box sizing, no viewport units: Safari resolves
         * 100vh against the window, not the page box, so a vh-sized card
         * paginates wrong there. --doc-page-w/h are the named size by
         * default and are overridden to the user's chosen paper by the
         * export path, so every card is exactly one sheet either way.
         * Width + height (same source values as @page size) rather than
         * width + aspect-ratio: the ratio is a 6-decimal rounding of the
         * same division, and a few millionths of overflow would spill a
         * blank sheet after every page. The screen-only aspect-ratio
         * (preview proportions) must not leak into print. cqh typography
         * tracks the same box.
         *
         * Every declaration is !important: per CSS Scoping, unimportant
         * shadow ::slotted rules LOSE to the document context, so a page
         * section's authored inline style would silently beat this print
         * geometry. A model-authored height:100% did exactly that — the
         * percentage resolves as auto in the all-auto print ancestry, the
         * base rule's size containment turns auto into ZERO, and
         * overflow:hidden then paints nothing: a blank PDF with perfect
         * page boxes. At print the component's geometry is the design's
         * whole contract, so it must win over any authored sizing. */
        aspect-ratio: auto !important;
        width: var(--doc-page-w) !important;
        height: var(--doc-page-h) !important;
        overflow: hidden !important;
      }
      .paginated ::slotted(.page:not(:first-child)) {
        break-before: page !important;
        margin-top: 0 !important;
      }
    }
    .fit-mode .fit-box {
      width: calc(var(--doc-fit-w) * var(--doc-fit-scale));
      height: calc(var(--doc-fit-h) * var(--doc-fit-scale));
      margin: 0 auto;
      break-inside: avoid;
    }
    /* Monolithic at print: Blink slices a transform-scaled child at
     * fragmentainer boundaries mapped in UNSCALED layout coordinates
     * (transforms are paint-time), so the .fit box (authored size, e.g.
     * 1400x990) gets cut at the page's free block space and spills onto
     * a second sheet even though its SCALED footprint fits the page by
     * construction. overflow:hidden makes .fit-box a scroll container —
     * monolithic under fragmentation (css-break-3) — so the scaled
     * content prints atomically on one sheet. No clipping for content
     * within the authored box: .fit-box is calc-sized to exactly the
     * scaled footprint. (Content that bleeds past content-width/height
     * is clipped at the footprint — fit mode's contract; it previously
     * painted beyond it at print.) Print-only, so the screen rendering
     * keeps visible overflow for editor affordances.
     * The export path injects the same rule into frozen copies
     * (print-eval.ts om-print-fit-contain). The .fit-mode scope is
     * load-bearing: .fit-box wraps slotted content in EVERY mode, and an
     * unscoped overflow:hidden would make whole flowing documents
     * monolithic (one truncated sheet). overflow:hidden, never clip —
     * clip is not a scroll container, so not monolithic. */
    @media print {
      .fit-mode .fit-box { overflow: hidden; }
    }
    .fit-mode .fit {
      width: var(--doc-fit-w);
      height: var(--doc-fit-h);
      transform: scale(var(--doc-fit-scale));
      transform-origin: top left;
    }
    .frame td, .frame th { padding: 0; text-align: left; font-weight: inherit; }
    .hdr-space { height: var(--doc-hdr-h); }
    .ftr-space { height: var(--doc-ftr-h); }
    ::slotted([slot="header"]),
    ::slotted([slot="footer"]) { display: block; box-sizing: border-box; }
    @media print {
      :host { background: none; padding: 0; min-width: 0; min-height: 0; }
      .sheet {
        width: auto; margin: 0; box-shadow: none; border-radius: 0;
        padding: 0 var(--doc-page-margin);
      }
      /* The thead/tfoot spacers repeat on every page, so they carry the
       * vertical page margin (which the sheet's own padding cannot, since
       * that padding is consumed once on the first/last page). The running
       * header/footer are fixed inside that band. */
      /* The 0.35in is breathing room between a running header/footer and
       * the body; without one the spacer is exactly the page margin, so a
       * margin="0" full-bleed document gets truly full-bleed pages. */
      .hdr-space { height: max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))); }
      .ftr-space { height: max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))); }
      /* WebKit flowing documents: @page carries the vertical margin (see
       * _syncPrintPageRule), so the spacers keep only whatever a running
       * header/footer needs BEYOND it — page 1 would otherwise double its
       * top inset. Paginated sheets already zero their spacers above. */
      .sheet.wk-print:not(.paginated) .hdr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))) - var(--doc-page-margin))); }
      .sheet.wk-print:not(.paginated) .ftr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))) - var(--doc-page-margin))); }
      ::slotted([slot="header"]) {
        position: fixed; top: 0; left: 0; right: 0; margin: 0;
        padding: calc(var(--doc-page-margin) * 0.45) var(--doc-page-margin) 0;
      }
      ::slotted([slot="footer"]) {
        position: fixed; bottom: 0; left: 0; right: 0; margin: 0;
        padding: 0 var(--doc-page-margin) calc(var(--doc-page-margin) * 0.45);
      }
    }
  `;
  class DocPage extends HTMLElement {
    static get observedAttributes() {
      return ['size', 'width', 'height', 'margin', 'orientation', 'content-width', 'content-height'];
    }
    constructor() {
      super();
      this._root = this.attachShadow({
        mode: 'open'
      });
      this._mo = typeof MutationObserver === 'function' ? new MutationObserver(() => this._scheduleMeasure()) : null;
    }

    /** The named paper's [w, h], swapped when orientation="landscape".
     *  Only the named size swaps — explicit width/height are exact values
     *  the author already oriented. */
    _paperSize() {
      const named = PAPER[(this.getAttribute('size') || '').toLowerCase()] || PAPER.letter;
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? [named[1], named[0]] : named;
    }
    get pageWidth() {
      return safeLen(this.getAttribute('width'), this._paperSize()[0]);
    }
    get pageHeight() {
      return safeLen(this.getAttribute('height'), this._paperSize()[1]);
    }
    get pageMargin() {
      return safeLen(this.getAttribute('margin'), '0.75in');
    }

    /** Scaled-fit mode's content box [w, h] as CSS lengths, or null when
     *  the mode is off (either attribute missing/invalid/zero — a partial
     *  declaration falls back to normal flow rather than guessing). */
    _contentFit() {
      const w = safeLen(this.getAttribute('content-width'), null);
      const h = safeLen(this.getAttribute('content-height'), null);
      if (!w || !h) return null;
      const wPx = toPx(w),
        hPx = toPx(h);
      return wPx > 0 && hPx > 0 ? [w, h, wPx, hPx] : null;
    }
    connectedCallback() {
      if (!this._sheet) this._render();
      this._syncSize();
      this._syncPrintPageRule();
      this._ensureTextWrapDefaults();
      this._ensureOwnsPrintMeta();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      if (this._mo) this._mo.observe(this, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true
      });
      this._onResize = () => this._scheduleMeasure();
      window.addEventListener('resize', this._onResize);
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => this._scheduleMeasure());
      }
      this._scheduleMeasure();
    }
    disconnectedCallback() {
      window.removeEventListener('resize', this._onResize);
      if (this._mo) this._mo.disconnect();
      if (this._raf) {
        cancelAnimationFrame(this._raf);
        this._raf = null;
      }
      // Drop the head rules when the last doc-page leaves, so a deleted
      // document's @page geometry and text-wrap defaults can't apply to
      // whatever replaces it.
      const survivor = document.querySelector('doc-page');
      if (!survivor) {
        ['doc-page-print', 'doc-page-text-wrap', 'doc-page-owns-print', 'doc-page-fixed-size', 'doc-page-print-sizing'].forEach(id => {
          const tag = document.getElementById(id);
          if (tag) tag.remove();
        });
        // A live deck-stage deferred its own print-sizing meta to ours —
        // hand the page-global meta over so the deck isn't left unmarked.
        const deck = document.querySelector('deck-stage');
        if (deck && typeof deck._ensurePrintSizingMeta === 'function') {
          deck._ensurePrintSizingMeta();
        }
      } else {
        // A departed owner hands each page-global meta to whatever
        // doc-page remains (or it's removed).
        if (typeof survivor._syncFixedSizeMeta === 'function') {
          survivor._syncFixedSizeMeta();
        }
        if (typeof survivor._syncPrintSizingMeta === 'function') {
          survivor._syncPrintSizingMeta();
        }
      }
    }
    attributeChangedCallback() {
      if (!this._sheet) return;
      this._syncSize();
      this._syncPrintPageRule();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      this._scheduleMeasure();
    }
    _render() {
      this._root.innerHTML = `
        <style>${stylesheet}</style>
        <style id="vars"></style>
        <div class="sheet" data-screen-label="Document">
          <table class="frame" role="presentation">
            <thead><tr><th><div class="hdr-space"><slot name="header"></slot></div></th></tr></thead>
            <tbody><tr><td class="body"><div class="fit-box"><div class="fit"><slot></slot></div></div></td></tr></tbody>
            <tfoot><tr><td><div class="ftr-space"><slot name="footer"></slot></div></td></tr></tfoot>
          </table>
        </div>`;
      this._sheet = this._root.querySelector('.sheet');
      this._vars = this._root.getElementById('vars');
    }

    /** Runtime sizing lives in a shadow <style> :host rule, never on the
     *  light-DOM host element, so serialize-persist can't write it back. */
    _syncSize(hdrH, ftrH) {
      // Scaled-fit mode: content at its authored size, scaled onto the
      // printable area (page minus margins on both axes). The factor is a
      // plain number var so calc(length * number) stays valid; 4 decimals
      // keeps the shadow style stable across re-measures. Upscaling is
      // allowed — print transforms are vector, so text and CSS stay crisp
      // (raster images soften, which the catalog bullet warns about).
      const fit = this._contentFit();
      let fitVars = '';
      if (fit) {
        const marginPx = toPx(this.pageMargin) || 0;
        const availW = toPx(this.pageWidth) - 2 * marginPx;
        const availH = toPx(this.pageHeight) - 2 * marginPx;
        const scale = Math.min(availW / fit[2], availH / fit[3]);
        if (scale > 0 && Number.isFinite(scale)) {
          fitVars = '--doc-fit-w:' + fit[0] + ';' + '--doc-fit-h:' + fit[1] + ';' + '--doc-fit-scale:' + scale.toFixed(4) + ';';
        }
      }
      this._sheet.classList.toggle('fit-mode', !!fitVars);
      // Numeric w/h ratio for the paginated page cards' aspect-ratio —
      // aspect-ratio takes a number, not a length ratio, so compute it
      // here (CSS length division isn't portable). 6 decimals keeps the
      // shadow style stable across re-syncs.
      const arW = toPx(this.pageWidth);
      const arH = toPx(this.pageHeight);
      const ar = arW > 0 && arH > 0 ? (arW / arH).toFixed(6) : '0.772727';
      this._vars.textContent = ':host{' + fitVars + '--doc-page-ar:' + ar + ';' + '--doc-page-w:' + this.pageWidth + ';' + '--doc-page-h:' + this.pageHeight + ';' + '--doc-page-margin:' + this.pageMargin + ';' + '--doc-hdr-h:' + (hdrH || 0) + 'px;' + '--doc-ftr-h:' + (ftrH || 0) + 'px;' + '--doc-hdr-pad:' + (hdrH ? '0.35in' : '0px') + ';' + '--doc-ftr-pad:' + (ftrH ? '0.35in' : '0px') + '}';
    }

    /** @page is a no-op inside shadow DOM, so the rule lives in <head>.
     *  Re-appended on every sync so it stays last in source order — the
     *  @page cascade is source-order per descriptor, so this rule wins
     *  over any other @page rule in the document.
     *
     *  The @page SIZE is pinned where the page box IS part of the design:
     *  explicit-fixed-size mode (width + height authored), scaled-fit
     *  mode (the named sheet the fit targets), and explicit pagination
     *  (the named size the cards share — so card and sheet agree on
     *  every print path, and the export path's chosen paper overrides
     *  BOTH with one later rule). For FLOWING documents no paper size is
     *  emitted at all — the true size comes from the user's preference,
     *  injected by the export path or chosen in the print dialog — so a
     *  flowing document never fights the paper it lands on.
     *  margin: 0 is emitted in every mode: it leaves Chrome no margin box
     *  to draw its date/URL/page-count header in, and the visual margin
     *  lives on the sheet's own padding. */
    _syncPrintPageRule() {
      const id = 'doc-page-print';
      let tag = document.getElementById(id);
      if (!tag) {
        tag = document.createElement('style');
        tag.id = id;
      }
      document.head.appendChild(tag);
      // Three print-geometry regimes:
      // - true-size: the page IS the design — pin its exact size.
      // - scaled-fit (content-width/height): the fit factor is computed
      //   against the NAMED paper's printable area, so that paper must
      //   stay pinned or the scaled content overflows a smaller sheet
      //   (the export path re-fits and re-pins at print time on top).
      // - default modes: no paper size — but landscape still needs the
      //   paper-agnostic 'size: landscape' keyword, because the size
      //   descriptor is what carries orientation; without it a landscape
      //   document prints portrait whenever nothing injects a size.
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      // Explicit pagination pins the page box to the SAME values that
      // size the cards (the named size by default, the export path's
      // chosen paper when its later rule overrides both) — card and
      // sheet agree on every print path, and a mismatched real paper
      // shrinks-to-fit in the dialog instead of clipping a Letter card
      // on A4. Declared before the paginated read below so both derive
      // from one check.
      const paginatedNow = this.querySelector(':scope > .page') !== null;
      const sizeDescriptor = this._trueSizePx() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : this._contentFit() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : paginatedNow ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : landscape ? 'size: landscape; ' : '';
      // WebKit never repeats the thead/tfoot spacers that carry a flowing
      // document's vertical page margins (see WK_PRINT above), so pages
      // after the first print edge-to-edge there. Carry the VERTICAL
      // margins on @page for WebKit instead, and the shadow print CSS
      // trims the first-page spacers by the same amount (.sheet.wk-print
      // rules). Horizontal inset stays on the sheet's own padding in
      // every engine. Blink keeps margin: 0 (a nonzero margin there
      // re-opens the box Chrome draws its header furniture in). One cost,
      // learned in testing: Safari's own date/URL headers are a USER
      // dialog setting ("Print headers and footers") that renders in the
      // margin area when room exists — margin: 0 only suppressed it by
      // leaving no room, and no CSS controls it. The export dialog's
      // Safari guide teaches turning the setting off for flowing
      // documents. Explicitly paginated and fixed-size documents keep
      // margin: 0 everywhere: their pages ARE the sheet.
      const wkFlowing = WK_PRINT && !paginatedNow && !this._trueSizePx() && !this._contentFit();
      const marginDescriptor = wkFlowing ? 'margin: ' + this.pageMargin + ' 0; ' : 'margin: 0; ';
      // Shadow-internal marker (never serialized), kept in lockstep with
      // the @page decision above: the print CSS trims the first-page
      // spacers ONLY while @page actually carries the margins — a
      // true-size or scaled-fit sheet keeps margin: 0 and must keep its
      // spacers too. Re-synced here so attribute changes and pagination
      // flips move both together.
      if (this._sheet) this._sheet.classList.toggle('wk-print', wkFlowing);
      tag.textContent = '@page { ' + sizeDescriptor + marginDescriptor + '} ' + '@media print { html, body { margin: 0 !important; padding: 0 !important; background: none !important; height: auto !important; overflow: visible !important; } ' + 'h1,h2,h3,h4,h5,h6 { break-after: avoid; } ' + 'figure,pre,blockquote,img,svg,tr { break-inside: avoid; } ' + 'p,li { orphans: 3; widows: 3; } ' + '* { -webkit-print-color-adjust: exact; print-color-adjust: exact; ' + 'backdrop-filter: none !important; -webkit-backdrop-filter: none !important; } ' + '*, *::before, *::after { animation-delay: -99s !important; animation-duration: .001s !important; ' + 'animation-iteration-count: 1 !important; animation-fill-mode: both !important; ' + 'animation-play-state: running !important; transition-duration: 0s !important; } }';
    }

    /** Typographic defaults for document text: balance headings, avoid
     *  widowed/orphaned words in body copy (browsers without text-wrap
     *  support drop the declarations). Zero-specificity via :where() so
     *  any text-wrap authored on those elements wins; document-level so the
     *  rules reach the slotted (light DOM) content — shadow styles can't.
     *  data-omelette-injected marks the tag for the host editor to strip
     *  at serialize, so it is never written back as authored source. */
    _ensureTextWrapDefaults() {
      if (document.getElementById('doc-page-text-wrap')) return;
      const tag = document.createElement('style');
      tag.id = 'doc-page-text-wrap';
      tag.setAttribute('data-omelette-injected', '');
      tag.textContent = ':where(h1,h2,h3,h4,h5,h6){text-wrap:balance}' + ':where(p,li,blockquote,figcaption){text-wrap:pretty}';
      document.head.appendChild(tag);
    }

    /** Declares that this document owns its print CSS. The instant-PDF
     *  export checks for the meta by NAME PRESENCE alone (content is
     *  ignored) and skips its automatic print-CSS injections, so the
     *  component's @page geometry is never overridden by a heuristic.
     *  data-omelette-injected keeps it out of serialized source. */
    _ensureOwnsPrintMeta() {
      if (document.getElementById('doc-page-owns-print')) return;
      const tag = document.createElement('meta');
      tag.id = 'doc-page-owns-print';
      tag.name = 'omelette-owns-print';
      tag.content = 'true';
      tag.setAttribute('data-omelette-injected', '');
      document.head.appendChild(tag);
    }

    /** This page's valid true-size page box (explicit width AND height)
     *  as [w, h] px ints, or null when the mode is off. */
    _trueSizePx() {
      if (!safeLen(this.getAttribute('width'), null) || !safeLen(this.getAttribute('height'), null)) return null;
      const w = Math.round(toPx(this.pageWidth));
      const h = Math.round(toPx(this.pageHeight));
      return w > 0 && h > 0 ? [w, h] : null;
    }

    /** True-size pages (explicit width AND height) also declare the page
     *  box as the preview size: the in-app preview reads
     *  meta[name="omelette-fixed-size"] (content "W,H" in px ints) and
     *  scales the sheet into view — without it an 18in poster previews at
     *  true size with scrollbars. Never overrides an author-set meta
     *  (only the component's own id is managed). The meta is page-global
     *  while doc-page instances are not, so every sync recomputes the
     *  page-wide owner — the first connected true-size doc-page — and a
     *  non-true-size sibling's sync can never delete the owner's meta.
     *  Removed when no true-size page remains (the owner's disconnect
     *  re-syncs via any survivor) or when an author-set meta exists. */
    _syncFixedSizeMeta() {
      const id = 'doc-page-fixed-size';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-fixed-size"]:not([data-omelette-injected])');
      // The page-wide owner, not this instance: an upgraded true-size page
      // anywhere in the document keeps the meta alive and sized.
      let box = null;
      for (const el of document.querySelectorAll('doc-page')) {
        box = typeof el._trueSizePx === 'function' ? el._trueSizePx() : null;
        if (box) break;
      }
      if (!box || authored) {
        if (own) own.remove();
        return;
      }
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-fixed-size';
      tag.content = box[0] + ',' + box[1];
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }

    /** This page's print-sizing mode: 'fixed' when an explicit width AND
     *  height are authored (the page is the design's own size), else the
     *  default paper in the authored orientation. */
    _printSizingMode() {
      if (this._trueSizePx()) return 'fixed';
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? 'default-landscape' : 'default-portrait';
    }

    /** Announces the print-sizing mode to the host app:
     *  meta[name="omelette-print-sizing"] with content 'default-portrait',
     *  'default-landscape', or 'fixed' (fixed pages also carry the
     *  omelette-fixed-size meta with the page box in px). The export path
     *  probes it to decide what true paper size to inject at print time —
     *  in the default modes the component emits no paper size of its own.
     *  Same page-global ownership rules as the fixed-size meta above:
     *  first connected doc-page owns it, an authored meta is never
     *  overridden, removed when no doc-page remains. */
    _syncPrintSizingMeta() {
      const id = 'doc-page-print-sizing';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-print-sizing"]:not([data-omelette-injected])');
      // A fixed page wins outright (mirroring the fixed-size loop above,
      // so the two metas can never contradict each other in a mixed
      // multi-page document); otherwise the first page's mode holds.
      let mode = null;
      for (const el of document.querySelectorAll('doc-page')) {
        if (typeof el._printSizingMode !== 'function') continue;
        const m = el._printSizingMode();
        if (m === 'fixed') {
          mode = m;
          break;
        }
        if (mode === null) mode = m;
      }
      if (!mode || authored) {
        if (own) own.remove();
        return;
      }
      // A deck-stage that connected first injected its own meta and
      // defers to any existing one — take it over, or the document ends
      // up with two conflicting injected metas (a doc-page page is the
      // document; the deck re-ensures its meta if every doc-page leaves).
      const deckMeta = document.getElementById('deck-stage-print-sizing');
      if (deckMeta) deckMeta.remove();
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-print-sizing';
      tag.content = mode;
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }
    _scheduleMeasure() {
      if (this._raf) return;
      this._raf = requestAnimationFrame(() => {
        this._raf = null;
        this._measure();
      });
    }

    /** Slot heights feed the print spacers (--doc-hdr-h / --doc-ftr-h), so
     *  they re-measure on content mutation, resize, and font load. The
     *  same pass detects explicit pagination (direct .page children) and
     *  toggles the sheet between the flowing-document card and the
     *  page-per-card stack — content edits can add or remove pages at any
     *  time, so this tracks the same mutations the measurement does. */
    _measure() {
      const hdr = this.querySelector(':scope > [slot="header"]');
      const ftr = this.querySelector(':scope > [slot="footer"]');
      const wasPaginated = this._sheet.classList.contains('paginated');
      this._sheet.classList.toggle('paginated', this.querySelector(':scope > .page') !== null);
      // The WebKit @page margin is flowing-only, so a pagination flip
      // must re-emit the rule (content edits can add or remove .page
      // sections at any time).
      if (this._sheet.classList.contains('paginated') !== wasPaginated) {
        this._syncPrintPageRule();
      }
      this._syncSize(hdr ? hdr.offsetHeight : 0, ftr ? ftr.offsetHeight : 0);
    }
  }
  if (!customElements.get('doc-page')) {
    customElements.define('doc-page', DocPage);
  }
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "concepts/quickstart/doc-page.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-09-29-docs/design-system/concepts/quickstart/doc-page.js
try { (() => {
// @ds-adherence-ignore -- omelette starter scaffold (raw elements/hex/px by design)
// Copied omelette starter. Re-running copy_starter_component with this kind overwrites this file with the latest version (page content is unaffected).
/* BEGIN USAGE */
/**
 * <doc-page> — paged-document shell for printable HTML.
 *
 * FIRST, decide how the document paginates — up front, before building:
 *
 * - FLOWING document (the default): write the whole document as one
 *   normal HTML flow inside <doc-page>; the browser's print engine
 *   splits it onto pages at export. Use for long-form documents with a
 *   single text flow: reports, memos, letters, essays.
 * - EXPLICIT pagination: a fixed set of pre-paginated pages, one
 *   <section class="page"> child per page. Use when the user asks for a
 *   specific page count, or the design implies one: a one-page resume, a
 *   two-sided flier, a poster, a certificate, a brochure — any richly
 *   laid-out document without a single text flow.
 * - If in doubt, ask the user as part of the build.
 *
 * PAGE SIZING — paper differs by country (letter vs A4), so the printed
 * sheet is not one fixed truth:
 * - FLOWING documents pin NO paper size: the print engine paginates
 *   onto the user's real paper, and the content reflows to it.
 * - EXPLICITLY PAGINATED documents print each page at a FIXED page box
 *   with overflow hidden — letter by default, size="a4" for a clearly
 *   metric user, the user's chosen paper when they export. Design each
 *   page to FILL that box, fitting letter and A4 alike without overlap.
 * - width/height pin an explicit fixed size, ONLY when the user gives
 *   one.
 * Never write your own @page rule or hard-code paper dimensions in the
 * content.
 *
 * Sizing modes (attributes):
 *   (none)                      — portrait: flowing docs use the user's
 *           paper; explicitly paginated pages use the named size box
 *           (letter unless size="a4")
 *   orientation="landscape"     — the same, landscape
 *   width / height              — explicit fixed size, ONLY when the user
 *           gives one (e.g. width="22in" height="30in" for a 22×30
 *           poster): the page IS the design's size, printed at true
 *           dimensions (or scaled onto the user's paper at print time).
 *           Any absolute CSS length: px/in/mm/cm/pt/pc.
 * The component announces the chosen mode to the host app at runtime (a
 * meta tag it injects), so the print path can inject the user's true
 * paper size.
 *
 * On screen the document renders on a desk background: a flowing
 * document as one tall scrolling sheet (Google Docs' pageless view);
 * explicitly paginated documents as one card per page.
 *
 * EXPLICIT pagination usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page>
 *     <section class="page" id="p1">…one page's design…</section>
 *     <section class="page" id="p2">…</section>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * How the page box works, concretely: each .page prints as ONE full-bleed
 * sheet at a FIXED physical size — letter by default (set size="a4" for
 * a clearly metric user), the user's chosen paper when they export —
 * with overflow hidden. Nothing scrolls and nothing reflows onto a next
 * sheet: content that misses the box is CLIPPED. Design each page to
 * FILL that page box, and to fit it — letter and A4 alike — without
 * overlap. Each page is a size container; don't size anything in
 * viewport units (they track the window, not the page), and never set
 * width or height on the .page section itself (the component sizes the
 * page box; an authored height like 100% is meaningless at print and is
 * overridden). The component owns the page box, the screen card chrome,
 * and the page breaks (never add your own break-before/after). Don't mix
 * .page sections with flowing content or header/footer slots in the same
 * document.
 *
 * FLOWING usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page margin="0.75in">
 *     <h1>Title</h1>
 *     <p>…body…</p>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * There is no manual page-splitting — the browser's print engine
 * paginates at export. Standard break-hygiene rules (`break-inside:
 * avoid` on figures, code blocks, images and table rows; `orphans/
 * widows: 3`) are applied so paragraphs and groups split cleanly. On
 * screen and at print, headings default to `text-wrap: balance` and
 * body text to `text-wrap: pretty`; the defaults have zero specificity,
 * so any text-wrap you declare wins.
 *
 * Other attributes:
 *   size    — letter | a4 | legal (default letter). Flowing documents:
 *           preview proportion only — it does NOT pin their printed
 *           paper (the print dialog's paper governs); leave it alone
 *           there. Explicitly paginated documents: it sets the page box
 *           the cards and the pinned @page share (the export dialog's
 *           choice overrides both at print) — set size="a4" for a
 *           clearly metric user. Scaled-fit: names the sheet the fit is
 *           computed against, same a4-for-metric-users advice.
 *   content-width / content-height — the design's own fixed dimensions
 *           (CSS lengths), for scaling a fixed-size design ONTO the
 *           named sheet: content lays out at exactly this size, and the
 *           component scales it to fit that sheet's printable area
 *           (centered horizontally, top-aligned; the export dialog
 *           re-fits to the user's actual paper choice where available).
 *           Both must be set; they do not change the page box. For pages
 *           WITHOUT running header/footer slots.
 *   margin  — printable inset on every page of a FLOWING document
 *           (default 0.75in); margin="0" makes pages full-bleed.
 *           Explicitly paginated pages are always full-bleed.
 *
 * Running header/footer (flowing documents only): give an element
 * `slot="header"` or `slot="footer"` and it repeats on every printed
 * page via `position: fixed`. To keep body text from sliding under it,
 * the component prints inside a single-cell table whose <thead>/<tfoot>
 * are spacers sized to the header/footer height — browsers repeat
 * thead/tfoot on every page, so each sheet's content starts below the
 * header and ends above the footer. On screen the header/footer render
 * once at the top/bottom of the sheet.
 *
 * At print the component injects `@page { margin: 0 }` (which leaves
 * Chrome no margin box to draw its date/URL/page-count header in) and
 * moves the visual margin onto the sheet's own padding. It also marks
 * the document as owning its print CSS (a
 * `meta[name="omelette-owns-print"]` it injects at runtime), so the
 * PDF export never injects page-geometry CSS of its own on top.
 *
 * Print best practices for the content you author:
 * - Multi-column text: use CSS columns (`column-count` +
 *   `column-gap`), never side-by-side flex/grid columns — only real
 *   CSS columns flow and break across pages. `column-span: all` lets
 *   a heading span the columns; `hyphens: auto` (needs `lang` on
 *   the html element) keeps narrow columns readable.
 * - Page breaks in flowing documents: `break-before: page` on an
 *   element that must start a new page (a chapter, an appendix). Add
 *   your own kept-together blocks (callouts, stat tiles, cards) to a
 *   `break-inside: avoid` rule, and keep each one shorter than a page.
 * - Extend `orphans: 3; widows: 3` to any custom text blocks you add
 *   (p and li are covered by default).
 * - Give long tables a <thead> — browsers repeat it on every printed
 *   page.
 * - No `position: fixed`/`sticky` and no viewport units in content:
 *   fixed elements stamp every printed page (running headers/footers go
 *   in the component's slots) and `100vh` mis-sizes at print.
 *
 * Author content as static HTML so the user can click-to-edit any text
 * directly. Do not set width/padding/background on the document body —
 * the component owns the sheet box.
 */
/* END USAGE */

(() => {
  const PAPER = {
    letter: ['8.5in', '11in'],
    a4: ['210mm', '297mm'],
    legal: ['8.5in', '14in']
  };
  const CSS_LENGTH = /^\d+(\.\d+)?(px|in|mm|cm|pt|pc)$/;
  // Unitless "0" is a valid CSS length and the natural way to write
  // margin="0"; normalise it to 0px so max()/calc() (which reject a bare
  // number) keep working.
  const safeLen = (v, fb) => {
    v = (v || '').trim();
    return v === '0' ? '0px' : CSS_LENGTH.test(v) ? v : fb;
  };
  // WebKit (Safari and every iOS browser shell) never repeats a table's
  // thead/tfoot on printed pages (WebKit bug 17205), so the spacer-borne
  // vertical margins of a FLOWING document reach only the first page
  // there. Engine check, not browser check: vendor is 'Apple Computer,
  // Inc.' exactly for WebKit and 'Google Inc.' for Blink.
  const WK_PRINT = /apple/i.test(navigator.vendor || '');
  // CSS length → px number (CSS absolute units are exact: 1in = 96px).
  // Returns NaN for anything safeLen would reject — callers gate on it.
  const PX_PER = {
    px: 1,
    in: 96,
    mm: 96 / 25.4,
    cm: 96 / 2.54,
    pt: 96 / 72,
    pc: 16
  };
  const toPx = v => {
    const m = /^(\d+(?:\.\d+)?)(px|in|mm|cm|pt|pc)$/.exec((v || '').trim());
    return m ? parseFloat(m[1]) * PX_PER[m[2]] : NaN;
  };
  const stylesheet = `
    :host {
      position: relative;
      display: block;
      /* When the viewport is narrower than the page, grow to wrap the
       * sheet (plus this padding) instead of staying viewport-width, so
       * the desk background and right margin reach the sheet's far edge
       * in the horizontal scroll. */
      min-width: max-content;
      min-height: 100vh;
      background: #f5f5f4;
      padding: 48px 24px;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
      --doc-page-w: 8.5in;
      --doc-page-h: 11in;
      --doc-page-margin: 0.75in;
      --doc-hdr-h: 0px;
      --doc-ftr-h: 0px;
      --doc-hdr-pad: 0px;
      --doc-ftr-pad: 0px;
    }
    .sheet {
      width: var(--doc-page-w);
      margin: 0 auto;
      background: #fff;
      box-shadow: 0 2px 10px rgba(20, 20, 19, 0.12);
      border-radius: 7px;
      box-sizing: border-box;
      padding: var(--doc-page-margin);
    }
    .frame { width: 100%; border-collapse: collapse; }
    /* Scaled-fit mode (content-width/content-height): the inner .fit box
     * lays the content out at its authored fixed size and scales it onto
     * the printable area; .fit-box reserves the scaled footprint in flow
     * (transforms don't affect layout) and centers it. Without the mode,
     * both divs are unstyled block pass-throughs. */
    /* Explicit pagination: direct .page children are the pages. The sheet
     * becomes a transparent stack and each page carries the card look on
     * screen; at print each page is exactly one full-bleed sheet. The
     * ::slotted defaults are deliberately weak (document CSS wins), so
     * authored page styling can override any of this. */
    .sheet.paginated {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      padding: 0;
    }
    .paginated ::slotted(.page) {
      position: relative;
      display: block;
      width: 100%;
      aspect-ratio: var(--doc-page-ar);
      container-type: size;
      overflow: hidden;
      box-sizing: border-box;
      background: #fff;
      border-radius: 7px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
      print-color-adjust: exact;
      -webkit-print-color-adjust: exact;
      break-inside: avoid;
    }
    .paginated ::slotted(.page:not(:first-child)) { margin-top: 1rem; }
    @media print {
      .sheet.paginated { padding: 0; }
      /* The flowing-document vertical inset lives on the repeating
       * thead/tfoot spacers, not the sheet padding — they must go too,
       * or each full-sheet .page is pushed ~margin down and spills onto
       * a second sheet. Paginated pages are full-bleed by definition
       * (content owns its insets). */
      .sheet.paginated .hdr-space,
      .sheet.paginated .ftr-space { height: 0; }
      .paginated ::slotted(.page) {
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        /* Physical page-box sizing, no viewport units: Safari resolves
         * 100vh against the window, not the page box, so a vh-sized card
         * paginates wrong there. --doc-page-w/h are the named size by
         * default and are overridden to the user's chosen paper by the
         * export path, so every card is exactly one sheet either way.
         * Width + height (same source values as @page size) rather than
         * width + aspect-ratio: the ratio is a 6-decimal rounding of the
         * same division, and a few millionths of overflow would spill a
         * blank sheet after every page. The screen-only aspect-ratio
         * (preview proportions) must not leak into print. cqh typography
         * tracks the same box.
         *
         * Every declaration is !important: per CSS Scoping, unimportant
         * shadow ::slotted rules LOSE to the document context, so a page
         * section's authored inline style would silently beat this print
         * geometry. A model-authored height:100% did exactly that — the
         * percentage resolves as auto in the all-auto print ancestry, the
         * base rule's size containment turns auto into ZERO, and
         * overflow:hidden then paints nothing: a blank PDF with perfect
         * page boxes. At print the component's geometry is the design's
         * whole contract, so it must win over any authored sizing. */
        aspect-ratio: auto !important;
        width: var(--doc-page-w) !important;
        height: var(--doc-page-h) !important;
        overflow: hidden !important;
      }
      .paginated ::slotted(.page:not(:first-child)) {
        break-before: page !important;
        margin-top: 0 !important;
      }
    }
    .fit-mode .fit-box {
      width: calc(var(--doc-fit-w) * var(--doc-fit-scale));
      height: calc(var(--doc-fit-h) * var(--doc-fit-scale));
      margin: 0 auto;
      break-inside: avoid;
    }
    /* Monolithic at print: Blink slices a transform-scaled child at
     * fragmentainer boundaries mapped in UNSCALED layout coordinates
     * (transforms are paint-time), so the .fit box (authored size, e.g.
     * 1400x990) gets cut at the page's free block space and spills onto
     * a second sheet even though its SCALED footprint fits the page by
     * construction. overflow:hidden makes .fit-box a scroll container —
     * monolithic under fragmentation (css-break-3) — so the scaled
     * content prints atomically on one sheet. No clipping for content
     * within the authored box: .fit-box is calc-sized to exactly the
     * scaled footprint. (Content that bleeds past content-width/height
     * is clipped at the footprint — fit mode's contract; it previously
     * painted beyond it at print.) Print-only, so the screen rendering
     * keeps visible overflow for editor affordances.
     * The export path injects the same rule into frozen copies
     * (print-eval.ts om-print-fit-contain). The .fit-mode scope is
     * load-bearing: .fit-box wraps slotted content in EVERY mode, and an
     * unscoped overflow:hidden would make whole flowing documents
     * monolithic (one truncated sheet). overflow:hidden, never clip —
     * clip is not a scroll container, so not monolithic. */
    @media print {
      .fit-mode .fit-box { overflow: hidden; }
    }
    .fit-mode .fit {
      width: var(--doc-fit-w);
      height: var(--doc-fit-h);
      transform: scale(var(--doc-fit-scale));
      transform-origin: top left;
    }
    .frame td, .frame th { padding: 0; text-align: left; font-weight: inherit; }
    .hdr-space { height: var(--doc-hdr-h); }
    .ftr-space { height: var(--doc-ftr-h); }
    ::slotted([slot="header"]),
    ::slotted([slot="footer"]) { display: block; box-sizing: border-box; }
    @media print {
      :host { background: none; padding: 0; min-width: 0; min-height: 0; }
      .sheet {
        width: auto; margin: 0; box-shadow: none; border-radius: 0;
        padding: 0 var(--doc-page-margin);
      }
      /* The thead/tfoot spacers repeat on every page, so they carry the
       * vertical page margin (which the sheet's own padding cannot, since
       * that padding is consumed once on the first/last page). The running
       * header/footer are fixed inside that band. */
      /* The 0.35in is breathing room between a running header/footer and
       * the body; without one the spacer is exactly the page margin, so a
       * margin="0" full-bleed document gets truly full-bleed pages. */
      .hdr-space { height: max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))); }
      .ftr-space { height: max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))); }
      /* WebKit flowing documents: @page carries the vertical margin (see
       * _syncPrintPageRule), so the spacers keep only whatever a running
       * header/footer needs BEYOND it — page 1 would otherwise double its
       * top inset. Paginated sheets already zero their spacers above. */
      .sheet.wk-print:not(.paginated) .hdr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))) - var(--doc-page-margin))); }
      .sheet.wk-print:not(.paginated) .ftr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))) - var(--doc-page-margin))); }
      ::slotted([slot="header"]) {
        position: fixed; top: 0; left: 0; right: 0; margin: 0;
        padding: calc(var(--doc-page-margin) * 0.45) var(--doc-page-margin) 0;
      }
      ::slotted([slot="footer"]) {
        position: fixed; bottom: 0; left: 0; right: 0; margin: 0;
        padding: 0 var(--doc-page-margin) calc(var(--doc-page-margin) * 0.45);
      }
    }
  `;
  class DocPage extends HTMLElement {
    static get observedAttributes() {
      return ['size', 'width', 'height', 'margin', 'orientation', 'content-width', 'content-height'];
    }
    constructor() {
      super();
      this._root = this.attachShadow({
        mode: 'open'
      });
      this._mo = typeof MutationObserver === 'function' ? new MutationObserver(() => this._scheduleMeasure()) : null;
    }

    /** The named paper's [w, h], swapped when orientation="landscape".
     *  Only the named size swaps — explicit width/height are exact values
     *  the author already oriented. */
    _paperSize() {
      const named = PAPER[(this.getAttribute('size') || '').toLowerCase()] || PAPER.letter;
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? [named[1], named[0]] : named;
    }
    get pageWidth() {
      return safeLen(this.getAttribute('width'), this._paperSize()[0]);
    }
    get pageHeight() {
      return safeLen(this.getAttribute('height'), this._paperSize()[1]);
    }
    get pageMargin() {
      return safeLen(this.getAttribute('margin'), '0.75in');
    }

    /** Scaled-fit mode's content box [w, h] as CSS lengths, or null when
     *  the mode is off (either attribute missing/invalid/zero — a partial
     *  declaration falls back to normal flow rather than guessing). */
    _contentFit() {
      const w = safeLen(this.getAttribute('content-width'), null);
      const h = safeLen(this.getAttribute('content-height'), null);
      if (!w || !h) return null;
      const wPx = toPx(w),
        hPx = toPx(h);
      return wPx > 0 && hPx > 0 ? [w, h, wPx, hPx] : null;
    }
    connectedCallback() {
      if (!this._sheet) this._render();
      this._syncSize();
      this._syncPrintPageRule();
      this._ensureTextWrapDefaults();
      this._ensureOwnsPrintMeta();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      if (this._mo) this._mo.observe(this, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true
      });
      this._onResize = () => this._scheduleMeasure();
      window.addEventListener('resize', this._onResize);
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => this._scheduleMeasure());
      }
      this._scheduleMeasure();
    }
    disconnectedCallback() {
      window.removeEventListener('resize', this._onResize);
      if (this._mo) this._mo.disconnect();
      if (this._raf) {
        cancelAnimationFrame(this._raf);
        this._raf = null;
      }
      // Drop the head rules when the last doc-page leaves, so a deleted
      // document's @page geometry and text-wrap defaults can't apply to
      // whatever replaces it.
      const survivor = document.querySelector('doc-page');
      if (!survivor) {
        ['doc-page-print', 'doc-page-text-wrap', 'doc-page-owns-print', 'doc-page-fixed-size', 'doc-page-print-sizing'].forEach(id => {
          const tag = document.getElementById(id);
          if (tag) tag.remove();
        });
        // A live deck-stage deferred its own print-sizing meta to ours —
        // hand the page-global meta over so the deck isn't left unmarked.
        const deck = document.querySelector('deck-stage');
        if (deck && typeof deck._ensurePrintSizingMeta === 'function') {
          deck._ensurePrintSizingMeta();
        }
      } else {
        // A departed owner hands each page-global meta to whatever
        // doc-page remains (or it's removed).
        if (typeof survivor._syncFixedSizeMeta === 'function') {
          survivor._syncFixedSizeMeta();
        }
        if (typeof survivor._syncPrintSizingMeta === 'function') {
          survivor._syncPrintSizingMeta();
        }
      }
    }
    attributeChangedCallback() {
      if (!this._sheet) return;
      this._syncSize();
      this._syncPrintPageRule();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      this._scheduleMeasure();
    }
    _render() {
      this._root.innerHTML = `
        <style>${stylesheet}</style>
        <style id="vars"></style>
        <div class="sheet" data-screen-label="Document">
          <table class="frame" role="presentation">
            <thead><tr><th><div class="hdr-space"><slot name="header"></slot></div></th></tr></thead>
            <tbody><tr><td class="body"><div class="fit-box"><div class="fit"><slot></slot></div></div></td></tr></tbody>
            <tfoot><tr><td><div class="ftr-space"><slot name="footer"></slot></div></td></tr></tfoot>
          </table>
        </div>`;
      this._sheet = this._root.querySelector('.sheet');
      this._vars = this._root.getElementById('vars');
    }

    /** Runtime sizing lives in a shadow <style> :host rule, never on the
     *  light-DOM host element, so serialize-persist can't write it back. */
    _syncSize(hdrH, ftrH) {
      // Scaled-fit mode: content at its authored size, scaled onto the
      // printable area (page minus margins on both axes). The factor is a
      // plain number var so calc(length * number) stays valid; 4 decimals
      // keeps the shadow style stable across re-measures. Upscaling is
      // allowed — print transforms are vector, so text and CSS stay crisp
      // (raster images soften, which the catalog bullet warns about).
      const fit = this._contentFit();
      let fitVars = '';
      if (fit) {
        const marginPx = toPx(this.pageMargin) || 0;
        const availW = toPx(this.pageWidth) - 2 * marginPx;
        const availH = toPx(this.pageHeight) - 2 * marginPx;
        const scale = Math.min(availW / fit[2], availH / fit[3]);
        if (scale > 0 && Number.isFinite(scale)) {
          fitVars = '--doc-fit-w:' + fit[0] + ';' + '--doc-fit-h:' + fit[1] + ';' + '--doc-fit-scale:' + scale.toFixed(4) + ';';
        }
      }
      this._sheet.classList.toggle('fit-mode', !!fitVars);
      // Numeric w/h ratio for the paginated page cards' aspect-ratio —
      // aspect-ratio takes a number, not a length ratio, so compute it
      // here (CSS length division isn't portable). 6 decimals keeps the
      // shadow style stable across re-syncs.
      const arW = toPx(this.pageWidth);
      const arH = toPx(this.pageHeight);
      const ar = arW > 0 && arH > 0 ? (arW / arH).toFixed(6) : '0.772727';
      this._vars.textContent = ':host{' + fitVars + '--doc-page-ar:' + ar + ';' + '--doc-page-w:' + this.pageWidth + ';' + '--doc-page-h:' + this.pageHeight + ';' + '--doc-page-margin:' + this.pageMargin + ';' + '--doc-hdr-h:' + (hdrH || 0) + 'px;' + '--doc-ftr-h:' + (ftrH || 0) + 'px;' + '--doc-hdr-pad:' + (hdrH ? '0.35in' : '0px') + ';' + '--doc-ftr-pad:' + (ftrH ? '0.35in' : '0px') + '}';
    }

    /** @page is a no-op inside shadow DOM, so the rule lives in <head>.
     *  Re-appended on every sync so it stays last in source order — the
     *  @page cascade is source-order per descriptor, so this rule wins
     *  over any other @page rule in the document.
     *
     *  The @page SIZE is pinned where the page box IS part of the design:
     *  explicit-fixed-size mode (width + height authored), scaled-fit
     *  mode (the named sheet the fit targets), and explicit pagination
     *  (the named size the cards share — so card and sheet agree on
     *  every print path, and the export path's chosen paper overrides
     *  BOTH with one later rule). For FLOWING documents no paper size is
     *  emitted at all — the true size comes from the user's preference,
     *  injected by the export path or chosen in the print dialog — so a
     *  flowing document never fights the paper it lands on.
     *  margin: 0 is emitted in every mode: it leaves Chrome no margin box
     *  to draw its date/URL/page-count header in, and the visual margin
     *  lives on the sheet's own padding. */
    _syncPrintPageRule() {
      const id = 'doc-page-print';
      let tag = document.getElementById(id);
      if (!tag) {
        tag = document.createElement('style');
        tag.id = id;
      }
      document.head.appendChild(tag);
      // Three print-geometry regimes:
      // - true-size: the page IS the design — pin its exact size.
      // - scaled-fit (content-width/height): the fit factor is computed
      //   against the NAMED paper's printable area, so that paper must
      //   stay pinned or the scaled content overflows a smaller sheet
      //   (the export path re-fits and re-pins at print time on top).
      // - default modes: no paper size — but landscape still needs the
      //   paper-agnostic 'size: landscape' keyword, because the size
      //   descriptor is what carries orientation; without it a landscape
      //   document prints portrait whenever nothing injects a size.
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      // Explicit pagination pins the page box to the SAME values that
      // size the cards (the named size by default, the export path's
      // chosen paper when its later rule overrides both) — card and
      // sheet agree on every print path, and a mismatched real paper
      // shrinks-to-fit in the dialog instead of clipping a Letter card
      // on A4. Declared before the paginated read below so both derive
      // from one check.
      const paginatedNow = this.querySelector(':scope > .page') !== null;
      const sizeDescriptor = this._trueSizePx() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : this._contentFit() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : paginatedNow ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : landscape ? 'size: landscape; ' : '';
      // WebKit never repeats the thead/tfoot spacers that carry a flowing
      // document's vertical page margins (see WK_PRINT above), so pages
      // after the first print edge-to-edge there. Carry the VERTICAL
      // margins on @page for WebKit instead, and the shadow print CSS
      // trims the first-page spacers by the same amount (.sheet.wk-print
      // rules). Horizontal inset stays on the sheet's own padding in
      // every engine. Blink keeps margin: 0 (a nonzero margin there
      // re-opens the box Chrome draws its header furniture in). One cost,
      // learned in testing: Safari's own date/URL headers are a USER
      // dialog setting ("Print headers and footers") that renders in the
      // margin area when room exists — margin: 0 only suppressed it by
      // leaving no room, and no CSS controls it. The export dialog's
      // Safari guide teaches turning the setting off for flowing
      // documents. Explicitly paginated and fixed-size documents keep
      // margin: 0 everywhere: their pages ARE the sheet.
      const wkFlowing = WK_PRINT && !paginatedNow && !this._trueSizePx() && !this._contentFit();
      const marginDescriptor = wkFlowing ? 'margin: ' + this.pageMargin + ' 0; ' : 'margin: 0; ';
      // Shadow-internal marker (never serialized), kept in lockstep with
      // the @page decision above: the print CSS trims the first-page
      // spacers ONLY while @page actually carries the margins — a
      // true-size or scaled-fit sheet keeps margin: 0 and must keep its
      // spacers too. Re-synced here so attribute changes and pagination
      // flips move both together.
      if (this._sheet) this._sheet.classList.toggle('wk-print', wkFlowing);
      tag.textContent = '@page { ' + sizeDescriptor + marginDescriptor + '} ' + '@media print { html, body { margin: 0 !important; padding: 0 !important; background: none !important; height: auto !important; overflow: visible !important; } ' + 'h1,h2,h3,h4,h5,h6 { break-after: avoid; } ' + 'figure,pre,blockquote,img,svg,tr { break-inside: avoid; } ' + 'p,li { orphans: 3; widows: 3; } ' + '* { -webkit-print-color-adjust: exact; print-color-adjust: exact; ' + 'backdrop-filter: none !important; -webkit-backdrop-filter: none !important; } ' + '*, *::before, *::after { animation-delay: -99s !important; animation-duration: .001s !important; ' + 'animation-iteration-count: 1 !important; animation-fill-mode: both !important; ' + 'animation-play-state: running !important; transition-duration: 0s !important; } }';
    }

    /** Typographic defaults for document text: balance headings, avoid
     *  widowed/orphaned words in body copy (browsers without text-wrap
     *  support drop the declarations). Zero-specificity via :where() so
     *  any text-wrap authored on those elements wins; document-level so the
     *  rules reach the slotted (light DOM) content — shadow styles can't.
     *  data-omelette-injected marks the tag for the host editor to strip
     *  at serialize, so it is never written back as authored source. */
    _ensureTextWrapDefaults() {
      if (document.getElementById('doc-page-text-wrap')) return;
      const tag = document.createElement('style');
      tag.id = 'doc-page-text-wrap';
      tag.setAttribute('data-omelette-injected', '');
      tag.textContent = ':where(h1,h2,h3,h4,h5,h6){text-wrap:balance}' + ':where(p,li,blockquote,figcaption){text-wrap:pretty}';
      document.head.appendChild(tag);
    }

    /** Declares that this document owns its print CSS. The instant-PDF
     *  export checks for the meta by NAME PRESENCE alone (content is
     *  ignored) and skips its automatic print-CSS injections, so the
     *  component's @page geometry is never overridden by a heuristic.
     *  data-omelette-injected keeps it out of serialized source. */
    _ensureOwnsPrintMeta() {
      if (document.getElementById('doc-page-owns-print')) return;
      const tag = document.createElement('meta');
      tag.id = 'doc-page-owns-print';
      tag.name = 'omelette-owns-print';
      tag.content = 'true';
      tag.setAttribute('data-omelette-injected', '');
      document.head.appendChild(tag);
    }

    /** This page's valid true-size page box (explicit width AND height)
     *  as [w, h] px ints, or null when the mode is off. */
    _trueSizePx() {
      if (!safeLen(this.getAttribute('width'), null) || !safeLen(this.getAttribute('height'), null)) return null;
      const w = Math.round(toPx(this.pageWidth));
      const h = Math.round(toPx(this.pageHeight));
      return w > 0 && h > 0 ? [w, h] : null;
    }

    /** True-size pages (explicit width AND height) also declare the page
     *  box as the preview size: the in-app preview reads
     *  meta[name="omelette-fixed-size"] (content "W,H" in px ints) and
     *  scales the sheet into view — without it an 18in poster previews at
     *  true size with scrollbars. Never overrides an author-set meta
     *  (only the component's own id is managed). The meta is page-global
     *  while doc-page instances are not, so every sync recomputes the
     *  page-wide owner — the first connected true-size doc-page — and a
     *  non-true-size sibling's sync can never delete the owner's meta.
     *  Removed when no true-size page remains (the owner's disconnect
     *  re-syncs via any survivor) or when an author-set meta exists. */
    _syncFixedSizeMeta() {
      const id = 'doc-page-fixed-size';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-fixed-size"]:not([data-omelette-injected])');
      // The page-wide owner, not this instance: an upgraded true-size page
      // anywhere in the document keeps the meta alive and sized.
      let box = null;
      for (const el of document.querySelectorAll('doc-page')) {
        box = typeof el._trueSizePx === 'function' ? el._trueSizePx() : null;
        if (box) break;
      }
      if (!box || authored) {
        if (own) own.remove();
        return;
      }
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-fixed-size';
      tag.content = box[0] + ',' + box[1];
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }

    /** This page's print-sizing mode: 'fixed' when an explicit width AND
     *  height are authored (the page is the design's own size), else the
     *  default paper in the authored orientation. */
    _printSizingMode() {
      if (this._trueSizePx()) return 'fixed';
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? 'default-landscape' : 'default-portrait';
    }

    /** Announces the print-sizing mode to the host app:
     *  meta[name="omelette-print-sizing"] with content 'default-portrait',
     *  'default-landscape', or 'fixed' (fixed pages also carry the
     *  omelette-fixed-size meta with the page box in px). The export path
     *  probes it to decide what true paper size to inject at print time —
     *  in the default modes the component emits no paper size of its own.
     *  Same page-global ownership rules as the fixed-size meta above:
     *  first connected doc-page owns it, an authored meta is never
     *  overridden, removed when no doc-page remains. */
    _syncPrintSizingMeta() {
      const id = 'doc-page-print-sizing';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-print-sizing"]:not([data-omelette-injected])');
      // A fixed page wins outright (mirroring the fixed-size loop above,
      // so the two metas can never contradict each other in a mixed
      // multi-page document); otherwise the first page's mode holds.
      let mode = null;
      for (const el of document.querySelectorAll('doc-page')) {
        if (typeof el._printSizingMode !== 'function') continue;
        const m = el._printSizingMode();
        if (m === 'fixed') {
          mode = m;
          break;
        }
        if (mode === null) mode = m;
      }
      if (!mode || authored) {
        if (own) own.remove();
        return;
      }
      // A deck-stage that connected first injected its own meta and
      // defers to any existing one — take it over, or the document ends
      // up with two conflicting injected metas (a doc-page page is the
      // document; the deck re-ensures its meta if every doc-page leaves).
      const deckMeta = document.getElementById('deck-stage-print-sizing');
      if (deckMeta) deckMeta.remove();
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-print-sizing';
      tag.content = mode;
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }
    _scheduleMeasure() {
      if (this._raf) return;
      this._raf = requestAnimationFrame(() => {
        this._raf = null;
        this._measure();
      });
    }

    /** Slot heights feed the print spacers (--doc-hdr-h / --doc-ftr-h), so
     *  they re-measure on content mutation, resize, and font load. The
     *  same pass detects explicit pagination (direct .page children) and
     *  toggles the sheet between the flowing-document card and the
     *  page-per-card stack — content edits can add or remove pages at any
     *  time, so this tracks the same mutations the measurement does. */
    _measure() {
      const hdr = this.querySelector(':scope > [slot="header"]');
      const ftr = this.querySelector(':scope > [slot="footer"]');
      const wasPaginated = this._sheet.classList.contains('paginated');
      this._sheet.classList.toggle('paginated', this.querySelector(':scope > .page') !== null);
      // The WebKit @page margin is flowing-only, so a pagination flip
      // must re-emit the rule (content edits can add or remove .page
      // sections at any time).
      if (this._sheet.classList.contains('paginated') !== wasPaginated) {
        this._syncPrintPageRule();
      }
      this._syncSize(hdr ? hdr.offsetHeight : 0, ftr ? ftr.offsetHeight : 0);
    }
  }
  if (!customElements.get('doc-page')) {
    customElements.define('doc-page', DocPage);
  }
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-09-29-docs/design-system/concepts/quickstart/doc-page.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/deck-stage.js
try { (() => {
// @ds-adherence-ignore -- omelette starter scaffold (raw elements/hex/px by design)
// Copied omelette starter. Re-running copy_starter_component with this kind overwrites this file with the latest version (page content is unaffected).
/* ═══ THIS PROJECT USES DESIGN COMPONENTS (.dc.html) ═══
 * Reference this stage from your <x-dc> template as an import — NEVER as a
 * raw <deck-stage> tag plus a <script src> (that hides the whole deck until
 * the stream finishes):
 *
 *   <x-import component-from-global-scope="deck-stage" from="./deck-stage.js"
 *             width="1920" height="1080" hint-size="100%,100%">
 *     <section data-label="Title" style="...">…</section>
 *     <section data-label="Agenda" style="...">…</section>
 *   </x-import>
 *
 * Slides are inline-styled <section> siblings; do not add a stylesheet or a
 * deck-stage:not(:defined) rule. The plain-HTML "Usage" block in the comment
 * below does NOT apply to .dc.html templates.
 */
/* BEGIN USAGE */
/**
 * <deck-stage> — reusable web component for HTML decks.
 *
 * Handles:
 *  (a) speaker notes — reads <script type="application/json" id="speaker-notes">
 *      and posts {slideIndexChanged: N} to the parent window on nav.
 *  (b) keyboard navigation — ←/→ and ↑/↓, PgUp/PgDn, Space, Home/End,
 *      number keys.
 *      On touch devices, tapping the left/right half of the stage goes
 *      prev/next — taps on links, buttons and other interactive slide
 *      content are left alone.
 *  (c) press R to reset to slide 0 (with a tasteful keyboard hint).
 *  (d) bottom-center overlay showing slide count + hints, fades out on
 *      idle; hovering or focusing its controls pins it visible until the
 *      pointer/focus leaves. While presenting it is pointer-summoned only:
 *      mouse movement (or hover/focus) shows it, slide changes never do.
 *  (e) auto-scaling — inner canvas is a fixed design size (default 1920×1080)
 *      scaled with `transform: scale()` to fit the viewport, letterboxed.
 *      Set the `noscale` attribute to render at authored size (1:1) — the
 *      PPTX exporter sets this so its DOM capture sees unscaled geometry.
 *  (f) print — `@media print` lays every slide out as its own page at the
 *      design size, so the browser's Print → Save as PDF produces a clean
 *      one-page-per-slide PDF with no extra setup.
 *  (g) thumbnail rail — resizable left-hand column of per-slide thumbnails
 *      (static clones). Click to navigate — the clicked slide becomes the
 *      selected (highlighted) slide; shift-click selects a range and
 *      cmd/ctrl-click toggles slides in and out of the selection
 *      (Escape collapses it back to the current slide); ↑/↓ with a
 *      thumbnail focused to step between slides; Delete/Backspace with a
 *      thumbnail focused to delete the selection (one confirm dialog,
 *      one undoable operation); drag to reorder (dragging collapses a
 *      multi-selection); right-click for
 *      Skip / Move up / Move down / Duplicate / Delete — over a
 *      multi-selection the menu offers "Delete N slides". Drag the rail's right edge to resize;
 *      width persists to
 *      localStorage. Skipped slides carry `data-deck-skip`, are dimmed in
 *      the rail, omitted from prev/next navigation, and hidden at print.
 *      They also carry no rail number and are excluded from the overlay's
 *      slide count: the remaining slides are numbered contiguously
 *      (Keynote-style), and a skipped CURRENT slide (reachable by rail
 *      click or deep link, never by prev/next) shows '–' as its position.
 *      The rail is suppressed in presenting mode, in the host's Preview
 *      mode (ViewerMode='none'), on `noscale`, on narrow viewports
 *      (≤640px), and via the `no-rail` attribute. Rail mutations dispatch
 *      a `dc-op` CustomEvent on the element (see docs/dc-ops.md) and do
 *      NOT touch the DOM: the host applies the op and re-renders;
 *      structural rail input is locked until the host posts
 *      {__dc_op_ack: true, applied}.
 *  (h) typographic defaults — a zero-specificity stylesheet injected into
 *      the document gives headings `text-wrap: balance` and body text
 *      (p, li, blockquote, figcaption) `text-wrap: pretty`, so slides
 *      avoid widowed/orphaned words by default. Any text-wrap declaration
 *      you author on those elements wins over these defaults.
 *
 * Slides are HIDDEN, not unmounted. Non-active slides stay in the DOM with
 * `visibility: hidden` + `opacity: 0`, so their state (videos, iframes,
 * form inputs, React trees) is preserved across navigation.
 *
 * Lifecycle event — the component dispatches a `slidechange` CustomEvent on
 * itself whenever the active slide changes (including the initial mount).
 * The event bubbles and composes out of shadow DOM, so you can listen on
 * the <deck-stage> element or on document:
 *
 *   document.querySelector('deck-stage').addEventListener('slidechange', (e) => {
 *     e.detail.index         // new 0-based index
 *     e.detail.previousIndex // previous index, or -1 on init
 *     e.detail.total         // total slide count
 *     e.detail.slide         // the new active slide element
 *     e.detail.previousSlide // the prior slide element, or null on init
 *     e.detail.reason        // 'init' | 'keyboard' | 'click' | 'tap' | 'api'
 *   });
 *
 * Persistence: none at the deck level. The host app keeps the current slide
 * in its own URL (?slide=) and re-delivers it via location.hash on load, so a
 * bare load with no hash always starts at slide 1.
 *
 * Usage:
 *   <style>deck-stage:not(:defined){visibility:hidden}</style>
 *   <deck-stage width="1920" height="1080">
 *     <section data-label="Title">...</section>
 *     <section data-label="Agenda">...</section>
 *   </deck-stage>
 *   <script src="deck-stage.js"></script>
 *
 * The :not(:defined) rule prevents a flash of the first slide at its
 * authored styles before this script runs and attaches the shadow root.
 *
 * Slides are the direct element children of <deck-stage>. Each slide is
 * automatically tagged with:
 *   - data-screen-label="NN Label"   (1-indexed, for comment flow)
 *   - data-om-validate="no_overflowing_text,no_overlapping_text,slide_sized_text"
 *
 * Speaker notes stay in sync because the component posts {slideIndexChanged: N}
 * to the parent — just include the #speaker-notes script tag if asked for notes.
 *
 * Authoring guidance:
 *   - Write slide bodies as static HTML inside <deck-stage>, with sizing via
 *     CSS custom properties in a <style> block rather than JS constants.
 *     Static slide markup is what lets the user click a heading in edit mode
 *     and retype it directly; a slide rendered through <script type="text/babel">,
 *     React, or a loop over a JS array has to round-trip every tweak through a
 *     chat message instead. Reach for script-generated slides only when the
 *     content genuinely needs interactive behaviour static HTML can't express.
 *   - Do NOT set position/inset/width/height on the slide <section> elements —
 *     the component absolutely positions every slotted child for you.
 *   - Entrance animations: make the visible end-state the base style and
 *     animate *from* hidden, so print and reduced-motion show content.
 *     Gate the animation on [data-deck-active] and the motion query, e.g.
 *     `@media (prefers-reduced-motion:no-preference){ [data-deck-active] .x{animation:fade-in .5s both} }`.
 *     Avoid infinite decorative loops on slide content.
 */
/* END USAGE */

(() => {
  const DESIGN_W_DEFAULT = 1920;
  const DESIGN_H_DEFAULT = 1080;
  const OVERLAY_HIDE_MS = 1800;
  const VALIDATE_ATTR = 'no_overflowing_text,no_overlapping_text,slide_sized_text';
  const FINE_POINTER_MQ = matchMedia('(hover: hover) and (pointer: fine)');
  const NARROW_MQ = matchMedia('(max-width: 640px)');
  // Slide-authored controls that should keep a tap instead of it navigating.
  const INTERACTIVE_SEL = 'a[href], button, input, select, textarea, summary, label, video[controls], audio[controls], [role="button"], [onclick], [tabindex]:not([tabindex^="-"]), [contenteditable]:not([contenteditable="false" i])';
  const pad2 = n => String(n).padStart(2, '0');

  // Label precedence: data-label → data-screen-label (number stripped) → first heading → "Slide".
  const getSlideLabel = el => {
    const explicit = el.getAttribute('data-label');
    if (explicit) return explicit;
    const existing = el.getAttribute('data-screen-label');
    if (existing) return existing.replace(/^\s*\d+\s*/, '').trim() || existing;
    const h = el.querySelector('h1, h2, h3, [data-title]');
    const t = h && (h.textContent || '').trim().slice(0, 40);
    if (t) return t;
    return 'Slide';
  };
  const stylesheet = `
    :host {
      position: fixed;
      inset: 0;
      display: block;
      background: #000;
      color: #fff;
      font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Helvetica, Arial, sans-serif;
      overflow: hidden;
      -webkit-tap-highlight-color: transparent;
    }
    /* connectedCallback holds this until document.fonts.ready (capped 2s) so
     * the first visible paint has the deck's real typography + final rail
     * layout. opacity (not visibility) so the active slide can't un-hide
     * itself via the ::slotted([data-deck-active]) visibility:visible rule.
     * Only the stage/rail hide — the black :host background stays, so the
     * iframe doesn't flash the page's default white. */
    :host([data-fonts-pending]) .stage,
    :host([data-fonts-pending]) .rail { opacity: 0; pointer-events: none; }

    .stage {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .canvas {
      position: relative;
      transform-origin: center center;
      flex-shrink: 0;
      background: #fff;
      will-change: transform;
      /* Slide edge on the black stage. Dark decks override the canvas
       * fill toward the stage's own black, leaving nothing to mark where
       * the slide ends — the faint white ring keeps the boundary legible
       * there while disappearing into the white of light decks. A
       * box-shadow, not outline/border: it follows any canvas rounding
       * and adds no layout size. */
      box-shadow: 0 0 0 1.5px rgba(255, 255, 255, 0.12);
    }

    /* Slides live in light DOM (via <slot>) so authored CSS still applies.
       We absolutely position each slotted child to stack them. */
    ::slotted(*) {
      position: absolute !important;
      inset: 0 !important;
      width: 100% !important;
      height: 100% !important;
      box-sizing: border-box !important;
      overflow: hidden;
      opacity: 0;
      pointer-events: none;
      visibility: hidden;
    }
    ::slotted([data-deck-active]) {
      opacity: 1;
      pointer-events: auto;
      visibility: visible;
    }

    .overlay {
      position: fixed;
      left: 50%;
      bottom: 22px;
      transform: translate(-50%, 6px) scale(0.92);
      filter: blur(6px);
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 4px;
      background: #000;
      color: #fff;
      border-radius: 999px;
      font-size: 12px;
      font-feature-settings: "tnum" 1;
      letter-spacing: 0.01em;
      opacity: 0;
      pointer-events: none;
      transition: opacity 260ms ease, transform 260ms cubic-bezier(.2,.8,.2,1), filter 260ms ease;
      transform-origin: center bottom;
      z-index: 2147483000;
      user-select: none;
    }
    .overlay[data-visible] {
      opacity: 1;
      pointer-events: auto;
      transform: translate(-50%, 0) scale(1);
      filter: blur(0);
    }

    .btn {
      appearance: none;
      -webkit-appearance: none;
      background: transparent;
      border: 0;
      margin: 0;
      padding: 0;
      color: inherit;
      font: inherit;
      cursor: default;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 28px;
      min-width: 28px;
      border-radius: 999px;
      color: rgba(255,255,255,0.72);
      transition: background 140ms ease, color 140ms ease;
      -webkit-tap-highlight-color: transparent;
    }
    .btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
    .btn:active { background: rgba(255,255,255,0.18); }
    .btn:focus { outline: none; }
    .btn:focus-visible { outline: none; }
    .btn::-moz-focus-inner { border: 0; }
    .btn svg { width: 14px; height: 14px; display: block; }
    .btn.reset {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.02em;
      padding: 0 10px 0 12px;
      gap: 6px;
      color: rgba(255,255,255,0.72);
    }
    .btn.reset .kbd {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 16px;
      height: 16px;
      padding: 0 4px;
      font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
      font-size: 10px;
      line-height: 1;
      color: rgba(255,255,255,0.88);
      background: rgba(255,255,255,0.12);
      border-radius: 4px;
    }

    .count {
      font-variant-numeric: tabular-nums;
      color: #fff;
      font-weight: 500;
      padding: 0 8px;
      min-width: 42px;
      text-align: center;
      font-size: 12px;
    }
    .count .sep { color: rgba(255,255,255,0.45); margin: 0 3px; font-weight: 400; }
    .count .total { color: rgba(255,255,255,0.55); }

    .divider {
      width: 1px;
      height: 14px;
      background: rgba(255,255,255,0.18);
      margin: 0 2px;
    }

    /* ── Thumbnail rail ──────────────────────────────────────────────────
       Fixed column on the left; each thumbnail is a static deep-clone of
       the light-DOM slide scaled into a 16:9 (or design-aspect) frame. The
       stage re-fits around it (see _fit); hidden during present / noscale
       / print so capture geometry and fullscreen output are unchanged. */
    .rail {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: var(--deck-rail-w, 188px);
      background: #141414;
      border-right: 1px solid rgba(255,255,255,0.08);
      overflow-y: auto;
      overflow-x: hidden;
      padding: 12px 10px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 12px;
      z-index: 2147482500;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,0.18) transparent;
    }
    .rail::-webkit-scrollbar { width: 8px; }
    .rail::-webkit-scrollbar-track { background: transparent; margin: 2px; }
    .rail::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.18);
      border-radius: 4px;
      border: 2px solid transparent;
      background-clip: content-box;
    }
    .rail::-webkit-scrollbar-thumb:hover {
      background: rgba(255,255,255,0.28);
      border: 2px solid transparent;
      background-clip: content-box;
    }
    :host([no-rail]) .rail,
    :host([noscale]) .rail { display: none; }
    .rail[data-presenting] { display: none; }
    @media (max-width: 640px) {
      .rail, .rail-resize { display: none; }
    }
    /* User-driven show/hide (the TweaksPanel toggle) slides instead of
       popping. Transitions are gated on :host([data-rail-anim]) — set only
       for the 200ms around the toggle — so window-resize and rail-width
       drag (which also call _fit) don't lag behind the cursor. */
    .rail[data-user-hidden] { transform: translateX(-100%); }
    :host([data-rail-anim]) .rail { transition: transform 200ms cubic-bezier(.3,.7,.4,1); }
    :host([data-rail-anim]) .stage { transition: left 200ms cubic-bezier(.3,.7,.4,1); }
    :host([data-rail-anim]) .canvas { transition: transform 200ms cubic-bezier(.3,.7,.4,1); }
    /* transition shorthand replaces rather than merges — repeat the base
       .overlay opacity/transform/filter transitions so visibility changes
       during the 200ms toggle window still fade instead of popping. */
    :host([data-rail-anim]) .overlay {
      transition: margin-left 200ms cubic-bezier(.3,.7,.4,1),
                  opacity 260ms ease,
                  transform 260ms cubic-bezier(.2,.8,.2,1),
                  filter 260ms ease;
    }

    .thumb {
      position: relative;
      display: flex;
      align-items: flex-start;
      gap: 8px;
      cursor: pointer;
      user-select: none;
    }
    .thumb .num {
      width: 16px;
      flex-shrink: 0;
      font-size: 11px;
      font-weight: 500;
      text-align: right;
      color: rgba(255,255,255,0.55);
      padding-top: 2px;
      font-variant-numeric: tabular-nums;
    }
    .thumb .frame {
      position: relative;
      flex: 1;
      min-width: 0;
      aspect-ratio: var(--deck-aspect);
      background: #fff;
      border-radius: 4px;
      outline: 2px solid transparent;
      outline-offset: 0;
      overflow: hidden;
      transition: outline-color 120ms ease;
    }
    .thumb:hover .frame { outline-color: rgba(255,255,255,0.25); }
    .thumb { outline: none; }
    .thumb:focus-visible .frame { outline-color: rgba(255,255,255,0.5); }
    .thumb[data-selected] .num { color: #fff; }
    .thumb[data-selected] .frame {
      outline-color: rgba(217,119,87,0.65);
      box-shadow: 0 0 0 4px rgba(217,119,87,0.18);
    }
    .thumb[data-current] .num { color: #fff; }
    .thumb[data-current] .frame {
      outline-color: #D97757;
      box-shadow: 0 0 0 4px rgba(217,119,87,0.25);
    }
    /* While dragging, the thumb itself is the drag visual (the native drag
       image is suppressed in dragstart so the snapshot can't wander off the
       rail horizontally): elevate it rather than dim it, and let hit-testing
       ignore it so dragover reaches the sibling thumb under the pointer
       instead of the moving element itself. */
    .thumb[data-dragging] { opacity: 0.9; z-index: 30; pointer-events: none; }
    .thumb[data-dragging] .frame {
      outline-color: rgba(255,255,255,0.5);
      box-shadow: 0 6px 24px rgba(0,0,0,0.5);
    }
    .thumb::before {
      content: '';
      position: absolute;
      left: 24px;
      right: 0;
      height: 3px;
      border-radius: 2px;
      background: #D97757;
      opacity: 0;
      pointer-events: none;
    }
    .thumb[data-drop="before"]::before { top: -8px; opacity: 1; }
    .thumb[data-drop="after"]::before { bottom: -8px; opacity: 1; }
    .thumb[data-skip] .frame { opacity: 0.35; }
    .thumb[data-skip] .frame::after {
      content: 'Skipped';
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0.45);
      color: #fff;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.04em;
    }

    .ctxmenu {
      position: fixed;
      min-width: 150px;
      padding: 4px;
      background: #242424;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 7px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.45);
      z-index: 2147483100;
      display: none;
      font-size: 12px;
    }
    .ctxmenu[data-open] { display: block; }
    .ctxmenu button {
      display: block;
      width: 100%;
      appearance: none;
      border: 0;
      background: transparent;
      color: #e8e8e8;
      font: inherit;
      text-align: left;
      padding: 6px 10px;
      border-radius: 4px;
      cursor: pointer;
    }
    .ctxmenu button:hover:not(:disabled) { background: rgba(255,255,255,0.08); }
    .ctxmenu button:disabled { opacity: 0.35; cursor: default; }
    .ctxmenu hr {
      border: 0;
      border-top: 1px solid rgba(255,255,255,0.1);
      margin: 4px 2px;
    }

    .rail-resize {
      position: fixed;
      left: calc(var(--deck-rail-w, 188px) - 3px);
      top: 0;
      bottom: 0;
      width: 6px;
      cursor: col-resize;
      z-index: 2147482600;
      touch-action: none;
    }
    .rail-resize:hover,
    .rail-resize[data-dragging] { background: rgba(255,255,255,0.12); }
    :host([no-rail]) .rail-resize,
    :host([noscale]) .rail-resize,
    .rail[data-presenting] + .rail-resize,
    .rail[data-user-hidden] + .rail-resize { display: none; }

    /* Delete-confirm popup — matches the SPA's ConfirmDialog layout
       (title + message body, depressed footer with Cancel / Delete). */
    .confirm-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 2147483200;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .confirm-backdrop[data-open] { display: flex; }
    .confirm {
      width: 320px;
      max-width: calc(100vw - 32px);
      background: #2a2a2a;
      color: #e8e8e8;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 12px;
      box-shadow: 0 12px 32px rgba(0,0,0,0.5);
      overflow: hidden;
      font-family: inherit;
      animation: deck-confirm-in 0.18s ease;
    }
    @keyframes deck-confirm-in {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }
    .confirm .body { padding: 20px 20px 16px; }
    .confirm .title { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
    .confirm .msg { font-size: 13px; line-height: 1.5; color: rgba(255,255,255,0.65); }
    .confirm .footer {
      padding: 14px 20px;
      background: #1f1f1f;
      border-top: 1px solid rgba(255,255,255,0.08);
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    .confirm button {
      appearance: none;
      font: inherit;
      font-size: 13px;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
    }
    .confirm .cancel {
      background: transparent;
      border: 0;
      color: rgba(255,255,255,0.8);
    }
    .confirm .cancel:hover { background: rgba(255,255,255,0.08); }
    .confirm .danger {
      background: #c96442;
      border: 1px solid rgba(0,0,0,0.15);
      color: #fff;
      box-shadow: 0 1px 3px rgba(166,50,68,0.3), 0 2px 6px rgba(166,50,68,0.18);
    }
    .confirm .danger:hover { background: #b5563a; }

    /* ── Print: one page per slide, no chrome ────────────────────────────
       The screen layout stacks every slide at inset:0 inside a scaled
       canvas; for print we want them in document flow at the authored
       design size so the browser paginates one slide per sheet. The
       @page size is set from the width/height attributes via the inline
       <style id="deck-stage-print-page"> that _syncPrintPageRule appends
       to the document (the @page at-rule has no effect inside shadow DOM). */
    @media print {
      :host {
        position: static;
        inset: auto;
        background: none;
        overflow: visible;
        color: inherit;
      }
      .stage { position: static; display: block; }
      .canvas {
        transform: none !important;
        width: auto !important;
        height: auto !important;
        background: none;
        will-change: auto;
      }
      ::slotted(*) {
        position: relative !important;
        inset: auto !important;
        width: var(--deck-design-w) !important;
        height: var(--deck-design-h) !important;
        box-sizing: border-box !important;
        /* Size containment: slotted content that overflows the design box
         * (an image-slot's aspect-ratio-derived width, say) must not count
         * toward Chromium's print document width — without this, an
         * abs-positioned child past the page edge shrinks the whole PDF
         * to fit (~75%). Containment is safe here because the definite
         * width/height above size the slide regardless of content.
         * (Absorbed from PR #2619 with its owner's agreement.) */
        contain: size !important;
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto;
        break-after: page;
        page-break-after: always;
        break-inside: avoid;
        overflow: hidden;
      }
      /* :last-child alone isn't enough once data-deck-skip hides the
         trailing slide(s) — the last *visible* slide still carries
         break-after:page and prints a blank sheet. _markLastVisible()
         maintains data-deck-last-visible on the last non-skipped slide. */
      ::slotted(*:last-child),
      ::slotted([data-deck-last-visible]) {
        break-after: auto;
        page-break-after: auto;
      }
      ::slotted([data-deck-skip]) { display: none !important; }
      .overlay, .rail, .rail-resize, .ctxmenu, .confirm-backdrop { display: none !important; }
    }
  `;
  class DeckStage extends HTMLElement {
    static get observedAttributes() {
      return ['width', 'height', 'noscale', 'no-rail'];
    }
    constructor() {
      super();
      this._root = this.attachShadow({
        mode: 'open'
      });
      this._index = 0;
      this._slides = [];
      // Explicit multi-selection (slide elements). Empty means the
      // selection is implicitly the current slide, so Delete always has
      // a well-defined target while the rail has focus.
      this._selected = new Set();
      this._selAnchor = null;
      this._notes = [];
      this._hideTimer = null;
      this._mouseIdleTimer = null;
      this._menuIndex = -1;
      // Overlay pinning: while the pointer is over the controls toolbar or
      // a control has keyboard focus, the idle-hide timeout must not
      // dismiss it (a pointer parked ON the controls doesn't generate
      // mousemove, so without the pin the toolbar vanishes under the
      // user's cursor after OVERLAY_HIDE_MS). Read by _flashOverlay's
      // hide timeout; cleared by mouseleave/focusout, which resume the
      // normal idle fade.
      this._overlayHover = false;
      this._overlayFocus = false;
      // Capability marker for the host's injected guest bundle. Copies
      // WITHOUT _navArrowsUpDown are frozen per-project builds that
      // predate native ArrowUp/ArrowDown slide nav — the bundle translates
      // Up/Down to Right/Left for those (installDeckArrowKeyTranslator in
      // apps/web/src/guest/edit-mode.ts) and must stand down here or every
      // press would advance twice. A marker, not a version number, so a
      // future capability can add its own independent probe.
      this._navArrowsUpDown = true;
      // Same contract for rail Delete/Backspace: copies WITHOUT
      // _railDeleteKey predate the thumbs' own Delete/Backspace binding,
      // and the bundle opens the delete confirm for them
      // (installDeckRailDeleteFallback in apps/web/src/guest/edit-mode.ts).
      // Current builds consume the key at the thumb (stopPropagation), so
      // the marker is belt-and-braces — it keeps the fallback standing
      // down even if a future build lets the key bubble past the thumb.
      this._railDeleteKey = true;
      // Same contract for skip-aware numbering: copies WITHOUT
      // _railSkipNumbers number every thumb 1..N and count skipped slides
      // in the overlay total — the bundle rewrites both for those
      // (installDeckSkipNumberingFallback in apps/web/src/guest/edit-mode.ts).
      // Here the component renumbers natively, so the fallback stands down.
      this._railSkipNumbers = true;
      this._onKey = this._onKey.bind(this);
      this._onResize = this._onResize.bind(this);
      this._onSlotChange = this._onSlotChange.bind(this);
      this._onMouseMove = this._onMouseMove.bind(this);
      this._onTap = this._onTap.bind(this);
      this._onMessage = this._onMessage.bind(this);
      // Capture-phase close so a click anywhere dismisses the menu, but
      // ignore clicks that land inside the menu itself — otherwise the
      // capture handler runs before the menu's own (bubble) handler and
      // clears _menuIndex out from under it.
      this._onDocClick = e => {
        if (this._menu && e.composedPath && e.composedPath().includes(this._menu)) return;
        this._closeMenu();
      };
    }
    get designWidth() {
      return parseInt(this.getAttribute('width'), 10) || DESIGN_W_DEFAULT;
    }
    get designHeight() {
      return parseInt(this.getAttribute('height'), 10) || DESIGN_H_DEFAULT;
    }
    connectedCallback() {
      // Presenter-view popup loads deckUrl?_snthumb=...#N for its prev/cur/
      // next thumbnails — the rail has no business rendering inside those
      // (wrong scale, and it offsets the stage so the thumb shows a gutter).
      if (/[?&]_snthumb=/.test(location.search)) this.setAttribute('no-rail', '');
      this._render();
      this._loadNotes();
      this._syncPrintPageRule();
      this._ensurePrintSizingMeta();
      this._ensureTextWrapDefaults();
      window.addEventListener('keydown', this._onKey);
      window.addEventListener('resize', this._onResize);
      window.addEventListener('mousemove', this._onMouseMove, {
        passive: true
      });
      window.addEventListener('message', this._onMessage);
      window.addEventListener('click', this._onDocClick, true);
      this.addEventListener('click', this._onTap);
      // Print lays every slide out as its own page, so [data-deck-active]-
      // gated entrance styles need the attribute on every slide (not just
      // the current one) or their content prints at the hidden base style.
      // The transient freeze style lands BEFORE the attributes so any
      // attribute-keyed transition fires at 0s (changing transition-
      // duration after a transition has started doesn't affect it).
      this._onBeforePrint = () => {
        this._syncPrintPageRule();
        // Self-heal: a departed doc-page may have removed the page-global
        // print-sizing meta this deck deferred to at connect time.
        this._ensurePrintSizingMeta();
        if (this._freezeStyle) this._freezeStyle.remove();
        this._freezeStyle = document.createElement('style');
        this._freezeStyle.textContent = '*,*::before,*::after{transition-duration:0s !important}';
        document.head.appendChild(this._freezeStyle);
        this._slides.forEach(s => s.setAttribute('data-deck-active', ''));
      };
      this._onAfterPrint = () => {
        this._applyIndex({
          showOverlay: false,
          broadcast: false
        });
        if (this._freezeStyle) {
          this._freezeStyle.remove();
          this._freezeStyle = null;
        }
      };
      window.addEventListener('beforeprint', this._onBeforePrint);
      window.addEventListener('afterprint', this._onAfterPrint);
      // Initial collection + layout happens via slotchange, which fires on mount.
      this._enableRail();
      // Hold the stage hidden until webfonts are ready so the first visible
      // paint has the deck's real typography — the :not(:defined) guard in
      // the page HTML only covers custom-element upgrade, not font load.
      // Capped so a 404'd font URL can't blank the deck indefinitely.
      this.setAttribute('data-fonts-pending', '');
      const reveal = () => this.removeAttribute('data-fonts-pending');
      // Unconditional cap — rAF can be suspended in a hidden iframe, which
      // would strand the one inside the rAF callback.
      setTimeout(reveal, 2000);
      // rAF first: fonts.ready is a pre-resolved promise until layout has
      // resolved the slotted text's font-family and pushed a FontFace into
      // 'loading'. Reading it here in connectedCallback (parse-time) would
      // settle the race in a microtask before any font fetch starts.
      requestAnimationFrame(() => {
        Promise.race([document.fonts ? document.fonts.ready : Promise.resolve(), new Promise(r => setTimeout(r, 2000))]).then(reveal, reveal);
      });
    }
    _enableRail() {
      // Idempotent — older host builds still post __omelette_rail_enabled.
      // no-rail guard keeps the observers/stylesheet walk off the cheap path
      // for presenter-popup thumbnail iframes (three per view — cur/prev/next).
      if (this._railEnabled || this.hasAttribute('no-rail')) return;
      this._railEnabled = true;
      // Per-viewer preference — restored alongside rail width. Default on;
      // only a stored '0' (from the TweaksPanel toggle) hides it.
      this._railVisible = true;
      try {
        if (localStorage.getItem('deck-stage.railVisible') === '0') this._railVisible = false;
      } catch (e) {}
      // Live thumbnail updates: watch the light-DOM slides for content
      // edits and re-clone just the affected thumb(s), debounced. Ignore
      // the data-deck-* / data-screen-label / data-om-validate attributes
      // this component itself writes so nav doesn't trigger spurious
      // refreshes — except data-deck-skip, which now arrives from the host
      // re-render and is what updates the rail badge, print bookkeeping,
      // and deckSkipped re-broadcast. Also ignore data-dc-tpl /
      // data-om-slide-id — host-reserved bookkeeping stamps (the host's
      // ATTR_RESERVED guard bounds them the same way) that structural
      // edits renumber/re-mint on slides whose content didn't change;
      // re-cloning on that churn is what made a slide move flash its
      // thumbnails.
      const OWN_ATTRS = /^data-(deck-(?!skip$)|screen-label$|om-(validate|slide-id)$|dc-tpl$)/;
      this._liveDirty = new Set();
      this._liveObserver = new MutationObserver(records => {
        for (const r of records) {
          if (r.type === 'attributes' && OWN_ATTRS.test(r.attributeName || '')) continue;
          let n = r.target;
          while (n && n.parentElement !== this) n = n.parentElement;
          // Skip/unskip is handled below without re-cloning (the badge sits
          // on the thumb wrapper, not the clone) — don't mark the slide
          // dirty for an attr change whose only visible effect is the badge.
          if (n && this._slideSet && this._slideSet.has(n) && !(r.type === 'attributes' && r.attributeName === 'data-deck-skip')) {
            this._liveDirty.add(n);
          }
          // Host-driven skip toggle: sync the rail badge + print + presenter
          // skipped-list the way _toggleSkip used to do locally.
          if (r.type === 'attributes' && r.attributeName === 'data-deck-skip' && n && this._slideSet && this._slideSet.has(n)) {
            const i = this._slides.indexOf(n);
            if (this._thumbs && this._thumbs[i]) {
              if (n.hasAttribute('data-deck-skip')) this._thumbs[i].thumb.setAttribute('data-skip', '');else this._thumbs[i].thumb.removeAttribute('data-skip');
            }
            this._markLastVisible();
            this._renumberRail();
            this._syncCount();
            try {
              window.postMessage({
                slideIndexChanged: this._index,
                deckTotal: this._slides.length,
                deckSkipped: this._skippedIndices()
              }, '*');
            } catch (e) {}
          }
        }
        if (this._liveDirty.size && !this._liveTimer) {
          this._liveTimer = setTimeout(() => {
            this._liveTimer = null;
            this._liveDirty.forEach(s => this._refreshThumb(s));
            this._liveDirty.clear();
          }, 200);
        }
      });
      this._liveObserver.observe(this, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true
      });
      // Lazy thumbnail materialization — clone the slide only when its
      // frame scrolls into (or near) the rail viewport. rootMargin gives
      // ~4 thumbs of pre-load so fast scrolling doesn't flash blanks.
      this._railObserver = new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (e.isIntersecting && e.target.__deckThumb) {
            this._materialize(e.target.__deckThumb);
          }
        });
      }, {
        root: this._rail,
        rootMargin: '400px 0px'
      });
      // Tweaks typically change CSS vars / attrs OUTSIDE <deck-stage>
      // (on <html>, <body>, a wrapper div, or a <style> tag), which
      // _liveObserver can't see. Re-snapshot author CSS (constructable
      // sheet is shared by reference, so one replaceSync updates every
      // thumb shadow root) and re-sync each thumb host's attrs + custom
      // properties. In-slide DOM mutations are _liveObserver's job.
      // Debounced so slider drags don't thrash.
      this._onTweakChange = () => {
        clearTimeout(this._tweakTimer);
        this._tweakTimer = setTimeout(() => {
          this._snapshotAuthorCss();
          // One getComputedStyle for the whole batch — each
          // getPropertyValue read below reuses the same computed style
          // as long as nothing invalidates layout between thumbs.
          const cs = getComputedStyle(this);
          (this._thumbs || []).forEach(t => {
            if (t.host) this._syncThumbHostAttrs(t.host, cs);
          });
        }, 120);
      };
      window.addEventListener('tweakchange', this._onTweakChange);
      // Stylesheets that finish loading AFTER the snapshot below never
      // reach the thumbs on their own: a still-pending <link> contributes
      // nothing to document.styleSheets, and nothing re-reads it on load,
      // so the live slides restyle while every clone keeps the stale
      // sheet. dc-runtime's helmet mounts design-system <link>s at render
      // time, so a deck-stage that connects first snapshots before that
      // CSS exists. Funnel late arrivals into the same debounced resync:
      // hook load/error on every current <link>, and watch <head> for
      // links and styles mounted or rewritten later. Deliberately not
      // rAF- or fonts.ready-driven — rAF is throttled/suspended in hidden
      // iframes (thumbnail/presenter contexts), and a font-file load
      // doesn't change cssRules, so it needs no resync.
      this._hookedLinks = [];
      this._hookSheetLoad = el => {
        if (!el.matches || !el.matches('link[rel~="stylesheet" i]')) return;
        if (this._hookedLinks.indexOf(el) !== -1) return;
        this._hookedLinks.push(el);
        el.addEventListener('load', this._onTweakChange);
        el.addEventListener('error', this._onTweakChange);
      };
      document.querySelectorAll('link[rel~="stylesheet" i]').forEach(this._hookSheetLoad);
      this._headObserver = new MutationObserver(records => {
        let resync = false;
        for (const r of records) {
          if (r.type === 'characterData') {
            // Only <style> text is CSS — a ticking <title> shouldn't
            // wake the resync forever.
            const p = r.target.parentNode;
            if (p && p.nodeName === 'STYLE') resync = true;
            continue;
          }
          if (r.type === 'attributes') {
            // A late rel/href rewrite turns an inert <link> into a
            // stylesheet (hook it; its load fires even on cache hits);
            // a media/disabled flip changes effective rules with no
            // event. Resync only if this link is or ever was a
            // stylesheet — favicon/preload/canonical href churn isn't
            // a resync.
            if (r.target.nodeName === 'LINK') {
              this._hookSheetLoad(r.target);
              if (this._hookedLinks.indexOf(r.target) !== -1) resync = true;
            } else if (r.target.nodeName === 'STYLE') resync = true;
            continue;
          }
          // childList: only links and styles carry CSS. A new <link> has
          // no rules until it loads — hook it rather than resync now; a
          // <style> mount/unmount or text-node swap takes effect
          // immediately. _freezeStyle (our beforeprint helper) is skipped
          // on add only — no removal-side guard: _onAfterPrint nulls the
          // ref before the observer fires, so that check would be dead;
          // the one debounced no-op resync per print is harmless.
          if (r.target.nodeName === 'STYLE') resync = true;
          for (const n of r.addedNodes) {
            if (n.nodeName === 'LINK') this._hookSheetLoad(n);else if (n.nodeName === 'STYLE' && n !== this._freezeStyle) resync = true;
          }
          for (const n of r.removedNodes) {
            if (n.nodeName === 'LINK') {
              const hi = this._hookedLinks.indexOf(n);
              if (hi !== -1) {
                this._hookedLinks.splice(hi, 1);
                n.removeEventListener('load', this._onTweakChange);
                n.removeEventListener('error', this._onTweakChange);
                resync = true;
              }
            } else if (n.nodeName === 'STYLE') resync = true;
          }
        }
        if (resync) this._onTweakChange();
      });
      this._headObserver.observe(document.head, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['rel', 'href', 'media', 'disabled']
      });
      this._snapshotAuthorCss();
      // Re-snapshot once any still-loading stylesheet settles — it throws on
      // .cssRules above and silently contributes '' → unstyled thumbs on a
      // cold mount. {once:true}; routed through the debounced handler.
      document.querySelectorAll('link[rel~="stylesheet"]').forEach(l => {
        try {
          if (l.sheet && l.sheet.cssRules) return;
        } catch (e) {}
        l.addEventListener('load', this._onTweakChange, {
          once: true
        });
        l.addEventListener('error', this._onTweakChange, {
          once: true
        });
      });
      if (document.fonts) document.fonts.ready.then(this._onTweakChange, this._onTweakChange);
      // Build the rail now that it's enabled — slotchange already fired,
      // so _renderRail's early-return skipped the initial build.
      this._syncRailHidden();
      this._renderRail();
      this._fit();
    }

    /** Snapshot document stylesheets into a constructable sheet that each
     *  thumbnail's nested shadow root adopts — so author CSS styles the
     *  cloned slide content without touching this component's chrome.
     *  Cross-origin sheets throw on .cssRules — skip them. Re-callable:
     *  the existing constructable sheet is reused via replaceSync so every
     *  already-adopted shadow root picks up the fresh CSS without re-adopt. */
    _snapshotAuthorCss() {
      // :root in an adopted sheet inside a shadow root matches nothing
      // (only the document root qualifies), so author rules like
      // `:root[data-voice="modern"] .serif` never reach the clones.
      // Rewrite :root → :host and mirror <html>'s data-*/class/lang onto
      // each thumb host (see _syncThumbHostAttrs) so the same selectors
      // match inside the thumbnail's shadow tree.
      const authorCss = Array.from(document.styleSheets).map(sh => {
        try {
          return Array.from(sh.cssRules).map(r => r.cssText).join('\n');
        } catch (e) {
          return '';
        }
      }).join('\n')
      // The shadow host is featureless outside the functional :host(...)
      // form, so any compound on :root — [attr], .class, #id, :pseudo —
      // must become :host(<compound>) not :host<compound>. Same for the
      // html type selector (Tailwind class-strategy dark mode emits
      // html.dark; Pico uses html[data-theme]), which has nothing to
      // match inside the thumb's shadow tree.
      .replace(/:root((?:\[[^\]]*\]|[.#][-\w]+|:[-\w]+(?:\([^)]*\))?)+)/g, ':host($1)').replace(/:root\b/g, ':host').replace(/(^|[\s,>~+(}])html((?:\[[^\]]*\]|[.#][-\w]+|:[-\w]+(?:\([^)]*\))?)+)(?![-\w])/g, '$1:host($2)').replace(/(^|[\s,>~+(}])html(?![-\w])/g, '$1:host');
      // Every custom property the author references. _syncThumbHostAttrs
      // mirrors each one's *computed* value at <deck-stage> onto the
      // thumb host so the live value wins over the :host default above
      // regardless of which ancestor the tweak wrote to (<html>, <body>,
      // a wrapper div, or the deck-stage element itself all inherit
      // down to getComputedStyle(this)).
      this._authorVars = new Set(authorCss.match(/--[\w-]+/g) || []);
      try {
        if (!this._adoptedSheet) this._adoptedSheet = new CSSStyleSheet();
        this._adoptedSheet.replaceSync(authorCss);
      } catch (e) {
        this._adoptedSheet = null;
        this._authorCss = authorCss;
      }
    }
    _syncThumbHostAttrs(host, cs) {
      const de = document.documentElement;
      // setAttribute overwrites but can't delete — an attr removed from
      // <html> (toggleAttribute off, classList emptied) would linger on
      // the host and :host([data-*]) / :host(.foo) rules would keep
      // matching. Remove stale mirrored attrs first; iterate backward
      // because removeAttribute mutates the live NamedNodeMap.
      for (let i = host.attributes.length - 1; i >= 0; i--) {
        const n = host.attributes[i].name;
        if ((n.startsWith('data-') || n === 'class' || n === 'lang') && !de.hasAttribute(n)) {
          host.removeAttribute(n);
        }
      }
      for (const a of de.attributes) {
        if (a.name.startsWith('data-') || a.name === 'class' || a.name === 'lang') {
          host.setAttribute(a.name, a.value);
        }
      }
      // The :root→:host rewrite in _snapshotAuthorCss pins each custom
      // property to its stylesheet default on the thumb host, shadowing
      // the live value that would otherwise inherit. Tweaks can write the
      // live value on any ancestor — <html>, <body>, a wrapper div, the
      // deck-stage element — so read it as the *computed* value at
      // <deck-stage> (which sees the whole inheritance chain) rather than
      // trying to guess which element the author wrote to. Inline on the
      // host beats the :host{} rule. remove-stale covers vars dropped
      // from the stylesheet between snapshots.
      const vars = this._authorVars || new Set();
      for (let i = host.style.length - 1; i >= 0; i--) {
        const p = host.style[i];
        if (p.startsWith('--') && !vars.has(p)) host.style.removeProperty(p);
      }
      const live = cs || getComputedStyle(this);
      vars.forEach(p => {
        const v = live.getPropertyValue(p);
        if (v) host.style.setProperty(p, v.trim());else host.style.removeProperty(p);
      });
    }
    disconnectedCallback() {
      // A disconnect mid-drag never gets a dragend, so the document-level
      // drag tracker must be torn down here like every other global hook.
      this._stopDragTrack();
      window.removeEventListener('keydown', this._onKey);
      window.removeEventListener('resize', this._onResize);
      window.removeEventListener('mousemove', this._onMouseMove);
      window.removeEventListener('message', this._onMessage);
      window.removeEventListener('click', this._onDocClick, true);
      window.removeEventListener('beforeprint', this._onBeforePrint);
      window.removeEventListener('afterprint', this._onAfterPrint);
      if (this._freezeStyle) {
        this._freezeStyle.remove();
        this._freezeStyle = null;
      }
      this.removeEventListener('click', this._onTap);
      if (this._hideTimer) clearTimeout(this._hideTimer);
      if (this._mouseIdleTimer) clearTimeout(this._mouseIdleTimer);
      if (this._liveTimer) clearTimeout(this._liveTimer);
      if (this._tweakTimer) clearTimeout(this._tweakTimer);
      if (this._railAnimTimer) clearTimeout(this._railAnimTimer);
      if (this._scaleRaf) cancelAnimationFrame(this._scaleRaf);
      if (this._liveObserver) this._liveObserver.disconnect();
      if (this._railObserver) this._railObserver.disconnect();
      if (this._headObserver) this._headObserver.disconnect();
      (this._hookedLinks || []).forEach(l => {
        l.removeEventListener('load', this._onTweakChange);
        l.removeEventListener('error', this._onTweakChange);
      });
      this._hookedLinks = [];
      if (this._onTweakChange) window.removeEventListener('tweakchange', this._onTweakChange);
      // Drop the text-wrap defaults when the last deck-stage leaves, so a
      // deleted deck's typography can't restyle whatever replaces it.
      // (#deck-stage-print-page keeps its existing keep-forever lifecycle.)
      if (!document.querySelector('deck-stage')) {
        const tw = document.getElementById('deck-stage-text-wrap');
        if (tw) tw.remove();
        const ps = document.getElementById('deck-stage-print-sizing');
        if (ps) ps.remove();
      }
    }
    attributeChangedCallback() {
      if (this._canvas) {
        this._canvas.style.width = this.designWidth + 'px';
        this._canvas.style.height = this.designHeight + 'px';
        this._canvas.style.setProperty('--deck-design-w', this.designWidth + 'px');
        this._canvas.style.setProperty('--deck-design-h', this.designHeight + 'px');
        if (this._rail) {
          this._rail.style.setProperty('--deck-aspect', this.designWidth + '/' + this.designHeight);
        }
        this._fit();
        this._scaleThumbs();
        this._syncPrintPageRule();
      }
    }
    _render() {
      const style = document.createElement('style');
      style.textContent = stylesheet;
      const stage = document.createElement('div');
      stage.className = 'stage';
      const canvas = document.createElement('div');
      canvas.className = 'canvas';
      canvas.style.width = this.designWidth + 'px';
      canvas.style.height = this.designHeight + 'px';
      canvas.style.setProperty('--deck-design-w', this.designWidth + 'px');
      canvas.style.setProperty('--deck-design-h', this.designHeight + 'px');
      const slot = document.createElement('slot');
      slot.addEventListener('slotchange', this._onSlotChange);
      canvas.appendChild(slot);
      stage.appendChild(canvas);

      // Overlay: compact, solid black, with clickable controls.
      const overlay = document.createElement('div');
      overlay.className = 'overlay export-hidden';
      overlay.setAttribute('role', 'toolbar');
      overlay.setAttribute('aria-label', 'Deck controls');
      overlay.setAttribute('data-omelette-chrome', '');
      overlay.innerHTML = `
        <button class="btn prev" type="button" aria-label="Previous slide" title="Previous (←)">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3L5 8l5 5"/></svg>
        </button>
        <span class="count" aria-live="polite"><span class="current">1</span><span class="sep">/</span><span class="total">1</span></span>
        <button class="btn next" type="button" aria-label="Next slide" title="Next (→)">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3l5 5-5 5"/></svg>
        </button>
        <span class="divider"></span>
        <button class="btn reset" type="button" aria-label="Reset to first slide" title="Reset (R)">Reset<span class="kbd">R</span></button>
      `;
      overlay.querySelector('.prev').addEventListener('click', () => this._advance(-1, 'click'));
      overlay.querySelector('.next').addEventListener('click', () => this._advance(1, 'click'));
      overlay.querySelector('.reset').addEventListener('click', () => this._go(0, 'click'));

      // Pin the controls while the user is interacting with them —
      // hovering, or keyboard focus on a control. The hidden overlay is
      // pointer-events:none, so these only ever engage while it's already
      // visible. 'pointer' source: these are user-interaction paths, so
      // they may show/refresh the overlay even while presenting (see
      // _flashOverlay).
      overlay.addEventListener('mouseenter', () => {
        this._overlayHover = true;
        this._flashOverlay('pointer');
      });
      overlay.addEventListener('mouseleave', () => {
        const hadPin = this._overlayHover;
        this._overlayHover = false;
        // Resume the idle fade — never summon. Without the guard, a
        // mouseleave that fires because the overlay was force-hidden
        // (presenting entry flips it to pointer-events:none under the
        // cursor) would pop the controls right back up.
        if (hadPin || overlay.hasAttribute('data-visible')) this._flashOverlay('pointer');
      });
      overlay.addEventListener('focusin', e => {
        // Keyboard-origin focus only (:focus-visible): a mouse click also
        // focuses the clicked button, and pinning on that would hold the
        // controls open indefinitely after a single click — the hover pin
        // already covers the mouse case. Engines without :focus-visible
        // fall back to pinning on any focus (the safe direction).
        var kb = true;
        try {
          var t = e.target;
          kb = !(t && t.matches && !t.matches(':focus-visible'));
        } catch (err) {
          kb = true;
        }
        if (!kb) return;
        this._overlayFocus = true;
        this._flashOverlay('pointer');
      });
      overlay.addEventListener('focusout', e => {
        // Only unpin when focus truly left the toolbar — tabbing between
        // its buttons stays pinned. relatedTarget is null when focus
        // leaves the document entirely; treat that as leaving.
        if (e.relatedTarget && overlay.contains(e.relatedTarget)) return;
        const hadPin = this._overlayFocus;
        this._overlayFocus = false;
        // Resume-the-fade only (see mouseleave): a click-focused button
        // losing focus to a later stage click must not summon the
        // controls mid-presentation.
        if (hadPin || overlay.hasAttribute('data-visible')) this._flashOverlay('pointer');
      });

      // Thumbnail rail + context menu. Thumbnails are populated in
      // _renderRail() after _collectSlides().
      const rail = document.createElement('div');
      rail.className = 'rail export-hidden';
      rail.setAttribute('data-omelette-chrome', '');
      // Edit mode hooks wheel to pan the canvas; this opts the rail's own
      // scrollview out so thumbnails stay scrollable while editing.
      rail.setAttribute('data-dc-wheel-passthru', '');
      rail.style.setProperty('--deck-aspect', this.designWidth + '/' + this.designHeight);
      // Edge auto-scroll while dragging a thumb near the rail's top/bottom
      // so off-screen drop targets are reachable. Native dragover fires
      // continuously while the pointer is stationary, so a per-event nudge
      // (ramped by edge proximity) is enough — no rAF loop needed.
      rail.addEventListener('dragover', e => {
        if (this._dragFrom == null) return;
        const r = rail.getBoundingClientRect();
        const EDGE = 40;
        const dt = e.clientY - r.top;
        const db = r.bottom - e.clientY;
        if (dt < EDGE) rail.scrollTop -= Math.ceil((EDGE - dt) / 3);else if (db < EDGE) rail.scrollTop += Math.ceil((EDGE - db) / 3);
      });
      const menu = document.createElement('div');
      menu.className = 'ctxmenu export-hidden';
      menu.setAttribute('data-omelette-chrome', '');
      menu.innerHTML = `
        <button type="button" data-act="skip">Skip slide</button>
        <button type="button" data-act="up">Move up</button>
        <button type="button" data-act="down">Move down</button>
        <button type="button" data-act="duplicate">Duplicate slide</button>
        <hr>
        <button type="button" data-act="delete">Delete slide</button>
      `;
      menu.addEventListener('click', e => {
        const act = e.target && e.target.getAttribute && e.target.getAttribute('data-act');
        if (!act) return;
        const i = this._menuIndex;
        const list = this._menuIndices;
        this._closeMenu();
        if (act === 'skip') this._toggleSkip(i);else if (act === 'up') this._moveSlide(i, i - 1);else if (act === 'down') this._moveSlide(i, i + 1);else if (act === 'duplicate') this._duplicateSlide(i);else if (act === 'delete') this._openConfirm(list && list.length ? list : [i]);
      });
      menu.addEventListener('contextmenu', e => e.preventDefault());

      // Rail resize handle — drag to set --deck-rail-w, persisted to
      // localStorage so the width survives reloads.
      const resize = document.createElement('div');
      resize.className = 'rail-resize export-hidden';
      resize.setAttribute('data-omelette-chrome', '');
      resize.addEventListener('pointerdown', e => {
        e.preventDefault();
        resize.setPointerCapture(e.pointerId);
        resize.setAttribute('data-dragging', '');
        const move = ev => this._setRailWidth(ev.clientX);
        const up = () => {
          resize.removeEventListener('pointermove', move);
          resize.removeEventListener('pointerup', up);
          resize.removeEventListener('pointercancel', up);
          resize.removeAttribute('data-dragging');
          try {
            localStorage.setItem('deck-stage.railWidth', String(this._railPx));
          } catch (err) {}
        };
        resize.addEventListener('pointermove', move);
        resize.addEventListener('pointerup', up);
        resize.addEventListener('pointercancel', up);
      });

      // Delete-confirm dialog — mirrors the SPA's ConfirmDialog layout.
      const confirm = document.createElement('div');
      confirm.className = 'confirm-backdrop export-hidden';
      confirm.setAttribute('data-omelette-chrome', '');
      confirm.innerHTML = `
        <div class="confirm" role="dialog" aria-modal="true">
          <div class="body">
            <div class="title">Delete slide?</div>
            <div class="msg">This slide will be removed from the deck.</div>
          </div>
          <div class="footer">
            <button type="button" class="cancel">Cancel</button>
            <button type="button" class="danger">Delete</button>
          </div>
        </div>
      `;
      confirm.addEventListener('click', e => {
        if (e.target === confirm) {
          this._closeConfirm();
          this._focusCurrentThumb();
        }
      });
      confirm.querySelector('.cancel').addEventListener('click', () => {
        this._closeConfirm();
        this._focusCurrentThumb();
      });
      confirm.querySelector('.danger').addEventListener('click', () => {
        // Re-resolve at click time — the elements are the user's actual
        // selection; their indices may have shifted since confirm-open.
        const list = (this._confirmEls || []).map(el => this._slides.indexOf(el)).filter(i => i >= 0);
        this._closeConfirm();
        this._deleteSlides(list);
        this._focusCurrentThumb();
      });
      this._root.append(style, rail, resize, stage, overlay, menu, confirm);
      this._canvas = canvas;
      this._stage = stage;
      this._slot = slot;
      this._overlay = overlay;
      this._rail = rail;
      this._resize = resize;
      this._menu = menu;
      this._confirm = confirm;
      this._countEl = overlay.querySelector('.current');
      this._totalEl = overlay.querySelector('.total');

      // Restore persisted rail width.
      let rw = 188;
      try {
        const s = localStorage.getItem('deck-stage.railWidth');
        if (s) rw = parseInt(s, 10) || rw;
      } catch (err) {}
      this._setRailWidth(rw);
      this._syncRailHidden();
    }
    _setRailWidth(px) {
      const w = Math.max(120, Math.min(360, Math.round(px)));
      this._railPx = w;
      this.style.setProperty('--deck-rail-w', w + 'px');
      this._fit();
      // _scaleThumbs forces a sync layout (frame.offsetWidth) then writes
      // N transforms. During a resize drag this runs per-pointermove;
      // coalesce to one per frame.
      if (!this._scaleRaf) {
        this._scaleRaf = requestAnimationFrame(() => {
          this._scaleRaf = null;
          this._scaleThumbs();
        });
      }
    }

    /** @page must live in the document stylesheet — it's a no-op inside
     *  shadow DOM. (Re-)append so any author @page landing later in
     *  source order can't reintroduce a margin and push each slide onto
     *  two sheets; called again from beforeprint. */
    _syncPrintPageRule() {
      const id = 'deck-stage-print-page';
      let tag = document.getElementById(id);
      if (!tag) {
        tag = document.createElement('style');
        tag.id = id;
      }
      (document.body || document.head).appendChild(tag);
      tag.textContent = '@page { size: ' + this.designWidth + 'px ' + this.designHeight + 'px; margin: 0; } ' + '@media print { html, body { margin: 0 !important; padding: 0 !important; background: none !important; overflow: visible !important; height: auto !important; } ' + '* { -webkit-print-color-adjust: exact; print-color-adjust: exact; ' + 'backdrop-filter: none !important; -webkit-backdrop-filter: none !important; } ' +
      // Jump authored animations/transitions to their end state so print
      // never captures mid-entrance — pairs with the beforeprint handler
      // in connectedCallback that sets data-deck-active on every slide.
      '*, *::before, *::after { animation-delay: -99s !important; animation-duration: .001s !important; ' + 'animation-iteration-count: 1 !important; animation-fill-mode: both !important; ' + 'animation-play-state: running !important; transition-duration: 0s !important; } }';
    }

    /** Announces the deck's print-sizing mode to the host app:
     *  meta[name="omelette-print-sizing"] content "default-landscape" — a
     *  deck prints one slide per page on the user's paper size, landscape.
     *  The export path probes the meta to decide what true paper size to
     *  inject at print time (the @page px rule above stays as the
     *  standalone-print fallback; an injected later rule overrides it).
     *  Never overrides an authored meta or another component's; removed
     *  when the last deck-stage leaves. data-omelette-injected keeps it
     *  out of serialized source. */
    _ensurePrintSizingMeta() {
      if (document.querySelector('meta[name="omelette-print-sizing"]')) return;
      const tag = document.createElement('meta');
      tag.id = 'deck-stage-print-sizing';
      tag.name = 'omelette-print-sizing';
      tag.content = 'default-landscape';
      tag.setAttribute('data-omelette-injected', '');
      document.head.appendChild(tag);
    }

    /** Typographic defaults for slide text: balance headings, avoid
     *  widowed/orphaned words in body copy (browsers without text-wrap
     *  support drop the declarations). Zero-specificity via :where() so
     *  any text-wrap authored on those elements wins. Lives in the document,
     *  not the shadow root, for two reasons: document rules reach the
     *  slotted (light DOM) slides, and _snapshotAuthorCss copies document
     *  stylesheets into each thumbnail's shadow root, so the thumbs wrap
     *  the same way — a deck-stage-scoped selector would match nothing
     *  there. data-omelette-injected marks the tag for the host editor
     *  to strip at serialize, so it is never written back as authored
     *  source. */
    _ensureTextWrapDefaults() {
      if (document.getElementById('deck-stage-text-wrap')) return;
      const tag = document.createElement('style');
      tag.id = 'deck-stage-text-wrap';
      tag.setAttribute('data-omelette-injected', '');
      tag.textContent = ':where(h1,h2,h3,h4,h5,h6){text-wrap:balance}' + ':where(p,li,blockquote,figcaption){text-wrap:pretty}';
      document.head.appendChild(tag);
    }
    _onSlotChange() {
      // Self-mutate path already reconciled synchronously and emitted
      // slidechange; skip the async slotchange it caused.
      if (this._squelchSlotChange) {
        this._squelchSlotChange = false;
        return;
      }
      // Primary lock-clear is the host's __deck_rail_ack; this clears on a
      // dropped ack so the rail can't stay dead.
      this._railLock = false;
      this._collectSlides();
      this._restoreIndex();
      this._applyIndex({
        showOverlay: false,
        broadcast: true,
        reason: 'init'
      });
      this._fit();
      // The deck just changed under any open rail surface — an open
      // confirm or menu is a question about the OLD deck (its labels and
      // counts may now lie), so close them rather than let a stale
      // answer fire. The element-held selection re-resolves, but the
      // user should re-read what they're deleting.
      if (this._confirm && this._confirm.hasAttribute('data-open')) {
        this._closeConfirm();
        // The dialog held focus (danger button); hand it back to the rail.
        this._focusCurrentThumb(true);
      }
      if (this._menu && this._menu.hasAttribute('data-open')) this._closeMenu();
      // Editor-mode deletes rebuild the rail through here; a confirmed
      // delete that started from the keyboard still owes focus to the
      // (new) current thumb.
      if (this._pendingRailRefocus) this._focusCurrentThumb(true);
    }
    _collectSlides() {
      const assigned = this._slot.assignedElements({
        flatten: true
      });
      this._slides = assigned.filter(el => {
        // Skip template/style/script nodes even if someone slots them.
        const tag = el.tagName;
        return tag !== 'TEMPLATE' && tag !== 'SCRIPT' && tag !== 'STYLE';
      });
      this._slideSet = new Set(this._slides);
      // Selection is element-keyed: drop entries whose slide is gone
      // (deleted, or replaced wholesale by a host re-render).
      if (this._selected && this._selected.size) {
        this._selected.forEach(s => {
          if (!this._slideSet.has(s)) this._selected.delete(s);
        });
      }
      if (this._selAnchor && !this._slideSet.has(this._selAnchor)) this._selAnchor = null;
      this._slides.forEach((slide, i) => {
        const n = i + 1;
        slide.setAttribute('data-screen-label', `${pad2(n)} ${getSlideLabel(slide)}`);

        // Validation attribute for comment flow / auto-checks.
        if (!slide.hasAttribute('data-om-validate')) {
          slide.setAttribute('data-om-validate', VALIDATE_ATTR);
        }
        slide.setAttribute('data-deck-slide', String(i));
      });
      if (this._index >= this._slides.length) this._index = Math.max(0, this._slides.length - 1);
      this._markLastVisible();
      this._syncCount();
      this._renderRail();
    }

    /** Tag the last non-skipped slide so print CSS can drop its
     *  break-after (see the @media print comment above — :last-child
     *  alone matches a hidden skipped slide). */
    _markLastVisible() {
      let last = null;
      this._slides.forEach(s => {
        s.removeAttribute('data-deck-last-visible');
        if (!s.hasAttribute('data-deck-skip')) last = s;
      });
      if (last) last.setAttribute('data-deck-last-visible', '');
    }
    _loadNotes() {
      // Per-slide data-speaker-notes is authoritative when present (attrs
      // travel with the element on reorder/dup/delete); a slide without
      // the attr falls through to the legacy #speaker-notes JSON array
      // PER SLIDE so a single attr on a JSON-authored deck doesn't blank
      // the rest.
      const tag = document.getElementById('speaker-notes');
      let json = null;
      if (tag) try {
        const p = JSON.parse(tag.textContent || '[]');
        if (Array.isArray(p)) json = p;
      } catch (e) {
        console.warn('[deck-stage] Failed to parse #speaker-notes JSON:', e);
      }
      this._notes = this._slides.map((s, i) => {
        const a = s.getAttribute('data-speaker-notes');
        return a !== null ? a : json && typeof json[i] === 'string' ? json[i] : '';
      });
    }
    _restoreIndex() {
      // The host's ?slide= param is delivered as a #<int> hash (1-indexed) on
      // the iframe src. No hash → slide 1; the deck itself keeps no position
      // state across loads.
      const h = (location.hash || '').match(/^#(\d+)$/);
      if (h) {
        const n = parseInt(h[1], 10) - 1;
        if (n >= 0 && n < this._slides.length) this._index = n;
      }
    }
    _applyIndex({
      showOverlay = true,
      broadcast = true,
      reason = 'init'
    } = {}) {
      if (!this._slides.length) return;
      const prev = this._prevIndex == null ? -1 : this._prevIndex;
      const curr = this._index;
      // Keep the iframe's own hash in sync so an in-iframe location.reload()
      // (reload banner path in viewer-handle.ts) lands on the current slide,
      // not the stale deep-link hash from initial load.
      try {
        history.replaceState(null, '', '#' + (curr + 1));
      } catch (e) {}
      this._slides.forEach((s, i) => {
        if (i === curr) s.setAttribute('data-deck-active', '');else s.removeAttribute('data-deck-active');
      });
      this._syncCount();
      // Follow-scroll on every navigation (init deep-link, keyboard, click,
      // tap, external goTo) — the only time we *don't* want the rail to
      // track current is after a rail-internal mutation, where _renderRail
      // has already restored the user's scroll position and yanking back to
      // current would undo it.
      this._syncRail(reason !== 'mutation');
      if (broadcast) {
        // (1) Legacy: host-window postMessage for speaker-notes renderers.
        try {
          window.postMessage({
            slideIndexChanged: curr,
            deckTotal: this._slides.length,
            deckSkipped: this._skippedIndices()
          }, '*');
        } catch (e) {}

        // (2) In-page CustomEvent on the <deck-stage> element itself.
        //     Bubbles and composes out of shadow DOM so slide code can listen:
        //       document.querySelector('deck-stage').addEventListener('slidechange', e => {
        //         e.detail.index, e.detail.previousIndex, e.detail.total, e.detail.slide, e.detail.reason
        //       });
        const detail = {
          index: curr,
          previousIndex: prev,
          total: this._slides.length,
          slide: this._slides[curr] || null,
          previousSlide: prev >= 0 ? this._slides[prev] || null : null,
          reason: reason // 'init' | 'keyboard' | 'click' | 'tap' | 'api'
        };
        this.dispatchEvent(new CustomEvent('slidechange', {
          detail,
          bubbles: true,
          composed: true
        }));
      }
      this._prevIndex = curr;
      if (showOverlay) this._flashOverlay();
    }
    _flashOverlay(source) {
      // Host posts __omelette_presenting while in fullscreen/tab
      // presentation mode. While presenting, the overlay is
      // pointer-summoned only: it appears on mouse movement and while the
      // user hovers/focuses the controls (source 'pointer'), but never
      // flashes on slide changes or nav-key presses (the default 'auto'
      // source) — a keyboard-driven advance must not blink chrome at the
      // audience. Outside presenting, both sources flash as before.
      if (!this._overlay) return;
      if (this._presenting && source !== 'pointer') return;
      this._overlay.setAttribute('data-visible', '');
      if (this._hideTimer) clearTimeout(this._hideTimer);
      this._hideTimer = setTimeout(() => {
        // Pinned by hover or focus on the controls — keep them up. The
        // matching mouseleave/focusout re-flashes, so the idle fade
        // resumes from that moment.
        if (this._overlayHover || this._overlayFocus) return;
        this._overlay.removeAttribute('data-visible');
      }, OVERLAY_HIDE_MS);
    }
    _railWidth() {
      // State-based, no offsetWidth: the first _fit() can run before the
      // rail has had layout on some load paths, and a 0 there paints the
      // slide full-width for one frame before the post-slotchange _fit()
      // corrects it.
      if (!this._railEnabled || !this._railVisible || this.hasAttribute('no-rail') || this.hasAttribute('noscale') || this._presenting || this._previewMode || NARROW_MQ.matches) return 0;
      return this._railPx || 0;
    }
    _fit() {
      if (!this._canvas) return;
      const stage = this._canvas.parentElement;
      // PPTX export sets noscale so the DOM capture sees authored-size
      // geometry — the scaled canvas is in shadow DOM, so the exporter's
      // resetTransformSelector can't reach .canvas.style.transform directly.
      if (this.hasAttribute('noscale')) {
        this._canvas.style.transform = 'none';
        if (stage) stage.style.left = '0';
        if (this._overlay) this._overlay.style.marginLeft = '0';
        return;
      }
      const rw = this._railWidth();
      if (stage) stage.style.left = rw + 'px';
      // Overlay is centred on the viewport via left:50% + translate(-50%);
      // marginLeft shifts the centre by rw/2 so it lands in the middle of
      // the [rw, innerWidth] stage region.
      if (this._overlay) this._overlay.style.marginLeft = rw / 2 + 'px';
      const vw = window.innerWidth - rw;
      const vh = window.innerHeight;
      const s = Math.min(vw / this.designWidth, vh / this.designHeight);
      this._canvas.style.transform = `scale(${s})`;
    }
    _onResize() {
      this._fit();
      // Crossing the narrow-viewport breakpoint reveals the rail — rerun the
      // thumbnail scale the same way _setRailWidth does.
      if (!this._scaleRaf) {
        this._scaleRaf = requestAnimationFrame(() => {
          this._scaleRaf = null;
          this._scaleThumbs();
        });
      }
    }
    _onMouseMove() {
      // Keep overlay visible while mouse moves; hide after idle. 'pointer'
      // source: mouse movement summons the controls even while presenting.
      this._flashOverlay('pointer');
    }
    _onMessage(e) {
      const d = e.data;
      if (d && typeof d.__omelette_presenting === 'boolean') {
        // Unchanged value → idempotent re-delivery (the guest bundle
        // re-posts when a deck mounts mid-presentation, and host + bundle
        // can both deliver at entry). Skip the resets: re-running the
        // entry work on every delivery would dismiss the pointer-summoned
        // overlay under a hovering cursor and close menus on every slide
        // change. Mirrors the preview_mode branch's unchanged-value guard
        // below.
        if (d.__omelette_presenting !== !!this._presenting) {
          this._presenting = d.__omelette_presenting;
          // A presenting transition invalidates interaction pins: carried
          // across the flip, a stale pin would hold the first summoned
          // overlay open with no pointer anywhere near it. Hide on BOTH
          // transitions: entry cleans the audience's screen, and on exit a
          // pin-skipped hide timeout may have left data-visible set with
          // no timer armed — without this, the footer would linger in the
          // editor until the next mousemove. The next interaction
          // re-summons it either way.
          this._overlayHover = false;
          this._overlayFocus = false;
          if (this._overlay) {
            this._overlay.removeAttribute('data-visible');
            if (this._hideTimer) clearTimeout(this._hideTimer);
          }
          this._syncRailHidden();
          this._closeMenu();
          this._closeConfirm();
          this._fit();
          this._scaleThumbs();
        }
      }
      // Host's Preview segment (ViewerMode='none'): the rail's drag-reorder /
      // right-click skip-delete affordances are editing chrome, so hide it
      // while the user is just looking at the deck. Same hard-hide path as
      // presenting; independent of the user's _railVisible preference so
      // returning to Edit restores whatever they had.
      if (d && typeof d.__omelette_preview_mode === 'boolean') {
        if (d.__omelette_preview_mode === this._previewMode) return;
        this._previewMode = d.__omelette_preview_mode;
        this._syncRailHidden();
        this._closeMenu();
        this._closeConfirm();
        this._fit();
        this._scaleThumbs();
      }
      // Host has processed a dc-op; rail input is safe again. Not tied to
      // slotchange — setAttr and refusal don't fire one. On refusal,
      // revert the optimistic _index/hash adjustment so the next nav
      // starts from what's actually on screen.
      if (d && d.__dc_op_ack) {
        this._railLock = false;
        if (d.applied === false && this._indexBeforeEmit != null) {
          this._index = this._indexBeforeEmit;
          try {
            history.replaceState(null, '', '#' + (this._index + 1));
          } catch (e) {}
        }
        this._indexBeforeEmit = null;
        // A refused op never re-renders, so slotchange won't restore the
        // keyboard flow's focus — do it here. (Applied ops refocus in
        // _onSlotChange, after the rail has been rebuilt.)
        if (d.applied === false && this._pendingRailRefocus) {
          this._focusCurrentThumb(true);
        }
      }
      // Per-viewer show/hide, driven by the TweaksPanel's auto-injected
      // "Thumbnail rail" toggle (or any author script). Independent of
      // whether the Tweaks panel itself is open — closing the panel
      // doesn't change rail visibility. Persists alongside rail width.
      if (d && d.type === '__deck_rail_visible' && typeof d.on === 'boolean') {
        if (d.on === this._railVisible) return;
        this._railVisible = d.on;
        try {
          localStorage.setItem('deck-stage.railVisible', d.on ? '1' : '0');
        } catch (e) {}
        // Arm the transition, commit it, then flip state — otherwise the
        // browser coalesces both writes and nothing animates on show.
        this.setAttribute('data-rail-anim', '');
        void (this._rail && this._rail.offsetHeight);
        this._syncRailHidden();
        this._fit();
        this._scaleThumbs();
        clearTimeout(this._railAnimTimer);
        this._railAnimTimer = setTimeout(() => this.removeAttribute('data-rail-anim'), 220);
      }
      if (d && d.type === '__omelette_rail_enabled') this._enableRail();
    }
    _syncRailHidden() {
      if (!this._rail) return;
      // data-presenting is the hard hide (display:none) for flag-off,
      // presentation mode, and the host's Preview segment — instant, no
      // transition. data-user-hidden is the soft hide (translateX(-100%))
      // for the viewer's rail toggle, so show/hide slides under
      // :host([data-rail-anim]).
      const hard = !this._railEnabled || this._presenting || this._previewMode;
      if (hard) this._rail.setAttribute('data-presenting', '');else this._rail.removeAttribute('data-presenting');
      if (!this._railVisible) this._rail.setAttribute('data-user-hidden', '');else this._rail.removeAttribute('data-user-hidden');
      // translateX hide leaves thumbs (tabIndex=0) in the tab order —
      // inert keeps them unfocusable while the rail is off-screen.
      this._rail.inert = hard || !this._railVisible;
    }
    _onTap(e) {
      // Touch-only — keyboard + the overlay toolbar cover nav on desktop.
      if (FINE_POINTER_MQ.matches) return;
      // Only taps that land on the stage (slide content or letterbox); the
      // overlay / rail / menus are siblings with their own click handlers.
      const path = e.composedPath();
      if (!this._stage || !path.includes(this._stage)) return;
      // Let interactive slide content keep the tap. composedPath (not
      // e.target.closest) so we see through open shadow roots — a <button>
      // inside a slide-authored custom element retargets e.target to the
      // host but still appears in the composed path.
      if (e.defaultPrevented) return;
      for (const n of path) {
        if (n === this._stage) break;
        if (n.matches && n.matches(INTERACTIVE_SEL)) return;
      }
      e.preventDefault();
      const rw = this._railWidth();
      const mid = rw + (window.innerWidth - rw) / 2;
      this._advance(e.clientX < mid ? -1 : 1, 'tap');
    }
    _onKey(e) {
      // Ignore when the user is typing. composedPath()[0], not e.target: a
      // window-level keydown retargets e.target to the shadow host, which
      // would miss an <input> or contenteditable inside a web component on
      // a slide (same reason _onTap uses composedPath).
      const t = e.composedPath ? e.composedPath()[0] : e.target;
      if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
      // Confirm dialog swallows nav keys while open; Escape cancels. Enter
      // is left to the focused button's native activation so Tab→Cancel
      // →Enter activates Cancel, not the window-level confirm path.
      if (this._confirm && this._confirm.hasAttribute('data-open')) {
        if (e.key === 'Escape') {
          this._closeConfirm();
          this._focusCurrentThumb();
          e.preventDefault();
        }
        return;
      }
      if (e.key === 'Escape' && this._menu && this._menu.hasAttribute('data-open')) {
        this._closeMenu();
        e.preventDefault();
        return;
      }
      if (e.key === 'Escape' && this._selected.size) {
        // Collapse the multi-selection back to the current slide (the
        // implicit selection), not to nothing.
        this._clearSelection();
        e.preventDefault();
        return;
      }
      if (e.metaKey || e.ctrlKey || e.altKey) return;
      const key = e.key;
      let handled = true;
      if (key === 'ArrowRight' || key === 'PageDown' || key === ' ' || key === 'Spacebar') {
        this._advance(1, 'keyboard');
      } else if (key === 'ArrowLeft' || key === 'PageUp') {
        this._advance(-1, 'keyboard');
      } else if (key === 'ArrowDown' && !e.defaultPrevented) {
        // ↓/↑ page slides like →/← (Keynote/PowerPoint parity). Window
        // level only: rail thumbs keep their own ↑/↓ walk (their handler
        // stops propagation before this one), and the typing guard above
        // already covers inputs and contenteditable slide content.
        // Deliberate tradeoff: like Space/PageDown before them, these are
        // scroll keys — slide content that wants keyboard scrolling claims
        // them with preventDefault, which this branch honors (checked here
        // and not for the long-standing keys above, so ←/→/Space behavior
        // is unchanged and ↑/↓ behave identically on frozen copies, whose
        // translator in the guest bundle applies the same guard).
        this._advance(1, 'keyboard');
      } else if (key === 'ArrowUp' && !e.defaultPrevented) {
        this._advance(-1, 'keyboard');
      } else if (key === 'Home') {
        this._go(0, 'keyboard');
      } else if (key === 'End') {
        this._go(this._slides.length - 1, 'keyboard');
      } else if (key === 'r' || key === 'R') {
        this._go(0, 'keyboard');
      } else if (/^[0-9]$/.test(key)) {
        // 1..9 jump to that slide; 0 jumps to 10.
        const n = key === '0' ? 9 : parseInt(key, 10) - 1;
        if (n < this._slides.length) this._go(n, 'keyboard');
      } else {
        handled = false;
      }
      if (handled) {
        e.preventDefault();
        this._flashOverlay();
      }
    }
    _go(i, reason = 'api') {
      // User-initiated navigation collapses a multi-selection down to
      // the (implicit) current slide, like Keynote's arrow keys. 'click'
      // handles its own selection; programmatic reasons leave it alone.
      if (reason === 'keyboard' || reason === 'tap') this._clearSelection();
      if (!this._slides.length) return;
      const clamped = Math.max(0, Math.min(this._slides.length - 1, i));
      if (clamped === this._index) {
        this._flashOverlay();
        return;
      }
      this._index = clamped;
      this._applyIndex({
        showOverlay: true,
        broadcast: true,
        reason
      });
    }

    /** Step forward/back skipping any slide marked data-deck-skip. Falls
     *  back to _go's clamp-at-ends behaviour (flash overlay) when there's
     *  nothing further in that direction. */
    _advance(dir, reason) {
      if (!this._slides.length) return;
      let i = this._index + dir;
      while (i >= 0 && i < this._slides.length && this._slides[i].hasAttribute('data-deck-skip')) {
        i += dir;
      }
      if (i < 0 || i >= this._slides.length) {
        this._flashOverlay();
        return;
      }
      this._go(i, reason);
    }

    // ── Thumbnail rail ────────────────────────────────────────────────────
    //
    // Thumbs are keyed by slide element and reused across _renderRail()
    // calls, so a reorder/delete is an O(changed) DOM shuffle instead of an
    // O(N) teardown-and-re-clone. Each thumb starts as a lightweight shell
    // (num + empty frame); the clone is materialized lazily by an
    // IntersectionObserver when the frame scrolls into (or near) view, so
    // only visible-ish slides pay the clone + image-decode cost.

    _renderRail() {
      if (!this._rail || !this._railEnabled) {
        this._thumbs = [];
        return;
      }
      // FLIP: record each *materialized* thumb's top before the reconcile.
      // Off-screen (non-materialized) thumbs don't need the animation and
      // skipping their getBoundingClientRect saves a forced layout per
      // off-screen thumb on large decks.
      const prevTops = new Map();
      (this._thumbs || []).forEach(({
        thumb,
        slide,
        host
      }) => {
        if (host) prevTops.set(slide, thumb.getBoundingClientRect().top);
      });
      const st = this._rail.scrollTop;

      // Reconcile: reuse thumbs that already exist for a slide, create
      // shells for new slides, drop thumbs for removed slides.
      const bySlide = new Map();
      (this._thumbs || []).forEach(t => bySlide.set(t.slide, t));
      const next = [];
      this._slides.forEach(slide => {
        let t = bySlide.get(slide);
        if (t) bySlide.delete(slide);else t = this._makeThumb(slide);
        next.push(t);
      });
      // Orphans — slides removed since last render.
      bySlide.forEach(t => {
        if (this._railObserver) this._railObserver.unobserve(t.frame);
        t.thumb.remove();
      });
      // Put thumbs into document order to match _slides. insertBefore on
      // an already-correctly-placed node is a no-op, so this is cheap
      // when nothing moved.
      next.forEach((t, i) => {
        const want = t.thumb;
        const at = this._rail.children[i];
        if (at !== want) this._rail.insertBefore(want, at || null);
        t.i = i;
        if (t.slide.hasAttribute('data-deck-skip')) t.thumb.setAttribute('data-skip', '');else t.thumb.removeAttribute('data-skip');
        if (this._selected.has(t.slide)) t.thumb.setAttribute('data-selected', '');else t.thumb.removeAttribute('data-selected');
      });
      this._thumbs = next;
      this._renumberRail();
      this._rail.scrollTop = st;
      if (prevTops.size) {
        const moved = [];
        this._thumbs.forEach(({
          thumb,
          slide
        }) => {
          // The live-dragged thumb is positioned by the drag tracker; a
          // FLIP transform+transition here would clobber it mid-drag.
          if (thumb === this._dragThumb) return;
          const old = prevTops.get(slide);
          if (old == null) return;
          const dy = old - thumb.getBoundingClientRect().top;
          if (Math.abs(dy) < 1) return;
          thumb.style.transition = 'none';
          thumb.style.transform = `translateY(${dy}px)`;
          moved.push(thumb);
        });
        if (moved.length) {
          // Commit the inverted positions before flipping the transition
          // on — otherwise the browser coalesces both style writes and
          // nothing animates.
          void this._rail.offsetHeight;
          moved.forEach(t => {
            t.style.transition = 'transform 180ms cubic-bezier(.2,.7,.3,1)';
            t.style.transform = '';
          });
          setTimeout(() => moved.forEach(t => {
            t.style.transition = '';
          }), 220);
        }
      }
      requestAnimationFrame(() => this._scaleThumbs());
      this._syncRail(false);
    }

    /** Create a lightweight thumb shell for one slide. The clone is
     *  materialized later by the IntersectionObserver. Event handlers
     *  look up the thumb's *current* index (via _thumbs.indexOf) so the
     *  same element can be reused across reorders. */
    _makeThumb(slide) {
      const thumb = document.createElement('div');
      thumb.className = 'thumb';
      thumb.tabIndex = 0;
      const num = document.createElement('div');
      num.className = 'num';
      const frame = document.createElement('div');
      frame.className = 'frame';
      thumb.append(num, frame);
      const entry = {
        thumb,
        num,
        frame,
        slide,
        clone: null,
        host: null,
        i: -1
      };
      // entry.i is refreshed on every _renderRail reconcile pass, so
      // handlers read the thumb's current position without an O(N) scan.
      const idx = () => entry.i;
      thumb.addEventListener('click', e => {
        const i = idx();
        const slide = this._slides[i];
        // WebKit doesn't focus a plain element on click — focus
        // explicitly so Delete/Backspace works right after selecting a
        // slide by mouse. preventScroll: _syncRail owns the rail's
        // scroll position.
        thumb.focus({
          preventScroll: true
        });
        if (e.shiftKey || e.metaKey || e.ctrlKey) {
          // Multi-select gestures adjust the selection without
          // navigating (Keynote/Figma convention).
          e.preventDefault();
          if (e.shiftKey) {
            // Range from the anchor (last plain/cmd-clicked slide;
            // falls back to the current slide) to here, replacing any
            // previous range.
            let a = this._selAnchor ? this._slides.indexOf(this._selAnchor) : -1;
            if (a < 0) {
              a = this._index;
              this._selAnchor = this._slides[a] || null;
            }
            this._selected.clear();
            for (let j = Math.min(a, i); j <= Math.max(a, i); j++) {
              this._selected.add(this._slides[j]);
            }
          } else if (slide) {
            // Toggle. An empty explicit selection implicitly holds the
            // current slide — materialize it first so cmd-clicking a
            // second slide selects both.
            if (!this._selected.size && i !== this._index && this._slides[this._index]) {
              this._selected.add(this._slides[this._index]);
            }
            if (this._selected.has(slide)) this._selected.delete(slide);else {
              this._selected.add(slide);
              this._selAnchor = slide;
            }
          }
          this._syncSelection();
          return;
        }
        this._clearSelection();
        this._selAnchor = slide || null;
        this._go(i, 'click');
      });
      // ↑/↓ step through the rail when a thumb has focus. _go clamps at the
      // ends and _applyIndex→_syncRail scrolls the new current thumb into
      // view; we move focus to it (preventScroll — _syncRail already
      // scrolled) so a held key walks the whole list. stopPropagation keeps
      // this out of the window-level _onKey nav handler.
      thumb.addEventListener('keydown', e => {
        // Delete/Backspace with the rail focused deletes this thumb's
        // slide through the same confirm dialog as the menu item.
        // Listening on the thumb (never window-level) is what keeps
        // typing in the notes panel / slide inputs from ever landing
        // here; the target check is belt-and-braces for anything
        // focusable that ends up inside a thumb.
        if ((e.key === 'Delete' || e.key === 'Backspace') && !e.metaKey && !e.ctrlKey && !e.altKey) {
          const t = e.target;
          if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
          e.preventDefault();
          e.stopPropagation();
          // Same refusals as the menu item: never every slide, never
          // while a prior structural op is waiting on its ack. The
          // whole-deck refusal is announced (the menu greys its item
          // out; a silently dead key reads as breakage). The rail-lock
          // refusal stays silent: it lasts one ack round-trip and
          // matches the existing single-delete behavior.
          if (this._railLock) return;
          // Explicit selection wins; otherwise the focused thumb (which
          // plain click and ↑/↓ keep equal to the current slide).
          const sel = this._selected.size ? this._selectionIndices() : [idx()];
          if (sel.length >= this._slides.length) {
            this._showNotice(sel.length === 1 ? 'The last slide can’t be deleted.' : 'At least one slide has to stay — the whole deck can’t be deleted.');
            return;
          }
          this._openConfirm(sel);
          return;
        }
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        if (e.metaKey || e.ctrlKey || e.altKey) return;
        e.preventDefault();
        e.stopPropagation();
        this._go(idx() + (e.key === 'ArrowDown' ? 1 : -1), 'keyboard');
        const cur = this._thumbs && this._thumbs[this._index];
        if (cur) cur.thumb.focus({
          preventScroll: true
        });
      });
      thumb.addEventListener('contextmenu', e => {
        e.preventDefault();
        this._openMenu(idx(), e.clientX, e.clientY);
      });
      thumb.draggable = true;
      thumb.addEventListener('dragstart', e => {
        // v1: dragging moves ONE slide, so a multi-selection would lie
        // about what's about to move — collapse it. (Group drag would
        // instead keep it and emit a batched move.)
        this._clearSelection();
        this._dragFrom = idx();
        // Deferred to the next frame: the [data-dragging] rule sets
        // pointer-events:none on the drag SOURCE, and applying that
        // synchronously inside dragstart makes Chromium (and WebKit) cancel
        // the drag — dragstart then an immediate dragend, no dragover or
        // drop, so thumbnails could not be reordered by dragging at all.
        // One frame is invisible and lands before the first dragover needs
        // the source to be hit-test-transparent. Guarded twice so the
        // attribute can never strand on a thumb that is no longer being
        // dragged (pointer-events:none would leave it unclickable for the
        // session): the pending frame is cancelled in dragend
        // (_cancelDragAttr), and the callback itself re-checks that THIS
        // thumb is still the live drag source (a new drag on another thumb
        // re-points the drag state). Deliberately NOT cancelled in
        // _stopDragTrack — _startDragTrack calls it at the start of every
        // drag, which would kill the mark this dragstart just scheduled
        // (see _cancelDragAttr).
        this._dragAttrRaf = requestAnimationFrame(() => {
          this._dragAttrRaf = null;
          if (this._dragFrom != null && this._dragThumb === thumb) {
            thumb.setAttribute('data-dragging', '');
          }
        });
        e.dataTransfer.effectAllowed = 'move';
        try {
          e.dataTransfer.setData('text/plain', String(this._dragFrom));
        } catch (err) {}
        // Constrain the drag visual to the rail's vertical axis. The
        // browser's default drag image is a free-floating snapshot that
        // follows the OS cursor in BOTH axes and the DnD API offers no way
        // to constrain it — so swap it for a transparent stand-in and move
        // the thumb itself along Y instead (_startDragTrack). The drop
        // logic below always read only clientY; this makes the visual
        // match it.
        try {
          e.dataTransfer.setDragImage(this._dragBlank(), 0, 0);
        } catch (err) {}
        this._startDragTrack(thumb, e.clientY);
      });
      thumb.addEventListener('dragend', () => {
        this._cancelDragAttr();
        thumb.removeAttribute('data-dragging');
        this._stopDragTrack();
        this._clearDrop();
        this._dragFrom = null;
      });
      thumb.addEventListener('dragover', e => {
        if (this._dragFrom == null) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const r = thumb.getBoundingClientRect();
        this._setDrop(idx(), e.clientY < r.top + r.height / 2 ? 'before' : 'after');
      });
      thumb.addEventListener('drop', e => {
        if (this._dragFrom == null) return;
        e.preventDefault();
        const i = idx();
        const r = thumb.getBoundingClientRect();
        let to = e.clientY >= r.top + r.height / 2 ? i + 1 : i;
        if (this._dragFrom < to) to--;
        const from = this._dragFrom;
        this._clearDrop();
        this._dragFrom = null;
        if (to !== from) this._moveSlide(from, to);
      });
      if (this._railObserver) this._railObserver.observe(frame);
      frame.__deckThumb = entry;
      return entry;
    }

    /** Lazily build the clone for a thumb that has scrolled into view. */
    _materialize(entry) {
      if (entry.host) return;
      const dw = this.designWidth,
        dh = this.designHeight;
      let clone = entry.slide.cloneNode(true);
      // The clone participates in the document's flat tree, so the
      // templates' position-based CSS page counters (.slide
      // { counter-increment: page }) would count every materialized
      // thumb before the real slides — folios print offset by the
      // thumb count (slide 2 reading "7" on a five-slide deck).
      // Neutralize the counter on the clone and drop its folio pill:
      // a thumbnail's own page number is unreadable at thumb scale
      // anyway, and the real slides' numbers stay truthful.
      clone.style.counterIncrement = 'none';
      clone.querySelectorAll('.page-foot').forEach(pf => pf.remove());
      // Canvas bitmaps don't clone — swap each cloned canvas for an <img>
      // of the live pixels. Best-effort: tainted canvases throw (left
      // as-is); zero-size are skipped; WebGL without preserveDrawingBuffer
      // reads back blank and the thumb gets a blank img (same as before).
      const liveCanvases = entry.slide.querySelectorAll('canvas');
      const cloneCanvases = clone.querySelectorAll('canvas');
      cloneCanvases.forEach((cv, i) => {
        const live = liveCanvases[i];
        if (!live || !live.width || !live.height) return;
        try {
          const img = document.createElement('img');
          img.src = live.toDataURL();
          img.alt = '';
          img.style.cssText = cv.style.cssText;
          img.className = cv.className;
          img.width = live.width;
          img.height = live.height;
          // Author CSS that sized the <canvas> via tag selector won't match
          // the <img> — pin the live canvas's laid-out box on the snapshot.
          if (live.clientWidth) {
            img.style.width = live.clientWidth + 'px';
            img.style.height = live.clientHeight + 'px';
          }
          cv.replaceWith(img);
        } catch (e) {}
      });
      // Neuter heavy media; replace <video> with its poster so the box
      // keeps a visual. <iframe>/<audio> become empty placeholders.
      // Parity with _inertify: transient top-layer UI never belongs in a
      // static thumb.
      clone.querySelectorAll('[popover], dialog').forEach(el => el.remove());
      clone.querySelectorAll('iframe, audio, object, embed').forEach(el => {
        el.removeAttribute('src');
        el.removeAttribute('srcdoc');
        el.removeAttribute('data');
        el.innerHTML = '';
      });
      clone.querySelectorAll('video').forEach(el => {
        if (!el.poster) {
          el.removeAttribute('src');
          el.innerHTML = '';
          return;
        }
        const img = document.createElement('img');
        img.src = el.poster;
        img.alt = '';
        img.style.cssText = el.style.cssText + ';object-fit:cover;width:100%;height:100%;';
        img.className = el.className;
        el.replaceWith(img);
      });
      // Images: defer decode and let the browser pick the smallest
      // srcset candidate for the ~140px thumb. Same-URL clones reuse the
      // slide's decoded bitmap (URL-keyed cache), so the remaining cost
      // is paint/composite — lazy+async keeps that off the main thread.
      clone.querySelectorAll('img').forEach(el => {
        el.loading = 'lazy';
        el.decoding = 'async';
        if (el.srcset) el.sizes = (this._railPx || 188) + 'px';
      });
      // Custom elements inside the slide would have their
      // connectedCallback fire when the clone is appended. Replace them
      // with inert boxes (_neuter) so a component-heavy deck doesn't run
      // N copies of each component's mount logic in the rail. Children
      // are preserved so layout-wrapper elements (<my-column><h2>…</h2>)
      // still show their authored content, and a shadow tree cloned along
      // via attachShadow({clonable:true}) (e.g. <image-slot>) moves onto
      // the box so the thumb shows the component's rendered content. The
      // querySelectorAll NodeList is static, so nested custom elements in
      // the moved subtree are still visited on later iterations.
      // querySelectorAll('*') returns descendants only — a custom-element
      // slide root (<my-slide>…</my-slide>) would slip through and upgrade
      // on append. Swap the root first.
      if (clone.tagName.includes('-')) clone = this._neuter(clone);
      clone.querySelectorAll('*').forEach(el => {
        if (el.tagName.includes('-')) el.replaceWith(this._neuter(el));
      });
      // Strip ids only now: a defined custom element upgrades synchronously
      // during cloneNode and re-renders on attribute callbacks, so removing
      // 'id' any earlier resets components (e.g. <image-slot> falls back to
      // its author src). Post-neuter, only inert boxes and plain elements
      // remain, where the strip is just the usual duplicate-id hygiene.
      clone.removeAttribute('id');
      clone.removeAttribute('data-deck-active');
      clone.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
      clone.style.cssText += ';position:absolute;top:0;left:0;transform-origin:0 0;' + 'pointer-events:none;width:' + dw + 'px;height:' + dh + 'px;' + 'box-sizing:border-box;overflow:hidden;visibility:visible;opacity:1;';
      const host = document.createElement('div');
      host.style.cssText = 'position:absolute;inset:0;';
      // Clones are display-only: inert removes anything focusable inside
      // them from the tab order, so the rail's Delete/Backspace handler
      // can never see a (retargeted) key press from cloned content.
      host.inert = true;
      this._syncThumbHostAttrs(host);
      const sr = host.attachShadow({
        mode: 'open'
      });
      if (this._adoptedSheet) sr.adoptedStyleSheets = [this._adoptedSheet];else {
        const st = document.createElement('style');
        st.textContent = this._authorCss || '';
        sr.appendChild(st);
      }
      sr.appendChild(clone);
      entry.frame.appendChild(host);
      entry.host = host;
      entry.clone = clone;
      if (this._thumbScale) clone.style.transform = 'scale(' + this._thumbScale + ')';
      // Once materialized the IO callback is a no-op early-return —
      // unobserve so scroll doesn't keep firing it.
      if (this._railObserver) this._railObserver.unobserve(entry.frame);
    }

    /** Replace a cloned custom element with an inert box (see the comment
     *  in _materialize). A shadow tree cloned along via {clonable:true}
     *  moves onto the box, so the thumb shows the component's real content
     *  with zero component logic; :host rules in the moved <style> match
     *  the box, and the preserved data-* attrs keep :host([data-…])
     *  selectors working. */
    _neuter(el) {
      // Adopt the shadow only when the cloned root carries renderable
      // content. A constructor-attach / connectedCallback-render component
      // clones into an empty (or style-only) slotless root — adopting that
      // would hide the light children the box is about to receive and drop
      // the placeholder chrome. Such components fall back to the plain box.
      let sr = el.shadowRoot;
      if (sr) {
        let renderable = false;
        for (let n = sr.firstElementChild; n; n = n.nextElementSibling) {
          const t = n.tagName;
          if (t !== 'STYLE' && t !== 'LINK') {
            renderable = true;
            break;
          }
        }
        if (!renderable) sr = null;
      }
      const box = document.createElement('div');
      box.style.cssText = (el.getAttribute('style') || '') + (sr ? '' : ';background:rgba(0,0,0,0.06);border:1px dashed rgba(0,0,0,0.15);');
      box.className = el.className;
      // Preserve theming/i18n hooks so [data-*] / :lang() / [dir]
      // descendant selectors still match the neutered root — but not
      // pointer-interaction transients (a mid-reframe/mid-drag re-clone
      // would render the interaction chrome statically in the thumb).
      for (const a of el.attributes) {
        const n = a.name;
        if (n === 'data-reframe' || n === 'data-panning' || n === 'data-over') continue;
        if (n.startsWith('data-') || n.startsWith('aria-') || n === 'lang' || n === 'dir' || n === 'role' || n === 'title') {
          box.setAttribute(n, a.value);
        }
      }
      while (el.firstChild) box.appendChild(el.firstChild);
      if (sr) this._adoptShadow(box, sr);
      return box;
    }

    /** Move a cloned shadow tree onto a neutered thumbnail box: attach an
     *  open root on the box, carry adoptedStyleSheets, move the children,
     *  then make the content inert. */
    _adoptShadow(box, sr) {
      let root;
      try {
        root = box.attachShadow({
          mode: 'open'
        });
      } catch (e) {
        return;
      }
      // Engine-cloned shadow roots never carry adoptedStyleSheets, but a
      // defined component's clone is upgrade-rebuilt (constructor runs
      // during cloneNode), so sheets it adopts there are present and
      // shared by reference — carry them.
      if (sr.adoptedStyleSheets && sr.adoptedStyleSheets.length) {
        try {
          root.adoptedStyleSheets = Array.prototype.slice.call(sr.adoptedStyleSheets);
        } catch (e) {}
      }
      // Clone rather than move: moving preserves listeners an upgraded
      // clone's constructor attached inside its shadow; cloning sheds
      // them, keeping thumbs free of component logic categorically.
      for (let n = sr.firstChild; n; n = n.nextSibling) {
        root.appendChild(n.cloneNode(true));
      }
      this._inertify(root);
    }

    /** Strip anything executable from copied shadow content and apply the
     *  same custom-element/media/img policy as the light-DOM clone.
     *  (Canvases inside copied shadow content stay blank — there is no
     *  live↔clone pairing across shadow boundaries to snapshot from.) */
    _inertify(root) {
      root.querySelectorAll('script').forEach(s => s.remove());
      // Transient top-layer UI can never belong in a static thumb. (A
      // cloned [popover] is display:none anyway — open state doesn't
      // clone — this just makes it categorical.)
      root.querySelectorAll('[popover], dialog').forEach(el => el.remove());
      // Same heavy-media policy as the light-DOM clone above.
      root.querySelectorAll('iframe, audio, object, embed').forEach(el => {
        el.removeAttribute('src');
        el.removeAttribute('srcdoc');
        el.removeAttribute('data');
        el.innerHTML = '';
      });
      root.querySelectorAll('video').forEach(el => {
        if (!el.poster) {
          el.removeAttribute('src');
          el.innerHTML = '';
          return;
        }
        const img = document.createElement('img');
        img.src = el.poster;
        img.alt = '';
        img.style.cssText = el.style.cssText + ';object-fit:cover;width:100%;height:100%;';
        img.className = el.className;
        el.replaceWith(img);
      });
      root.querySelectorAll('*').forEach(el => {
        for (let i = el.attributes.length - 1; i >= 0; i--) {
          if (/^on/i.test(el.attributes[i].name)) {
            el.removeAttribute(el.attributes[i].name);
          }
        }
      });
      root.querySelectorAll('img').forEach(el => {
        el.loading = 'lazy';
        el.decoding = 'async';
        if (el.srcset) el.sizes = (this._railPx || 188) + 'px';
      });
      // Nested custom elements inside copied shadow content would upgrade
      // on append — same treatment as the light DOM. querySelectorAll is
      // static, so boxes created mid-walk don't re-enter this loop.
      root.querySelectorAll('*').forEach(el => {
        if (el.tagName.includes('-')) el.replaceWith(this._neuter(el));
      });
    }

    /** Re-clone a single thumb (live-update path). No-op if the thumb
     *  hasn't been materialized yet — it'll pick up current content when
     *  it scrolls into view. */
    _refreshThumb(slide) {
      const entry = (this._thumbs || []).find(t => t.slide === slide);
      if (!entry || !entry.host) return;
      entry.host.remove();
      entry.host = entry.clone = null;
      this._materialize(entry);
    }
    _scaleThumbs() {
      if (!this._thumbs || !this._thumbs.length) return;
      // Every frame is the same width; if it reads 0 the rail is
      // display:none (noscale / no-rail / presenting / print) — leave the
      // clones as-is and re-run when the rail is revealed.
      const fw = this._thumbs[0].frame.offsetWidth;
      if (!fw) return;
      this._thumbScale = fw / this.designWidth;
      this._thumbs.forEach(({
        clone
      }) => {
        if (clone) clone.style.transform = 'scale(' + this._thumbScale + ')';
      });
    }
    _setDrop(i, where) {
      // dragover fires at pointer-event rate; touch only the previous
      // and new target rather than sweeping all N thumbs.
      const t = this._thumbs && this._thumbs[i];
      if (this._dropOn && this._dropOn !== t) {
        this._dropOn.thumb.removeAttribute('data-drop');
      }
      if (t) t.thumb.setAttribute('data-drop', where);
      this._dropOn = t || null;
    }
    _clearDrop() {
      if (this._dropOn) this._dropOn.thumb.removeAttribute('data-drop');
      this._dropOn = null;
    }

    /** 1×1 transparent stand-in for setDragImage. Kept attached (offscreen
     *  in the shadow root) because some engines ignore a drag image that
     *  isn't in a rendered tree. Created lazily, reused for every drag. */
    _dragBlank() {
      if (!this._dragBlankEl) {
        const c = document.createElement('canvas');
        c.width = 1;
        c.height = 1;
        c.style.cssText = 'position:fixed;left:-9999px;top:0;width:1px;height:1px;';
        this._root.appendChild(c);
        this._dragBlankEl = c;
      }
      return this._dragBlankEl;
    }

    /** Vertical-only drag tracking: translate the dragged thumb along Y to
     *  follow the pointer, clamped to the rail, ignoring X entirely. A
     *  document-level capture listener is used because native dragover
     *  fires wherever the pointer is — so the thumb keeps tracking even
     *  while the pointer wanders over the stage — and it is removed the
     *  moment the drag ends. getBoundingClientRect already reflects the
     *  current transform, so the layout position is recovered by
     *  subtracting the translation applied so far (rail auto-scroll moves
     *  the layout position mid-drag; see the rail dragover handler). */
    _startDragTrack(thumb, startY) {
      // A lost dragend (the dragged thumb removed mid-drag by a remote
      // edit's re-render — browsers fire no dragend on a disconnected
      // source) would otherwise leave the previous listener installed
      // forever once this overwrite lands.
      this._stopDragTrack();
      this._dragThumb = thumb;
      // The FLIP reorder animation drives transform through a transition;
      // the live drag must not inherit one, or the thumb rubber-bands.
      // Killed BEFORE the grab-offset read: mid-FLIP the rect includes the
      // interpolated transform, which would bake a constant offset into
      // the whole drag.
      thumb.style.transition = 'none';
      this._dragGrab = startY - thumb.getBoundingClientRect().top;
      this._dragTy = 0;
      this._onDragTrack = e => {
        const t = this._dragThumb;
        if (!t) return;
        const rail = this._rail.getBoundingClientRect();
        const r = t.getBoundingClientRect();
        // A transformed ancestor (author wraps the deck in a CSS scale;
        // canvas-mode pan/zoom) scales viewport deltas: translateY(N)
        // moves the rect by s·N. Measure s from the thumb itself (rect is
        // scaled, offsetHeight is layout px) so the feedback loop stays
        // exact instead of oscillating at s ≥ 2. offsetHeight is 0 only
        // when unrendered — nothing to track then, treat as unscaled.
        const s = t.offsetHeight ? r.height / t.offsetHeight : 1;
        const layoutTop = r.top - s * this._dragTy;
        let want = e.clientY - this._dragGrab;
        want = Math.max(rail.top, Math.min(want, rail.bottom - r.height));
        this._dragTy = (want - layoutTop) / s;
        t.style.transform = 'translateY(' + this._dragTy + 'px)';
      };
      document.addEventListener('dragover', this._onDragTrack, true);
    }

    /** Cancel the thumb's deferred data-dragging mark if its frame has not
     *  fired yet — see the dragstart deferral. Called from dragend only:
     *  _stopDragTrack is the wrong home for it, because _startDragTrack
     *  defensively calls _stopDragTrack at the START of every drag (its
     *  lost-dragend reset), so a cancel there kills the mark the same
     *  dragstart just scheduled. The strand that matters — pointer-
     *  events:none left on a CONNECTED thumb that is no longer being
     *  dragged — is closed two ways: dragend cancels the pending frame
     *  here, and the frame callback re-checks that THIS thumb is still the
     *  live drag source (_dragFrom and _dragThumb, both cleared/re-pointed
     *  by dragend or by a new drag). The remaining lost-dragend case — the
     *  source slide removed mid-drag, so no dragend fires — ends with that
     *  thumb discarded by the rail reconcile (thumbs are keyed by slide
     *  element and a removed slide's thumb is not reused), so a mark landing
     *  on it is on a discarded node. The risk this defer adds over the old
     *  synchronous set is therefore the narrow rAF-after-dragend window,
     *  which the dragend cancel covers. */
    _cancelDragAttr() {
      if (this._dragAttrRaf != null) {
        cancelAnimationFrame(this._dragAttrRaf);
        this._dragAttrRaf = null;
      }
    }
    _stopDragTrack() {
      if (this._onDragTrack) {
        document.removeEventListener('dragover', this._onDragTrack, true);
        this._onDragTrack = null;
      }
      const t = this._dragThumb;
      if (t) {
        t.style.transform = '';
        t.style.transition = '';
      }
      this._dragThumb = null;
      this._dragTy = 0;
    }
    _syncRail(follow) {
      if (!this._thumbs) return;
      this._thumbs.forEach(({
        thumb
      }, i) => {
        if (i === this._index) {
          thumb.setAttribute('data-current', '');
          if (follow && typeof thumb.scrollIntoView === 'function') {
            thumb.scrollIntoView({
              block: 'nearest'
            });
          }
        } else {
          thumb.removeAttribute('data-current');
        }
      });
    }
    _openMenu(i, x, y) {
      if (!this._menu) return;
      this._menuIndex = i;
      const slide = this._slides[i];
      // Right-clicking a thumb OUTSIDE the selection collapses the
      // selection to that thumb (platform convention) — the menu then
      // always targets exactly what's highlighted.
      if (this._selected.size && slide && !this._selected.has(slide)) {
        this._selected.clear();
        this._selected.add(slide);
        this._selAnchor = slide;
        this._syncSelection();
      }
      const sel = this._selectionIndices();
      const bulk = sel.length > 1;
      this._menuIndices = bulk ? sel : [i];
      // Bulk mode offers only the one batched op that exists (delete);
      // the single-slide items address one index and stay hidden.
      this._menu.querySelectorAll('[data-act="skip"], [data-act="up"], [data-act="down"], [data-act="duplicate"], hr').forEach(el => {
        el.style.display = bulk ? 'none' : '';
      });
      const skip = slide && slide.hasAttribute('data-deck-skip');
      this._menu.querySelector('[data-act="skip"]').textContent = skip ? 'Unskip slide' : 'Skip slide';
      this._menu.querySelector('[data-act="up"]').disabled = i <= 0;
      this._menu.querySelector('[data-act="down"]').disabled = i >= this._slides.length - 1;
      const del = this._menu.querySelector('[data-act="delete"]');
      del.textContent = bulk ? 'Delete ' + sel.length + ' slides' : 'Delete slide';
      del.disabled = bulk ? sel.length >= this._slides.length : this._slides.length <= 1;
      // Place, then clamp to viewport after it's measurable.
      this._menu.style.left = x + 'px';
      this._menu.style.top = y + 'px';
      this._menu.setAttribute('data-open', '');
      const r = this._menu.getBoundingClientRect();
      const nx = Math.min(x, window.innerWidth - r.width - 4);
      const ny = Math.min(y, window.innerHeight - r.height - 4);
      this._menu.style.left = Math.max(4, nx) + 'px';
      this._menu.style.top = Math.max(4, ny) + 'px';
    }
    _closeMenu() {
      if (this._menu) this._menu.removeAttribute('data-open');
      this._menuIndex = -1;
      this._menuIndices = null;
    }
    _openConfirm(sel) {
      if (!this._confirm) return;
      const list = Array.isArray(sel) ? sel : [sel];
      // Hold the slide ELEMENTS: the deck can re-render while the dialog
      // is open (collaborator/agent edit), and a frozen index list would
      // then address the wrong slides — a same-count reorder even passes
      // the host's witness guard. Elements re-resolve at danger-click.
      this._confirmEls = list.map(i => this._slides[i]).filter(Boolean);
      // Title uses the rail's skip-aware label, so the confirm names the
      // number the user right-clicked (a raw index would disagree with the
      // rail whenever a skipped slide precedes the target).
      const lbl = list.length === 1 ? this._slideLabel(list[0]) : '';
      this._confirm.querySelector('.title').textContent = list.length === 1 ? lbl ? 'Delete slide ' + lbl + '?' : 'Delete skipped slide?' : 'Delete ' + list.length + ' slides?';
      this._confirm.querySelector('.msg').textContent = list.length === 1 ? 'This slide will be removed from the deck.' : 'These slides will be removed from the deck.';
      this._confirm.setAttribute('data-open', '');
      const btn = this._confirm.querySelector('.danger');
      if (btn && btn.focus) btn.focus();
    }
    _closeConfirm() {
      if (this._confirm) this._confirm.removeAttribute('data-open');
      this._confirmEls = null;
    }

    /** Return focus to the current slide's thumb so the keyboard flow
     *  (Delete → Enter → Delete …) survives the confirm dialog closing.
     *  Without 'force', skipped while a structural op is in flight
     *  (_railLock): _index is then an optimistic post-op value that
     *  doesn't address the pre-op thumb list — _pendingRailRefocus stays
     *  armed and the ack/slotchange paths call back with force once the
     *  rail reflects the op. Skipped (and disarmed) while the rail is
     *  inert (hidden / presenting). */
    _focusCurrentThumb(force) {
      if (!force && this._railLock) return;
      this._pendingRailRefocus = false;
      // Never yank focus from content the user reached meanwhile (e.g.
      // an input inside a slide during the ack round-trip) — only
      // reclaim it from the rail's own surfaces, or from nowhere.
      const ae = this._root && this._root.activeElement;
      const ours = !ae || this._rail && this._rail.contains(ae) || this._confirm && this._confirm.contains(ae) || this._menu && this._menu.contains(ae);
      const lightAe = document.activeElement;
      const lightOk = !lightAe || lightAe === document.body || lightAe === this;
      if (!ours || !lightOk) return;
      const cur = this._thumbs && this._thumbs[this._index];
      if (cur && this._rail && !this._rail.inert) cur.thumb.focus({
        preventScroll: true
      });
    }

    /** Selection as sorted slide indices. An empty explicit selection
     *  means the current slide (the rail's implicit selection). */
    _selectionIndices() {
      const out = [];
      this._slides.forEach((s, i) => {
        if (this._selected.has(s)) out.push(i);
      });
      if (!out.length && this._slides[this._index]) out.push(this._index);
      return out;
    }
    _clearSelection() {
      // Re-anchor before the early return: a plain click followed by
      // arrow/tap navigation leaves _selected empty but the anchor
      // pointing at the old slide, and a later shift-click would range
      // from there instead of the current slide.
      this._selAnchor = null;
      if (!this._selected.size) return;
      this._selected.clear();
      this._syncSelection();
    }
    _syncSelection() {
      (this._thumbs || []).forEach(t => {
        if (this._selected.has(t.slide)) t.thumb.setAttribute('data-selected', '');else t.thumb.removeAttribute('data-selected');
      });
    }

    /** Rail mutations. When a dc-runtime is present (`window.__dcUpdate`)
     *  the host owns the light DOM — handlers emit a dc-op only and the
     *  host applies it (to the editor's model or to the source file) and
     *  re-renders via dc-runtime; slotchange catches the rail up.
     *  Structural ops lock rail input until the host acks so a rapid second
     *  click can't address a stale index; setAttr/removeAttr respect the
     *  lock but don't set it (indices unchanged; the host serializes).
     *  `newIndex` is written to location.hash so slotchange's
     *  _restoreIndex lands on the right slide.
     *
     *  With NO dc-runtime (a raw .html deck), there's no re-render path,
     *  so handlers self-mutate locally for an instant update and emit
     *  `emitOnly: false`; the host persists to disk without
     *  re-rendering over the already-mutated DOM.
     *
     *  See docs/dc-ops.md for the contract. */
    /** True when the page's DC runtime reports a live template stream for
     *  any component here (newer support.js bundles only — older bundles
     *  lack the signal and the HOST-side gate covers those decks). Rail
     *  mutations are refused for the duration: a mid-stream op addresses
     *  slide indices the stream is rewriting underneath the click. */
    _streamActive() {
      try {
        return !!window.__dcUpdate && typeof window.__dcStreaming === 'function' && window.__dcStreaming();
      } catch (e) {
        return false;
      }
    }

    /** Transient in-stage notice for a refused mid-stream rail op. */
    _showStreamNotice() {
      this._showNotice('Claude is still updating this deck — try again when it finishes.');
    }

    /** Transient bottom-center toast for a refused rail gesture. */
    _showNotice(text) {
      if (!this._root) return;
      let n = this._streamNotice;
      if (!n) {
        n = document.createElement('div');
        n.className = 'export-hidden';
        n.setAttribute('data-omelette-chrome', '');
        n.setAttribute('role', 'status');
        n.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);' + 'background:rgba(22,22,22,.94);color:#fff;' + 'font:500 13px/1.4 system-ui,sans-serif;padding:8px 14px;' + 'border-radius:8px;z-index:2147483646;pointer-events:none;' + 'opacity:0;transition:opacity .15s ease';
        this._root.append(n);
        this._streamNotice = n;
      }
      n.textContent = text;
      n.style.opacity = '1';
      if (this._streamNoticeTimer) clearTimeout(this._streamNoticeTimer);
      this._streamNoticeTimer = setTimeout(() => {
        n.style.opacity = '0';
      }, 2600);
    }
    _emitDcOp(op, slide, lock, newIndex) {
      // Mid-stream guard: refuse the gesture outright — no lock, no
      // optimistic index change, no emit, no self-mutation (returning
      // true short-circuits every caller). The host applies the same
      // gate for decks whose committed support.js predates the signal.
      if (this._streamActive()) {
        this._showStreamNotice();
        return true;
      }
      // Slide index (template/script/style filtered — same as
      // _collectSlides). deck-stage is a filtered-index dc-op emitter;
      // the host resolves against findDeckStage().slideTids. Callers
      // already pass `to` as a slide index.
      op.at = this._slides.indexOf(slide);
      op.witness = {
        childCount: this._slides.length
      };
      // dc-runtime wraps an <x-import>-mounted component in a
      // <div class="sc-host-x" data-dc-tpl="N"> host — the stamp is on the
      // WRAPPER, not this element. closest() finds it (or this element's
      // own stamp when directly templated).
      const host = this.closest('[data-dc-tpl]');
      const tid = host && host.getAttribute('data-dc-tpl');
      op.mount = {
        tid: tid !== null ? parseInt(tid, 10) : null,
        tag: 'deck-stage'
      };
      op.emitOnly = !!window.__dcUpdate;
      if (op.emitOnly) {
        if (lock) this._railLock = true;
        if (newIndex != null && newIndex !== this._index) {
          this._indexBeforeEmit = this._index;
          this._index = newIndex;
          try {
            history.replaceState(null, '', '#' + (newIndex + 1));
          } catch (e) {}
        }
      }
      this.dispatchEvent(new CustomEvent('dc-op', {
        detail: op,
        bubbles: true,
        composed: true
      }));
      return op.emitOnly;
    }

    /** Delete a set of slides (pre-op indices). One slide delegates to
     *  _deleteSlide — the plain 'remove' op — so single deletes keep
     *  working against hosts that predate 'removeMany'. A bulk delete is
     *  ONE op: one host write, one undo snapshot, and indices that all
     *  address the same pre-op deck (N acked single ops would each need
     *  a fresh witness). */
    _deleteSlides(list) {
      if (this._railLock || !list) return;
      const indices = [...new Set(list)].filter(i => this._slides[i]).sort((a, b) => a - b);
      if (!indices.length || indices.length >= this._slides.length) return;
      if (indices.length === 1) {
        this._deleteSlide(indices[0]);
        return;
      }
      // Mirrors _duplicateSlide: check the stream gate before doing any
      // work (_emitDcOp re-checks).
      if (this._streamActive()) {
        this._showStreamNotice();
        return;
      }
      const els = indices.map(i => this._slides[i]);
      const del = new Set(indices);
      const cur = this._index;
      // New current index in post-op space: shift the kept slide left by
      // the deletions below it; if the current slide itself is deleted,
      // land on the nearest survivor (after, else before).
      const below = n => indices.reduce((k, x) => k + (x < n ? 1 : 0), 0);
      let ni;
      if (!del.has(cur)) {
        ni = cur - below(cur);
      } else {
        let s = -1;
        for (let j = cur + 1; j < this._slides.length; j++) {
          if (!del.has(j)) {
            s = j;
            break;
          }
        }
        if (s === -1) {
          for (let j = cur - 1; j >= 0; j--) {
            if (!del.has(j)) {
              s = j;
              break;
            }
          }
        }
        ni = s < 0 ? 0 : s - below(s);
      }
      // Emit-path deletes can't refocus until the host re-renders; arm
      // the flag at emit time (never on a refused/no-op path) so
      // ack/slotchange can finish the keyboard flow's focus hand-back.
      // The local path clears it via the caller's _focusCurrentThumb().
      this._pendingRailRefocus = true;
      if (this._emitDcOp({
        op: 'removeMany',
        indices
      }, els[0], true, ni)) return;
      this._index = ni;
      this._squelchSlotChange = true;
      els.forEach(el => el.remove());
      this._collectSlides();
      this._applyIndex({
        showOverlay: true,
        broadcast: true,
        reason: 'mutation'
      });
    }
    _deleteSlide(i) {
      if (this._railLock) return;
      const slide = this._slides[i];
      if (!slide || this._slides.length <= 1) return;
      const cur = this._index;
      const ni = i < cur || i === cur && i === this._slides.length - 1 ? cur - 1 : cur;
      this._pendingRailRefocus = true;
      if (this._emitDcOp({
        op: 'remove'
      }, slide, true, ni)) return;
      this._index = ni;
      this._squelchSlotChange = true;
      slide.remove();
      this._collectSlides();
      this._applyIndex({
        showOverlay: true,
        broadcast: true,
        reason: 'mutation'
      });
    }
    _duplicateSlide(i) {
      if (this._railLock) return;
      const slide = this._slides[i];
      if (!slide) return;
      // Mint ids + copy component state BEFORE emitting, so the op can
      // carry the id map — but never mint for an op the stream gate is
      // about to refuse (_emitDcOp re-checks; this avoids orphaned keys).
      if (this._streamActive()) {
        this._showStreamNotice();
        return;
      }
      const copy = slide.cloneNode(true);
      copy.removeAttribute('id');
      const ids = this._remintDuplicateIds(copy);
      const op = {
        op: 'duplicate'
      };
      if (ids) op.ids = ids;
      if (this._emitDcOp(op, slide, true, i + 1)) return;
      this._index = i + 1;
      this._squelchSlotChange = true;
      this.insertBefore(copy, slide.nextSibling);
      this._collectSlides();
      this._applyIndex({
        showOverlay: true,
        broadcast: true,
        reason: 'mutation'
      });
    }

    /** Duplicate id policy. Plain ids are stripped — two live slides must
     *  not share one id. But a component that KEYS persistent state by id
     *  (image-slot's sidecar photo) would silently lose that state with
     *  its id. Such a component opts out of the strip by exposing a
     *  static cloneSlot(fromId, isFree) that copies its stored state
     *  under a fresh id of its choosing and returns that id. The old→new
     *  map is returned (or null) and rides the dc-op so the host writes
     *  the SAME ids into source — without that, the copy's state would
     *  revert on reload (docs/dc-ops.md). */
    _remintDuplicateIds(copy) {
      const ids = {};
      let found = false;
      const used = new Set();
      const idOk = /^[A-Za-z][\w-]{0,63}$/;
      const isFree = id => idOk.test(id) && !used.has(id) && !document.getElementById(id);
      copy.querySelectorAll('[id]').forEach(el => {
        const tag = el.tagName.toLowerCase();
        const cls = tag.indexOf('-') >= 0 && customElements.get(tag);
        let next = null;
        if (el.id && cls && typeof cls.cloneSlot === 'function') {
          try {
            next = cls.cloneSlot(el.id, isFree);
          } catch (e) {}
        }
        // Re-checked here so a misbehaving static can't smuggle a dupe
        // or an unsafe value into the document / the emitted op.
        if (typeof next === 'string' && isFree(next)) {
          ids[el.id] = next;
          used.add(next);
          el.id = next;
          found = true;
        } else {
          el.removeAttribute('id');
        }
      });
      return found ? ids : null;
    }
    _toggleSkip(i) {
      if (this._railLock) return;
      const slide = this._slides[i];
      if (!slide) return;
      const on = !slide.hasAttribute('data-deck-skip');
      if (this._emitDcOp(on ? {
        op: 'setAttr',
        attr: 'data-deck-skip',
        value: ''
      } : {
        op: 'removeAttr',
        attr: 'data-deck-skip'
      }, slide, false)) return;
      if (on) slide.setAttribute('data-deck-skip', '');else slide.removeAttribute('data-deck-skip');
    }
    _skippedIndices() {
      const out = [];
      for (let i = 0; i < this._slides.length; i++) {
        if (this._slides[i].hasAttribute('data-deck-skip')) out.push(i);
      }
      return out;
    }

    /** Rail numbering, skip-aware: a skipped slide shows no number and the
     *  rest stay contiguous (1..visible), so the labels match the positions
     *  the overlay counter reports. Cheap (text writes are diffed), safe to
     *  call after any reconcile or skip toggle. */
    _renumberRail() {
      let v = 0;
      (this._thumbs || []).forEach(t => {
        const label = t.slide.hasAttribute('data-deck-skip') ? '' : String(++v);
        if (t.num.textContent !== label) t.num.textContent = label;
      });
    }

    /** Skip-aware label for slide i — the same numbering _renumberRail
     *  paints: '' for a skipped slide, else its 1-based position among
     *  non-skipped slides. Display surfaces (e.g. the delete confirm)
     *  use this so they never name a number the rail doesn't show. */
    _slideLabel(i) {
      const s = this._slides[i];
      if (!s || s.hasAttribute('data-deck-skip')) return '';
      let v = 0;
      for (let k = 0; k <= i; k++) {
        if (!this._slides[k].hasAttribute('data-deck-skip')) v++;
      }
      return String(v);
    }

    /** Overlay counter, skip-aware: position among non-skipped slides over
     *  the non-skipped total. A skipped CURRENT slide (reachable by rail
     *  click or deep link, never by _advance) shows '–' — its number is
     *  gone from the rail, so any digit here would lie. */
    _syncCount() {
      if (!this._countEl || !this._totalEl) return;
      // Empty deck: keep the overlay's initial "1 / 1" (it has nothing to
      // count and isn't visible without slides) — the guest fallback for
      // frozen copies leaves empty decks alone for the same rendering.
      if (!this._slides.length) {
        this._countEl.textContent = '1';
        this._totalEl.textContent = '1';
        return;
      }
      let pos = 0,
        total = 0;
      this._slides.forEach((s, i) => {
        if (!s.hasAttribute('data-deck-skip')) {
          total++;
          if (i <= this._index) pos = total;
        }
      });
      const cur = this._slides[this._index];
      const curSkipped = !cur || cur.hasAttribute('data-deck-skip');
      this._countEl.textContent = curSkipped ? '–' : String(pos);
      this._totalEl.textContent = String(total);
    }
    _moveSlide(i, j) {
      if (this._railLock || j < 0 || j >= this._slides.length || j === i) return;
      const cur = this._index;
      const ni = cur === i ? j : i < cur && j >= cur ? cur - 1 : i > cur && j <= cur ? cur + 1 : cur;
      const slide = this._slides[i];
      if (this._emitDcOp({
        op: 'move',
        to: j
      }, slide, true, ni)) return;
      const ref = j < i ? this._slides[j] : this._slides[j].nextSibling;
      this._index = ni;
      this._squelchSlotChange = true;
      this.insertBefore(slide, ref);
      this._collectSlides();
      this._applyIndex({
        showOverlay: false,
        broadcast: true,
        reason: 'mutation'
      });
    }

    // Public API ------------------------------------------------------------

    /** Current slide index (0-based). */
    get index() {
      return this._index;
    }
    /** Total slide count. */
    get length() {
      return this._slides.length;
    }
    /** Programmatically navigate. */
    goTo(i) {
      this._go(i, 'api');
    }
    next() {
      this._advance(1, 'api');
    }
    prev() {
      this._advance(-1, 'api');
    }
    reset() {
      this._go(0, 'api');
    }
  }
  if (!customElements.get('deck-stage')) {
    customElements.define('deck-stage', DeckStage);
  }
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/deck-stage.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/ds-base.js
try { (() => {
// Loads this design system into the template. In a consuming project, point
// base at the bound DS folder relative to this file (e.g. '_ds/<folder>' at
// the project root, '../_ds/<folder>' one level down) — one line to edit.
(() => {
  const base = '../..';
  for (const p of ["tokens/fonts.css", "tokens/colors.css", "tokens/typography.css", "tokens/spacing.css", "tokens/radii-shadows.css", "styles/base.css", "styles/classes.css", "styles/controls-v2.css", "styles/tables-v2.css", "styles/feedback-v2.css", "styles.css"]) {
    const l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = base + '/' + p;
    document.head.appendChild(l);
  }
  const s = document.createElement('script');
  s.src = base + '/_ds_bundle.js';
  s.onerror = () => console.error('ds-base.js: failed to load ' + s.src + ' — if this is a consuming project, point the base line in ds-base.js at the bound _ds/<folder> tree relative to this page (e.g. _ds/<folder> at the project root, ../_ds/<folder> one level down); in a fresh design system this can just mean the bundle is not compiled yet');
  document.head.appendChild(s);
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-09-29-docs/design-system/templates/pitch-deck/ds-base.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-01-documents/template/doc-page.js
try { (() => {
// @ds-adherence-ignore -- omelette starter scaffold (raw elements/hex/px by design)
// Copied omelette starter. Re-running copy_starter_component with this kind overwrites this file with the latest version (page content is unaffected).
/* BEGIN USAGE */
/**
 * <doc-page> — paged-document shell for printable HTML.
 *
 * FIRST, decide how the document paginates — up front, before building:
 *
 * - FLOWING document (the default): write the whole document as one
 *   normal HTML flow inside <doc-page>; the browser's print engine
 *   splits it onto pages at export. Use for long-form documents with a
 *   single text flow: reports, memos, letters, essays.
 * - EXPLICIT pagination: a fixed set of pre-paginated pages, one
 *   <section class="page"> child per page. Use when the user asks for a
 *   specific page count, or the design implies one: a one-page resume, a
 *   two-sided flier, a poster, a certificate, a brochure — any richly
 *   laid-out document without a single text flow.
 * - If in doubt, ask the user as part of the build.
 *
 * PAGE SIZING — paper differs by country (letter vs A4), so the printed
 * sheet is not one fixed truth:
 * - FLOWING documents pin NO paper size: the print engine paginates
 *   onto the user's real paper, and the content reflows to it.
 * - EXPLICITLY PAGINATED documents print each page at a FIXED page box
 *   with overflow hidden — letter by default, size="a4" for a clearly
 *   metric user, the user's chosen paper when they export. Design each
 *   page to FILL that box, fitting letter and A4 alike without overlap.
 * - width/height pin an explicit fixed size, ONLY when the user gives
 *   one.
 * Never write your own @page rule or hard-code paper dimensions in the
 * content.
 *
 * Sizing modes (attributes):
 *   (none)                      — portrait: flowing docs use the user's
 *           paper; explicitly paginated pages use the named size box
 *           (letter unless size="a4")
 *   orientation="landscape"     — the same, landscape
 *   width / height              — explicit fixed size, ONLY when the user
 *           gives one (e.g. width="22in" height="30in" for a 22×30
 *           poster): the page IS the design's size, printed at true
 *           dimensions (or scaled onto the user's paper at print time).
 *           Any absolute CSS length: px/in/mm/cm/pt/pc.
 * The component announces the chosen mode to the host app at runtime (a
 * meta tag it injects), so the print path can inject the user's true
 * paper size.
 *
 * On screen the document renders on a desk background: a flowing
 * document as one tall scrolling sheet (Google Docs' pageless view);
 * explicitly paginated documents as one card per page.
 *
 * EXPLICIT pagination usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page>
 *     <section class="page" id="p1">…one page's design…</section>
 *     <section class="page" id="p2">…</section>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * How the page box works, concretely: each .page prints as ONE full-bleed
 * sheet at a FIXED physical size — letter by default (set size="a4" for
 * a clearly metric user), the user's chosen paper when they export —
 * with overflow hidden. Nothing scrolls and nothing reflows onto a next
 * sheet: content that misses the box is CLIPPED. Design each page to
 * FILL that page box, and to fit it — letter and A4 alike — without
 * overlap. Each page is a size container; don't size anything in
 * viewport units (they track the window, not the page), and never set
 * width or height on the .page section itself (the component sizes the
 * page box; an authored height like 100% is meaningless at print and is
 * overridden). The component owns the page box, the screen card chrome,
 * and the page breaks (never add your own break-before/after). Don't mix
 * .page sections with flowing content or header/footer slots in the same
 * document.
 *
 * FLOWING usage:
 *   <style>doc-page:not(:defined){visibility:hidden}</style>
 *   <doc-page margin="0.75in">
 *     <h1>Title</h1>
 *     <p>…body…</p>
 *   </doc-page>
 *   <script src="doc-page.js"></script>
 * There is no manual page-splitting — the browser's print engine
 * paginates at export. Standard break-hygiene rules (`break-inside:
 * avoid` on figures, code blocks, images and table rows; `orphans/
 * widows: 3`) are applied so paragraphs and groups split cleanly. On
 * screen and at print, headings default to `text-wrap: balance` and
 * body text to `text-wrap: pretty`; the defaults have zero specificity,
 * so any text-wrap you declare wins.
 *
 * Other attributes:
 *   size    — letter | a4 | legal (default letter). Flowing documents:
 *           preview proportion only — it does NOT pin their printed
 *           paper (the print dialog's paper governs); leave it alone
 *           there. Explicitly paginated documents: it sets the page box
 *           the cards and the pinned @page share (the export dialog's
 *           choice overrides both at print) — set size="a4" for a
 *           clearly metric user. Scaled-fit: names the sheet the fit is
 *           computed against, same a4-for-metric-users advice.
 *   content-width / content-height — the design's own fixed dimensions
 *           (CSS lengths), for scaling a fixed-size design ONTO the
 *           named sheet: content lays out at exactly this size, and the
 *           component scales it to fit that sheet's printable area
 *           (centered horizontally, top-aligned; the export dialog
 *           re-fits to the user's actual paper choice where available).
 *           Both must be set; they do not change the page box. For pages
 *           WITHOUT running header/footer slots.
 *   margin  — printable inset on every page of a FLOWING document
 *           (default 0.75in); margin="0" makes pages full-bleed.
 *           Explicitly paginated pages are always full-bleed.
 *
 * Running header/footer (flowing documents only): give an element
 * `slot="header"` or `slot="footer"` and it repeats on every printed
 * page via `position: fixed`. To keep body text from sliding under it,
 * the component prints inside a single-cell table whose <thead>/<tfoot>
 * are spacers sized to the header/footer height — browsers repeat
 * thead/tfoot on every page, so each sheet's content starts below the
 * header and ends above the footer. On screen the header/footer render
 * once at the top/bottom of the sheet.
 *
 * At print the component injects `@page { margin: 0 }` (which leaves
 * Chrome no margin box to draw its date/URL/page-count header in) and
 * moves the visual margin onto the sheet's own padding. It also marks
 * the document as owning its print CSS (a
 * `meta[name="omelette-owns-print"]` it injects at runtime), so the
 * PDF export never injects page-geometry CSS of its own on top.
 *
 * Print best practices for the content you author:
 * - Multi-column text: use CSS columns (`column-count` +
 *   `column-gap`), never side-by-side flex/grid columns — only real
 *   CSS columns flow and break across pages. `column-span: all` lets
 *   a heading span the columns; `hyphens: auto` (needs `lang` on
 *   the html element) keeps narrow columns readable.
 * - Page breaks in flowing documents: `break-before: page` on an
 *   element that must start a new page (a chapter, an appendix). Add
 *   your own kept-together blocks (callouts, stat tiles, cards) to a
 *   `break-inside: avoid` rule, and keep each one shorter than a page.
 * - Extend `orphans: 3; widows: 3` to any custom text blocks you add
 *   (p and li are covered by default).
 * - Give long tables a <thead> — browsers repeat it on every printed
 *   page.
 * - No `position: fixed`/`sticky` and no viewport units in content:
 *   fixed elements stamp every printed page (running headers/footers go
 *   in the component's slots) and `100vh` mis-sizes at print.
 *
 * Author content as static HTML so the user can click-to-edit any text
 * directly. Do not set width/padding/background on the document body —
 * the component owns the sheet box.
 */
/* END USAGE */

(() => {
  const PAPER = {
    letter: ['8.5in', '11in'],
    a4: ['210mm', '297mm'],
    legal: ['8.5in', '14in']
  };
  const CSS_LENGTH = /^\d+(\.\d+)?(px|in|mm|cm|pt|pc)$/;
  // Unitless "0" is a valid CSS length and the natural way to write
  // margin="0"; normalise it to 0px so max()/calc() (which reject a bare
  // number) keep working.
  const safeLen = (v, fb) => {
    v = (v || '').trim();
    return v === '0' ? '0px' : CSS_LENGTH.test(v) ? v : fb;
  };
  // WebKit (Safari and every iOS browser shell) never repeats a table's
  // thead/tfoot on printed pages (WebKit bug 17205), so the spacer-borne
  // vertical margins of a FLOWING document reach only the first page
  // there. Engine check, not browser check: vendor is 'Apple Computer,
  // Inc.' exactly for WebKit and 'Google Inc.' for Blink.
  const WK_PRINT = /apple/i.test(navigator.vendor || '');
  // CSS length → px number (CSS absolute units are exact: 1in = 96px).
  // Returns NaN for anything safeLen would reject — callers gate on it.
  const PX_PER = {
    px: 1,
    in: 96,
    mm: 96 / 25.4,
    cm: 96 / 2.54,
    pt: 96 / 72,
    pc: 16
  };
  const toPx = v => {
    const m = /^(\d+(?:\.\d+)?)(px|in|mm|cm|pt|pc)$/.exec((v || '').trim());
    return m ? parseFloat(m[1]) * PX_PER[m[2]] : NaN;
  };
  const stylesheet = `
    :host {
      position: relative;
      display: block;
      /* When the viewport is narrower than the page, grow to wrap the
       * sheet (plus this padding) instead of staying viewport-width, so
       * the desk background and right margin reach the sheet's far edge
       * in the horizontal scroll. */
      min-width: max-content;
      min-height: 100vh;
      background: #f5f5f4;
      padding: 48px 24px;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
      --doc-page-w: 8.5in;
      --doc-page-h: 11in;
      --doc-page-margin: 0.75in;
      --doc-hdr-h: 0px;
      --doc-ftr-h: 0px;
      --doc-hdr-pad: 0px;
      --doc-ftr-pad: 0px;
    }
    .sheet {
      width: var(--doc-page-w);
      margin: 0 auto;
      background: #fff;
      box-shadow: 0 2px 10px rgba(20, 20, 19, 0.12);
      border-radius: 7px;
      box-sizing: border-box;
      padding: var(--doc-page-margin);
    }
    .frame { width: 100%; border-collapse: collapse; }
    /* Scaled-fit mode (content-width/content-height): the inner .fit box
     * lays the content out at its authored fixed size and scales it onto
     * the printable area; .fit-box reserves the scaled footprint in flow
     * (transforms don't affect layout) and centers it. Without the mode,
     * both divs are unstyled block pass-throughs. */
    /* Explicit pagination: direct .page children are the pages. The sheet
     * becomes a transparent stack and each page carries the card look on
     * screen; at print each page is exactly one full-bleed sheet. The
     * ::slotted defaults are deliberately weak (document CSS wins), so
     * authored page styling can override any of this. */
    .sheet.paginated {
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      padding: 0;
    }
    .paginated ::slotted(.page) {
      position: relative;
      display: block;
      width: 100%;
      aspect-ratio: var(--doc-page-ar);
      container-type: size;
      overflow: hidden;
      box-sizing: border-box;
      background: #fff;
      border-radius: 7px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
      print-color-adjust: exact;
      -webkit-print-color-adjust: exact;
      break-inside: avoid;
    }
    .paginated ::slotted(.page:not(:first-child)) { margin-top: 1rem; }
    @media print {
      .sheet.paginated { padding: 0; }
      /* The flowing-document vertical inset lives on the repeating
       * thead/tfoot spacers, not the sheet padding — they must go too,
       * or each full-sheet .page is pushed ~margin down and spills onto
       * a second sheet. Paginated pages are full-bleed by definition
       * (content owns its insets). */
      .sheet.paginated .hdr-space,
      .sheet.paginated .ftr-space { height: 0; }
      .paginated ::slotted(.page) {
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        /* Physical page-box sizing, no viewport units: Safari resolves
         * 100vh against the window, not the page box, so a vh-sized card
         * paginates wrong there. --doc-page-w/h are the named size by
         * default and are overridden to the user's chosen paper by the
         * export path, so every card is exactly one sheet either way.
         * Width + height (same source values as @page size) rather than
         * width + aspect-ratio: the ratio is a 6-decimal rounding of the
         * same division, and a few millionths of overflow would spill a
         * blank sheet after every page. The screen-only aspect-ratio
         * (preview proportions) must not leak into print. cqh typography
         * tracks the same box.
         *
         * Every declaration is !important: per CSS Scoping, unimportant
         * shadow ::slotted rules LOSE to the document context, so a page
         * section's authored inline style would silently beat this print
         * geometry. A model-authored height:100% did exactly that — the
         * percentage resolves as auto in the all-auto print ancestry, the
         * base rule's size containment turns auto into ZERO, and
         * overflow:hidden then paints nothing: a blank PDF with perfect
         * page boxes. At print the component's geometry is the design's
         * whole contract, so it must win over any authored sizing. */
        aspect-ratio: auto !important;
        width: var(--doc-page-w) !important;
        height: var(--doc-page-h) !important;
        overflow: hidden !important;
      }
      .paginated ::slotted(.page:not(:first-child)) {
        break-before: page !important;
        margin-top: 0 !important;
      }
    }
    .fit-mode .fit-box {
      width: calc(var(--doc-fit-w) * var(--doc-fit-scale));
      height: calc(var(--doc-fit-h) * var(--doc-fit-scale));
      margin: 0 auto;
      break-inside: avoid;
    }
    /* Monolithic at print: Blink slices a transform-scaled child at
     * fragmentainer boundaries mapped in UNSCALED layout coordinates
     * (transforms are paint-time), so the .fit box (authored size, e.g.
     * 1400x990) gets cut at the page's free block space and spills onto
     * a second sheet even though its SCALED footprint fits the page by
     * construction. overflow:hidden makes .fit-box a scroll container —
     * monolithic under fragmentation (css-break-3) — so the scaled
     * content prints atomically on one sheet. No clipping for content
     * within the authored box: .fit-box is calc-sized to exactly the
     * scaled footprint. (Content that bleeds past content-width/height
     * is clipped at the footprint — fit mode's contract; it previously
     * painted beyond it at print.) Print-only, so the screen rendering
     * keeps visible overflow for editor affordances.
     * The export path injects the same rule into frozen copies
     * (print-eval.ts om-print-fit-contain). The .fit-mode scope is
     * load-bearing: .fit-box wraps slotted content in EVERY mode, and an
     * unscoped overflow:hidden would make whole flowing documents
     * monolithic (one truncated sheet). overflow:hidden, never clip —
     * clip is not a scroll container, so not monolithic. */
    @media print {
      .fit-mode .fit-box { overflow: hidden; }
    }
    .fit-mode .fit {
      width: var(--doc-fit-w);
      height: var(--doc-fit-h);
      transform: scale(var(--doc-fit-scale));
      transform-origin: top left;
    }
    .frame td, .frame th { padding: 0; text-align: left; font-weight: inherit; }
    .hdr-space { height: var(--doc-hdr-h); }
    .ftr-space { height: var(--doc-ftr-h); }
    ::slotted([slot="header"]),
    ::slotted([slot="footer"]) { display: block; box-sizing: border-box; }
    @media print {
      :host { background: none; padding: 0; min-width: 0; min-height: 0; }
      .sheet {
        width: auto; margin: 0; box-shadow: none; border-radius: 0;
        padding: 0 var(--doc-page-margin);
      }
      /* The thead/tfoot spacers repeat on every page, so they carry the
       * vertical page margin (which the sheet's own padding cannot, since
       * that padding is consumed once on the first/last page). The running
       * header/footer are fixed inside that band. */
      /* The 0.35in is breathing room between a running header/footer and
       * the body; without one the spacer is exactly the page margin, so a
       * margin="0" full-bleed document gets truly full-bleed pages. */
      .hdr-space { height: max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))); }
      .ftr-space { height: max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))); }
      /* WebKit flowing documents: @page carries the vertical margin (see
       * _syncPrintPageRule), so the spacers keep only whatever a running
       * header/footer needs BEYOND it — page 1 would otherwise double its
       * top inset. Paginated sheets already zero their spacers above. */
      .sheet.wk-print:not(.paginated) .hdr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-hdr-h) + var(--doc-hdr-pad))) - var(--doc-page-margin))); }
      .sheet.wk-print:not(.paginated) .ftr-space { height: max(0px, calc(max(var(--doc-page-margin), calc(var(--doc-ftr-h) + var(--doc-ftr-pad))) - var(--doc-page-margin))); }
      ::slotted([slot="header"]) {
        position: fixed; top: 0; left: 0; right: 0; margin: 0;
        padding: calc(var(--doc-page-margin) * 0.45) var(--doc-page-margin) 0;
      }
      ::slotted([slot="footer"]) {
        position: fixed; bottom: 0; left: 0; right: 0; margin: 0;
        padding: 0 var(--doc-page-margin) calc(var(--doc-page-margin) * 0.45);
      }
    }
  `;
  class DocPage extends HTMLElement {
    static get observedAttributes() {
      return ['size', 'width', 'height', 'margin', 'orientation', 'content-width', 'content-height'];
    }
    constructor() {
      super();
      this._root = this.attachShadow({
        mode: 'open'
      });
      this._mo = typeof MutationObserver === 'function' ? new MutationObserver(() => this._scheduleMeasure()) : null;
    }

    /** The named paper's [w, h], swapped when orientation="landscape".
     *  Only the named size swaps — explicit width/height are exact values
     *  the author already oriented. */
    _paperSize() {
      const named = PAPER[(this.getAttribute('size') || '').toLowerCase()] || PAPER.letter;
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? [named[1], named[0]] : named;
    }
    get pageWidth() {
      return safeLen(this.getAttribute('width'), this._paperSize()[0]);
    }
    get pageHeight() {
      return safeLen(this.getAttribute('height'), this._paperSize()[1]);
    }
    get pageMargin() {
      return safeLen(this.getAttribute('margin'), '0.75in');
    }

    /** Scaled-fit mode's content box [w, h] as CSS lengths, or null when
     *  the mode is off (either attribute missing/invalid/zero — a partial
     *  declaration falls back to normal flow rather than guessing). */
    _contentFit() {
      const w = safeLen(this.getAttribute('content-width'), null);
      const h = safeLen(this.getAttribute('content-height'), null);
      if (!w || !h) return null;
      const wPx = toPx(w),
        hPx = toPx(h);
      return wPx > 0 && hPx > 0 ? [w, h, wPx, hPx] : null;
    }
    connectedCallback() {
      if (!this._sheet) this._render();
      this._syncSize();
      this._syncPrintPageRule();
      this._ensureTextWrapDefaults();
      this._ensureOwnsPrintMeta();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      if (this._mo) this._mo.observe(this, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true
      });
      this._onResize = () => this._scheduleMeasure();
      window.addEventListener('resize', this._onResize);
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => this._scheduleMeasure());
      }
      this._scheduleMeasure();
    }
    disconnectedCallback() {
      window.removeEventListener('resize', this._onResize);
      if (this._mo) this._mo.disconnect();
      if (this._raf) {
        cancelAnimationFrame(this._raf);
        this._raf = null;
      }
      // Drop the head rules when the last doc-page leaves, so a deleted
      // document's @page geometry and text-wrap defaults can't apply to
      // whatever replaces it.
      const survivor = document.querySelector('doc-page');
      if (!survivor) {
        ['doc-page-print', 'doc-page-text-wrap', 'doc-page-owns-print', 'doc-page-fixed-size', 'doc-page-print-sizing'].forEach(id => {
          const tag = document.getElementById(id);
          if (tag) tag.remove();
        });
        // A live deck-stage deferred its own print-sizing meta to ours —
        // hand the page-global meta over so the deck isn't left unmarked.
        const deck = document.querySelector('deck-stage');
        if (deck && typeof deck._ensurePrintSizingMeta === 'function') {
          deck._ensurePrintSizingMeta();
        }
      } else {
        // A departed owner hands each page-global meta to whatever
        // doc-page remains (or it's removed).
        if (typeof survivor._syncFixedSizeMeta === 'function') {
          survivor._syncFixedSizeMeta();
        }
        if (typeof survivor._syncPrintSizingMeta === 'function') {
          survivor._syncPrintSizingMeta();
        }
      }
    }
    attributeChangedCallback() {
      if (!this._sheet) return;
      this._syncSize();
      this._syncPrintPageRule();
      this._syncFixedSizeMeta();
      this._syncPrintSizingMeta();
      this._scheduleMeasure();
    }
    _render() {
      this._root.innerHTML = `
        <style>${stylesheet}</style>
        <style id="vars"></style>
        <div class="sheet" data-screen-label="Document">
          <table class="frame" role="presentation">
            <thead><tr><th><div class="hdr-space"><slot name="header"></slot></div></th></tr></thead>
            <tbody><tr><td class="body"><div class="fit-box"><div class="fit"><slot></slot></div></div></td></tr></tbody>
            <tfoot><tr><td><div class="ftr-space"><slot name="footer"></slot></div></td></tr></tfoot>
          </table>
        </div>`;
      this._sheet = this._root.querySelector('.sheet');
      this._vars = this._root.getElementById('vars');
    }

    /** Runtime sizing lives in a shadow <style> :host rule, never on the
     *  light-DOM host element, so serialize-persist can't write it back. */
    _syncSize(hdrH, ftrH) {
      // Scaled-fit mode: content at its authored size, scaled onto the
      // printable area (page minus margins on both axes). The factor is a
      // plain number var so calc(length * number) stays valid; 4 decimals
      // keeps the shadow style stable across re-measures. Upscaling is
      // allowed — print transforms are vector, so text and CSS stay crisp
      // (raster images soften, which the catalog bullet warns about).
      const fit = this._contentFit();
      let fitVars = '';
      if (fit) {
        const marginPx = toPx(this.pageMargin) || 0;
        const availW = toPx(this.pageWidth) - 2 * marginPx;
        const availH = toPx(this.pageHeight) - 2 * marginPx;
        const scale = Math.min(availW / fit[2], availH / fit[3]);
        if (scale > 0 && Number.isFinite(scale)) {
          fitVars = '--doc-fit-w:' + fit[0] + ';' + '--doc-fit-h:' + fit[1] + ';' + '--doc-fit-scale:' + scale.toFixed(4) + ';';
        }
      }
      this._sheet.classList.toggle('fit-mode', !!fitVars);
      // Numeric w/h ratio for the paginated page cards' aspect-ratio —
      // aspect-ratio takes a number, not a length ratio, so compute it
      // here (CSS length division isn't portable). 6 decimals keeps the
      // shadow style stable across re-syncs.
      const arW = toPx(this.pageWidth);
      const arH = toPx(this.pageHeight);
      const ar = arW > 0 && arH > 0 ? (arW / arH).toFixed(6) : '0.772727';
      this._vars.textContent = ':host{' + fitVars + '--doc-page-ar:' + ar + ';' + '--doc-page-w:' + this.pageWidth + ';' + '--doc-page-h:' + this.pageHeight + ';' + '--doc-page-margin:' + this.pageMargin + ';' + '--doc-hdr-h:' + (hdrH || 0) + 'px;' + '--doc-ftr-h:' + (ftrH || 0) + 'px;' + '--doc-hdr-pad:' + (hdrH ? '0.35in' : '0px') + ';' + '--doc-ftr-pad:' + (ftrH ? '0.35in' : '0px') + '}';
    }

    /** @page is a no-op inside shadow DOM, so the rule lives in <head>.
     *  Re-appended on every sync so it stays last in source order — the
     *  @page cascade is source-order per descriptor, so this rule wins
     *  over any other @page rule in the document.
     *
     *  The @page SIZE is pinned where the page box IS part of the design:
     *  explicit-fixed-size mode (width + height authored), scaled-fit
     *  mode (the named sheet the fit targets), and explicit pagination
     *  (the named size the cards share — so card and sheet agree on
     *  every print path, and the export path's chosen paper overrides
     *  BOTH with one later rule). For FLOWING documents no paper size is
     *  emitted at all — the true size comes from the user's preference,
     *  injected by the export path or chosen in the print dialog — so a
     *  flowing document never fights the paper it lands on.
     *  margin: 0 is emitted in every mode: it leaves Chrome no margin box
     *  to draw its date/URL/page-count header in, and the visual margin
     *  lives on the sheet's own padding. */
    _syncPrintPageRule() {
      const id = 'doc-page-print';
      let tag = document.getElementById(id);
      if (!tag) {
        tag = document.createElement('style');
        tag.id = id;
      }
      document.head.appendChild(tag);
      // Three print-geometry regimes:
      // - true-size: the page IS the design — pin its exact size.
      // - scaled-fit (content-width/height): the fit factor is computed
      //   against the NAMED paper's printable area, so that paper must
      //   stay pinned or the scaled content overflows a smaller sheet
      //   (the export path re-fits and re-pins at print time on top).
      // - default modes: no paper size — but landscape still needs the
      //   paper-agnostic 'size: landscape' keyword, because the size
      //   descriptor is what carries orientation; without it a landscape
      //   document prints portrait whenever nothing injects a size.
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      // Explicit pagination pins the page box to the SAME values that
      // size the cards (the named size by default, the export path's
      // chosen paper when its later rule overrides both) — card and
      // sheet agree on every print path, and a mismatched real paper
      // shrinks-to-fit in the dialog instead of clipping a Letter card
      // on A4. Declared before the paginated read below so both derive
      // from one check.
      const paginatedNow = this.querySelector(':scope > .page') !== null;
      const sizeDescriptor = this._trueSizePx() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : this._contentFit() ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : paginatedNow ? 'size: ' + this.pageWidth + ' ' + this.pageHeight + '; ' : landscape ? 'size: landscape; ' : '';
      // WebKit never repeats the thead/tfoot spacers that carry a flowing
      // document's vertical page margins (see WK_PRINT above), so pages
      // after the first print edge-to-edge there. Carry the VERTICAL
      // margins on @page for WebKit instead, and the shadow print CSS
      // trims the first-page spacers by the same amount (.sheet.wk-print
      // rules). Horizontal inset stays on the sheet's own padding in
      // every engine. Blink keeps margin: 0 (a nonzero margin there
      // re-opens the box Chrome draws its header furniture in). One cost,
      // learned in testing: Safari's own date/URL headers are a USER
      // dialog setting ("Print headers and footers") that renders in the
      // margin area when room exists — margin: 0 only suppressed it by
      // leaving no room, and no CSS controls it. The export dialog's
      // Safari guide teaches turning the setting off for flowing
      // documents. Explicitly paginated and fixed-size documents keep
      // margin: 0 everywhere: their pages ARE the sheet.
      const wkFlowing = WK_PRINT && !paginatedNow && !this._trueSizePx() && !this._contentFit();
      const marginDescriptor = wkFlowing ? 'margin: ' + this.pageMargin + ' 0; ' : 'margin: 0; ';
      // Shadow-internal marker (never serialized), kept in lockstep with
      // the @page decision above: the print CSS trims the first-page
      // spacers ONLY while @page actually carries the margins — a
      // true-size or scaled-fit sheet keeps margin: 0 and must keep its
      // spacers too. Re-synced here so attribute changes and pagination
      // flips move both together.
      if (this._sheet) this._sheet.classList.toggle('wk-print', wkFlowing);
      tag.textContent = '@page { ' + sizeDescriptor + marginDescriptor + '} ' + '@media print { html, body { margin: 0 !important; padding: 0 !important; background: none !important; height: auto !important; overflow: visible !important; } ' + 'h1,h2,h3,h4,h5,h6 { break-after: avoid; } ' + 'figure,pre,blockquote,img,svg,tr { break-inside: avoid; } ' + 'p,li { orphans: 3; widows: 3; } ' + '* { -webkit-print-color-adjust: exact; print-color-adjust: exact; ' + 'backdrop-filter: none !important; -webkit-backdrop-filter: none !important; } ' + '*, *::before, *::after { animation-delay: -99s !important; animation-duration: .001s !important; ' + 'animation-iteration-count: 1 !important; animation-fill-mode: both !important; ' + 'animation-play-state: running !important; transition-duration: 0s !important; } }';
    }

    /** Typographic defaults for document text: balance headings, avoid
     *  widowed/orphaned words in body copy (browsers without text-wrap
     *  support drop the declarations). Zero-specificity via :where() so
     *  any text-wrap authored on those elements wins; document-level so the
     *  rules reach the slotted (light DOM) content — shadow styles can't.
     *  data-omelette-injected marks the tag for the host editor to strip
     *  at serialize, so it is never written back as authored source. */
    _ensureTextWrapDefaults() {
      if (document.getElementById('doc-page-text-wrap')) return;
      const tag = document.createElement('style');
      tag.id = 'doc-page-text-wrap';
      tag.setAttribute('data-omelette-injected', '');
      tag.textContent = ':where(h1,h2,h3,h4,h5,h6){text-wrap:balance}' + ':where(p,li,blockquote,figcaption){text-wrap:pretty}';
      document.head.appendChild(tag);
    }

    /** Declares that this document owns its print CSS. The instant-PDF
     *  export checks for the meta by NAME PRESENCE alone (content is
     *  ignored) and skips its automatic print-CSS injections, so the
     *  component's @page geometry is never overridden by a heuristic.
     *  data-omelette-injected keeps it out of serialized source. */
    _ensureOwnsPrintMeta() {
      if (document.getElementById('doc-page-owns-print')) return;
      const tag = document.createElement('meta');
      tag.id = 'doc-page-owns-print';
      tag.name = 'omelette-owns-print';
      tag.content = 'true';
      tag.setAttribute('data-omelette-injected', '');
      document.head.appendChild(tag);
    }

    /** This page's valid true-size page box (explicit width AND height)
     *  as [w, h] px ints, or null when the mode is off. */
    _trueSizePx() {
      if (!safeLen(this.getAttribute('width'), null) || !safeLen(this.getAttribute('height'), null)) return null;
      const w = Math.round(toPx(this.pageWidth));
      const h = Math.round(toPx(this.pageHeight));
      return w > 0 && h > 0 ? [w, h] : null;
    }

    /** True-size pages (explicit width AND height) also declare the page
     *  box as the preview size: the in-app preview reads
     *  meta[name="omelette-fixed-size"] (content "W,H" in px ints) and
     *  scales the sheet into view — without it an 18in poster previews at
     *  true size with scrollbars. Never overrides an author-set meta
     *  (only the component's own id is managed). The meta is page-global
     *  while doc-page instances are not, so every sync recomputes the
     *  page-wide owner — the first connected true-size doc-page — and a
     *  non-true-size sibling's sync can never delete the owner's meta.
     *  Removed when no true-size page remains (the owner's disconnect
     *  re-syncs via any survivor) or when an author-set meta exists. */
    _syncFixedSizeMeta() {
      const id = 'doc-page-fixed-size';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-fixed-size"]:not([data-omelette-injected])');
      // The page-wide owner, not this instance: an upgraded true-size page
      // anywhere in the document keeps the meta alive and sized.
      let box = null;
      for (const el of document.querySelectorAll('doc-page')) {
        box = typeof el._trueSizePx === 'function' ? el._trueSizePx() : null;
        if (box) break;
      }
      if (!box || authored) {
        if (own) own.remove();
        return;
      }
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-fixed-size';
      tag.content = box[0] + ',' + box[1];
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }

    /** This page's print-sizing mode: 'fixed' when an explicit width AND
     *  height are authored (the page is the design's own size), else the
     *  default paper in the authored orientation. */
    _printSizingMode() {
      if (this._trueSizePx()) return 'fixed';
      const landscape = (this.getAttribute('orientation') || '').trim().toLowerCase() === 'landscape';
      return landscape ? 'default-landscape' : 'default-portrait';
    }

    /** Announces the print-sizing mode to the host app:
     *  meta[name="omelette-print-sizing"] with content 'default-portrait',
     *  'default-landscape', or 'fixed' (fixed pages also carry the
     *  omelette-fixed-size meta with the page box in px). The export path
     *  probes it to decide what true paper size to inject at print time —
     *  in the default modes the component emits no paper size of its own.
     *  Same page-global ownership rules as the fixed-size meta above:
     *  first connected doc-page owns it, an authored meta is never
     *  overridden, removed when no doc-page remains. */
    _syncPrintSizingMeta() {
      const id = 'doc-page-print-sizing';
      const own = document.getElementById(id);
      const authored = document.querySelector('meta[name="omelette-print-sizing"]:not([data-omelette-injected])');
      // A fixed page wins outright (mirroring the fixed-size loop above,
      // so the two metas can never contradict each other in a mixed
      // multi-page document); otherwise the first page's mode holds.
      let mode = null;
      for (const el of document.querySelectorAll('doc-page')) {
        if (typeof el._printSizingMode !== 'function') continue;
        const m = el._printSizingMode();
        if (m === 'fixed') {
          mode = m;
          break;
        }
        if (mode === null) mode = m;
      }
      if (!mode || authored) {
        if (own) own.remove();
        return;
      }
      // A deck-stage that connected first injected its own meta and
      // defers to any existing one — take it over, or the document ends
      // up with two conflicting injected metas (a doc-page page is the
      // document; the deck re-ensures its meta if every doc-page leaves).
      const deckMeta = document.getElementById('deck-stage-print-sizing');
      if (deckMeta) deckMeta.remove();
      const tag = own || document.createElement('meta');
      tag.id = id;
      tag.name = 'omelette-print-sizing';
      tag.content = mode;
      tag.setAttribute('data-omelette-injected', '');
      if (!own) document.head.appendChild(tag);
    }
    _scheduleMeasure() {
      if (this._raf) return;
      this._raf = requestAnimationFrame(() => {
        this._raf = null;
        this._measure();
      });
    }

    /** Slot heights feed the print spacers (--doc-hdr-h / --doc-ftr-h), so
     *  they re-measure on content mutation, resize, and font load. The
     *  same pass detects explicit pagination (direct .page children) and
     *  toggles the sheet between the flowing-document card and the
     *  page-per-card stack — content edits can add or remove pages at any
     *  time, so this tracks the same mutations the measurement does. */
    _measure() {
      const hdr = this.querySelector(':scope > [slot="header"]');
      const ftr = this.querySelector(':scope > [slot="footer"]');
      const wasPaginated = this._sheet.classList.contains('paginated');
      this._sheet.classList.toggle('paginated', this.querySelector(':scope > .page') !== null);
      // The WebKit @page margin is flowing-only, so a pagination flip
      // must re-emit the rule (content edits can add or remove .page
      // sections at any time).
      if (this._sheet.classList.contains('paginated') !== wasPaginated) {
        this._syncPrintPageRule();
      }
      this._syncSize(hdr ? hdr.offsetHeight : 0, ftr ? ftr.offsetHeight : 0);
    }
  }
  if (!customElements.get('doc-page')) {
    customElements.define('doc-page', DocPage);
  }
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-01-documents/template/doc-page.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-01-documents/template/ds-base.js
try { (() => {
// Loads this design system into the template. In a consuming project, point
// base at the bound DS folder relative to this file (e.g. '_ds/<folder>' at
// the project root, '../_ds/<folder>' one level down) — one line to edit.
(() => {
  const base = '../design-system';
  for (const p of ["tokens/fonts.css", "tokens/colors.css", "tokens/typography.css", "tokens/spacing.css", "tokens/radii-shadows.css", "styles/base.css", "styles/classes.css", "styles/controls-v2.css", "styles/tables-v2.css", "styles/feedback-v2.css", "styles.css"]) {
    const l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = base + '/' + p;
    document.head.appendChild(l);
  }
  const s = document.createElement('script');
  s.src = base + '/_ds_bundle.js';
  s.onerror = () => console.error('ds-base.js: failed to load ' + s.src + ' — if this is a consuming project, point the base line in ds-base.js at the bound _ds/<folder> tree relative to this page (e.g. _ds/<folder> at the project root, ../_ds/<folder> one level down); in a fresh design system this can just mean the bundle is not compiled yet');
  document.head.appendChild(s);
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-01-documents/template/ds-base.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-08-admin-mvp/concept/data.js
try { (() => {
// Demo data — Demo Junior School (nursery and primary). Demo only; no real people.
window.KD = (() => {
  const PAL = ['#1E6FD9', '#15803D', '#B45309', '#1E293B'];
  const SCHOOL = {
    name: 'Demo Junior School',
    no: '007',
    currency: 'UGX',
    year: '2026',
    term: 'Term 3'
  }; // currency comes from school settings
  const FIRST = ['Amara', 'Liam', 'Sofia', 'Noah', 'Aisha', 'Mateo', 'Grace', 'Yusuf', 'Priya', 'Ethan', 'Zara', 'Kofi', 'Mei', 'Omar', 'Lucía', 'Daniel', 'Nia', 'Arjun', 'Hana', 'Samuel', 'Leila', 'Tomás', 'Imani', 'Ravi', 'Elena', 'Musa', 'Chloe', 'Ibrahim', 'Ana', 'Joseph', 'Fatima', 'Lucas'];
  const LAST = ['Okafor', 'Chen', 'Haddad', 'Mensah', 'Rahman', 'García', 'Wanjiru', 'Demir', 'Nair', 'Brooks', 'Ali', 'Asante', 'Tanaka', 'Farouk', 'Morales', 'Kim', 'Ndlovu', 'Patel', 'Sato', 'Okello', 'Karimi', 'Silva', 'Mwangi', 'Iyer', 'Petrova', 'Bello', 'Martin', 'Hassan', 'Costa', 'Achieng', 'Yilmaz', 'Rossi'];
  const CLASSES = [{
    id: 'n1',
    name: 'Nursery',
    streams: ['Sunflower'],
    n: 22,
    ct: 't7',
    avg: null,
    att: 95
  }, {
    id: 'rc',
    name: 'Reception',
    streams: ['Sunflower'],
    n: 24,
    ct: 't8',
    avg: null,
    att: 94
  }, {
    id: 'p1',
    name: 'Primary 1',
    streams: ['Blue', 'Red'],
    n: 31,
    ct: 't2',
    avg: 74,
    att: 96
  }, {
    id: 'p2',
    name: 'Primary 2',
    streams: ['Blue', 'Red'],
    n: 29,
    ct: 't3',
    avg: 71,
    att: 93
  }, {
    id: 'p3',
    name: 'Primary 3',
    streams: ['Blue'],
    n: 28,
    ct: 't4',
    avg: 69,
    att: 92
  }, {
    id: 'p4',
    name: 'Primary 4',
    streams: ['Blue'],
    n: 30,
    ct: 't5',
    avg: 66,
    att: 91
  }, {
    id: 'p5',
    name: 'Primary 5',
    streams: ['Blue', 'Red'],
    n: 32,
    ct: 't1',
    avg: 68,
    att: 94
  }, {
    id: 'p6',
    name: 'Primary 6',
    streams: ['Blue'],
    n: 27,
    ct: 't6',
    avg: 63,
    att: 90
  }];
  const T = [{
    id: 't1',
    fn: 'Sarah',
    ln: 'Nakato',
    email: 's.nakato@demojunior.school',
    ph: '+000 700 100 101',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 5 · Blue', 'Mathematics, Science'], ['Primary 6 · Blue', 'Mathematics']],
    ctOf: 'Primary 5 · Blue',
    lessons: 24,
    pendAtt: 0,
    pendMarks: ['Science · Primary 5 Blue'],
    last: '2 hours ago'
  }, {
    id: 't2',
    fn: 'Daniel',
    ln: 'Mensah',
    email: 'd.mensah@demojunior.school',
    ph: '+000 700 100 102',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 1 · Blue', 'English, Literacy']],
    ctOf: 'Primary 1 · Blue',
    lessons: 20,
    pendAtt: 1,
    pendMarks: [],
    last: 'Yesterday'
  }, {
    id: 't3',
    fn: 'Mei',
    ln: 'Tanaka',
    email: 'm.tanaka@demojunior.school',
    ph: '+000 700 100 103',
    role: 'Teacher',
    invite: 'invited',
    cls: [['Primary 2 · Blue', 'English']],
    ctOf: 'Primary 2 · Blue',
    lessons: 18,
    pendAtt: 0,
    pendMarks: ['English · Primary 2 Blue'],
    last: ''
  }, {
    id: 't4',
    fn: 'Omar',
    ln: 'Farouk',
    email: '',
    ph: '+000 700 100 104',
    role: 'Teacher',
    invite: 'none',
    cls: [['Primary 3 · Blue', 'Social Studies']],
    ctOf: 'Primary 3 · Blue',
    lessons: 16,
    pendAtt: 1,
    pendMarks: [],
    last: ''
  }, {
    id: 't5',
    fn: 'Elena',
    ln: 'Petrova',
    email: 'e.petrova@demojunior.school',
    ph: '+000 700 100 105',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Primary 4 · Blue', 'Mathematics']],
    ctOf: 'Primary 4 · Blue',
    lessons: 22,
    pendAtt: 0,
    pendMarks: [],
    last: '3 days ago'
  }, {
    id: 't6',
    fn: 'Samuel',
    ln: 'Asante',
    email: 's.asante@demojunior.school',
    ph: '+000 700 100 106',
    role: 'Head teacher',
    invite: 'accepted',
    cls: [['Primary 6 · Blue', 'English']],
    ctOf: 'Primary 6 · Blue',
    lessons: 10,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }, {
    id: 't7',
    fn: 'Hana',
    ln: 'Sato',
    email: 'h.sato@demojunior.school',
    ph: '+000 700 100 107',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Nursery · Sunflower', 'All areas']],
    ctOf: 'Nursery · Sunflower',
    lessons: 25,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }, {
    id: 't8',
    fn: 'Ravi',
    ln: 'Iyer',
    email: 'r.iyer@demojunior.school',
    ph: '+000 700 100 108',
    role: 'Teacher',
    invite: 'accepted',
    cls: [['Reception · Sunflower', 'All areas']],
    ctOf: 'Reception · Sunflower',
    lessons: 25,
    pendAtt: 0,
    pendMarks: [],
    last: 'Today'
  }].map((t, i) => ({
    ...t,
    n: i + 11,
    status: 'active'
  }));
  const S = Array.from({
    length: 32
  }, (_, i) => {
    const fn = FIRST[i],
      ln = LAST[i * 7 % 32],
      g = i % 2 ? 'Male' : 'Female';
    const bal = [0, 180000, 0, 95000, 0, 0, 240000, 0][i % 8];
    return {
      id: 1040 + i,
      fn,
      ln,
      sex: i === 9 ? '' : g,
      kls: 'KLS007' + String(1 + i).padStart(4, '0'),
      cls: i === 5 ? '' : 'Primary 5 · ' + (i % 3 ? 'Blue' : 'Red'),
      status: i === 12 ? 'inactive' : 'active',
      parent: i === 7 ? null : {
        fn: ['Grace', 'Peter', 'Ana', 'Musa'][i % 4],
        ln,
        ph: '+000 772 418 2' + String(10 + i).padStart(2, '0')
      },
      att: 88 + i * 3 % 12,
      avg: 58 + i * 11 % 34,
      bal,
      dob: '14 Mar 2015'
    };
  });
  S[0] = {
    ...S[0],
    fn: 'Amara',
    ln: 'Okafor',
    avg: 74,
    att: 91,
    bal: 180000,
    pos: 6
  };
  const P = [{
    id: 2210,
    fn: 'Grace',
    ln: 'Okafor',
    ph: '+000 772 418 205',
    email: 'grace.okafor@example.com',
    wa: 'in',
    waDate: '12 Sep 2026',
    last: 'Today, 07:42',
    kids: [S[0], {
      id: 1090,
      fn: 'Tobi',
      ln: 'Okafor',
      kls: 'KLS0070033',
      cls: 'Primary 2 · Blue',
      bal: 0,
      att: 97,
      avg: 81
    }]
  }, {
    id: 2211,
    fn: 'Peter',
    ln: 'Chen',
    ph: '+000 701 552 930',
    email: '',
    wa: 'none',
    last: 'Never',
    kids: [S[1]]
  }, {
    id: 2212,
    fn: 'Ana',
    ln: 'Haddad',
    ph: '+000 755 003 118',
    email: 'ana.h@example.com',
    wa: 'in',
    waDate: '2 Oct 2026',
    last: '3 days ago',
    kids: [S[2]]
  }, {
    id: 2213,
    fn: 'Musa',
    ln: 'Mensah',
    ph: '+000 782 660 471',
    email: '',
    wa: 'pending',
    last: 'Never',
    kids: [S[3], S[11]]
  }];
  const ini = (f, l) => {
    f = (f || '').trim().split(/\s+/)[0] || '';
    l = (l || '').trim();
    return (([...f][0] || '') + ([...l][0] || '')).toLocaleUpperCase();
  };
  const av = (p, s = 40) => {
    const i = ini(p.fn, p.ln);
    const r = s <= 32 ? 8 : 12;
    return `<span class="av" style="width:${s}px;height:${s}px;font-size:${Math.round(s * .4)}px;border-radius:${r}px;background:${i ? PAL[p.id ? (typeof p.id === 'number' ? p.id : p.n || 0) % 4 : 0] : '#64748B'}" aria-hidden="true">${i}</span>`;
  };
  const money = v => SCHOOL.currency + ' ' + Number(v).toLocaleString('en');
  const grade = a => a >= 80 ? 'A' : a >= 70 ? 'B' : a >= 60 ? 'C' : a >= 50 ? 'D' : 'E';
  const ic = (n, c = 'ic') => `<i data-lucide="${n}" class="${c}" aria-hidden="true"></i>`;
  return {
    SCHOOL,
    CLASSES,
    T,
    S,
    P,
    av,
    ini,
    money,
    grade,
    ic,
    PAL
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-08-admin-mvp/concept/data.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-08-admin-mvp/concept/screens-a.js
try { (() => {
// Shell, dashboard, people list, classes — admin MVP concept
(() => {
  const {
    SCHOOL,
    CLASSES,
    T,
    S,
    P,
    av,
    money,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  // Groups follow dashboard v2 (handoff-2026-09-30-profiles Part B)
  const NAV = [['', [['dashboard', 'layout-dashboard', 'Dashboard']]], ['People', [['students', 'graduation-cap', 'Students'], ['teachers', 'presentation', 'Teachers and staff'], ['parents', 'users', 'Parents']]], ['Academics', [['classes', 'school', 'Classes and streams'], ['subjects', 'book-open', 'Subjects'], ['attendance', 'calendar-check', 'Attendance'], ['exams', 'clipboard-list', 'Exams and marks'], ['reports', 'file-text', 'Report cards']]], ['Money', [['fees', 'wallet', 'Fees']]], ['Messages', [['messages', 'message-circle', 'WhatsApp']]], ['School', [['settings', 'settings', 'Settings'], ['help', 'life-buoy', 'Help']]]];
  const ME = {
    id: 3300,
    fn: 'Mucunguzi',
    ln: 'Moses',
    email: 'mucunguzi.moses.admin@demojunior.school'
  };
  function acctPop(mob) {
    return `<div class="pop" role="menu" aria-label="Account" id="acct-pop">
  <div class="who">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span></div>
  <a class="mi" role="menuitem" href="#" data-go="me">${ic('user-round')}Edit profile</a>
  <a class="mi" role="menuitem" href="#">${ic('key-round')}Change password</a>
  <a class="mi" role="menuitem" href="#">${ic('settings')}Settings</a>
  <div class="sep" role="separator"></div>
  <button class="mi" role="menuitem" type="button">${ic('log-out')}Log out</button></div>`;
  }
  window.shell = (active, title, body) => {
    const nav = NAV.map(([g, items]) => `${g ? `<div class="grp">${g}</div>` : ''}<ul class="nav">${items.map(([k, i, l]) => `<li><a href="#" data-go="${k}" ${k === active ? 'aria-current="page"' : ''}>${ic(i)}${l}</a></li>`).join('')}</ul>`).join('');
    return `<div class="shell">
  <aside class="side ${ST.drawer ? 'open' : ''}" aria-label="Main menu" id="side">
    <div class="brand"><img src="../design-system/assets/brand/klassapp-horizontal-light.svg" alt="KlassApp"></div>
    <nav aria-label="Main">${nav}</nav>
    <div class="grow"></div>
    ${ST.hideSetup && ST.state !== 'data' ? `<a class="schip" href="#" aria-label="Finish setup, ${ST.state === 'new' ? 1 : 4} of 7 steps done"><span><b>Finish setup</b><span class="pill">${ST.state === 'new' ? 1 : 4}/7</span></span><small>Next: ${ST.state === 'new' ? 'Add your students' : 'Set up fees for Term 3'}</small><span class="bar"><i style="width:${(ST.state === 'new' ? 1 : 4) / 7 * 100}%"></i></span></a>` : ''}
    <div class="soon"><img src="../design-system/assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant · coming soon</div>
    <div class="acct">${ST.acct && !ST.mob ? acctPop() : ''}
      <button class="acct-btn" type="button" data-act="acct" aria-haspopup="menu" aria-expanded="${ST.acct && !ST.mob}" aria-controls="acct-pop">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span>${ic('chevrons-up-down', 'ic ic-sm')}</button></div>
  </aside>
  <div class="mainw">
    <header class="topbar"><button class="btn icon ghost" type="button" data-act="drawer" aria-label="Open menu" aria-expanded="${ST.drawer}" aria-controls="side">${ic('menu')}</button><span class="t">${title}</span>
      <div class="acct"><button class="btn icon ghost" type="button" data-act="acct" aria-label="Account" aria-haspopup="menu" aria-expanded="${ST.acct && ST.mob}">${av(ME, 32)}</button>${ST.acct && ST.mob ? acctPop(1) : ''}</div></header>
    <div class="panel"><main class="page" id="main">${body}</main></div>
  </div></div>${ST.dlg ? dialog(ST.dlg) : ''}`;
  };
  function dialog(d) {
    return `<div class="dlg-bg" data-act="dlg-x"><div class="dlg" role="alertdialog" aria-modal="true" aria-labelledby="dlg-t" aria-describedby="dlg-d" data-stop>
  <h2 id="dlg-t">${d.t}</h2><p id="dlg-d">${d.p}</p><div class="row-acts"><button class="btn" type="button" data-act="dlg-x">Cancel</button><button class="btn danger" type="button" data-act="dlg-x">${d.b}</button></div></div></div>`;
  }
  const ayPick = () => `<label class="ay">Academic year <select class="sel" aria-label="Academic year"><option selected>2026</option><option>2025</option></select></label>`;
  /* ---------- 1. Dashboard ---------- */
  const bars = (rows, max = 100) => `<div class="hbars">${rows.map(r => `<div class="hb"><span>${r[0]}</span><span class="t" role="img" aria-label="${r[0]}: ${r[1]}%"><i style="width:${r[1] / max * 100}%;${r[2] ? 'background:' + r[2] : ''}"></i></span><b>${r[1]}%</b></div>`).join('')}</div>`;
  function line(pts, w = 520, h = 160) {
    const mn = 80,
      mx = 100,
      x = i => 36 + i * (w - 48) / (pts.length - 1),
      y = v => 12 + (mx - v) / (mx - mn) * (h - 40);
    const d = pts.map((p, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(p[1]).toFixed(1)).join(' ');
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Attendance trend: ${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">
  ${[80, 90, 100].map(v => `<line x1="36" x2="${w - 12}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="30" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}
  <path d="${d}" fill="none" stroke="#15803D" stroke-width="2.5"/>${pts.map((p, i) => `<circle cx="${x(i)}" cy="${y(p[1])}" r="3.5" fill="#15803D"/><text x="${x(i)}" y="${h - 8}" text-anchor="middle" font-size="11" fill="#475569">${p[0]}</text>`).join('')}</svg>`;
  }
  function cols(vals, exp, w = 520, h = 170) {
    const mx = Math.max(...exp),
      bw = (w - 60) / vals.length,
      y = v => h - 34 - v / mx * (h - 50);
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Fees collected by month: ${vals.map(v => v[0] + ' ' + money(v[1])).join(', ')}">
  ${vals.map((v, i) => {
      const x = 40 + i * bw;
      return `<rect x="${x + 8}" y="${y(exp[i])}" width="${bw - 16}" height="${h - 34 - y(exp[i])}" fill="none" stroke="#94A3B8" stroke-dasharray="4 3" rx="4"/><rect x="${x + 8}" y="${y(v[1])}" width="${bw - 16}" height="${h - 34 - y(v[1])}" fill="#15803D" rx="4"/><text x="${x + bw / 2}" y="${h - 14}" text-anchor="middle" font-size="11" fill="#475569">${v[0]}</text><text x="${x + bw / 2}" y="${y(v[1]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${Math.round(v[1] / 1e6 * 10) / 10}M</text>`;
    }).join('')}</svg>`;
  }
  function kpi(icon, l, v, s, cls = '', meter) {
    return `<div class="kpi"><span class="l">${ic(icon, 'ic ic-sm')}${l}</span><span class="v">${v}</span>${meter != null ? `<span class="meter" role="img" aria-label="${meter}%"><i style="width:${meter}%"></i></span>` : ''}<span class="s ${cls}">${s}</span></div>`;
  }
  const gender = (g, b, u) => `<div class="stack" role="img" aria-label="Girls ${g}%, boys ${b}%, not specified ${u}%"><i style="width:${g}%;background:#B45309"></i><i style="width:${b}%;background:#1E6FD9"></i><i style="width:${u}%;background:#64748B"></i></div><div class="legend"><span><i style="background:#B45309"></i>Girls ${g}%</span><span><i style="background:#1E6FD9"></i>Boys ${b}%</span><span><i style="background:#64748B"></i>Not specified ${u}%</span></div>`;
  window.scrDashboard = () => {
    const st = ST.state;
    const steps = st === 'new' ? 1 : st === 'mid' ? 4 : 7;
    const next = st === 'new' ? 'Add your students' : 'Set up fees for Term 3';
    const setup = steps < 7 && !ST.hideSetup ? `<div class="setup" role="region" aria-label="School setup">${ic('list-checks')}<b>Setup ${steps} of 7 done</b><span class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="7" aria-valuenow="${steps}" aria-label="Setup progress"><i style="width:${steps / 7 * 100}%"></i></span><span class="nx">Next: ${next}</span><a class="btn pri" href="#">Continue setup</a><button class="btn icon ghost" type="button" data-act="hideSetup" aria-label="Hide setup bar. Progress stays in the sidebar.">${ic('x')}</button></div>` : '';
    const QA = [['user-plus', 'Add students', 'One by one or from a spreadsheet', ''], ['clipboard-list', 'Enter marks', st === 'new' ? 'Needs students and an exam' : 'Mid-term exams are open', st === 'new'], ['file-text', 'Generate report cards', st === 'data' ? '140 ready to generate' : 'Needs marks for an exam', st !== 'data'], ['message-circle', 'Send report cards on WhatsApp', st === 'data' ? '142 sent last term' : 'Needs report cards', st !== 'data'], ['wallet', 'Fees', st === 'data' ? 'Record payments and send reminders' : 'Set up Term 3 fees first', st !== 'data']];
    const qaRow = `<nav class="qa" aria-label="Quick actions">${QA.map(q => `<a class="btn" href="#">${ic(q[0])}${q[1]}</a>`).join('')}</nav>`;
    const qaTiles = `<section aria-label="Quick actions"><h2 class="sh">Quick actions</h2><div class="qat">${QA.map(q => `<a class="qt" href="#"><span class="ib">${ic(q[0])}</span><span><b>${q[1]}</b><span class="${q[3] ? 'pre' : ''}">${q[2]}</span></span></a>`).join('')}</div></section>`;
    const head = `<div class="ph"><div><h1>${greet()}, ${ME.fn}</h1><p>${SCHOOL.name} · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts">${ayPick()}</div></div>`;
    if (st === 'new') return head + setup + qaTiles + `<div class="kpis">${kpi('graduation-cap', 'Students', '0', 'None added yet')}${kpi('presentation', 'Staff', '1', 'Just you')}${kpi('calendar-check', 'Attendance this week', '–', 'Starts after students are added')}${kpi('wallet', 'Fees collected', '–', 'No fee structure yet')}${kpi('file-text', 'Report cards ready', '–', 'After the first exam')}</div>
  <div class="empty"><b>Add your students to get started</b><p>Your dashboard fills in as you add students, take attendance and enter marks. You can add students one by one or import a spreadsheet.</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}Add student</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>
  <p class="toshi-soon"><img src="../design-system/assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
    const mid = st === 'mid';
    return head + setup + (mid ? '' : qaRow) + `<div class="kpis">
  ${kpi('graduation-cap', 'Students', '248', '+12 this term', 'up')}${kpi('presentation', 'Staff', '18', '16 teachers · 2 admin')}
  ${kpi('calendar-check', 'Attendance this week', '93.4%', '▲ 1.2 pts on last week', 'up')}
  ${mid ? kpi('wallet', 'Fees collected', '–', 'Set up Term 3 fees first', 'warn') : kpi('wallet', 'Fees collected', '62%', money(46500000) + ' of ' + money(75000000), '', 62)}
  ${kpi('file-text', 'Report cards ready', mid ? '0' : '140', mid ? 'No exam closed yet' : 'of 248 · Mid-term exams', mid ? '' : '', mid ? null : 56)}</div>
  ${mid ? qaTiles : ''}<div class="grid2">
   <div class="card"><div class="hd"><h2>Performance by class</h2><small>${mid ? 'No exam yet' : 'Mid-term exams · average mark'}</small></div>${mid ? `<div class="empty in"><b>No marks entered yet</b><p>Averages appear when teachers enter marks for an exam.</p><a class="btn" href="#">Go to exams</a></div>` : bars(CLASSES.filter(c => c.avg).map(c => [c.name, c.avg]))}</div>
   <div class="card"><div class="hd"><h2>Attendance trend</h2><small>Last 8 weeks · whole school</small></div>${line([['W1', 91], ['W2', 92], ['W3', 90], ['W4', 93], ['W5', 94], ['W6', 92], ['W7', 92.2], ['W8', 93.4]])}</div>
   <div class="card"><div class="hd"><h2>Students by gender</h2><small>248 students</small></div>${gender(51, 48, 1)}</div>
   <div class="card"><div class="hd"><h2>Fees collection</h2><small>Collected against expected, by month</small></div>${mid ? `<div class="empty in"><b>No fee structure for Term 3</b><p>Set the term's fees to start recording payments.</p><a class="btn pri" href="#">Set up fees</a></div>` : cols([['Sep', 24100000], ['Oct', 14900000], ['Nov', 7500000]], [30000000, 25000000, 20000000]) + `<div class="legend"><span><i style="background:#15803D"></i>Collected</span><span><i style="border:1px dashed #94A3B8"></i>Expected</span></div>`}</div>
  </div>
  <div class="card"><div class="hd"><h2>Recent activity</h2><a href="#">See all</a></div><ul class="act">
   <li><span class="ib">${ic('calendar-check', 'ic ic-sm')}</span><span>Sarah Nakato took attendance for <b>Primary 5 · Blue</b> (30 of 32 present)</span><time>08:12</time></li>
   <li><span class="ib">${ic('wallet', 'ic ic-sm')}</span><span>Payment of ${money(270000)} recorded for <b>Amara Okafor</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('clipboard-list', 'ic ic-sm')}</span><span>Mathematics marks entered for <b>Primary 6 · Blue</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('message-circle', 'ic ic-sm')}</span><span>142 report cards sent to parents on WhatsApp</span><time>Mon</time></li>
   <li><span class="ib">${ic('user-plus', 'ic ic-sm')}</span><span>3 students added to <b>Reception · Sunflower</b></span><time>Mon</time></li></ul></div>
  <p class="toshi-soon"><img src="../design-system/assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
  };
  function greet() {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  }
  /* ---------- 2. People list (students / teachers / parents) ---------- */
  const CFG = {
    students: {
      t: 'Students',
      one: 'student',
      add: 'Add student',
      rows: () => S.slice(0, 10),
      chips: [['all', 'All', 248], ['cls', 'Class: All', null, 'chevron-down'], ['active', 'Active', 241], ['inactive', 'Inactive', 7], ['nocls', 'No class', 3], ['nopar', 'No parent', 5]],
      cols: ['Student', 'KLS number', 'Class', 'Parent or guardian', 'Status'],
      cell: s => [`<span class="who">${av(s, 36)}<span style="min-width:0"><a href="#" data-go="student">${s.fn} ${s.ln}</a></span></span>`, `<span class="mono">${s.kls}</span>`, s.cls || '<span class="badge b-warn">No class</span>', s.parent ? `${s.parent.fn} ${s.parent.ln}` : '<span class="badge b-warn">No parent</span>', st(s.status)],
      card: s => [`${s.fn} ${s.ln}`, `<span class="mono">${s.kls}</span><span>${s.cls || '<span class="badge b-warn">No class</span>'}</span>${s.status !== 'active' ? st(s.status) : ''}`],
      menu: ['View profile', 'Edit', 'Move to class', 'Message parent'],
      bulk: ['Message parents', 'Move to class', 'Export'],
      go: 'student'
    },
    teachers: {
      t: 'Teachers',
      one: 'teacher',
      add: 'Add teacher',
      rows: () => T,
      chips: [['all', 'All', 18], ['active', 'Active', 17], ['inv', 'Not yet invited', 1], ['pend', 'Invite pending', 1], ['ct', 'Class teachers', 8]],
      cols: ['Teacher', 'Teaches', 'Class teacher of', 'Invite', 'Status'],
      cell: t => [`<span class="who">${av(t, 36)}<span style="min-width:0"><a href="#" data-go="teacher">${t.fn} ${t.ln}</a><span class="sub trunc">${t.role}</span></span></span>`, t.cls.map(c => c[1]).join(', '), t.ctOf || '–', inv(t.invite), st(t.status)],
      card: t => [`${t.fn} ${t.ln}`, `<span>${t.ctOf ? 'Class teacher · ' + t.ctOf : t.role}</span>${t.invite !== 'accepted' ? inv(t.invite) : ''}`],
      menu: ['View profile', 'Edit', 'Send invite', 'Assign classes'],
      bulk: ['Send invites', 'Export'],
      go: 'teacher'
    },
    parents: {
      t: 'Parents',
      one: 'parent',
      add: 'Add parent',
      rows: () => P.concat(P.map(p => ({
        ...p,
        id: p.id + 10,
        fn: p.fn === 'Grace' ? 'Joy' : p.fn === 'Peter' ? 'Ruth' : p.fn === 'Ana' ? 'Ade' : 'Lina'
      }))),
      chips: [['all', 'All', 211], ['wa', 'On WhatsApp', 188], ['nowa', 'Not opted in', 23], ['never', 'Never logged in', 41]],
      cols: ['Parent or guardian', 'Children', 'Phone', 'WhatsApp', 'Last login'],
      cell: p => [`<span class="who">${av(p, 36)}<span style="min-width:0"><a href="#" data-go="parent">${p.fn} ${p.ln}</a></span></span>`, p.kids.map(k => k.fn).join(', '), `<span class="mono">${p.ph}</span>`, wa(p.wa), p.last],
      card: p => [`${p.fn} ${p.ln}`, `<span>${p.kids.map(k => k.fn).join(', ')}</span>${wa(p.wa)}`],
      menu: ['View profile', 'Edit', 'Link a child', 'Send WhatsApp opt-in'],
      bulk: ['Send WhatsApp opt-in', 'Export'],
      go: 'parent'
    }
  };
  const st = s => s === 'active' ? '<span class="badge b-ok">Active</span>' : '<span class="badge b-off">Inactive</span>';
  const inv = s => s === 'accepted' ? '<span class="badge b-ok">Joined</span>' : s === 'invited' ? '<span class="badge b-info">Invited</span>' : '<span class="badge b-warn">Not invited</span>';
  const wa = s => s === 'in' ? '<span class="badge b-ok">Opted in</span>' : s === 'pending' ? '<span class="badge b-info">Asked</span>' : '<span class="badge b-off">Not opted in</span>';
  window.peopleList = (kind, opts = {}) => {
    const c = CFG[kind],
      rows = opts.rows || c.rows(),
      state = opts.state || ST.state;
    const chipList = opts.chips || c.chips.filter(x => !(opts.inClass && x[0] === 'cls'));
    const tools = `<div class="lt"><label class="search"><span class="sr">Search ${c.t.toLowerCase()}</span>${ic('search')}<input type="search" placeholder="Search by name${kind === 'students' ? ', KLS number' : kind === 'parents' ? ', phone' : ', email'}"></label>
   <div class="chips" role="group" aria-label="Filters">${chipList.map((x, i) => `<button class="chip" type="button" aria-pressed="${i === 0}">${x[1]}${x[2] != null ? ` <span class="n">${x[2]}</span>` : ''}${x[3] ? ic(x[3], 'ic ic-sm') : ''}</button>`).join('')}</div></div>`;
    const sel = ST.sel.size;
    const bulk = sel ? `<div class="bulk" role="region" aria-label="Bulk actions"><b>${sel} selected</b>${c.bulk.map(b => `<button class="btn" type="button">${b}</button>`).join('')}<button class="btn" type="button" data-act="clr">Clear</button></div>` : '';
    if (state === 'empty') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} yet</b><p>${kind === 'students' ? 'Add students one by one, or import a spreadsheet with names and classes.' : kind === 'teachers' ? 'Add your teaching staff, then send each one an invite to join.' : 'Parents are added with their children, or you can add them here and link them.'}</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>`;
    if (state === 'nomatch') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} match “Zed”</b><p>Check the spelling or clear the filters to search all ${c.t.toLowerCase()}.</p><button class="btn" type="button">Clear filters</button></div>`;
    const loading = state === 'loading';
    const menu = r => `<button class="btn icon ghost" type="button" data-act="rmenu" data-id="${r.id}" aria-haspopup="menu" aria-expanded="${ST.menu == r.id}" aria-label="Actions for ${r.fn} ${r.ln}">${ic('ellipsis-vertical')}</button>${ST.menu == r.id ? `<div class="rmenu" role="menu">${c.menu.map((m, i) => `<a class="mi" role="menuitem" href="#" ${i === 0 ? `data-go="${c.go}"` : ''}>${m}</a>`).join('')}</div>` : ''}`;
    const ck = r => `<label class="ckb"><input type="checkbox" data-act="ck" data-id="${r.id}" ${ST.sel.has(String(r.id)) ? 'checked' : ''} aria-label="Select ${r.fn} ${r.ln}"></label>`;
    const head = `<thead><tr><th class="ck"><label class="ckb"><input type="checkbox" data-act="ckall" ${sel === rows.length ? 'checked' : ''} aria-label="Select all on this page"></label></th>${c.cols.map(h => `<th scope="col">${h}</th>`).join('')}<th class="menu"><span class="sr">Actions</span></th></tr></thead>`;
    const sk = '<span class="skel" style="width:70%"></span>';
    const body = loading ? Array.from({
      length: 6
    }, () => `<tr aria-hidden="true"><td class="ck"></td>${c.cols.map((_, i) => `<td>${i ? sk : '<span class="who"><span class="skel" style="width:36px;height:36px;border-radius:12px"></span><span class="skel" style="width:140px"></span></span>'}</td>`).join('')}<td></td></tr>`).join('') : rows.map(r => `<tr class="${ST.sel.has(String(r.id)) ? 'sel' : ''}"><td class="ck">${ck(r)}</td>${c.cell(r).map(x => `<td>${x}</td>`).join('')}<td class="menu">${menu(r)}</td></tr>`).join('');
    const cards = loading ? Array.from({
      length: 5
    }, () => `<div class="pc" aria-hidden="true"><span></span><span class="skel" style="width:40px;height:40px;border-radius:12px"></span><span><span class="skel" style="width:60%"></span><span class="skel" style="width:40%;margin-top:6px"></span></span><span></span></div>`).join('') : rows.map(r => {
      const [n, l2] = c.card(r);
      return `<div class="pc ${ST.sel.has(String(r.id)) ? 'sel' : ''}">${ck(r)}${av(r, 40)}<div style="min-width:0"><a class="nm trunc" href="#" data-go="${c.go}">${n}</a><div class="ln2">${l2}</div></div><div style="position:relative">${menu(r)}</div></div>`;
    }).join('');
    return tools + bulk + `<div class="tbl" ${loading ? 'aria-busy="true"' : ''}>${loading ? '<span class="sr" role="status">Loading ' + c.t.toLowerCase() + '…</span>' : ''}<table class="pl">${head}<tbody>${body}</tbody></table><div class="cards">${cards}</div>
  <div class="pager"><span>${loading ? '&nbsp;' : `Showing 1–${rows.length} of ${opts.total || c.chips[0][2]}`}</span><span class="row-acts"><button class="btn icon" type="button" aria-label="Previous page" disabled>${ic('chevron-left')}</button><button class="btn icon" type="button" aria-label="Next page">${ic('chevron-right')}</button></span></div></div>`;
  };
  window.scrList = kind => {
    const c = CFG[kind];
    return `<div class="ph"><div><h1>${c.t}</h1><p>${c.chips[0][2]} ${c.t.toLowerCase()} at ${SCHOOL.name}</p></div><div class="row-acts"><a class="btn" href="#">${ic('upload')}Import</a><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a></div></div>` + peopleList(kind);
  };
  /* ---------- 6. Classes (class teacher per stream, class-level default) ---------- */
  const streamTeacher = (c, i) => i === 0 || c.streams.length === 1 ? {
    t: T.find(x => x.id === c.ct),
    own: true
  } : {
    t: T.find(x => x.id === c.ct),
    own: false
  };
  const lvl = c => c.id === 'n1' || c.id === 'rc' ? 'Nursery' : 'Primary';
  window.scrClasses = () => {
    if (ST.state === 'empty') return `<div class="ph"><div><h1>Classes and streams</h1></div></div><div class="empty"><b>No classes yet</b><p>Add the classes your school teaches, from nursery to the final year. Add streams if a class is split into groups.</p><div class="row-acts"><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>`;
    return `<div class="ph"><div><h1>Classes and streams</h1><p>${CLASSES.length} classes · ${CLASSES.reduce((a, c) => a + c.streams.length, 0)} streams · Mid-term exams</p></div><div class="row-acts"><a class="btn" href="#">${ic('plus')}Add stream</a><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>
  <div class="lt"><label class="search"><span class="sr">Find a class</span>${ic('search')}<input type="search" placeholder="Find a class"></label><div class="chips" role="group" aria-label="Level"><button class="chip" type="button" aria-pressed="true">All <span class="n">8</span></button><button class="chip" type="button" aria-pressed="false">Nursery <span class="n">2</span></button><button class="chip" type="button" aria-pressed="false">Primary <span class="n">6</span></button></div></div>
  <div class="ccards">${CLASSES.map(c => `<article class="cc"><span class="kick">${lvl(c)}</span><a class="t" href="#" data-go="class">${c.name}</a>
    <ul class="strl">${c.streams.map((s, i) => {
      const x = streamTeacher(c, i);
      return `<li><span>${s}</span><span class="tch">${av(x.t, 24)}<span class="trunc">${x.t.fn} ${x.t.ln}</span>${x.own ? '' : '<span class="badge b-off">Class default</span>'}</span></li>`;
    }).join('')}</ul>
    <div class="st"><span>${c.n} students</span><span>Attendance ${c.att}%</span></div>
    <div class="hint">${c.avg ? `<span>Average <b>${c.avg}%</b> · ${grade(c.avg)}</span><span>${c.id === 'p6' ? '▼ 2 since last exam' : '▲ 3 since last exam'}</span>` : '<span>No exams for this class</span>'}</div></article>`).join('')}</div>`;
  };
  window.scrClass = () => {
    const c = CLASSES.find(x => x.id === 'p5'),
      t = T[0];
    const subj = [['Mathematics', T[0]], ['English', T[2]], ['Science', T[0]], ['Social Studies', T[3]], ['Religious Education', null], ['Creative Arts', T[6]]];
    const dist = [['A', 6, '#15803D'], ['B', 9, '#1E6FD9'], ['C', 10, '#B45309'], ['D', 5, '#1E293B'], ['E', 2, '#B91C1C']];
    const ST2 = [{
      s: 'Blue',
      n: 16,
      avg: 70,
      att: 95,
      own: true
    }, {
      s: 'Red',
      n: 16,
      avg: 66,
      att: 93,
      own: false
    }];
    const blue = S.filter(s => s.cls.endsWith('Blue')).slice(0, 8);
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="classes">Classes</a> <span aria-hidden="true">›</span> Primary 5</nav>
  <div class="ph"><div><h1>Primary 5</h1><p>2 streams · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts"><a class="btn" href="#">${ic('calendar-check')}Take attendance</a><a class="btn" href="#">${ic('clipboard-list')}Enter marks</a><button class="btn icon" type="button" aria-label="More actions">${ic('ellipsis')}</button></div></div>
  <div class="kpis k4">
   <div class="kpi"><span class="l">${ic('user-round-check', 'ic ic-sm')}Class teacher (default)</span><span style="display:flex;align-items:center;gap:10px;min-width:0">${av(t, 32)}<a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a></span><span class="s">For any stream without its own</span></div>
   ${kpi('graduation-cap', 'Students', '32', '17 girls · 15 boys')}${kpi('chart-column', 'Average · Mid-term', '68% · C', '▲ 3 pts since last exam', 'up')}${kpi('calendar-check', 'Attendance this week', '94%', '30 of 32 present today')}</div>
  <div class="card"><div class="hd"><h2>Streams</h2><a href="#">${ic('plus', 'ic ic-sm')} Add stream</a></div><div class="sgrid">${ST2.map(x => `<div class="scard"><b>Primary 5 · ${x.s}</b>
   <div class="tch">${av(t, 32)}<span style="min-width:0"><a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a><span class="sub">${x.own ? 'Class teacher of this stream' : 'Class default · no teacher assigned to this stream'}</span></span></div>
   ${x.own ? '' : '<a class="btn" href="#">Assign a class teacher</a>'}
   <div class="st"><span>${x.n} students</span><span>Average ${x.avg}%</span><span>Attendance ${x.att}%</span></div></div>`).join('')}</div></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Grade distribution</h2><small>Mid-term exams · 32 students</small></div>
   <div class="stack" role="img" aria-label="${dist.map(d => d[0] + ': ' + d[1]).join(', ')}">${dist.map(d => `<i style="width:${d[1] / 32 * 100}%;background:${d[2]}"></i>`).join('')}</div><div class="legend">${dist.map(d => `<span><i style="background:${d[2]}"></i>${d[0]} · ${d[1]}</span>`).join('')}</div>
   <div class="hd" style="margin-top:6px"><h3>Students by gender</h3></div>${gender(53, 47, 0)}</div>
   <div class="card"><div class="hd"><h2>Subjects</h2><small>6</small></div><ul class="subj">${subj.map(([s, tt]) => `<li><span>${s}</span><span class="tch">${tt ? av(tt, 28) + `<span class="trunc">${tt.fn} ${tt.ln}</span>` : '<span class="badge b-warn">No teacher</span>'}</span></li>`).join('')}</ul></div></div>
  <h2 class="sh">Students</h2>` + peopleList('students', {
      inClass: 1,
      rows: blue,
      total: 32,
      chips: [['all', 'All streams', 32], ['b', 'Blue', 16], ['r', 'Red', 16], ['active', 'Active', 31], ['nopar', 'No parent', 2]]
    });
  };
  window.KC = {
    bars,
    line,
    cols,
    kpi,
    gender,
    ayPick
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-08-admin-mvp/concept/screens-a.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-08-admin-mvp/concept/screens-b.js
try { (() => {
// Profiles: student, teacher, parent — admin viewer
(() => {
  const {
    SCHOOL,
    T,
    S,
    P,
    av,
    money,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  const tabs = (list, cur) => `<div class="tabs" role="tablist">${list.map(x => `<button role="tab" type="button" data-act="tab" data-tab="${x}" aria-selected="${x === cur}">${x}</button>`).join('')}</div>`;
  const more = (id, items) => `<span style="position:relative"><button class="btn icon" type="button" data-act="rmenu" data-id="${id}" aria-haspopup="menu" aria-expanded="${ST.menu == id}" aria-label="More actions">${ic('ellipsis')}</button>${ST.menu == id ? `<div class="rmenu" role="menu" style="top:calc(100% + 6px);right:0;width:240px">${items}</div>` : ''}</span>`;
  const empty = (t, p, b) => `<div class="empty"><b>${t}</b><p>${p}</p>${b ? `<a class="btn" href="#">${b}</a>` : ''}</div>`;
  const E = () => ST.state === 'empty';
  /* ---------- 3. Student ---------- */
  window.scrStudent = () => {
    const s = S[0],
      tl = ['Overview', 'Academics', 'Attendance', 'Fees', 'Parents and guardians', 'Health and support', 'Documents', 'Notes'],
      tab = tl.includes(ST.tab) ? ST.tab : 'Overview';
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a><a class="mi" role="menuitem" href="#">${ic('arrow-right-left')}Move to class</a><div class="sep" role="separator"></div>
   <button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deact">${ic('user-x')}Deactivate student</button>`;
    const hd = `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="students">Students</a> <span aria-hidden="true">›</span> ${s.fn} ${s.ln}</nav>
  <div class="phd">${av(s, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${s.fn} ${s.ln}</h1><div class="meta"><a href="#" data-go="class" style="font-weight:700">${s.cls}</a><span><span class="k">KLS</span> <span style="font-variant-numeric:tabular-nums">${s.kls}</span></span><span class="badge b-ok">Active</span></div></div>
  <div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit</a><a class="btn" href="#">${ic('message-circle')}Message parent</a>${more('stu', menu)}</div></div>`;
    const k = E() ? `<div class="kpis k3"><div class="kpi"><span class="l">${ic('calendar-check', 'ic ic-sm')}Attendance this term</span><span class="v">–</span><span class="s">No register taken yet</span></div><div class="kpi"><span class="l">${ic('chart-column', 'ic ic-sm')}Latest exam</span><span class="v">–</span><span class="s">No marks yet</span></div><div class="kpi"><span class="l">${ic('wallet', 'ic ic-sm')}Fees</span><span class="v">–</span><span class="s">No fee structure</span></div></div>` : `<div class="kpis k3"><div class="kpi"><span class="l">${ic('calendar-check', 'ic ic-sm')}Attendance this term</span><span class="v">91%</span><span class="meter" role="img" aria-label="91%"><i style="width:91%"></i></span><span class="s">5 days absent · 2 late</span></div>
     <div class="kpi"><span class="l">${ic('chart-column', 'ic ic-sm')}Latest exam · Mid-term</span><span class="v">74% · B</span><span class="s">6th of 32 in Primary 5 · Blue</span></div>
     <div class="kpi"><span class="l">${ic('wallet', 'ic ic-sm')}Fees · ${SCHOOL.term}</span><span class="kl">Balance</span><span class="v owed">${money(180000)}</span><span class="s"><span class="badge b-warn">Partly paid</span> ${money(270000)} of ${money(450000)}</span></div></div>`;
    let b;
    if (tab === 'Overview') b = `<div class="grid2"><div class="card"><h2>Student</h2><dl class="kv"><dt>Full name</dt><dd>${s.fn} ${s.ln}</dd><dt>KLS number</dt><dd>${s.kls}</dd><dt>Class</dt><dd>${s.cls}</dd><dt>Gender</dt><dd>Female</dd><dt>Date of birth</dt><dd>${E() ? '<span style="color:var(--d-text-secondary)">Not given</span>' : s.dob}</dd><dt>Joined</dt><dd>${E() ? 'This term' : 'Term 1, 2024'}</dd></dl></div>
   <div class="card"><h2>Parents and guardians</h2>${E() ? `<p style="margin:0">No parent linked yet.</p><a class="btn" href="#" style="align-self:flex-start">${ic('link')}Link a parent</a>` : `<div class="kidc">${av(P[0], 40)}<div><a href="#" data-go="parent" style="font-weight:700">${P[0].fn} ${P[0].ln}</a><div class="pc-sub" style="font-size:13.5px;color:var(--d-text-secondary)">Mother · <a href="tel:+000772418205">${P[0].ph}</a> · on WhatsApp</div></div></div>`}</div></div>`;else if (E()) b = {
      Academics: empty('No marks yet', 'Marks appear here once teachers enter them for an exam.'),
      Attendance: empty('No attendance taken yet this term', 'It appears after the class teacher takes the first register.'),
      Fees: empty('No fee structure for Primary 5 this term', 'Set up the term’s fees before recording payments.', 'Set up fees'),
      'Parents and guardians': empty('No parent linked yet', 'Link a parent so they receive report cards and fee messages on WhatsApp.', 'Link a parent'),
      'Health and support': empty('No health or support notes', 'Parents can add allergies, medical conditions and support needs on the admission form, or you can add them here.', 'Add notes'),
      Documents: empty('No documents yet', 'Birth certificates and photos sent with the admission form appear here.', 'Upload document'),
      Notes: empty('No notes', 'Notes are visible to admins only, and each view is logged.', 'Add a note')
    }[tab];else if (tab === 'Academics') {
      const sub = [['English', 70, 72, 76], ['Mathematics', 66, 71, 73], ['Science', 74, 75, 79], ['Social Studies', 62, 66, 68], ['Religious Education', 81, 80, 84], ['Creative Arts', 77, 79, 0]];
      b = `<div class="card"><div class="hd"><h2>Marks by subject</h2><small>${SCHOOL.year} · % per exam</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Subject</th><th class="n" scope="col">Term 1</th><th class="n" scope="col">Term 2</th><th class="n" scope="col">Term 3 mid-term</th><th scope="col">Grade</th></tr></thead><tbody>${sub.map(r => `<tr><td>${r[0]}</td><td class="n">${r[1]}</td><td class="n">${r[2]}</td><td class="n">${r[3] || '<span style="color:var(--d-text-secondary)">Pending</span>'}</td><td>${r[3] ? grade(r[3]) : '–'}</td></tr>`).join('')}<tr><th scope="row">Average</th><td class="n"><b>72</b></td><td class="n"><b>74</b></td><td class="n"><b>74</b></td><td><b>B</b></td></tr></tbody></table></div></div>
    <div class="card"><div class="hd"><h2>This term against the class</h2><small>Amara · class average</small></div><div class="hbars">${sub.filter(r => r[3]).map((r, i) => `<div class="hb"><span>${r[0].split(' ')[0]}</span><span class="t" role="img" aria-label="${r[0]}: ${r[3]}%, class average ${r[3] - 5 + i}%"><i style="width:${r[3]}%"></i></span><b>${r[3]}%</b></div><div class="hb" style="margin-top:-6px"><span></span><span class="t" style="height:6px" aria-hidden="true"><i style="width:${r[3] - 5 + i}%;background:#94A3B8"></i></span><span style="font-size:12px;text-align:right;color:var(--d-text-secondary)">${r[3] - 5 + i}%</span></div>`).join('')}</div><div class="legend"><span><i style="background:#1E6FD9"></i>Amara</span><span><i style="background:#94A3B8"></i>Class average</span></div></div>`;
    } else if (tab === 'Attendance') b = `<div class="card"><div class="hd"><h2>${SCHOOL.term}</h2><small>Marked by Sarah Nakato</small></div><div class="kpis k3"><div class="kpi"><span class="l">Present</span><span class="v">91%</span></div><div class="kpi"><span class="l">Absent</span><span class="v">5 days</span></div><div class="kpi"><span class="l">Late</span><span class="v">2 days</span></div></div><table class="dt"><thead><tr><th scope="col">Date</th><th scope="col">Status</th></tr></thead><tbody><tr><td>Mon 5 Oct</td><td><span class="badge b-bad">Absent</span></td></tr><tr><td>Fri 2 Oct</td><td><span class="badge b-ok">Present</span></td></tr><tr><td>Thu 1 Oct</td><td><span class="badge b-warn">Late</span></td></tr></tbody></table></div>`;else if (tab === 'Fees') b = `<div class="card"><div class="hd"><h2>${SCHOOL.term}, ${SCHOOL.year}</h2><span class="badge b-warn">Partly paid</span></div><dl class="kv"><dt>Expected</dt><dd>${money(450000)}</dd><dt>Paid</dt><dd>${money(270000)}</dd><dt>Balance</dt><dd class="owed"><b>${money(180000)}</b></dd></dl><table class="dt"><thead><tr><th scope="col">Date</th><th scope="col">Method</th><th class="n" scope="col">Amount</th></tr></thead><tbody><tr><td>2 Sep</td><td>Bank transfer</td><td class="n">${money(270000)}</td></tr></tbody></table><div class="row-acts"><a class="btn pri" href="#">Record payment</a><a class="btn" href="#">Send reminder on WhatsApp</a></div></div>`;else if (tab === 'Parents and guardians') b = `<div class="card"><div class="hd"><h2>Parents and guardians</h2><a class="btn" href="#">${ic('link')}Link another</a></div><div class="kidc">${av(P[0], 48)}<div><a href="#" data-go="parent" style="font-weight:700">${P[0].fn} ${P[0].ln}</a><div style="font-size:13.5px;color:var(--d-text-secondary)">Mother · primary contact</div><a class="ctline" href="tel:+000772418205">${ic('phone')}${P[0].ph}</a><span class="badge b-ok">Opted in to WhatsApp</span></div></div></div>`;else if (tab === 'Documents') b = `<div class="card"><div class="hd"><h2>Documents</h2><a class="btn" href="#">${ic('upload')}Upload</a></div><table class="dt"><thead><tr><th scope="col">Document</th><th scope="col">From</th><th scope="col">Added</th></tr></thead><tbody><tr><td>${ic('file-text', 'ic ic-sm')} Birth certificate.pdf</td><td>Admission form</td><td>12 Jan 2024</td></tr><tr><td>${ic('image', 'ic ic-sm')} Passport photo.jpg</td><td>Admission form</td><td>12 Jan 2024</td></tr></tbody></table></div>`;else if (tab === 'Health and support') b = `<div class="card"><div class="hd"><h2>Health and support</h2><span class="badge b-info">${ic('lock-keyhole', 'ic ic-sm')}Admins and head teacher only</span></div><p class="note">${ic('history', 'ic ic-sm')}Opening this tab is logged. Last opened by Mucunguzi Moses today at 09:14.</p><dl class="kv"><dt>Allergies</dt><dd>Peanuts (severe). Carries an adrenaline pen in her school bag.</dd><dt>Medical conditions</dt><dd>Mild asthma. Inhaler kept at the school office.</dd><dt>Support needs</dt><dd>Sits near the front of the class for hearing.</dd><dt>Emergency contact</dt><dd>Grace Okafor (mother) · <a href="tel:+000772418205">${P[0].ph}</a></dd><dt>Last updated</dt><dd>12 Jan 2024 · admission form</dd></dl><div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit notes</a></div></div>`;else b = `<div class="card"><div class="reqrow"><span>Notes are visible to admins only. Opening them is logged.</span><button class="btn" type="button">${ic('lock-keyhole-open')}Show notes</button></div></div>`;
    return hd + k + `<div class="tabw">${tabs(tl, tab)}</div>` + b;
  };
  /* ---------- 4. Teacher ---------- */
  window.scrTeacher = () => {
    const inv = ST.invite || 'accepted',
      t = {
        ...T[0],
        invite: inv
      };
    const badge = inv === 'accepted' ? '<span class="badge b-ok">Joined 3 Sep 2026</span>' : inv === 'invited' ? '<span class="badge b-info">Invited 5 Oct · not accepted yet</span>' : '<span class="badge b-warn">Not invited</span>';
    const invBtn = inv === 'accepted' ? '' : inv === 'invited' ? `<a class="btn" href="#">${ic('send')}Resend invite</a>` : `<a class="btn pri" href="#">${ic('send')}Send invite</a>`;
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('book-open')}Assign classes and subjects</a>${inv === 'accepted' ? `<a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a>` : ''}<div class="sep" role="separator"></div><button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deactT">${ic('user-x')}Deactivate account</button>`;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="teachers">Teachers</a> <span aria-hidden="true">›</span> ${t.fn} ${t.ln}</nav>
  <div class="phd">${av(t, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${t.fn} ${t.ln}</h1><div class="meta"><span class="badge b-info">${t.role}</span><span>Class teacher of <a href="#" data-go="class" style="font-weight:700">${t.ctOf}</a></span>${badge}</div></div>
  <div class="row-acts">${invBtn}<a class="btn" href="#">${ic('pencil')}Edit</a>${more('tch', menu)}</div></div>
  <div class="kpis k4">
   <div class="kpi"><span class="l">${ic('school', 'ic ic-sm')}Classes</span><span class="v">2</span><span class="s">Primary 5 · Blue, Primary 6 · Blue</span></div>
   <div class="kpi"><span class="l">${ic('book-open', 'ic ic-sm')}Subjects</span><span class="v">2</span><span class="s">Mathematics, Science</span></div>
   ${ST.wl === 'as' ? `<div class="kpi"><span class="l">${ic('clock', 'ic ic-sm')}Workload</span><span class="v">3</span><span class="s">class–subject assignments</span><span class="badge b-off" style="align-self:flex-start">No timetable yet</span></div>` : `<div class="kpi"><span class="l">${ic('clock', 'ic ic-sm')}Workload</span><span class="v">${t.lessons}</span><span class="s">lessons a week</span><span class="badge b-ok" style="align-self:flex-start">From the timetable</span></div>`}
   <div class="kpi"><span class="l">${ic('circle-alert', 'ic ic-sm')}Still pending</span><span class="v">1</span><span class="s warn">Science marks · Primary 5 Blue</span></div></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Teaches</h2><small>${SCHOOL.term}</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Class</th><th scope="col">Subject</th><th class="n" scope="col">Students</th><th scope="col">Mid-term marks</th></tr></thead><tbody>
   <tr><td><a href="#" data-go="class">Primary 5 · Blue</a> <span class="badge b-ok">Class teacher</span></td><td>Mathematics</td><td class="n">32</td><td><span class="badge b-ok">Entered</span></td></tr>
   <tr><td><a href="#" data-go="class">Primary 5 · Blue</a></td><td>Science</td><td class="n">32</td><td><span class="badge b-warn">Pending</span></td></tr>
   <tr><td>Primary 6 · Blue</td><td>Mathematics</td><td class="n">27</td><td><span class="badge b-ok">Entered</span></td></tr></tbody></table></div>
   <div class="reqrow"><span>Today's attendance for Primary 5 · Blue</span><span class="badge b-ok">Taken at 08:12</span></div></div>
   <div class="card"><h2>Contact</h2><a class="ctline" href="tel:+000700100101">${ic('phone')}${t.ph}</a><a class="ctline trunc" href="mailto:${t.email}">${ic('mail')}<span class="trunc">${t.email}</span></a><dl class="kv" style="margin-top:4px"><dt>Last login</dt><dd>${inv === 'accepted' ? t.last : 'Never'}</dd><dt>Account</dt><dd>${inv === 'accepted' ? 'Active' : inv === 'invited' ? 'Invite sent, link valid 72 hours' : 'No login yet'}</dd></dl></div></div>`;
  };
  /* ---------- 5. Parent ---------- */
  window.scrParent = () => {
    const p = P[0],
      waSt = ST.wa || 'in';
    const waRow = waSt === 'in' ? `<span class="badge b-ok">Opted in on ${p.waDate}</span>` : waSt === 'pending' ? `<span class="badge b-info">Opt-in request sent, waiting for reply</span>` : `<span class="badge b-off">Not opted in</span><a class="btn" href="#">${ic('message-circle')}Send opt-in request</a>`;
    const menu = `<a class="mi" role="menuitem" href="#">${ic('pencil')}Edit details</a><a class="mi" role="menuitem" href="#">${ic('link')}Link a child</a><a class="mi" role="menuitem" href="#">${ic('key-round')}Reset password</a><div class="sep" role="separator"></div><button class="mi danger" role="menuitem" type="button" data-act="dlg" data-k="deactP">${ic('user-x')}Deactivate account</button>`;
    const kids = E() ? empty('No children linked yet', 'Link this parent to their children so they receive report cards and fee messages on WhatsApp.', 'Link a child') : `<div class="grid2">${p.kids.map(k => `<article class="card"><div class="kidc">${av(k, 48)}<div style="min-width:0"><a href="#" data-go="student" style="font:600 16px var(--d-font-display,'Sora',sans-serif);color:#0F172A">${k.fn} ${k.ln}</a><div style="font-size:13.5px;color:var(--d-text-secondary)">${k.cls} · <span style="font-variant-numeric:tabular-nums">${k.kls}</span></div><div class="meta" style="margin-top:6px;font-size:13.5px"><span>Attendance ${k.att}%</span>${k.bal ? `<span class="owed">${money(k.bal)} due</span>` : '<span class="badge b-ok">Fees cleared</span>'}</div></div>
     <div class="ql"><a class="btn" href="#">${ic('file-text')}Report card</a><a class="btn" href="#">${ic('wallet')}Fees</a></div></div></article>`).join('')}</div>`;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="parents">Parents</a> <span aria-hidden="true">›</span> ${p.fn} ${p.ln}</nav>
  <div class="phd">${av(p, ST.mob ? 64 : 112)}<div style="min-width:0"><h1>${p.fn} ${p.ln}</h1><div class="meta"><span class="badge b-info">Parent</span><span>${E() ? 'No children linked' : p.kids.length + ' children at ' + SCHOOL.name}</span></div></div>
  <div class="row-acts"><a class="btn" href="#">${ic('message-circle')}Message on WhatsApp</a><a class="btn" href="#">${ic('pencil')}Edit</a>${more('par', menu)}</div></div>
  <h2 style="font:600 18px var(--d-font-display,'Sora',sans-serif);margin:0;color:#0F172A">Children</h2>${kids}
  <div class="grid2"><div class="card"><h2>Contact</h2><a class="ctline" href="tel:+000772418205">${ic('phone')}${p.ph}</a><a class="ctline" href="mailto:${p.email}">${ic('mail')}<span class="trunc">${p.email}</span></a></div>
  <div class="card"><h2>WhatsApp and access</h2><div class="reqrow">${waRow}</div><dl class="kv"><dt>Last login</dt><dd>${p.last}</dd><dt>Messages this term</dt><dd>12 sent · 2 replies</dd></dl></div></div>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-08-admin-mvp/concept/screens-b.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-08-admin-mvp/concept/screens-c.js
try { (() => {
// Batch two: Subjects, Subject page, Attendance overview, Exams (subject-first)
(() => {
  const {
    SCHOOL,
    T,
    av,
    grade,
    ic
  } = KD;
  const ST = window.ST;
  const {
    bars,
    line,
    kpi
  } = window.KC;
  const tt = id => T.find(t => t.id === id);
  const STREAMS = [['Primary 1', 'Blue', 't2', 16], ['Primary 1', 'Red', 't2', 15], ['Primary 2', 'Blue', 't3', 15], ['Primary 2', 'Red', 't3', 14], ['Primary 3', 'Blue', 't4', 28], ['Primary 4', 'Blue', 't5', 30], ['Primary 5', 'Blue', 't1', 16], ['Primary 5', 'Red', 't1', 16], ['Primary 6', 'Blue', 't6', 27]];
  const SUB = [{
    id: 'eng',
    n: 'English',
    code: 'ENG',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t2', 't3', 't6'],
    avg: 71,
    d: 2,
    ent: 9
  }, {
    id: 'mth',
    n: 'Mathematics',
    code: 'MTH',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t1', 't5'],
    avg: 66,
    d: -1,
    ent: 6
  }, {
    id: 'sci',
    n: 'Science',
    code: 'SCI',
    type: 'Core',
    lv: 'Primary 3–6',
    from: 4,
    tch: ['t1', 't4'],
    avg: 72,
    d: 3,
    ent: 3
  }, {
    id: 'sst',
    n: 'Social Studies',
    code: 'SST',
    type: 'Core',
    lv: 'Primary 3–6',
    from: 4,
    tch: ['t4'],
    avg: 63,
    d: 0,
    ent: 5
  }, {
    id: 're',
    n: 'Religious Education',
    code: 'RE',
    type: 'Core',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t6'],
    avg: 79,
    d: 1,
    ent: 2,
    gap: 1
  }, {
    id: 'ca',
    n: 'Creative Arts',
    code: 'CA',
    type: 'Optional',
    lv: 'Primary 1–6',
    from: 0,
    tch: ['t7'],
    avg: null,
    d: 0,
    ent: 0
  }, {
    id: 'lan',
    n: 'Language',
    code: 'LAN',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }, {
    id: 'num',
    n: 'Numbers',
    code: 'NUM',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }, {
    id: 'rdg',
    n: 'Reading',
    code: 'RDG',
    type: 'Core',
    lv: 'Nursery, Reception',
    nur: 1,
    tch: ['t7', 't8']
  }];
  const rowsOf = s => s.nur ? [] : STREAMS.slice(s.from);
  const stu = s => s.nur ? 46 : rowsOf(s).reduce((a, r) => a + r[3], 0);
  const avs = ids => `<span class="avs">${ids.slice(0, 3).map(i => av(tt(i), 28)).join('')}${ids.length > 3 ? `<span class="sub">+${ids.length - 3}</span>` : ''}</span>`;
  const prog = (a, b) => `<span class="prw"><span class="prog" role="progressbar" aria-valuemin="0" aria-valuemax="${b}" aria-valuenow="${a}" aria-label="${a} of ${b} classes"><i style="width:${b ? a / b * 100 : 0}%"></i></span><span class="sub">${a} of ${b}</span></span>`;
  const stat = (a, b) => b === 0 ? '<span class="badge b-off">No exam</span>' : a === b ? '<span class="badge b-ok">Complete</span>' : a === 0 ? '<span class="badge b-off">Not started</span>' : '<span class="badge b-warn">In progress</span>';
  const menu = (id, items) => `<span style="position:relative;display:inline-flex"><button class="btn icon ghost" type="button" data-act="rmenu" data-id="${id}" aria-haspopup="menu" aria-expanded="${ST.menu == id}" aria-label="More actions">${ic('ellipsis-vertical')}</button>${ST.menu == id ? `<div class="rmenu" role="menu" style="top:calc(100% + 4px);right:0">${items.map(m => m === '-' ? '<div class="sep" role="separator"></div>' : `<a class="mi${m.startsWith('!') ? ' danger' : ''}" role="menuitem" href="#" ${m === 'View subject' ? 'data-go="subject"' : ''}>${m.replace('!', '')}</a>`).join('')}</div>` : ''}</span>`;
  const avgCell = s => s.nur ? '<span class="sub">Not examined</span>' : s.avg ? `<b>${s.avg}%</b> · ${grade(s.avg)} <span class="sub" style="display:inline">${s.d > 0 ? '▲ ' + s.d : s.d < 0 ? '▼ ' + -s.d : '–'}</span>` : '<span class="sub">No marks yet</span>';
  /* ---------- Subjects index ---------- */
  window.scrSubjects = () => {
    const head = `<div class="ph"><div><h1>Subjects</h1><p>${SUB.length} subjects · each listed once, across all its classes</p></div><div class="row-acts"><a class="btn pri" href="#" data-go="form-subject">${ic('plus')}Add subject</a></div></div>
  <div class="lt"><label class="search"><span class="sr">Search subjects</span>${ic('search')}<input type="search" placeholder="Search by subject or code"></label><div class="chips" role="group" aria-label="Filters"><button class="chip" type="button" aria-pressed="true">All <span class="n">9</span></button><button class="chip" type="button" aria-pressed="false">Core <span class="n">8</span></button><button class="chip" type="button" aria-pressed="false">Optional <span class="n">1</span></button><button class="chip" type="button" aria-pressed="false">Nursery <span class="n">3</span></button><button class="chip" type="button" aria-pressed="false">Primary <span class="n">6</span></button><button class="chip" type="button" aria-pressed="false">Missing a teacher <span class="n">1</span></button></div></div>`;
    if (ST.state === 'empty') return head + `<div class="empty"><b>No subjects yet</b><p>Add each subject once, then choose the classes that take it and who teaches it in each class.</p><a class="btn pri" href="#">${ic('plus')}Add subject</a></div>`;
    const M = ['View subject', 'Edit', 'Assign teachers', '-', '!Archive subject'];
    return head + `<div class="tbl"><table class="pl"><thead><tr><th scope="col">Subject</th><th scope="col">Type</th><th scope="col">Classes</th><th scope="col">Teachers</th><th scope="col">Average · Mid-term</th><th scope="col">Mid-term marks</th><th class="menu"><span class="sr">Actions</span></th></tr></thead><tbody>
  ${SUB.map(s => `<tr><td><span class="who"><span class="sic" aria-hidden="true">${s.code}</span><span style="min-width:0"><a href="#" data-go="subject">${s.n}</a><span class="sub">${s.code}</span></span></span></td><td>${s.type}</td><td>${s.nur ? 2 : rowsOf(s).length} <span class="sub" style="display:inline">· ${s.lv}</span>${s.gap ? ' <span class="badge b-warn">1 without a teacher</span>' : ''}</td><td>${avs(s.tch)}</td><td>${avgCell(s)}</td><td>${s.nur ? '<span class="badge b-off">No exam</span>' : prog(s.ent, rowsOf(s).length)}</td><td class="menu">${menu('s' + s.id, M)}</td></tr>`).join('')}</tbody></table>
  <div class="cards">${SUB.map(s => `<div class="pc nock"><span class="sic" aria-hidden="true">${s.code}</span><div style="min-width:0"><a class="nm trunc" href="#" data-go="subject">${s.n}</a><div class="ln2"><span>${s.lv}</span>${s.nur ? '' : stat(s.ent, rowsOf(s).length)}${s.gap ? '<span class="badge b-warn">1 without a teacher</span>' : ''}</div></div><div style="position:relative">${menu('m' + s.id, M)}</div></div>`).join('')}</div>
  <div class="pager"><span>Showing all 9</span></div></div>`;
  };
  /* ---------- Subject page ---------- */
  window.scrSubject = () => {
    const s = SUB[1],
      rows = rowsOf(s);
    const st = (i, n) => i < 6 ? [n, n] : i === 6 ? [18, n] : [0, n];
    const EX = [['Beginning of term', '7 Sep', 9, 9, 64, 'Complete'], ['Mid-term', '5–9 Oct', 6, 9, 66, 'In progress'], ['End of term', 'From 24 Nov', 0, 9, null, 'Scheduled']];
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="subjects">Subjects</a> <span aria-hidden="true">›</span> ${s.n}</nav>
  <div class="ph"><div><h1>${s.n}</h1><div class="meta"><span class="badge b-info">${s.type}</span><span><span class="k">Code</span> ${s.code}</span><span>${s.lv}</span></div></div><div class="row-acts"><a class="btn" href="#">${ic('pencil')}Edit</a><a class="btn" href="#">${ic('user-round-plus')}Assign teachers</a>${menu('subj', ['Download all marksheets', '-', '!Archive subject'])}</div></div>
  <div class="kpis k4">${kpi('school', 'Classes', String(rows.length), s.lv)}<div class="kpi"><span class="l">${ic('presentation', 'ic ic-sm')}Teachers</span><span class="v">2</span><span class="s">${s.tch.map(i => tt(i).fn + ' ' + tt(i).ln).join(', ')}</span></div>${kpi('graduation-cap', 'Students', String(stu(s)), 'taking ' + s.n)}${kpi('chart-column', 'Average · Mid-term', '66% · C', '▼ 1 pt on Beginning of term', 'warn')}</div>
  <div class="card"><div class="hd"><h2>Exams this term</h2><small>${SCHOOL.term}, ${SCHOOL.year}</small></div>
  <ul class="exl">${EX.map((e, i) => `<li><span><b>${e[0]}</b><span class="sub">${e[1]}</span></span><span class="hm">${e[4] ? `Average <b>${e[4]}%</b>` : '<span class="sub">No marks yet</span>'}</span>${prog(e[2], e[3])}<span class="hm">${e[5] === 'Complete' ? '<span class="badge b-ok">Complete</span>' : e[5] === 'In progress' ? '<span class="badge b-warn">In progress</span>' : '<span class="badge b-off">Scheduled</span>'}</span><span class="row-acts">${i === 1 ? '<a class="btn pri" href="#">Enter marks</a>' : i === 0 ? '<a class="btn" href="#">View marks</a>' : ''}${menu('ex' + i, ['Import from spreadsheet', 'Download marksheet', 'Remind teachers'])}</span></li>`).join('')}</ul></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Classes</h2><small>Mid-term marks and report cards</small></div><div class="scroll-x"><table class="dt"><thead><tr><th scope="col">Class</th><th scope="col" class="hm">Teacher</th><th class="n hm" scope="col">Average</th><th scope="col">Marks</th><th scope="col" class="hm">Report card</th></tr></thead><tbody>
  ${rows.map((r, i) => {
      const [a, n] = st(i, r[3]);
      const t = tt(i > 5 ? 't1' : i < 4 ? 't5' : 't1');
      return `<tr><td><a href="#" data-go="class">${r[0]} · ${r[1]}</a></td><td class="hm"><span class="tch" style="display:flex;gap:8px;align-items:center">${av(t, 24)}<span class="trunc">${t.fn} ${t.ln}</span></span></td><td class="n hm">${a === n ? 60 + i * 5 % 14 + '%' : '–'}</td><td>${a === n ? `<span class="badge b-ok">Entered ${a}/${n}</span>` : a ? `<span class="badge b-warn">${a} of ${n}</span>` : `<span class="badge b-off">Not started</span>`}</td><td class="hm">${a === n ? '<span class="badge b-ok">Ready</span>' : '<span class="sub">Waiting for marks</span>'}</td></tr>`;
    }).join('')}</tbody></table></div></div>
  <div class="card"><div class="hd"><h2>Average by class</h2><small>Mid-term · entered so far</small></div>${bars(rows.slice(0, 6).map((r, i) => [r[0].replace('Primary ', 'P.') + ' ' + r[1], 60 + i * 5 % 14]))}<p class="sub" style="margin:0">3 classes have no Mid-term marks yet.</p></div></div>`;
  };
  /* ---------- Attendance overview ---------- */
  const vbars = (pts, lo = 80) => {
    const w = 360,
      h = 170,
      bw = (w - 40) / pts.length,
      y = v => 14 + (100 - v) / (100 - lo) * (h - 50);
    return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">${[80, 90, 100].map(v => `<line x1="30" x2="${w - 6}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="26" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}${pts.map((p, i) => {
      const x = 34 + i * bw;
      return `<rect x="${x + 6}" y="${y(p[1])}" width="${bw - 12}" height="${h - 36 - y(p[1])}" rx="4" fill="${p[2] || '#15803D'}"/><text x="${x + bw / 2}" y="${y(p[1]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${p[1]}%</text><text x="${x + bw / 2}" y="${h - 18}" text-anchor="middle" font-size="11" fill="#475569">${p[0]}</text>`;
    }).join('')}</svg>`;
  };
  window.scrAttendance = () => {
    const head = `<div class="ph"><div><h1>Attendance</h1><p>Today · Thursday 8 October 2026</p></div><div class="row-acts"><label class="ay">Period <select class="sel" aria-label="Period"><option selected>This week</option><option>This term</option><option>Last 8 weeks</option></select></label><a class="btn" href="#">${ic('download')}Export</a></div></div>`;
    if (ST.state === 'empty') return head + `<div class="empty"><b>No attendance taken yet this term</b><p>Class teachers take the register each morning. Patterns by class and by day appear after the first week.</p><a class="btn pri" href="#">${ic('bell')}Remind class teachers</a></div>`;
    const REG = STREAMS.map((r, i) => ({
      c: r[0] + ' · ' + r[1],
      t: tt(r[2]),
      n: r[3],
      ok: i !== 3 && i !== 5,
      at: ['07:52', '08:05', '08:20', '', '08:12', '', '08:12', '08:31', '07:58'][i]
    })).concat([{
      c: 'Nursery · Sunflower',
      t: tt('t7'),
      n: 22,
      ok: true,
      at: '08:02'
    }, {
      c: 'Reception · Sunflower',
      t: tt('t8'),
      n: 24,
      ok: true,
      at: '08:09'
    }]);
    REG.sort((a, b) => a.ok - b.ok);
    return head + `<div class="kpis k4">${kpi('user-round-check', 'Present today', '93.1%', '231 of 248 · 17 absent', '', 93.1)}<div class="kpi"><span class="l">${ic('clipboard-check', 'ic ic-sm')}Registers taken today</span><span class="v">9 of 11</span><span class="meter" role="img" aria-label="9 of 11"><i style="width:82%"></i></span><span class="s warn">2 not taken yet</span></div>${kpi('calendar-check', 'This week', '93.4%', '▲ 1.2 pts on last week', 'up')}${kpi('calendar-range', 'This term', '92.6%', 'Since 7 September')}</div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Who has taken today</h2><small>Not taken first</small></div><ul class="reg">${REG.map(r => `<li><span style="min-width:0"><b class="trunc">${r.c}</b><span class="tch">${av(r.t, 24)}<span class="trunc sub" style="display:inline">${r.t.fn} ${r.t.ln}</span></span></span>${r.ok ? `<span class="badge b-ok">Taken ${r.at}</span>` : `<span class="row-acts"><span class="badge b-warn">Not yet</span><a class="btn" href="#">${ic('bell')}Remind</a></span>`}</li>`).join('')}</ul></div>
  <div class="card"><div class="hd"><h2>By day of the week</h2><small>This term</small></div>${vbars([['Mon', 94], ['Tue', 95], ['Wed', 94], ['Thu', 93], ['Fri', 89, '#B45309']])}<p class="note">${ic('info', 'ic ic-sm')}Fridays average 5 points below the rest of the week.</p></div></div>
  <div class="grid2"><div class="card"><div class="hd"><h2>By class</h2><small>This week</small></div>${bars(STREAMS.map((r, i) => [r[0].replace('Primary ', 'P.') + ' ' + r[1], [96, 95, 93, 92, 92, 91, 95, 93, 90][i]]))}</div>
  <div class="card"><div class="hd"><h2>Trend</h2><small>Last 8 weeks · whole school</small></div>${line([['W1', 91], ['W2', 92], ['W3', 90], ['W4', 93], ['W5', 94], ['W6', 92], ['W7', 92.2], ['W8', 93.4]])}</div></div>`;
  };
  /* ---------- Exams, subject first ---------- */
  window.scrExams = () => {
    const ex = SUB.filter(s => !s.nur);
    const tot = ex.reduce((a, s) => a + rowsOf(s).length, 0),
      done = ex.reduce((a, s) => a + s.ent, 0);
    const open = ST.open || 'mth';
    const head = `<div class="ph"><div><h1>Exams and marks</h1><p>${SCHOOL.term}, ${SCHOOL.year} · by subject</p></div><div class="row-acts"><label class="ay">Term <select class="sel" aria-label="Term"><option selected>Term 3, 2026</option><option>Term 2, 2026</option></select></label><a class="btn pri" href="#">${ic('plus')}Add exam</a></div></div>
  <div class="chips" role="group" aria-label="Exam"><button class="chip" type="button" aria-pressed="false">Beginning of term <span class="n">· Complete</span></button><button class="chip" type="button" aria-pressed="true">Mid-term <span class="n">· In progress</span></button><button class="chip" type="button" aria-pressed="false">End of term <span class="n">· From 24 Nov</span></button></div>`;
    if (ST.state === 'empty') return head.replace(/<div class="chips"[\s\S]*$/, '') + `<div class="empty"><b>No exams this term</b><p>Add an exam, such as a mid-term or end-of-term exam, then choose its subjects and classes. Teachers enter marks per subject.</p><a class="btn pri" href="#">${ic('plus')}Add exam</a></div>`;
    return head + `<div class="card exsum"><div style="min-width:0;flex:1 1 280px"><h2>Mid-term exams · 5–9 October</h2><p style="margin:4px 0 8px">Marks entered for <b>${done} of ${tot}</b> subject classes</p><span class="prog" style="height:8px" role="progressbar" aria-valuemin="0" aria-valuemax="${tot}" aria-valuenow="${done}" aria-label="Marks entered"><i style="width:${done / tot * 100}%"></i></span></div>
   <div class="exrc"><span><b>Report cards</b><span class="sub">4 of 11 classes have every mark</span></span><span class="row-acts"><a class="btn" href="#">${ic('bell')}Remind teachers</a><a class="btn pri" href="#">${ic('file-text')}Generate for 4 classes</a></span></div></div>
  <div class="lt"><label class="search"><span class="sr">Search subjects</span>${ic('search')}<input type="search" placeholder="Search subjects"></label><div class="chips" role="group" aria-label="Status"><button class="chip" type="button" aria-pressed="true">All <span class="n">6</span></button><button class="chip" type="button" aria-pressed="false">Not started <span class="n">1</span></button><button class="chip" type="button" aria-pressed="false">In progress <span class="n">3</span></button><button class="chip" type="button" aria-pressed="false">Complete <span class="n">2</span></button></div></div>
  <ul class="xl">${ex.map(s => {
      const rows = rowsOf(s),
        n = rows.length,
        o = open === s.id;
      return `<li class="${o ? 'open' : ''}"><div class="xh"><button class="btn icon ghost" type="button" data-act="tog" data-id="${s.id}" aria-expanded="${o}" aria-controls="x-${s.id}" aria-label="${o ? 'Hide' : 'Show'} classes for ${s.n}">${ic(o ? 'chevron-down' : 'chevron-right')}</button>
   <span class="xn"><a href="#" data-go="subject"><b>${s.n}</b></a><span class="tch">${avs(s.tch)}<span class="sub" style="display:inline">${n} classes</span></span></span>${prog(s.ent, n)}<span class="hm">${stat(s.ent, n)}</span><span class="hm xa">${s.avg && s.ent ? `Avg <b>${s.avg}%</b>` : '<span class="sub">–</span>'}</span>${menu('x' + s.id, ['Download all marksheets', 'Import from spreadsheet', 'Remind teachers'])}</div>
   ${o ? `<ul class="xc" id="x-${s.id}">${rows.map((r, i) => {
        const a = i < s.ent ? r[3] : i === s.ent ? Math.round(r[3] / 2) : 0;
        const t = tt(s.tch[i % s.tch.length]);
        return `<li><span style="min-width:0"><b class="trunc">${r[0]} · ${r[1]}</b><span class="tch">${av(t, 24)}<span class="sub trunc" style="display:inline">${t.fn} ${t.ln}</span></span></span>${a === r[3] ? `<span class="badge b-ok">Entered ${a}/${r[3]}</span>` : a ? `<span class="badge b-warn">${a} of ${r[3]}</span>` : '<span class="badge b-off">Not started</span>'}<span class="row-acts"><a class="btn${a === r[3] ? '' : ' pri'}" href="#">${a === r[3] ? 'View' : 'Enter marks'}</a>${menu('c' + s.id + i, ['Import from spreadsheet', 'Download marksheet', 'Remind teacher'])}</span></li>`;
      }).join('')}</ul>` : ''}</li>`;
    }).join('')}</ul>
  <p class="sub" style="margin:0">Language, Numbers and Reading (Nursery and Reception) have no Mid-term exam.</p>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-08-admin-mvp/concept/screens-c.js", error: String((e && e.message) || e) }); }

// klassapp-handoff-2026-10-08-admin-mvp/concept/screens-d.js
try { (() => {
// Batch two: shared add / edit person form (student, teacher, parent, staff)
(() => {
  const {
    SCHOOL,
    T,
    S,
    P,
    av,
    ic
  } = KD;
  const ST = window.ST;
  const f = (id, label, o = {}) => {
    const err = ST.state === 'error' && o.err;
    const v = ST.mode === 'edit' && o.v != null ? o.v : '';
    const ctl = o.type === 'select' ? `<select class="inp sel" id="${id}" ${err ? `aria-invalid="true" aria-describedby="${id}-e"` : ''}>${(o.opts || []).map((x, i) => `<option ${ST.mode === 'edit' && x === o.v || !i && o.ph ? 'selected' : ''}>${x}</option>`).join('')}</select>` : o.type === 'textarea' ? `<textarea class="inp" id="${id}" rows="3" ${o.hint ? `aria-describedby="${id}-h"` : ''}>${v}</textarea>` : `<input class="inp" id="${id}" type="${o.type || 'text'}" value="${err ? '' : v}" ${o.ac ? `autocomplete="${o.ac}"` : ''} ${o.ph2 ? `placeholder="${o.ph2}"` : ''} ${err ? `aria-invalid="true" aria-describedby="${id}-e"` : o.hint ? `aria-describedby="${id}-h"` : ''}>`;
    return `<div class="f${o.full ? ' full' : ''}${err ? ' err' : ''}"><label for="${id}">${label}${o.opt ? '<span class="opt">Optional</span>' : ''}</label>${ctl}${err ? `<span class="emsg" id="${id}-e">${ic('circle-alert', 'ic ic-sm')}${o.err}</span>` : o.hint ? `<span class="hint" id="${id}-h">${o.hint}</span>` : ''}</div>`;
  };
  const radios = (name, label, opts, o = {}) => `<fieldset class="f${o.full ? ' full' : ''}"><legend>${label}${o.opt ? '<span class="opt">Optional</span>' : ''}</legend><div class="radios">${opts.map((x, i) => `<label class="rc"><input type="radio" name="${name}" ${o.sel != null && i === o.sel ? 'checked' : ''}>${x}</label>`).join('')}</div>${o.hint ? `<span class="hint">${o.hint}</span>` : ''}</fieldset>`;
  const check = (label, on, hint) => `<label class="rc ck full"><input type="checkbox" ${on ? 'checked' : ''}><span>${label}${hint ? `<span class="hint" style="display:block">${hint}</span>` : ''}</span></label>`;
  const sec = (t, p, body) => `<section class="fsec"><h2>${t}</h2>${p ? `<p class="hint" style="margin:-8px 0 0">${p}</p>` : ''}<div class="fg">${body}</div></section>`;
  const nameRow = (p, err) => f('fn', 'First name', {
    ac: 'given-name',
    v: p && p.fn
  }) + f('ln', 'Last name', {
    ac: 'family-name',
    v: p && p.ln,
    err: err ? 'Enter a last name' : null
  });
  const photo = p => `<div class="f full"><span class="lab">Photo<span class="opt">Optional</span></span><div class="phrow">${p ? av(p, 64) : '<span class="av" style="width:64px;height:64px;background:#F1F5F9;color:#475569;border:1px dashed #94A3B8" aria-hidden="true">' + ic('camera') + '</span>'}<span style="display:flex;flex-direction:column;gap:4px"><a class="btn" href="#">${ic('upload')}${p ? 'Change photo' : 'Upload photo'}</a><span class="hint">Until there's a photo, KlassApp shows initials.</span></span></div></div>`;
  function invite(kind, edit) {
    if (edit) {
      const st = kind === 'parent' ? 'Opted in to WhatsApp on 12 Sep 2026' : 'Joined on 3 Sep 2026';
      return sec(kind === 'parent' ? 'WhatsApp and login' : 'Login', '', `<div class="f full"><div class="reqrow"><span class="badge b-ok">${st}</span>${kind === 'parent' ? '<a class="btn" href="#">Send a login invite by email</a>' : '<a class="btn" href="#">Reset password</a>'}</div></div>`);
    }
    if (kind === 'parent') return sec('Invite', 'Parents get report cards, fee balances and attendance on WhatsApp once they reply to the opt-in message.', check('Send a WhatsApp opt-in message after saving', true, 'Sent to the phone number above. They reply YES to start receiving messages.') + check('Also send a login invite by email', false, 'For parents who also want to use the web app. Needs an email address.'));
    return sec('Invite', 'They choose their own password. The invite link works for 72 hours.', radios('inv', 'Send an invite', ['By email', 'By WhatsApp', 'Not yet'], {
      sel: 0,
      full: 1,
      hint: 'You can send or resend it later from their profile.'
    }));
  }
  const SPEC = {
    student: {
      t: 'student',
      p: () => S[0],
      body: (p, e) => [sec('Student', '', nameRow(p, e) + radios('gender', 'Gender', ['Female', 'Male', 'Not specified'], {
        sel: ST.mode === 'edit' ? 0 : 2
      }) + f('dob', 'Date of birth', {
        type: 'date',
        opt: 1,
        v: '2015-03-14'
      }) + photo(ST.mode === 'edit' ? p : null)), sec('Class', '', f('cls', 'Class', {
        type: 'select',
        opts: ['Choose a class', 'Nursery', 'Reception', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'],
        ph: 1,
        v: 'Primary 5'
      }) + f('str', 'Stream', {
        type: 'select',
        opts: ['Choose a stream', 'Blue', 'Red'],
        ph: 1,
        v: 'Blue',
        hint: 'Shown only for classes with streams.'
      }) + f('jd', 'Joining date', {
        type: 'date',
        v: '2024-01-12',
        hint: ST.mode === 'edit' ? '' : 'Defaults to today.'
      }) + `<div class="f"><span class="lab">KLS number</span><span class="ro">${ST.mode === 'edit' ? p.kls : 'Created when you save'}</span><span class="hint">KLS + your school's number (${SCHOOL.no}) + a 4-digit sequence. It never changes.</span></div>`), ST.mode === 'edit' ? sec('Parents and guardians', '', `<div class="f full"><div class="reqrow"><span class="tch" style="display:flex;gap:10px;align-items:center">${av(P[0], 40)}<span><b>${P[0].fn} ${P[0].ln}</b><span class="sub">Mother · ${P[0].ph}</span></span></span><a class="btn" href="#">${ic('link')}Link another</a></div></div>`) : sec('Parent or guardian', 'Link an existing parent, or add a new one. Parents receive updates on WhatsApp.', `<div class="f full"><label for="ps">Find a parent already in KlassApp<span class="opt">Optional</span></label><span class="search" style="flex:none">${ic('search')}<input class="inp" id="ps" type="search" placeholder="Search by name or phone" style="padding-left:40px"></span></div><p class="or full">Or add a new parent</p>` + f('pfn', 'First name') + f('pln', 'Last name') + f('rel', 'Relationship to the student', {
        type: 'select',
        opts: ['Choose', 'Mother', 'Father', 'Guardian', 'Other'],
        ph: 1
      }) + f('pph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        err: e ? 'Enter a phone number so the parent can get WhatsApp updates' : null,
        hint: 'Include the country code.'
      }) + check('This number is on WhatsApp', true) + check('Send a WhatsApp opt-in message after saving', true)), sec('Health and support', 'Only admins and the head teacher can see this. Each view is logged.', f('al', 'Allergies or medical conditions', {
        type: 'textarea',
        opt: 1,
        full: 1,
        v: 'Peanuts (severe). Mild asthma.'
      }) + f('sn', 'Support needs', {
        type: 'textarea',
        opt: 1,
        full: 1,
        v: 'Sits near the front for hearing.'
      }))]
    },
    teacher: {
      t: 'teacher',
      p: () => T[0],
      body: (p, e) => [sec('Teacher', '', nameRow(p, e) + f('role', 'Role', {
        type: 'select',
        opts: ['Teacher', 'Head teacher', 'Deputy head teacher'],
        v: 'Teacher'
      }) + f('sno', 'Staff number', {
        opt: 1,
        hint: 'If your school uses one.'
      }) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph,
        hint: 'Include the country code.'
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        v: p.email,
        err: e ? 'Enter an email or a phone number to send the invite' : null
      }) + photo(ST.mode === 'edit' ? p : null)), sec('Teaching', 'You can also do this later from the class or subject page.', `<div class="f full"><span class="lab">Classes and subjects<span class="opt">Optional</span></span><ul class="assign">${(ST.mode === 'edit' ? [['Primary 5 · Blue', 'Mathematics'], ['Primary 5 · Blue', 'Science'], ['Primary 6 · Blue', 'Mathematics']] : [['Primary 5 · Blue', 'Mathematics']]).map((r, i) => `<li><select class="inp sel" aria-label="Class ${i + 1}"><option>${r[0]}</option></select><select class="inp sel" aria-label="Subject ${i + 1}"><option>${r[1]}</option></select><button class="btn icon" type="button" aria-label="Remove ${r[1]}, ${r[0]}">${ic('x')}</button></li>`).join('')}</ul><a class="btn" href="#" style="align-self:flex-start">${ic('plus')}Add a class and subject</a></div>` + f('ct', 'Class teacher of', {
        type: 'select',
        opt: 1,
        opts: ['None', 'Primary 5 · Blue', 'Primary 5 · Red', 'Primary 6 · Blue'],
        v: 'Primary 5 · Blue'
      })), invite('teacher', ST.mode === 'edit')]
    },
    parent: {
      t: 'parent',
      p: () => P[0],
      body: (p, e) => [sec('Parent or guardian', '', nameRow(p, e) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph,
        hint: 'Include the country code.'
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        opt: 1,
        v: p.email
      }) + check('This number is on WhatsApp', true) + photo(ST.mode === 'edit' ? p : null)), sec('Children', '', `<div class="f full"><label for="ks">Link a child</label><span class="search" style="flex:none">${ic('search')}<input class="inp" id="ks" type="search" placeholder="Search by name or KLS number" style="padding-left:40px"></span>${ST.state === 'error' ? `<span class="emsg">${ic('circle-alert', 'ic ic-sm')}Link at least one child</span>` : ''}<ul class="kids">${(ST.mode === 'edit' || ST.state !== 'error' ? p.kids : []).map(k => `<li>${av(k, 32)}<span style="min-width:0"><b>${k.fn} ${k.ln}</b><span class="sub">${k.cls} · ${k.kls}</span></span><select class="inp sel" aria-label="Relationship to ${k.fn}"><option>Mother</option><option>Father</option><option>Guardian</option><option>Other</option></select><button class="btn icon" type="button" aria-label="Unlink ${k.fn}">${ic('x')}</button></li>`).join('')}</ul></div>`), invite('parent', ST.mode === 'edit')]
    },
    staff: {
      t: 'staff member',
      p: () => ({
        id: 3310,
        fn: 'Ruth',
        ln: 'Kim',
        ph: '+000 700 100 120',
        email: 'r.kim@demojunior.school'
      }),
      body: (p, e) => [sec('Staff member', 'For non-teaching staff, such as the bursar or librarian. Their role decides what they can see.', nameRow(p, e) + f('role', 'Role', {
        type: 'select',
        opts: ['Choose a role', 'Bursar', 'Librarian', 'School admin', 'Office staff'],
        ph: 1,
        v: 'Bursar'
      }) + f('sno', 'Staff number', {
        opt: 1
      }) + f('ph', 'Phone', {
        type: 'tel',
        ac: 'tel',
        v: p.ph
      }) + f('em', 'Email', {
        type: 'email',
        ac: 'email',
        v: p.email
      }) + photo(ST.mode === 'edit' ? p : null))]
    }
  };
  window.scrForm = kind => {
    const k = SPEC[kind],
      p = k.p(),
      edit = ST.mode === 'edit',
      e = ST.state === 'error';
    const back = {
      student: 'students',
      teacher: 'teachers',
      parent: 'parents',
      staff: 'teachers'
    }[kind];
    const title = edit ? `Edit ${p.fn} ${p.ln}` : `Add ${k.t}`;
    const errs = e ? {
      student: ['Last name', 'Parent phone'],
      teacher: ['Last name', 'Email'],
      parent: ['Last name', 'Children'],
      staff: ['Last name']
    }[kind] : null;
    return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="${back}">${back === 'teachers' ? 'Teachers and staff' : back[0].toUpperCase() + back.slice(1)}</a> <span aria-hidden="true">›</span> ${edit ? p.fn + ' ' + p.ln : 'Add'}</nav>
  <div class="ph"><div><h1>${title}</h1><p>${edit ? 'Changes save when you press Save.' : 'Fields are required unless marked Optional.'}</p></div></div>
  <form class="form" novalidate onsubmit="return false">${errs ? `<div class="errsum" role="alert" tabindex="-1"><b>${errs.length} things need fixing</b><ul>${errs.map(x => `<li><a href="#">${x}</a></li>`).join('')}</ul></div>` : ''}
  ${k.body(p, e).join('')}
  <div class="fbar"><a class="btn" href="#" data-go="${back}">Cancel</a>${edit ? '' : '<button class="btn" type="button">Save and add another</button>'}<button class="btn pri" type="submit">${edit ? 'Save changes' : kind === 'student' ? 'Add student' : `Save${kind === 'parent' ? ' and send opt-in' : kind === 'teacher' ? ' and send invite' : ''}`}</button></div></form>`;
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "klassapp-handoff-2026-10-08-admin-mvp/concept/screens-d.js", error: String((e && e.message) || e) }); }

// ui_kits/onboarding-wizard/WizardApp.jsx
try { (() => {
const {
  useState
} = React;
const {
  Card,
  Button,
  Icon
} = window.KlassAppDesignSystem_df5836;

/* Step keys, order, labels and the optional/checkpoint rules are taken from the
   real ManualOnboardingWizard Livewire component and its Blade partial.
   OPTIONAL_STEPS: teachers, students — Next with an empty draft list = skip. */
const STEPS = [{
  key: 'school_name',
  label: 'School name',
  icon: '🏫',
  title: 'School name'
}, {
  key: 'student_size',
  label: 'Student size',
  icon: '👥',
  title: 'Approximate number of students'
}, {
  key: 'country',
  label: 'Country',
  icon: '📍',
  title: 'Country'
}, {
  key: 'curriculum',
  label: 'Curriculum',
  icon: '📘',
  title: 'Board / Curriculum'
}, {
  key: 'school_category',
  label: 'Category',
  icon: '🏷️',
  title: 'School category'
}, {
  key: 'emis',
  label: 'EMIS code',
  icon: '🆔',
  title: 'EMIS / Ministry code'
}, {
  key: 'uneb_center',
  label: 'UNEB centre',
  icon: '📄',
  title: 'UNEB centre number'
}, {
  key: 'academic_year',
  label: 'Academic year',
  icon: '📅',
  title: 'Academic year'
}, {
  key: 'standards',
  label: 'Classes & streams',
  icon: '🚪',
  title: 'Structure & class teachers'
}, {
  key: 'subjects',
  label: 'Subjects',
  icon: '📚',
  title: 'Subjects'
}, {
  key: 'teachers',
  label: 'Teachers',
  icon: '👨‍🏫',
  title: 'Add your teachers',
  optional: true
}, {
  key: 'students',
  label: 'Students',
  icon: '🎒',
  title: 'Add your students',
  optional: true
}, {
  key: 'terms',
  label: 'Terms',
  icon: '🗓️',
  title: 'First term'
}, {
  key: 'fees',
  label: 'Fees',
  icon: '💰',
  title: 'First fee'
}, {
  key: 'whatsapp_verify',
  label: 'WhatsApp',
  icon: '💬',
  title: 'Verify WhatsApp'
}, {
  key: 'plan_selection',
  label: 'Plan',
  icon: '💳',
  title: 'Choose a plan'
}, {
  key: 'review',
  label: 'Review',
  icon: '✅',
  title: 'Review your setup'
}];
const PLANS = [{
  id: 1,
  name: 'Freemium',
  price: 'Free to start',
  hint: 'Default on signup'
}, {
  id: 2,
  name: 'School',
  price: 'UGX 420,000 / term',
  hint: 'Most schools pick this'
}, {
  id: 3,
  name: 'Group',
  price: 'Talk to us',
  hint: 'Multiple campuses'
}];
const SEEDED_CLASSES = [{
  section_id: 1,
  name: 'P1',
  streams: [],
  class_teacher_id: null,
  class_teacher_name: '',
  class_teacher_email: ''
}, {
  section_id: 2,
  name: 'P2',
  streams: [{
    label: 'A'
  }, {
    label: 'B'
  }],
  class_teacher_id: 7,
  class_teacher_name: 'Grace Nakamya',
  class_teacher_email: 'grace@school.ug'
}, {
  section_id: 3,
  name: 'P3',
  streams: [],
  class_teacher_id: null,
  class_teacher_name: '',
  class_teacher_email: ''
}];
function WizardApp() {
  const [i, setI] = useState(0);
  const [finished, setFinished] = useState(false);
  const [returnToReview, setReturnToReview] = useState(false);
  const [skipped, setSkipped] = useState([]);
  const [d, setD] = useState({
    schoolName: '',
    studentSize: '',
    countryName: 'Uganda',
    curriculum: 'uneb',
    schoolCategory: '',
    ministryCode: '',
    unebCenterNumber: '',
    academicYearDescription: '2026',
    academicYearStart: '2026-02-02',
    academicYearEnd: '2026-12-04',
    structureClasses: SEEDED_CLASSES,
    structureTeachers: [{
      id: 7,
      name: 'Grace Nakamya',
      email: 'grace@school.ug'
    }, {
      id: 9,
      name: 'John Ssali',
      email: 'john@school.ug'
    }],
    existingSubjectNames: ['Mathematics', 'English', 'Science', 'Social Studies'],
    subjectName: '',
    teacherDrafts: [],
    studentDrafts: [],
    termName: 'Term 1',
    termStart: '2026-02-02',
    termEnd: '2026-05-08',
    feeName: '',
    feeAmount: '',
    waNumber: '',
    plans: PLANS,
    selectedPlanId: 1,
    errorMessage: ''
  });
  const set = (k, v) => setD(p => ({
    ...p,
    [k]: v
  }));
  const reviewIndex = STEPS.length - 1;
  const step = STEPS[i];
  function go(n) {
    setI(n);
    set('errorMessage', '');
  }
  function next() {
    if (returnToReview) {
      setReturnToReview(false);
      go(reviewIndex);
      return;
    }
    // Real checkpoints: academic_year always lands on standards once; standards always lands on subjects once.
    go(Math.min(i + 1, reviewIndex));
  }
  function skipOptional() {
    if (!step.optional) return;
    setSkipped(s => s.includes(step.key) ? s : [...s, step.key]);
    next();
  }
  const reviewRows = [{
    key: 'school_name',
    icon: '🏫',
    label: 'School',
    value: d.schoolName || 'Not set'
  }, {
    key: 'country',
    icon: '📍',
    label: 'Country',
    value: d.countryName
  }, {
    key: 'school_category',
    icon: '🏷️',
    label: 'Category',
    value: d.schoolCategory || 'Not set'
  }, {
    key: 'academic_year',
    icon: '📅',
    label: 'Academic year',
    value: d.academicYearDescription
  }, {
    key: 'standards',
    icon: '🚪',
    label: 'Classes',
    value: d.structureClasses.map(c => c.name + (c.streams.length ? ' (' + c.streams.map(s => s.label).join(', ') + ')' : '')).join(', ')
  }, {
    key: 'subjects',
    icon: '📚',
    label: 'Subjects',
    value: d.existingSubjectNames.length + ' set up'
  }, {
    key: 'teachers',
    icon: '👨‍🏫',
    label: 'Teachers',
    value: skipped.includes('teachers') ? 'Skipped' : d.teacherDrafts.length ? d.teacherDrafts.length + ' added' : 'None yet'
  }, {
    key: 'students',
    icon: '🎒',
    label: 'Students',
    value: skipped.includes('students') ? 'Skipped' : d.studentDrafts.length ? d.studentDrafts.length + ' added' : 'None yet'
  }, {
    key: 'plan_selection',
    icon: '💳',
    label: 'Plan',
    value: (PLANS.find(p => p.id === d.selectedPlanId) || {}).name || 'Not chosen'
  }];
  const SUGGESTIONS = [{
    title: 'Manage classes & streams',
    body: 'Split a class into streams, or assign the class teachers you skipped.',
    href: '#'
  }, {
    title: 'Add more students',
    body: 'A spreadsheet with names and parent numbers is enough.',
    href: '#'
  }, {
    title: 'Review your plan',
    body: 'Capacity limits are based on the plan you picked.',
    href: '#'
  }];
  function editSection(key) {
    const idx = STEPS.findIndex(s => s.key === key);
    if (idx < 0) return;
    setReturnToReview(true);
    go(idx);
  }
  let body;
  if (finished) body = /*#__PURE__*/React.createElement(StepDone, {
    d: d,
    suggestions: SUGGESTIONS
  });else if (step.key === 'school_name') body = /*#__PURE__*/React.createElement(StepSchoolName, {
    d: d,
    set: set
  });else if (step.key === 'student_size') body = /*#__PURE__*/React.createElement(StepStudentSize, {
    d: d,
    set: set
  });else if (step.key === 'country') body = /*#__PURE__*/React.createElement(StepCountry, {
    d: d,
    set: set
  });else if (step.key === 'curriculum') body = /*#__PURE__*/React.createElement(StepCurriculum, {
    d: d,
    set: set
  });else if (step.key === 'school_category') body = /*#__PURE__*/React.createElement(StepCategory, {
    d: d,
    set: set
  });else if (step.key === 'emis') body = /*#__PURE__*/React.createElement(StepEmis, {
    d: d,
    set: set
  });else if (step.key === 'uneb_center') body = /*#__PURE__*/React.createElement(StepUneb, {
    d: d,
    set: set
  });else if (step.key === 'academic_year') body = /*#__PURE__*/React.createElement(StepAcademicYear, {
    d: d,
    set: set
  });else if (step.key === 'standards') body = /*#__PURE__*/React.createElement(StepStandards, {
    d: d,
    set: set
  });else if (step.key === 'subjects') body = /*#__PURE__*/React.createElement(StepSubjects, {
    d: d,
    set: set
  });else if (step.key === 'teachers') body = /*#__PURE__*/React.createElement(StepTeachers, {
    d: d,
    set: set,
    onSkip: skipOptional
  });else if (step.key === 'students') body = /*#__PURE__*/React.createElement(StepStudents, {
    d: d,
    set: set,
    onSkip: skipOptional
  });else if (step.key === 'terms') body = /*#__PURE__*/React.createElement(StepTerms, {
    d: d,
    set: set
  });else if (step.key === 'fees') body = /*#__PURE__*/React.createElement(StepFees, {
    d: d,
    set: set
  });else if (step.key === 'whatsapp_verify') body = /*#__PURE__*/React.createElement(StepWhatsApp, {
    d: d,
    set: set
  });else if (step.key === 'plan_selection') body = /*#__PURE__*/React.createElement(StepPlan, {
    d: d,
    set: set
  });else body = /*#__PURE__*/React.createElement(StepReview, {
    rows: reviewRows,
    onEdit: editSection
  });
  return /*#__PURE__*/React.createElement("div", {
    style: {
      minHeight: '100vh',
      background: 'var(--d-canvas)',
      padding: '32px 20px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 11,
      marginBottom: 22
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: "../../assets/brand/klassapp-icon.svg",
    height: "32",
    alt: ""
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--d-font-display)',
      fontWeight: 700,
      fontSize: 21,
      letterSpacing: '-0.02em',
      color: 'var(--d-dark)'
    }
  }, "KlassApp"), /*#__PURE__*/React.createElement("span", {
    style: {
      marginLeft: 'auto',
      fontSize: 13,
      color: 'var(--d-muted)'
    }
  }, "Setting up without Toshi")), finished ? null : /*#__PURE__*/React.createElement("div", {
    className: "setup-banner",
    style: {
      marginBottom: 4
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "setup-banner-icon"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "whatsapp",
    size: 20
  })), /*#__PURE__*/React.createElement("div", {
    className: "setup-banner-body"
  }, /*#__PURE__*/React.createElement("div", {
    className: "setup-banner-title"
  }, "Rather not fill forms?"), /*#__PURE__*/React.createElement("div", {
    className: "setup-banner-text"
  }, "Toshi can set the whole school up from a spreadsheet and a short chat."), /*#__PURE__*/React.createElement("div", {
    className: "setup-banner-actions"
  }, /*#__PURE__*/React.createElement("a", {
    className: "ds-btn ds-btn-outline ds-btn-sm",
    href: "../toshi-assistant/index.html"
  }, "Let Toshi do it")))), /*#__PURE__*/React.createElement(Card, {
    className: "manual-wizard-card",
    padding: "lg"
  }, finished ? null : /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 18
    }
  }, /*#__PURE__*/React.createElement("h1", {
    className: "ds-page-head-title",
    style: {
      marginBottom: 4
    }
  }, step.title), /*#__PURE__*/React.createElement("p", {
    className: "ds-page-head-sub"
  }, `Step ${i + 1} of ${STEPS.length}`, step.optional ? ' · optional' : '', returnToReview ? ' · Next returns you to review' : '')), d.errorMessage ? /*#__PURE__*/React.createElement("p", {
    className: "ds-form-error",
    style: {
      marginTop: 0
    }
  }, d.errorMessage) : null, body), finished ? /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      marginTop: 24
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    href: "../school-dashboard/index.html"
  }, "Go to the dashboard")) : /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-nav"
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    onClick: () => go(Math.max(0, i - 1)),
    disabled: i === 0
  }, "\u2190 Previous"), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-progress"
  }, STEPS.map((s, n) => /*#__PURE__*/React.createElement("button", {
    key: s.key,
    title: s.label,
    className: 'manual-wizard-dot' + (n < i ? ' is-complete' : '') + (n === i ? ' is-current' : '') + (s.key === 'review' ? ' is-review' : ''),
    onClick: () => go(n)
  }))), i === reviewIndex ? /*#__PURE__*/React.createElement(Button, {
    variant: "success",
    size: "sm",
    onClick: () => setFinished(true)
  }, "Confirm & finish") : /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "sm",
    onClick: next
  }, "Continue \u2192"))));
}
document.body.classList.add('toshi-manual-wizard');
ReactDOM.createRoot(document.getElementById('root')).render(/*#__PURE__*/React.createElement(WizardApp, null));
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/onboarding-wizard/WizardApp.jsx", error: String((e && e.message) || e) }); }

// ui_kits/onboarding-wizard/WizardSteps.jsx
try { (() => {
const {
  useState
} = React;
const {
  Button,
  FormGroup,
  Icon
} = window.KlassAppDesignSystem_df5836;

/* Step keys, order and copy are verbatim from manual-wizard-step-fields.blade.php.
   Values marked INFERRED come from PHP constants that were not supplied. */
const CURRICULA = {
  uneb: 'UNEB (Uganda National Examinations Board)',
  cambridge: 'Cambridge',
  montessori: 'Montessori',
  other: 'Other'
};
const COUNTRIES = {
  Uganda: 'Uganda',
  Kenya: 'Kenya',
  Tanzania: 'Tanzania'
}; // blade's own empty-DB fallback
const SIZES = {
  '1-100': '1-100',
  '101-300': '101-300',
  '301-600': '301-600',
  '601-1000': '601-1000',
  '1000+': '1000+'
}; // INFERRED: OnboardingStepsService::STUDENT_SIZE_OPTIONS not supplied
const CATEGORIES = {
  nursery: 'Nursery / Kindergarten',
  primary: 'Primary (P1-P7)',
  o_level: 'Secondary O-Level (S1-S4)',
  a_level: 'Secondary A-Level (S5-S6)',
  primary_secondary: 'Primary & Secondary'
}; // INFERRED: SchoolCategorySeeder::CATEGORIES not supplied

function Help({
  children
}) {
  return /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 12,
      color: '#64748B',
      margin: '4px 0 0'
    }
  }, children);
}
function StepSchoolName({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "School name",
    name: "schoolName",
    required: true,
    placeholder: "e.g. Sunrise Academy",
    value: d.schoolName,
    onChange: e => set('schoolName', e.target.value)
  });
}
function StepStudentSize({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "Approximate number of students",
    name: "studentSize",
    required: true,
    type: "select",
    placeholder: "Select range",
    options: SIZES,
    value: d.studentSize,
    onChange: e => set('studentSize', e.target.value),
    help: "Helps tailor setup defaults for your school. You can change this later."
  });
}
function StepCountry({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "Country",
    name: "countryName",
    required: true,
    type: "select",
    options: COUNTRIES,
    value: d.countryName,
    onChange: e => set('countryName', e.target.value),
    help: "Saves both country and Toshi registration country."
  });
}
function StepCurriculum({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "Board / Curriculum",
    name: "curriculum",
    required: true,
    type: "select",
    options: CURRICULA,
    value: d.curriculum,
    onChange: e => set('curriculum', e.target.value)
  });
}
function StepCategory({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: "ds-form-group"
  }, /*#__PURE__*/React.createElement("label", {
    className: "ds-form-label ds-form-label-required"
  }, "School category"), /*#__PURE__*/React.createElement(Help, null, "Sets the default classes, subjects, and grading system. Everything stays editable later."), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-plan-grid",
    role: "radiogroup",
    "aria-label": "School category",
    style: {
      marginTop: 10
    }
  }, Object.entries(CATEGORIES).map(([value, label]) => /*#__PURE__*/React.createElement("button", {
    type: "button",
    key: value,
    role: "radio",
    "aria-checked": d.schoolCategory === value,
    className: 'manual-wizard-plan-card' + (d.schoolCategory === value ? ' is-selected' : ''),
    onClick: () => set('schoolCategory', value)
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-plan-name"
  }, label)))));
}
function StepEmis({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "EMIS / Ministry code",
    name: "ministryCode",
    required: true,
    placeholder: "e.g. EMIS-1001",
    value: d.ministryCode,
    onChange: e => set('ministryCode', e.target.value)
  });
}
function StepUneb({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement(FormGroup, {
    label: "UNEB centre number",
    name: "unebCenterNumber",
    placeholder: "Optional \u2014 leave blank to skip",
    value: d.unebCenterNumber,
    onChange: e => set('unebCenterNumber', e.target.value),
    help: "Optional for UNEB schools. Leave blank if you do not have one yet."
  });
}
function StepAcademicYear({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Description",
    name: "academicYearDescription",
    value: d.academicYearDescription,
    onChange: e => set('academicYearDescription', e.target.value)
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
      gap: '0 16px'
    }
  }, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Starts on",
    name: "academicYearStart",
    required: true,
    type: "date",
    value: d.academicYearStart,
    onChange: e => set('academicYearStart', e.target.value)
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Ends on",
    name: "academicYearEnd",
    required: true,
    type: "date",
    value: d.academicYearEnd,
    onChange: e => set('academicYearEnd', e.target.value)
  })));
}

/* The Structure & Class Teacher checkpoint. Classes arrive pre-seeded from the
   school category; streams and CT invites are both optional and persist
   immediately (addStructureStream / inviteStructureClassTeacher). */
function StepStandards({
  d,
  set
}) {
  const [flash, setFlash] = useState('');
  const [streamDrafts, setStreamDrafts] = useState({});
  const [ctDrafts, setCtDrafts] = useState({});
  function addStream(sid, name) {
    const label = (streamDrafts[sid] || '').trim();
    if (!label) {
      setFlash('');
      set('errorMessage', 'Enter a stream name (e.g. A, East, Science).');
      return;
    }
    set('errorMessage', '');
    set('structureClasses', d.structureClasses.map(c => c.section_id === sid ? {
      ...c,
      streams: [...c.streams, {
        label
      }]
    } : c));
    setStreamDrafts({
      ...streamDrafts,
      [sid]: ''
    });
    setFlash('Added stream \u201C' + label + '\u201D to ' + name + '.');
  }
  function invite(sid, name) {
    const draft = ctDrafts[sid] || {};
    const email = (draft.email || '').trim();
    if (!email && !draft.existing_teacher_id) {
      set('errorMessage', 'Enter an email address for the class teacher.');
      return;
    }
    set('errorMessage', '');
    const label = draft.existing_teacher_id ? d.structureTeachers.find(t => String(t.id) === String(draft.existing_teacher_id)).name : (draft.name || '').trim() || email;
    set('structureClasses', d.structureClasses.map(c => c.section_id === sid ? {
      ...c,
      class_teacher_id: 1,
      class_teacher_name: label,
      class_teacher_email: email
    } : c));
    setFlash('Invited ' + label + ' as class teacher for ' + name + '.');
  }
  const setCt = (sid, k, v) => setCtDrafts({
    ...ctDrafts,
    [sid]: {
      ...(ctDrafts[sid] || {}),
      [k]: v
    }
  });
  return /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-structure"
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 14,
      color: '#64748B',
      margin: '0 0 16px',
      lineHeight: 1.5
    }
  }, "Your classes are ready from school category. Optionally add streams or invite a class teacher \u2014 both are optional. Click Continue anytime to skip."), flash ? /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 14,
      fontWeight: 500,
      color: '#15803D',
      marginBottom: 16
    }
  }, flash) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16
    }
  }, d.structureClasses.map(c => /*#__PURE__*/React.createElement("div", {
    key: c.section_id,
    style: {
      border: '1px solid #E2E8F0',
      borderRadius: 10,
      padding: 16
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 8,
      marginBottom: 12
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h3", {
    style: {
      fontFamily: 'var(--d-font-display)',
      fontWeight: 600,
      fontSize: 15,
      color: '#0F172A',
      margin: 0
    }
  }, c.name), c.streams.length ? /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 12,
      color: '#64748B',
      margin: '4px 0 0'
    }
  }, "Streams:", ' ', c.streams.map(s => /*#__PURE__*/React.createElement("span", {
    key: s.label,
    style: {
      display: 'inline-block',
      background: '#F1F5F9',
      padding: '1px 8px',
      borderRadius: 4,
      marginRight: 4
    }
  }, s.label))) : /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 12,
      color: '#64748B',
      margin: '4px 0 0'
    }
  }, "No streams yet \u2014 undivided base class.")), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: '#64748B'
    }
  }, c.class_teacher_name ? /*#__PURE__*/React.createElement("span", null, "CT: ", /*#__PURE__*/React.createElement("strong", null, c.class_teacher_name), " ", c.class_teacher_email ? /*#__PURE__*/React.createElement("span", {
    style: {
      color: '#94A3B8'
    }
  }, "(", c.class_teacher_email, ")") : null) : /*#__PURE__*/React.createElement("span", {
    style: {
      color: '#94A3B8'
    }
  }, "No class teacher yet"))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "ds-form-group",
    style: {
      marginBottom: 0
    }
  }, /*#__PURE__*/React.createElement("label", {
    className: "ds-form-label"
  }, "Add stream"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    placeholder: "e.g. A, East, Science",
    value: streamDrafts[c.section_id] || '',
    onChange: e => setStreamDrafts({
      ...streamDrafts,
      [c.section_id]: e.target.value
    })
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm",
    onClick: () => addStream(c.section_id, c.name)
  }, "Add"))), !c.class_teacher_id ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("label", {
    className: "ds-form-label"
  }, "Invite Class Teacher"), /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    inputMode: "email",
    placeholder: "teacher@school.ug",
    value: (ctDrafts[c.section_id] || {}).email || '',
    onChange: e => setCt(c.section_id, 'email', e.target.value)
  }), /*#__PURE__*/React.createElement("select", {
    className: "ds-form-input ds-form-select",
    value: (ctDrafts[c.section_id] || {}).existing_teacher_id || '',
    onChange: e => setCt(c.section_id, 'existing_teacher_id', e.target.value)
  }, /*#__PURE__*/React.createElement("option", {
    value: ""
  }, "\u2014 Create a new teacher \u2014"), d.structureTeachers.map(t => /*#__PURE__*/React.createElement("option", {
    key: t.id,
    value: t.id
  }, t.name, " (", t.email, ")"))), !(ctDrafts[c.section_id] || {}).existing_teacher_id ? /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    placeholder: "Teacher name",
    value: (ctDrafts[c.section_id] || {}).name || '',
    onChange: e => setCt(c.section_id, 'name', e.target.value)
  }), /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    placeholder: "Phone (optional)",
    value: (ctDrafts[c.section_id] || {}).phone || '',
    onChange: e => setCt(c.section_id, 'phone', e.target.value)
  })) : null, /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm",
    onClick: () => invite(c.section_id, c.name)
  }, "Send invite")) : null)))));
}
function StepSubjects({
  d,
  set
}) {
  const seeded = d.existingSubjectNames;
  return /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-subjects"
  }, seeded.length ? /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 16,
      borderRadius: 10,
      border: '1px solid #BBF7D0',
      background: '#F0FDF4',
      padding: '12px 16px'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 14,
      fontWeight: 500,
      color: '#14532D',
      margin: 0
    }
  }, "Subjects already set up"), /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 12,
      color: '#166534',
      margin: '4px 0 0',
      lineHeight: 1.5
    }
  }, "These came from your school category (or a previous save). Review them below \u2014 click Next to continue, or add another subject."), /*#__PURE__*/React.createElement("ul", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      gap: 8,
      listStyle: 'none',
      margin: '8px 0 0',
      padding: 0
    }
  }, seeded.map(s => /*#__PURE__*/React.createElement("li", {
    key: s,
    style: {
      fontSize: 12,
      fontWeight: 500,
      padding: '4px 8px',
      borderRadius: 4,
      background: '#FFFFFF',
      border: '1px solid #BBF7D0',
      color: '#14532D'
    }
  }, s)))) : null, /*#__PURE__*/React.createElement(FormGroup, {
    label: seeded.length ? 'Add another subject (optional)' : 'First subject',
    name: "subjectName",
    required: !seeded.length,
    placeholder: seeded.length ? 'e.g. Music' : 'e.g. Mathematics',
    value: d.subjectName,
    onChange: e => set('subjectName', e.target.value)
  }));
}

/* Optional bulk step. Three real input modes: file upload (with template
   download), a paste block, and single-add with name/email/phone. */
function BulkStep({
  noun,
  drafts,
  onAdd,
  onRemove,
  onPaste,
  nameHelp,
  namePlaceholder,
  onSkip
}) {
  const [paste, setPaste] = useState('');
  const [row, setRow] = useState({
    name: '',
    email: '',
    phone: ''
  });
  return /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-bulk"
  }, /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-bulk-toolbar"
  }, /*#__PURE__*/React.createElement("a", {
    className: "manual-wizard-bulk-link",
    href: "#",
    download: true
  }, "Download template"), /*#__PURE__*/React.createElement("label", {
    className: "manual-wizard-bulk-upload"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "upload",
    size: 15
  }), /*#__PURE__*/React.createElement("span", null, "Upload file"))), drafts.length ? /*#__PURE__*/React.createElement("ul", {
    className: "manual-wizard-bulk-list"
  }, drafts.map((r, i) => /*#__PURE__*/React.createElement("li", {
    className: "manual-wizard-bulk-item",
    key: r.name + i
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-bulk-item-main"
  }, /*#__PURE__*/React.createElement("strong", null, r.name), /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-bulk-meta"
  }, r.email, r.phone ? ' \u00B7 ' + r.phone : '')), /*#__PURE__*/React.createElement("button", {
    className: "manual-wizard-bulk-remove",
    "aria-label": "Remove",
    onClick: () => onRemove(i)
  }, "\u2715")))) : null, /*#__PURE__*/React.createElement("div", {
    className: "ds-form-group"
  }, /*#__PURE__*/React.createElement("label", {
    className: "ds-form-label"
  }, "Paste names (one per line)"), /*#__PURE__*/React.createElement("textarea", {
    className: "ds-form-input ds-form-textarea",
    rows: 3,
    value: paste,
    placeholder: 'John Ssali\nGrace Nakamya',
    onChange: e => setPaste(e.target.value)
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm",
    className: "mt-2",
    onClick: () => {
      onPaste(paste);
      setPaste('');
    }
  }, "Add from paste")), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-bulk-divider"
  }, /*#__PURE__*/React.createElement("span", null, "or add one at a time")), /*#__PURE__*/React.createElement(FormGroup, {
    label: noun === 'teacher' ? 'Teacher name' : 'Student name',
    value: row.name,
    placeholder: namePlaceholder,
    help: nameHelp,
    onChange: e => setRow({
      ...row,
      name: e.target.value
    })
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Email",
    value: row.email,
    placeholder: noun + '@school.ug',
    onChange: e => setRow({
      ...row,
      email: e.target.value
    })
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Phone (optional)",
    type: "text",
    value: row.phone,
    placeholder: "+2567\u2026",
    onChange: e => setRow({
      ...row,
      phone: e.target.value
    })
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm",
    onClick: () => {
      if (row.name.trim()) {
        onAdd(row);
        setRow({
          name: '',
          email: '',
          phone: ''
        });
      }
    }
  }, `+ Add ${noun}`), /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 12,
      color: '#64748B',
      margin: '12px 0 0'
    }
  }, `Optional — skip if you’ll add ${noun}s later. Continue saves everyone in the list.`), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 8
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    onClick: () => {
      if (drafts.length && !window.confirm(`You have ${noun}s in the list that will not be saved. Skip anyway?`)) return;
      onSkip();
    }
  }, "Skip for now")));
}
function StepTeachers({
  d,
  set,
  onSkip
}) {
  return /*#__PURE__*/React.createElement(BulkStep, {
    noun: "teacher",
    drafts: d.teacherDrafts,
    onSkip: onSkip,
    namePlaceholder: "e.g. Jane Nabirye",
    nameHelp: "Full name only \u2014 put the phone number in the Phone field below.",
    onAdd: r => set('teacherDrafts', [...d.teacherDrafts, r]),
    onRemove: i => set('teacherDrafts', d.teacherDrafts.filter((_, j) => j !== i)),
    onPaste: t => set('teacherDrafts', [...d.teacherDrafts, ...t.split('\n').map(n => n.trim()).filter(Boolean).map(n => ({
      name: n,
      email: '',
      phone: ''
    }))])
  });
}
function StepStudents({
  d,
  set,
  onSkip
}) {
  return /*#__PURE__*/React.createElement(BulkStep, {
    noun: "student",
    drafts: d.studentDrafts,
    onSkip: onSkip,
    namePlaceholder: "e.g. Nakato Sarah",
    nameHelp: "Full name only \u2014 class and stream are set after setup.",
    onAdd: r => set('studentDrafts', [...d.studentDrafts, r]),
    onRemove: i => set('studentDrafts', d.studentDrafts.filter((_, j) => j !== i)),
    onPaste: t => set('studentDrafts', [...d.studentDrafts, ...t.split('\n').map(n => n.trim()).filter(Boolean).map(n => ({
      name: n,
      email: '',
      phone: ''
    }))])
  });
}
function StepTerms({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Term name",
    name: "termName",
    required: true,
    value: d.termName,
    onChange: e => set('termName', e.target.value)
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
      gap: '0 16px'
    }
  }, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Starts on",
    name: "termStart",
    required: true,
    type: "date",
    value: d.termStart,
    onChange: e => set('termStart', e.target.value)
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Ends on",
    name: "termEnd",
    required: true,
    type: "date",
    value: d.termEnd,
    onChange: e => set('termEnd', e.target.value)
  })));
}
function StepFees({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Fee name",
    name: "feeName",
    required: true,
    placeholder: "e.g. Tuition",
    value: d.feeName,
    onChange: e => set('feeName', e.target.value)
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Amount",
    name: "feeAmount",
    required: true,
    type: "number",
    placeholder: "450000",
    value: d.feeAmount,
    onChange: e => set('feeAmount', e.target.value)
  }));
}
function StepWhatsApp({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Your WhatsApp number",
    name: "waNumber",
    required: true,
    placeholder: "+2567\u2026",
    value: d.waNumber,
    onChange: e => set('waNumber', e.target.value),
    help: "We send a one-time code to confirm the number parents will see."
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm"
  }, "Send code"));
}
function StepPlan({
  d,
  set
}) {
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 14,
      color: '#64748B',
      margin: '0 0 12px',
      lineHeight: 1.5
    }
  }, "Schools are free to start \u2014 pick a plan so capacity limits are clear. No payment is required now."), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-plan-grid"
  }, d.plans.map(p => /*#__PURE__*/React.createElement("div", {
    key: p.id,
    className: 'manual-wizard-plan-card' + (d.selectedPlanId === p.id ? ' is-selected' : ''),
    onClick: () => set('selectedPlanId', p.id)
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-plan-name"
  }, p.name), /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-plan-price"
  }, p.price), /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-plan-hint"
  }, p.hint)))));
}
function StepReview({
  rows,
  onEdit
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-review"
  }, /*#__PURE__*/React.createElement("p", {
    className: "manual-wizard-review-intro"
  }, "Check this over. Nothing is sent to parents until you publish something yourself."), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-review-card"
  }, /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-review-header"
  }, "Your school on KlassApp"), /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-review-body"
  }, rows.map(r => /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-review-row",
    key: r.key
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-review-row-main"
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-review-icon"
  }, r.icon), /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 2,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-review-label"
  }, r.label), /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-review-value"
  }, r.value))), /*#__PURE__*/React.createElement("a", {
    className: "manual-wizard-review-edit",
    onClick: () => onEdit(r.key)
  }, "Edit"))))));
}
function StepDone({
  d,
  suggestions
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      padding: '12px 0 4px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "manual-wizard-done-icon"
  }, "\u2713"), /*#__PURE__*/React.createElement("h2", {
    className: "ds-section-title",
    style: {
      marginBottom: 4
    }
  }, d.schoolName || 'Your school', " is set up"), /*#__PURE__*/React.createElement("p", {
    className: "ds-section-subtitle"
  }, "Here is what most schools do next."), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 10,
      textAlign: 'left',
      marginTop: 16
    }
  }, suggestions.map(s => /*#__PURE__*/React.createElement("a", {
    className: "manual-wizard-suggestion",
    href: s.href,
    key: s.title
  }, /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-suggestion-title"
  }, s.title), /*#__PURE__*/React.createElement("span", {
    className: "manual-wizard-suggestion-body"
  }, s.body)))));
}
Object.assign(window, {
  StepSchoolName,
  StepStudentSize,
  StepCountry,
  StepCurriculum,
  StepCategory,
  StepEmis,
  StepUneb,
  StepAcademicYear,
  StepStandards,
  StepSubjects,
  StepTeachers,
  StepStudents,
  StepTerms,
  StepFees,
  StepWhatsApp,
  StepPlan,
  StepReview,
  StepDone
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/onboarding-wizard/WizardSteps.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/App.jsx
try { (() => {
const {
  useState
} = React;
const {
  Icon
} = window.KlassAppDesignSystem_df5836;
function App() {
  const [screen, setScreen] = useState('home');
  let view;
  if (screen === 'home') view = /*#__PURE__*/React.createElement(DashboardHome, {
    onNav: setScreen
  });else if (screen === 'students') view = /*#__PURE__*/React.createElement(StudentsScreen, null);else if (screen === 'fees') view = /*#__PURE__*/React.createElement(FeesScreen, null);else if (screen === 'exams') view = /*#__PURE__*/React.createElement(ExamsScreen, null);else view = /*#__PURE__*/React.createElement(PlaceholderScreen, {
    title: screen[0].toUpperCase() + screen.slice(1)
  });
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      height: '100vh',
      overflow: 'hidden',
      background: 'var(--d-canvas)'
    }
  }, /*#__PURE__*/React.createElement(Sidebar, {
    active: screen,
    onSelect: setScreen
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      display: 'flex',
      flexDirection: 'column',
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("header", {
    style: {
      height: 58,
      flexShrink: 0,
      background: 'var(--d-white)',
      borderBottom: '1px solid var(--d-border)',
      display: 'flex',
      alignItems: 'center',
      gap: 14,
      padding: '0 20px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      width: 320,
      maxWidth: '40%'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      left: 12,
      top: 10,
      color: 'var(--d-muted)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "search",
    size: 17
  })), /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    style: {
      paddingLeft: 36,
      height: 38,
      minHeight: 38
    },
    placeholder: "Search students, classes, payments"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      marginLeft: 'auto',
      display: 'flex',
      alignItems: 'center',
      gap: 14,
      color: 'var(--d-text-secondary)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 13
    }
  }, "Term 2 2026"), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "bell",
    size: 20
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: -2,
      right: -2,
      width: 8,
      height: 8,
      borderRadius: 999,
      background: 'var(--d-red)'
    }
  })))), /*#__PURE__*/React.createElement("main", {
    className: "dashboard-content-area",
    style: {
      flex: 1,
      overflowY: 'auto',
      padding: 20
    }
  }, view)));
}
ReactDOM.createRoot(document.getElementById('root')).render(/*#__PURE__*/React.createElement(App, null));
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/App.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/DashboardHome.jsx
try { (() => {
const {
  KpiCard,
  Card,
  Table,
  Badge,
  Button,
  WhatsAppMark,
  GoogleDriveMark,
  Icon
} = window.KlassAppDesignSystem_df5836;
function PageHead({
  title,
  sub,
  action
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: "ds-page-head"
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    className: "ds-page-head-title"
  }, title), /*#__PURE__*/React.createElement("p", {
    className: "ds-page-head-sub"
  }, sub)), action);
}
function DashboardHome({
  onNav
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell dashboard-shell--admin"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 16,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    className: "dashboard-title"
  }, "Good morning, Josephine"), /*#__PURE__*/React.createElement("p", {
    className: "dashboard-subtitle"
  }, "Term 2 2026 \xB7 Week 6 \xB7 1,284 students enrolled")), /*#__PURE__*/React.createElement("span", {
    className: "dashboard-live-badge"
  }, /*#__PURE__*/React.createElement("span", {
    className: "dashboard-live-dot"
  }), "Live")), /*#__PURE__*/React.createElement("div", {
    className: "dashboard-kpi-grid"
  }, /*#__PURE__*/React.createElement(KpiCard, {
    icon: "users",
    value: "1,284",
    label: "Total Students",
    color: "blue",
    link: "#students"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "dollar",
    value: "UGX 18.4M",
    label: "Fees Collected",
    color: "amber",
    link: "#fees"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "whatsapp",
    value: "1,102",
    label: "WhatsApp Linked",
    color: "green"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "exam",
    value: "12",
    label: "Upcoming Exams",
    color: "purple"
  })), /*#__PURE__*/React.createElement("div", {
    className: "dashboard-topfold",
    style: {
      display: 'grid',
      gridTemplateColumns: '1.6fr 1fr',
      gap: 20
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h2", {
    className: "ds-section-title"
  }, "Fees collected this week"), /*#__PURE__*/React.createElement("p", {
    className: "ds-section-subtitle"
  }, "UGX 4.2M of a UGX 6.0M target"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      gap: 10,
      height: 128
    }
  }, [['Mon', 52], ['Tue', 68], ['Wed', 41], ['Thu', 84], ['Fri', 96], ['Sat', 30]].map(([d, v]) => /*#__PURE__*/React.createElement("div", {
    key: d,
    style: {
      flex: 1,
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: v + '%',
      background: 'rgba(34,197,94,0.18)',
      borderTop: '3px solid var(--d-green)',
      borderRadius: '6px 6px 0 0'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: 'var(--d-muted)',
      marginTop: 6
    }
  }, d))))), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h2", {
    className: "ds-section-title"
  }, "Connected tools"), /*#__PURE__*/React.createElement("p", {
    className: "ds-section-subtitle"
  }, "KlassApp runs inside what the school already uses"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 10
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      fontSize: 13,
      color: 'var(--d-text)'
    }
  }, /*#__PURE__*/React.createElement(WhatsAppMark, {
    style: {
      width: 22,
      height: 22
    }
  }), " 1,102 parents reachable", /*#__PURE__*/React.createElement("span", {
    style: {
      marginLeft: 'auto'
    }
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "active"
  }, "Live"))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      fontSize: 13,
      color: 'var(--d-text)'
    }
  }, /*#__PURE__*/React.createElement(GoogleDriveMark, {
    style: {
      width: 22
    }
  }), " Reports filed to Drive", /*#__PURE__*/React.createElement("span", {
    style: {
      marginLeft: 'auto'
    }
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "active"
  }, "Live"))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      fontSize: 13,
      color: 'var(--d-text)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "bell",
    size: 20,
    style: {
      color: 'var(--d-muted)'
    }
  }), " Notice board", /*#__PURE__*/React.createElement("span", {
    style: {
      marginLeft: 'auto'
    }
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "pending"
  }, "3 drafts")))))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 16,
      marginTop: 20
    }
  }, /*#__PURE__*/React.createElement(Card, {
    title: "Recent payments",
    padding: "none"
  }, /*#__PURE__*/React.createElement(Table, {
    headers: ['Student', 'Amount', 'Method', 'Status'],
    density: "compact"
  }, /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    "data-label": "Student"
  }, "Nakato Sarah"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-num",
    "data-label": "Amount"
  }, "UGX 450,000"), /*#__PURE__*/React.createElement("td", {
    "data-label": "Method"
  }, "SchoolPay"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-badge",
    "data-label": "Status"
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "paid"
  }, "Paid"))), /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    "data-label": "Student"
  }, "Mukasa David"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-num",
    "data-label": "Amount"
  }, "UGX 300,000"), /*#__PURE__*/React.createElement("td", {
    "data-label": "Method"
  }, "Mobile Money"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-badge",
    "data-label": "Status"
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "warning"
  }, "Part paid"))), /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    "data-label": "Student"
  }, "Namuli Grace"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-num",
    "data-label": "Amount"
  }, "UGX 120,000"), /*#__PURE__*/React.createElement("td", {
    "data-label": "Method"
  }, "Cash"), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-badge",
    "data-label": "Status"
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "paid"
  }, "Paid")))), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '12px 16px'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    onClick: () => onNav('fees')
  }, "View all payments \u2192"))), /*#__PURE__*/React.createElement(Card, {
    title: "Needs your attention"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, [['Senior 4 marks not submitted', 'Mr. Opio · due Friday', 'amber'], ['38 parents without WhatsApp', 'Reception can collect numbers', 'blue'], ['Term 2 report cards ready', 'Publish to send 1,102 messages', 'green']].map(([t, s, c]) => /*#__PURE__*/React.createElement("div", {
    key: t,
    style: {
      display: 'flex',
      gap: 10,
      alignItems: 'flex-start'
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: 'ds-dot ds-dot-' + c,
    style: {
      marginTop: 6
    }
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      fontWeight: 600,
      color: 'var(--d-text)'
    }
  }, t), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: 'var(--d-muted)'
    }
  }, s))))), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 16,
      display: 'flex',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "sm"
  }, "Publish reports"), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm"
  }, "Remind teachers")))));
}
Object.assign(window, {
  DashboardHome,
  PageHead
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/DashboardHome.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/ExamsScreen.jsx
try { (() => {
const {
  Card,
  Button,
  Badge
} = window.KlassAppDesignSystem_df5836;
const SUBJECTS = ['MTC', 'ENG', 'PHY', 'CHE', 'BIO', 'HIS'];
const PUPILS = [['01', 'Nakato Sarah', [82, 74, 68, 71, 79, 65], 439, 12, 3], ['02', 'Mukasa David', [64, 70, 59, 62, 66, 58], 379, 18, 11], ['03', 'Namuli Grace', [91, 85, 80, 77, 88, 74], 495, 8, 1], ['04', 'Ssempala Isaac', [55, 61, 49, 53, 60, 52], 330, 24, 19]];
function ExamsScreen() {
  return /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell dashboard-shell--teacher"
  }, /*#__PURE__*/React.createElement(PageHead, {
    title: "End of term marks",
    sub: "Senior 2 \xB7 Term 2 2026 \xB7 46 students",
    action: /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: 8,
        alignItems: 'center'
      }
    }, /*#__PURE__*/React.createElement("span", {
      className: "ds-save-indicator ds-save-indicator--saved"
    }, /*#__PURE__*/React.createElement("span", {
      className: "ds-save-indicator__dot"
    }), "All marks saved"), /*#__PURE__*/React.createElement(Button, {
      variant: "outline",
      size: "sm"
    }, "Download sheet"), /*#__PURE__*/React.createElement(Button, {
      variant: "primary",
      size: "sm"
    }, "Publish to parents"))
  }), /*#__PURE__*/React.createElement(Card, {
    padding: "none"
  }, /*#__PURE__*/React.createElement("div", {
    className: "ds-table-wrap"
  }, /*#__PURE__*/React.createElement("table", {
    className: "ds-grid-marks"
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("th", null, "#"), /*#__PURE__*/React.createElement("th", null, "Student"), SUBJECTS.map(s => /*#__PURE__*/React.createElement("th", {
    key: s
  }, s, /*#__PURE__*/React.createElement("span", {
    className: "gm-subject-code"
  }, "/100"))), /*#__PURE__*/React.createElement("th", null, "Total"), /*#__PURE__*/React.createElement("th", null, "Agg"), /*#__PURE__*/React.createElement("th", null, "Pos"))), /*#__PURE__*/React.createElement("tbody", null, PUPILS.map(p => /*#__PURE__*/React.createElement("tr", {
    key: p[0]
  }, /*#__PURE__*/React.createElement("td", null, p[0]), /*#__PURE__*/React.createElement("td", null, p[1]), p[2].map((m, i) => /*#__PURE__*/React.createElement("td", {
    key: i
  }, m)), /*#__PURE__*/React.createElement("td", {
    className: "gm-total"
  }, p[3]), /*#__PURE__*/React.createElement("td", {
    className: "gm-agg"
  }, p[4]), /*#__PURE__*/React.createElement("td", {
    className: "gm-pos"
  }, p[5]))))))), /*#__PURE__*/React.createElement("div", {
    className: "ds-reminder-banner",
    style: {
      marginTop: 16
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--d-font-display)',
      fontWeight: 600,
      fontSize: 14,
      color: 'var(--d-dark)'
    }
  }, "Two subjects still missing marks"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      color: 'var(--d-text-secondary)',
      marginTop: 4
    }
  }, "Geography and Agriculture have no entries for Senior 2. Publishing now sends incomplete report cards to 46 parents."), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 10,
      display: 'flex',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "warning",
    size: "sm"
  }, "Remind subject teachers"), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm"
  }, "Publish anyway"))));
}
function PlaceholderScreen({
  title
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell"
  }, /*#__PURE__*/React.createElement(PageHead, {
    title: title,
    sub: "Not recreated in this kit"
  }), /*#__PURE__*/React.createElement(Card, null, /*#__PURE__*/React.createElement("div", {
    className: "ds-empty-state"
  }, /*#__PURE__*/React.createElement("p", {
    className: "ds-empty-state-title"
  }, "No source for this screen"), /*#__PURE__*/React.createElement("p", {
    className: "ds-empty-state-desc"
  }, "The supplied KlassApp bundle contains styling for this area but no screen markup, so it is left blank on purpose rather than invented. Dashboard, Students, Fee payments and Exams are the recreated views."))));
}
Object.assign(window, {
  ExamsScreen,
  PlaceholderScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/ExamsScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/FeesScreen.jsx
try { (() => {
const {
  useState
} = React;
const {
  Card,
  Table,
  Badge,
  Button,
  FormGroup,
  KpiCard
} = window.KlassAppDesignSystem_df5836;
const INITIAL = [['12 Sep', 'Nakato Sarah', 'Senior 2', 'UGX 450,000', 'SchoolPay', 'paid'], ['11 Sep', 'Mukasa David', 'Senior 2', 'UGX 300,000', 'Mobile Money', 'warning'], ['09 Sep', 'Namuli Grace', 'Senior 4', 'UGX 120,000', 'Cash', 'paid'], ['08 Sep', 'Ssempala Isaac', 'Senior 4', 'UGX 0', '—', 'unpaid']];
function FeesScreen() {
  const [rows, setRows] = useState(INITIAL);
  const [open, setOpen] = useState(false);
  const [saved, setSaved] = useState(false);
  const [form, setForm] = useState({
    student: '',
    amount: '',
    method: 'schoolpay'
  });
  const [error, setError] = useState('');
  function record() {
    if (!form.student.trim() || !form.amount.trim()) {
      setError('Enter both a student and an amount.');
      return;
    }
    setError('');
    const method = {
      schoolpay: 'SchoolPay',
      momo: 'Mobile Money',
      cash: 'Cash'
    }[form.method];
    setRows([['Today', form.student, 'Senior 2', 'UGX ' + Number(form.amount).toLocaleString(), method, 'paid'], ...rows]);
    setForm({
      student: '',
      amount: '',
      method: 'schoolpay'
    });
    setOpen(false);
    setSaved(true);
    setTimeout(() => setSaved(false), 2600);
  }
  return /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell dashboard-shell--accountant"
  }, /*#__PURE__*/React.createElement(PageHead, {
    title: "Fee payments",
    sub: "Term 2 2026 \xB7 all classes",
    action: /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: 10,
        alignItems: 'center'
      }
    }, saved ? /*#__PURE__*/React.createElement("span", {
      className: "ds-save-indicator ds-save-indicator--saved"
    }, /*#__PURE__*/React.createElement("span", {
      className: "ds-save-indicator__dot"
    }), "Payment recorded") : null, /*#__PURE__*/React.createElement(Button, {
      variant: "outline",
      size: "sm"
    }, "Export statement"), /*#__PURE__*/React.createElement(Button, {
      variant: "success",
      size: "sm",
      onClick: () => setOpen(!open)
    }, "Record payment"))
  }), /*#__PURE__*/React.createElement("div", {
    className: "dashboard-kpi-grid",
    style: {
      marginTop: 0,
      marginBottom: 20
    }
  }, /*#__PURE__*/React.createElement(KpiCard, {
    icon: "dollar",
    value: "UGX 18.4M",
    label: "Collected this term",
    color: "green"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "money",
    value: "UGX 6.1M",
    label: "Outstanding",
    color: "amber"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "users",
    value: "182",
    label: "Students in arrears",
    color: "red"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "check",
    value: "86%",
    label: "Collection rate",
    color: "blue"
  })), open ? /*#__PURE__*/React.createElement(Card, {
    title: "Record a payment",
    style: {
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3, 1fr)',
      gap: '0 16px'
    }
  }, /*#__PURE__*/React.createElement(FormGroup, {
    label: "Student",
    name: "student",
    required: true,
    placeholder: "e.g. Nakato Sarah",
    value: form.student,
    onChange: e => setForm({
      ...form,
      student: e.target.value
    })
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Amount (UGX)",
    name: "amount",
    type: "number",
    required: true,
    placeholder: "450000",
    value: form.amount,
    onChange: e => setForm({
      ...form,
      amount: e.target.value
    }),
    help: "Figures only, no separators."
  }), /*#__PURE__*/React.createElement(FormGroup, {
    label: "Method",
    name: "method",
    type: "select",
    options: {
      schoolpay: 'SchoolPay',
      momo: 'Mobile Money',
      cash: 'Cash'
    },
    value: form.method,
    onChange: e => setForm({
      ...form,
      method: e.target.value
    })
  })), error ? /*#__PURE__*/React.createElement("p", {
    className: "ds-form-error",
    style: {
      marginTop: 0
    }
  }, error) : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      marginTop: 4
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "success",
    onClick: record
  }, "Save payment"), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    onClick: () => {
      setOpen(false);
      setError('');
    }
  }, "Cancel")), /*#__PURE__*/React.createElement("p", {
    className: "ds-form-help",
    style: {
      marginTop: 10
    }
  }, "The parent gets a WhatsApp receipt as soon as you save.")) : null, /*#__PURE__*/React.createElement(Card, {
    padding: "none"
  }, /*#__PURE__*/React.createElement(Table, {
    headers: ['Date', 'Student', 'Class', 'Amount', 'Method', 'Status'],
    sortable: true
  }, rows.map((r, i) => /*#__PURE__*/React.createElement("tr", {
    key: i
  }, /*#__PURE__*/React.createElement("td", {
    "data-label": "Date"
  }, r[0]), /*#__PURE__*/React.createElement("td", {
    "data-label": "Student"
  }, /*#__PURE__*/React.createElement("a", {
    className: "dt-name-link",
    href: "#"
  }, r[1])), /*#__PURE__*/React.createElement("td", {
    "data-label": "Class"
  }, r[2]), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-num",
    "data-label": "Amount"
  }, r[3]), /*#__PURE__*/React.createElement("td", {
    "data-label": "Method"
  }, r[4]), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-badge",
    "data-label": "Status"
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: r[5]
  }, r[5] === 'paid' ? 'Paid' : r[5] === 'warning' ? 'Part paid' : 'Unpaid')))))));
}
Object.assign(window, {
  FeesScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/FeesScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/Sidebar.jsx
try { (() => {
const {
  useState
} = React;
const {
  Icon
} = window.KlassAppDesignSystem_df5836;
const NAV = [{
  group: null,
  items: [{
    id: 'home',
    label: 'Dashboard',
    icon: 'home'
  }]
}, {
  group: 'People',
  items: [{
    id: 'students',
    label: 'Students',
    icon: 'users'
  }, {
    id: 'teachers',
    label: 'Teachers',
    icon: 'users'
  }]
}, {
  group: 'Academics',
  items: [{
    id: 'classes',
    label: 'Classes',
    icon: 'classes'
  }, {
    id: 'exams',
    label: 'Exams & marks',
    icon: 'exam'
  }]
}, {
  group: 'Finance',
  items: [{
    id: 'fees',
    label: 'Fee payments',
    icon: 'dollar'
  }, {
    id: 'reports',
    label: 'Statements',
    icon: 'document'
  }]
}, {
  group: 'Communication',
  items: [{
    id: 'notices',
    label: 'Notices',
    icon: 'bell'
  }, {
    id: 'whatsapp',
    label: 'WhatsApp log',
    icon: 'whatsapp'
  }]
}];
const itemStyle = active => ({
  display: 'flex',
  alignItems: 'center',
  gap: 12,
  padding: '10px 16px',
  minHeight: 44,
  fontSize: 14,
  fontWeight: active ? 600 : 500,
  cursor: 'pointer',
  color: active ? '#166534' : 'var(--d-text)',
  background: active ? 'rgba(34, 197, 94, 0.24)' : 'transparent',
  borderRadius: 8,
  margin: '0 8px'
});
function SidebarGroup({
  label,
  items,
  active,
  onSelect
}) {
  const [open, setOpen] = useState(true);
  return /*#__PURE__*/React.createElement("div", {
    className: "sidebar-group"
  }, label ? /*#__PURE__*/React.createElement("div", {
    className: 'sidebar-group-header' + (open ? ' sidebar-group-header--open' : ''),
    onClick: () => setOpen(!open)
  }, /*#__PURE__*/React.createElement("span", {
    className: "sidebar-group-label"
  }, label), /*#__PURE__*/React.createElement("span", {
    className: 'sidebar-group-chevron' + (open ? ' rotate-180' : '')
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "chevronDown",
    size: 14
  }))) : null, open ? /*#__PURE__*/React.createElement("ul", null, items.map(it => /*#__PURE__*/React.createElement("li", {
    key: it.id
  }, /*#__PURE__*/React.createElement("div", {
    className: 'dashboard-menu-item' + (active === it.id ? ' active' : ''),
    style: itemStyle(active === it.id),
    onClick: () => onSelect(it.id)
  }, /*#__PURE__*/React.createElement(Icon, {
    name: it.icon,
    size: 19
  }), /*#__PURE__*/React.createElement("span", null, it.label))))) : null);
}
function Sidebar({
  active,
  onSelect
}) {
  return /*#__PURE__*/React.createElement("aside", {
    style: {
      width: 252,
      flexShrink: 0,
      background: '#FFFFFC',
      borderRight: '1px solid var(--d-border)',
      display: 'flex',
      flexDirection: 'column',
      height: '100%'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '20px 16px 14px',
      borderBottom: '1px solid var(--d-border)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: "../../assets/brand/klassapp-icon.svg",
    height: "28",
    alt: ""
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--d-font-display)',
      fontWeight: 700,
      fontSize: 19,
      letterSpacing: '-0.02em',
      color: 'var(--d-dark)'
    }
  }, "KlassApp")), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: 'var(--d-muted)',
      marginTop: 6
    }
  }, "St. Mary\u2019s SS, Kampala")), /*#__PURE__*/React.createElement("nav", {
    style: {
      paddingTop: 8,
      overflowY: 'auto',
      flex: 1
    }
  }, NAV.map((g, i) => /*#__PURE__*/React.createElement(SidebarGroup, {
    key: i,
    label: g.group,
    items: g.items,
    active: active,
    onSelect: onSelect
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      borderTop: '1px solid var(--d-border)',
      padding: 14,
      display: 'flex',
      alignItems: 'center',
      gap: 10
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      width: 34,
      height: 34,
      borderRadius: 999,
      background: 'rgba(30,111,217,0.10)',
      color: 'var(--d-blue)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: 700,
      fontSize: 13
    }
  }, "JN"), /*#__PURE__*/React.createElement("div", {
    style: {
      lineHeight: 1.3,
      flex: 1,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      fontWeight: 600,
      color: 'var(--d-text)'
    }
  }, "Josephine N."), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: 'var(--d-muted)'
    }
  }, "Head Teacher")), /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--d-muted)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "logout",
    size: 17
  }))));
}
Object.assign(window, {
  Sidebar
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/Sidebar.jsx", error: String((e && e.message) || e) }); }

// ui_kits/school-dashboard/StudentsScreen.jsx
try { (() => {
const {
  useState
} = React;
const {
  Card,
  Table,
  Badge,
  Button,
  FormGroup,
  Icon
} = window.KlassAppDesignSystem_df5836;
const STUDENTS = [['1', 'Nakato Sarah', 'Senior 2', 'Okello Anne', '+256 772 114 220', 'active'], ['2', 'Mukasa David', 'Senior 2', 'Mukasa John', '+256 701 883 004', 'active'], ['3', 'Namuli Grace', 'Senior 4', 'Namuli Betty', '+256 758 220 991', 'active'], ['4', 'Ssempala Isaac', 'Senior 4', 'Ssempala Paul', '—', 'inactive'], ['5', 'Achieng Mercy', 'Primary 5', 'Achieng Rose', '+256 772 660 118', 'active']];
function StudentsScreen() {
  const [query, setQuery] = useState('');
  const [cls, setCls] = useState('all');
  const rows = STUDENTS.filter(r => r[1].toLowerCase().includes(query.toLowerCase()) && (cls === 'all' || r[2] === cls));
  return /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell dashboard-shell--reception"
  }, /*#__PURE__*/React.createElement(PageHead, {
    title: "Students",
    sub: rows.length + ' of ' + STUDENTS.length + ' shown · Term 2 2026',
    action: /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: 8
      }
    }, /*#__PURE__*/React.createElement(Button, {
      variant: "outline",
      size: "sm"
    }, "Import list"), /*#__PURE__*/React.createElement(Button, {
      variant: "primary",
      size: "sm"
    }, "Add student"))
  }), /*#__PURE__*/React.createElement(Card, {
    padding: "sm",
    style: {
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 12,
      alignItems: 'center',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      flex: 1,
      minWidth: 220
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      left: 12,
      top: 11,
      color: 'var(--d-muted)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "search",
    size: 18
  })), /*#__PURE__*/React.createElement("input", {
    className: "ds-form-input",
    style: {
      paddingLeft: 38
    },
    placeholder: "Search by student name",
    value: query,
    onChange: e => setQuery(e.target.value)
  })), /*#__PURE__*/React.createElement("select", {
    className: "ds-form-input ds-form-select",
    style: {
      width: 180
    },
    value: cls,
    onChange: e => setCls(e.target.value)
  }, /*#__PURE__*/React.createElement("option", {
    value: "all"
  }, "All classes"), /*#__PURE__*/React.createElement("option", {
    value: "Primary 5"
  }, "Primary 5"), /*#__PURE__*/React.createElement("option", {
    value: "Senior 2"
  }, "Senior 2"), /*#__PURE__*/React.createElement("option", {
    value: "Senior 4"
  }, "Senior 4")))), /*#__PURE__*/React.createElement(Card, {
    padding: "none"
  }, rows.length ? /*#__PURE__*/React.createElement(Table, {
    headers: ['#', 'Student name', 'Class', 'Parent', 'WhatsApp', 'Status'],
    selectable: true,
    sortable: true
  }, rows.map(r => /*#__PURE__*/React.createElement("tr", {
    key: r[0]
  }, /*#__PURE__*/React.createElement("td", {
    "data-label": "#"
  }, /*#__PURE__*/React.createElement("input", {
    type: "checkbox",
    className: "dt-checkbox"
  })), /*#__PURE__*/React.createElement("td", {
    "data-label": "#"
  }, r[0]), /*#__PURE__*/React.createElement("td", {
    "data-label": "Student name"
  }, /*#__PURE__*/React.createElement("a", {
    className: "dt-name-link",
    href: "#"
  }, r[1])), /*#__PURE__*/React.createElement("td", {
    "data-label": "Class"
  }, r[2]), /*#__PURE__*/React.createElement("td", {
    "data-label": "Parent"
  }, r[3]), /*#__PURE__*/React.createElement("td", {
    "data-label": "WhatsApp"
  }, r[4]), /*#__PURE__*/React.createElement("td", {
    className: "dt-cell-badge",
    "data-label": "Status"
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: r[5]
  }, r[5] === 'active' ? 'Active' : 'Left school'))))) : /*#__PURE__*/React.createElement("div", {
    className: "ds-empty-state"
  }, /*#__PURE__*/React.createElement("p", {
    className: "ds-empty-state-title"
  }, "No students match \u201C", query, "\u201D"), /*#__PURE__*/React.createElement("p", {
    className: "ds-empty-state-desc"
  }, "Check the spelling, or clear the class filter to search the whole school.")), /*#__PURE__*/React.createElement("div", {
    className: "dt-pagination",
    style: {
      padding: '12px 16px'
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "dt-pagination-info"
  }, "Showing 1\u2013", rows.length, " of 1,284 students"), /*#__PURE__*/React.createElement("span", {
    className: "dt-pagination-pages"
  }, /*#__PURE__*/React.createElement("button", {
    className: "dt-page-btn active"
  }, "1"), /*#__PURE__*/React.createElement("button", {
    className: "dt-page-btn"
  }, "2"), /*#__PURE__*/React.createElement("button", {
    className: "dt-page-btn"
  }, "3"), /*#__PURE__*/React.createElement("a", {
    className: "dt-page-nav",
    href: "#"
  }, "Next \u2192")))));
}
Object.assign(window, {
  StudentsScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/school-dashboard/StudentsScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/toshi-assistant/ToshiApp.jsx
try { (() => {
const {
  useState
} = React;
const {
  Card,
  Button,
  Badge,
  KpiCard
} = window.KlassAppDesignSystem_df5836;
function ToshiApp() {
  const [open, setOpen] = useState(true);
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      height: '100vh',
      overflow: 'hidden',
      background: 'var(--d-canvas)'
    }
  }, /*#__PURE__*/React.createElement("main", {
    style: {
      flex: 1,
      overflowY: 'auto',
      padding: 24,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("div", {
    className: "dashboard-shell dashboard-shell--admin"
  }, /*#__PURE__*/React.createElement("h1", {
    className: "dashboard-title"
  }, "Good morning, Josephine"), /*#__PURE__*/React.createElement("p", {
    className: "dashboard-subtitle"
  }, "Toshi sits beside the dashboard on desktop and goes full-screen on a phone."), /*#__PURE__*/React.createElement("div", {
    className: "dashboard-kpi-grid"
  }, /*#__PURE__*/React.createElement(KpiCard, {
    icon: "users",
    value: "1,284",
    label: "Total Students",
    color: "blue"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "dollar",
    value: "UGX 18.4M",
    label: "Fees Collected",
    color: "amber"
  }), /*#__PURE__*/React.createElement(KpiCard, {
    icon: "whatsapp",
    value: "1,102",
    label: "WhatsApp Linked",
    color: "green"
  })), /*#__PURE__*/React.createElement(Card, {
    title: "Why an assistant",
    style: {
      marginTop: 20
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontSize: 14,
      lineHeight: 1.55,
      color: 'var(--d-text-secondary)'
    }
  }, "Toshi does the multi-step office work \u2014 adding a student, recording a payment, sending a notice \u2014 and asks for confirmation before anything is written. It runs on its own warm clay palette so it never reads as part of the ledger."), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 14,
      display: 'flex',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "sm",
    onClick: () => setOpen(true)
  }, "Open Toshi"), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "sm",
    onClick: () => setOpen(false)
  }, "Collapse"))))), /*#__PURE__*/React.createElement("div", {
    "data-toshi-root": true,
    style: {
      position: 'static',
      display: 'flex',
      flexDirection: 'column',
      width: open ? 400 : 0,
      flexShrink: 0,
      padding: open ? 16 : 0,
      boxSizing: 'content-box'
    }
  }, open ? /*#__PURE__*/React.createElement(ToshiPanel, {
    onClose: () => setOpen(false)
  }) : /*#__PURE__*/React.createElement("div", {
    className: "toshi-pill",
    onClick: () => setOpen(true),
    style: {
      position: 'fixed',
      bottom: 24,
      right: 24
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-pill-avatar"
  }, /*#__PURE__*/React.createElement("img", {
    src: "../../assets/brand/klassapp-icon.svg",
    height: "18",
    alt: ""
  })), /*#__PURE__*/React.createElement("span", {
    className: "toshi-pill-text"
  }, "Ask Toshi to do it for you"), /*#__PURE__*/React.createElement("span", {
    className: "toshi-pill-badge"
  }, "Open"))));
}
ReactDOM.createRoot(document.getElementById('root')).render(/*#__PURE__*/React.createElement(ToshiApp, null));
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/toshi-assistant/ToshiApp.jsx", error: String((e && e.message) || e) }); }

// ui_kits/toshi-assistant/ToshiPanel.jsx
try { (() => {
const {
  useState
} = React;
const {
  Icon
} = window.KlassAppDesignSystem_df5836;
const SUGGESTIONS = [{
  icon: '👥',
  label: 'Add a new student'
}, {
  icon: '💰',
  label: 'Record a fee payment'
}, {
  icon: '📣',
  label: 'Send a notice to Senior 2'
}, {
  icon: '📊',
  label: 'How are fees this week?'
}];
function Bubble({
  from,
  children
}) {
  const mine = from === 'user';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: mine ? 'flex-end' : 'flex-start'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: '82%',
      padding: '10px 14px',
      borderRadius: 14,
      background: mine ? 'var(--toshi-user-bubble)' : 'var(--toshi-bot-bubble)',
      border: mine ? 'none' : '1px solid var(--toshi-border)',
      fontSize: 13.5,
      lineHeight: 1.5,
      color: 'var(--toshi-title-text)'
    }
  }, children));
}
function ConfirmCard({
  params,
  state,
  onYes,
  onNo
}) {
  return /*#__PURE__*/React.createElement("div", {
    className: 'toshi-confirm-card' + (state === 'cancelled' ? ' is-cancelled' : '')
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-card-header"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-card-title"
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-confirm-card-icon"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "tick",
    size: 15
  })), "Record this payment?"), state === 'cancelled' ? /*#__PURE__*/React.createElement("span", {
    className: "toshi-confirm-cancelled-badge"
  }, "Cancelled") : null), /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-card-body"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-param-list"
  }, params.map(([k, v]) => /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-param-row",
    key: k
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-confirm-param-label"
  }, k), /*#__PURE__*/React.createElement("span", {
    className: "toshi-confirm-param-value"
  }, v))))), /*#__PURE__*/React.createElement("div", {
    className: "toshi-confirm-card-footer"
  }, state === 'open' ? /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("button", {
    className: "toshi-confirm-btn toshi-confirm-btn-yes",
    onClick: onYes
  }, "Yes, record it"), /*#__PURE__*/React.createElement("button", {
    className: "toshi-confirm-btn toshi-confirm-btn-no",
    onClick: onNo
  }, "No, change something")) : /*#__PURE__*/React.createElement("span", {
    className: "toshi-confirm-cancelled-msg"
  }, state === 'done' ? 'Recorded · parent notified on WhatsApp' : 'Nothing was saved.')));
}
function PlanCard({
  step
}) {
  const steps = ['Find the student', 'Record UGX 450,000', 'Send WhatsApp receipt'];
  return /*#__PURE__*/React.createElement("div", {
    className: "toshi-plan-card"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-plan-card-header"
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-card-icon"
  }, "\uD83D\uDCCB"), /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-card-title"
  }, "Plan"), /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-card-count"
  }, step, "/3")), /*#__PURE__*/React.createElement("div", {
    className: "toshi-plan-card-steps"
  }, steps.map((s, i) => /*#__PURE__*/React.createElement("div", {
    key: s,
    className: 'toshi-plan-step ' + (i < step ? 'step-completed' : i === step ? 'step-active' : 'step-pending')
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-step-icon"
  }, i < step ? '✓' : i === step ? '▸' : '·'), /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-step-num"
  }, i + 1), /*#__PURE__*/React.createElement("span", {
    className: "toshi-plan-step-label"
  }, s)))));
}
function ToshiPanel({
  onClose
}) {
  const [messages, setMessages] = useState([{
    from: 'bot',
    text: 'Morning Josephine. Fees are at UGX 18.4M this term — 86% of the target. What do you need?'
  }]);
  const [confirm, setConfirm] = useState(null);
  const [draft, setDraft] = useState('');
  const [used, setUsed] = useState([]);
  function ask(text) {
    setMessages(m => [...m, {
      from: 'user',
      text
    }]);
    setDraft('');
    setTimeout(() => {
      if (/pay|fee|money/i.test(text)) {
        setMessages(m => [...m, {
          from: 'bot',
          text: 'Got it — here is what I will do.'
        }, {
          from: 'plan',
          step: 1
        }]);
        setConfirm('open');
      } else {
        setMessages(m => [...m, {
          from: 'bot',
          text: 'I can do that. Which class should it go to — Senior 2, or the whole school?'
        }]);
      }
    }, 420);
  }
  return /*#__PURE__*/React.createElement("div", {
    className: "toshi-panel"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-header"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-header-logo",
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 9
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: "../../assets/brand/klassapp-icon.svg",
    height: "22",
    alt: ""
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--d-font-display)',
      fontSize: 15,
      fontWeight: 700,
      color: 'var(--toshi-title-text)'
    }
  }, "Toshi"), /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 11,
      color: 'var(--d-muted)'
    }
  }, "KlassApp assistant")), /*#__PURE__*/React.createElement("div", {
    className: "toshi-header-actions"
  }, /*#__PURE__*/React.createElement("button", {
    className: "toshi-header-btn",
    title: "Expand"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "plus",
    size: 16
  })), /*#__PURE__*/React.createElement("button", {
    className: "toshi-header-btn",
    onClick: onClose,
    title: "Close"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "close",
    size: 16
  })))), /*#__PURE__*/React.createElement("div", {
    className: "toshi-messages-area"
  }, messages.map((m, i) => m.from === 'plan' ? /*#__PURE__*/React.createElement(PlanCard, {
    key: i,
    step: m.step
  }) : /*#__PURE__*/React.createElement(Bubble, {
    key: i,
    from: m.from
  }, m.text)), confirm ? /*#__PURE__*/React.createElement(ConfirmCard, {
    state: confirm,
    params: [['Student', 'Nakato Sarah · Senior 2'], ['Amount', 'UGX 450,000'], ['Method', 'SchoolPay'], ['Receipt', 'WhatsApp to +256 772 114 220']],
    onYes: () => {
      setConfirm('done');
      setMessages(m => [...m, {
        from: 'bot',
        text: 'Done. Receipt sent to Okello Anne on WhatsApp.'
      }]);
    },
    onNo: () => setConfirm('cancelled')
  }) : null), /*#__PURE__*/React.createElement("div", {
    className: "toshi-suggestions-wrapper"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-suggestions-scroll"
  }, SUGGESTIONS.map(s => /*#__PURE__*/React.createElement("button", {
    key: s.label,
    className: 'toshi-chip-suggestion' + (used.includes(s.label) ? ' toshi-chip-used' : ''),
    onClick: () => {
      setUsed(u => [...u, s.label]);
      ask(s.label);
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-chip-icon"
  }, s.icon), s.label)))), /*#__PURE__*/React.createElement("div", {
    className: "toshi-composer"
  }, /*#__PURE__*/React.createElement("div", {
    className: "toshi-composer-inner"
  }, /*#__PURE__*/React.createElement("span", {
    className: "toshi-attach-btn"
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "upload",
    size: 17
  })), /*#__PURE__*/React.createElement("textarea", {
    className: "toshi-composer-input",
    rows: 1,
    placeholder: "Ask Toshi to do something\u2026",
    value: draft,
    onChange: e => setDraft(e.target.value),
    onKeyDown: e => {
      if (e.key === 'Enter' && !e.shiftKey && draft.trim()) {
        e.preventDefault();
        ask(draft.trim());
      }
    }
  }), /*#__PURE__*/React.createElement("button", {
    className: "toshi-btn-done",
    onClick: () => draft.trim() && ask(draft.trim())
  }, "Send"))));
}
Object.assign(window, {
  ToshiPanel
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/toshi-assistant/ToshiPanel.jsx", error: String((e && e.message) || e) }); }

__ds_ns.Button = __ds_scope.Button;

__ds_ns.GoogleDriveMark = __ds_scope.GoogleDriveMark;

__ds_ns.SlackMark = __ds_scope.SlackMark;

__ds_ns.WhatsAppMark = __ds_scope.WhatsAppMark;

__ds_ns.ICONS = __ds_scope.ICONS;

__ds_ns.Icon = __ds_scope.Icon;

__ds_ns.KpiCard = __ds_scope.KpiCard;

__ds_ns.Table = __ds_scope.Table;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.FormGroup = __ds_scope.FormGroup;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Card = __ds_scope.Card;

})();

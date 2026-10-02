/* @ds-bundle: {"format":4,"namespace":"KlassAppDesignSystem_df5836","components":[{"name":"Button","sourcePath":"components/actions/Button.jsx"},{"name":"GoogleDriveMark","sourcePath":"components/brand/GoogleDriveMark.jsx"},{"name":"SlackMark","sourcePath":"components/brand/SlackMark.jsx"},{"name":"WhatsAppMark","sourcePath":"components/brand/WhatsAppMark.jsx"},{"name":"ICONS","sourcePath":"components/core/Icon.jsx"},{"name":"Icon","sourcePath":"components/core/Icon.jsx"},{"name":"KpiCard","sourcePath":"components/data-display/KpiCard.jsx"},{"name":"Table","sourcePath":"components/data-display/Table.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"FormGroup","sourcePath":"components/forms/FormGroup.jsx"},{"name":"Badge","sourcePath":"components/surfaces/Badge.jsx"},{"name":"Card","sourcePath":"components/surfaces/Card.jsx"}],"sourceHashes":{"components/actions/Button.jsx":"1f711d38fc8c","components/brand/GoogleDriveMark.jsx":"3e572ab24ef1","components/brand/SlackMark.jsx":"22e2c222b3c3","components/brand/WhatsAppMark.jsx":"d4dd34ab2dbf","components/core/Icon.jsx":"cadc7fa3ceb6","components/data-display/KpiCard.jsx":"b260c440c5be","components/data-display/Table.jsx":"2925884a8caa","components/forms/Checkbox.jsx":"b71c969210d6","components/forms/FormGroup.jsx":"57e88e2f0aa1","components/surfaces/Badge.jsx":"a76dfb7cec49","components/surfaces/Card.jsx":"1ab75301fdfd","ui_kits/onboarding-wizard/WizardApp.jsx":"d8c38074461f","ui_kits/onboarding-wizard/WizardSteps.jsx":"56c713039862","ui_kits/school-dashboard/App.jsx":"431b72105531","ui_kits/school-dashboard/DashboardHome.jsx":"f9a515595fc4","ui_kits/school-dashboard/ExamsScreen.jsx":"168e298afe6c","ui_kits/school-dashboard/FeesScreen.jsx":"14efc1873b61","ui_kits/school-dashboard/Sidebar.jsx":"d5e3ba9349fa","ui_kits/school-dashboard/StudentsScreen.jsx":"dedb96ace81c","ui_kits/toshi-assistant/ToshiApp.jsx":"9b70c3f6b06d","ui_kits/toshi-assistant/ToshiPanel.jsx":"a46aa72e95b5"},"inlinedExternals":[],"unexposedExports":[]} */

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

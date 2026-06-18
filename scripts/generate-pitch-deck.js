const pptxgen = require("pptxgenjs");

const pres = new pptxgen();
pres.layout = "LAYOUT_16x9"; // 10" x 5.625"
pres.author = "KlassApp";
pres.title = "KlassApp — Venture Capital Pitch Deck";

// Brand colors
const C = {
  blue: "1E6FD9",
  green: "22C55E",
  dark: "0F172A",
  amber: "D97706",
  white: "FFFFFF",
  offWhite: "F8FAFC",
  lightGray: "F1F5F9",
  midGray: "94A3B8",
  textDark: "1E293B",
  textGray: "64748B",
};

const FONT_TITLE = "Arial";
const FONT_BODY = "Arial";

// Helper: add a dark footer bar
function addFooter(slide, pageNum) {
  slide.addText(`Confidential — June 2026`, {
    x: 0.5, y: 5.15, w: 9, h: 0.35,
    fontSize: 7, color: C.midGray, fontFace: FONT_BODY, align: "left", margin: 0,
  });
  slide.addText(`${pageNum}`, {
    x: 0.5, y: 5.15, w: 9, h: 0.35,
    fontSize: 7, color: C.midGray, fontFace: FONT_BODY, align: "right", margin: 0,
  });
}

// Helper: section title with accent bar
function addSectionTitle(slide, title, subtitle) {
  slide.addShape(slide._slideLayout ? pres.shapes.RECTANGLE : pres.shapes.RECTANGLE, {
    x: 0.5, y: 0.35, w: 0.06, h: 0.45,
    fill: { color: C.green },
  });
  slide.addText(title, {
    x: 0.75, y: 0.3, w: 8.5, h: 0.55,
    fontSize: 26, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
  });
  if (subtitle) {
    slide.addText(subtitle, {
      x: 0.75, y: 0.85, w: 8.5, h: 0.35,
      fontSize: 13, fontFace: FONT_BODY, color: C.textGray, margin: 0,
    });
  }
}

// Helper: stat card
function addStatCard(slide, x, y, w, number, label) {
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w, h: 1.1, fill: { color: C.white },
    shadow: { type: "outer", color: "000000", blur: 4, offset: 1, angle: 135, opacity: 0.08 },
    rectRadius: 0.08,
  });
  slide.addText(number, {
    x: x + 0.1, y: y + 0.15, w: w - 0.2, h: 0.5,
    fontSize: 28, fontFace: FONT_TITLE, color: C.blue, bold: true, align: "center", margin: 0,
  });
  slide.addText(label, {
    x: x + 0.1, y: y + 0.65, w: w - 0.2, h: 0.35,
    fontSize: 10, fontFace: FONT_BODY, color: C.textGray, align: "center", margin: 0,
  });
}

// ══════════════════════════════════════════════════
// SLIDE 1 — TITLE
// ══════════════════════════════════════════════════
let slide1 = pres.addSlide();
slide1.background = { color: C.dark };

// Decorative gradient bar at top
slide1.addShape(pres.shapes.RECTANGLE, {
  x: 0, y: 0, w: 10, h: 0.06,
  fill: { color: C.green },
});

// Large logo text
slide1.addText("KlassApp", {
  x: 0.8, y: 1.0, w: 8.4, h: 1.2,
  fontSize: 54, fontFace: FONT_TITLE, color: C.white, bold: true, margin: 0,
  charSpacing: 3,
});

// Green accent underline
slide1.addShape(pres.shapes.RECTANGLE, {
  x: 0.8, y: 2.25, w: 1.6, h: 0.05,
  fill: { color: C.green },
});

// Subtitle
slide1.addText("The School in Every Parent's Pocket", {
  x: 0.8, y: 2.5, w: 8.4, h: 0.7,
  fontSize: 22, fontFace: FONT_TITLE, color: C.midGray, margin: 0,
});

// Tagline
slide1.addText("WhatsApp-first school management for Africa", {
  x: 0.8, y: 3.3, w: 8.4, h: 0.5,
  fontSize: 14, fontFace: FONT_BODY, color: C.textGray, margin: 0,
});

// Footer
slide1.addText("Confidential — June 2026", {
  x: 0.8, y: 4.8, w: 8.4, h: 0.35,
  fontSize: 9, color: C.textGray, fontFace: FONT_BODY, margin: 0,
});

// ══════════════════════════════════════════════════
// SLIDE 2 — PROBLEM
// ══════════════════════════════════════════════════
let slide2 = pres.addSlide();
slide2.background = { color: C.offWhite };
addSectionTitle(slide2, "The Communication Gap");

// Left column: bullets
slide2.addText([
  { text: "74,000+ schools across Uganda. 20 million students. Zero real-time parent communication.", options: { bullet: true, breakLine: true } },
  { text: "Parents find out exam results at end of term — weeks after publication.", options: { bullet: true, breakLine: true } },
  { text: "Schools spend 30 UGX per SMS. 70% delivery. One-way. No replies.", options: { bullet: true, breakLine: true } },
  { text: "Fee collection is chaotic. Absence notifications never reach home.", options: { bullet: true, breakLine: true } },
  { text: "\"School apps\" require downloads, logins, and updates. Parents don't use them.", options: { bullet: true, breakLine: true } },
  { text: "Paper circulars are lost, late, and unread.", options: { bullet: true } },
], {
  x: 0.5, y: 1.4, w: 5.5, h: 3.5,
  fontSize: 12.5, fontFace: FONT_BODY, color: C.textDark, margin: 0,
  lineSpacingMultiple: 1.35, paraSpaceAfter: 10,
});

// Right column: stat cards
addStatCard(slide2, 6.5, 1.4, 3.0, "98%", "WhatsApp penetration\namong smartphone users");
addStatCard(slide2, 6.5, 2.7, 3.0, "$40", "Smartphones now\ncommon in Uganda");
addStatCard(slide2, 6.5, 4.0, 3.0, "70%+", "School fees paid\nvia mobile money");

addFooter(slide2, 2);

// ══════════════════════════════════════════════════
// SLIDE 3 — SOLUTION
// ══════════════════════════════════════════════════
let slide3 = pres.addSlide();
slide3.background = { color: C.white };
addSectionTitle(slide3, "Meet KlassApp", "The platform that makes every school a WhatsApp contact.");

slide3.addText([
  { text: "Parents get grades, fees, child's health updates, attendance, and more via WhatsApp. No app. No login. Just a message.", options: { bullet: true, breakLine: true } },
  { text: "Schools get dashboards, automated campaigns, and delivery tracking.", options: { bullet: true, breakLine: true } },
  { text: "Works on top of existing school systems — no rip-and-replace. Just plug and connect.", options: { bullet: true, breakLine: true } },
  { text: "Two-way: parents can check balances, report absences, and request results.", options: { bullet: true, breakLine: true } },
  { text: "Free to receive. WhatsApp is zero-rated on MTN and Airtel in Uganda.", options: { bullet: true } },
], {
  x: 0.5, y: 1.4, w: 5.8, h: 3.2,
  fontSize: 13, fontFace: FONT_BODY, color: C.textDark, margin: 0,
  lineSpacingMultiple: 1.35, paraSpaceAfter: 10,
});

// Metric cards on the right
slide3.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 6.8, y: 1.3, w: 2.8, h: 3.6,
  fill: { color: C.offWhite }, rectRadius: 0.1,
  line: { color: "E2E8F0", width: 1 },
});

// Green metric 1
slide3.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 7.0, y: 1.5, w: 2.4, h: 0.9,
  fill: { color: C.green }, rectRadius: 0.08,
});
slide3.addText("0-15 UGX", {
  x: 7.0, y: 1.55, w: 2.4, h: 0.35,
  fontSize: 18, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", margin: 0,
});
slide3.addText("per message (vs 30 UGX SMS)", {
  x: 7.0, y: 1.9, w: 2.4, h: 0.35,
  fontSize: 8.5, fontFace: FONT_BODY, color: C.white, align: "center", margin: 0,
});

// Blue metric 2
slide3.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 7.0, y: 2.6, w: 2.4, h: 0.9,
  fill: { color: C.blue }, rectRadius: 0.08,
});
slide3.addText("95%+", {
  x: 7.0, y: 2.65, w: 2.4, h: 0.35,
  fontSize: 18, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", margin: 0,
});
slide3.addText("delivery rate (vs 70% SMS)", {
  x: 7.0, y: 3.0, w: 2.4, h: 0.35,
  fontSize: 8.5, fontFace: FONT_BODY, color: C.white, align: "center", margin: 0,
});

// Amber metric 3
slide3.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 7.0, y: 3.7, w: 2.4, h: 0.9,
  fill: { color: C.amber }, rectRadius: 0.08,
});
slide3.addText("97%", {
  x: 7.0, y: 3.75, w: 2.4, h: 0.35,
  fontSize: 18, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", margin: 0,
});
slide3.addText("gross margin", {
  x: 7.0, y: 4.1, w: 2.4, h: 0.35,
  fontSize: 8.5, fontFace: FONT_BODY, color: C.white, align: "center", margin: 0,
});

addFooter(slide3, 3);

// ══════════════════════════════════════════════════
// SLIDE 4 — PRODUCT ARCHITECTURE
// ══════════════════════════════════════════════════
let slide4 = pres.addSlide();
slide4.background = { color: C.white };
addSectionTitle(slide4, "How It Works");

// Flow diagram using shapes
const boxW = 1.8, boxH = 0.7, arrowW = 0.6;
const boxes = [
  { text: "School\nAdmin / Teacher", x: 0.5, color: C.blue },
  { text: "KlassApp\nEngine", x: 2.9, color: C.green },
  { text: "WhatsApp\nAPI", x: 5.3, color: C.amber },
  { text: "Parent's\nPhone", x: 7.7, color: C.dark },
];

boxes.forEach((b) => {
  slide4.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: b.x, y: 1.6, w: boxW, h: boxH,
    fill: { color: b.color }, rectRadius: 0.1,
  });
  slide4.addText(b.text, {
    x: b.x, y: 1.6, w: boxW, h: boxH,
    fontSize: 11, fontFace: FONT_BODY, color: C.white, bold: true, align: "center", valign: "middle", margin: 0,
  });
});

// Arrows between boxes
for (let i = 0; i < 3; i++) {
  slide4.addText("→", {
    x: boxes[i].x + boxW + 0.05, y: 1.6, w: 0.5, h: boxH,
    fontSize: 22, fontFace: FONT_BODY, color: C.midGray, align: "center", valign: "middle", margin: 0,
  });
}

// Dashboard box below
slide4.addShape(pres.shapes.RECTANGLE, {
  x: 2.9, y: 2.7, w: 4.2, h: 0.55,
  fill: { color: C.offWhite }, line: { color: "E2E8F0", width: 1 },
});
slide4.addText("Delivery Dashboard  ·  Analytics  ·  Failure Escalation", {
  x: 2.9, y: 2.7, w: 4.2, h: 0.55,
  fontSize: 10, fontFace: FONT_BODY, color: C.textGray, align: "center", valign: "middle", margin: 0,
});

// Arrow down
slide4.addText("↑", {
  x: 4.45, y: 2.35, w: 1, h: 0.35,
  fontSize: 14, fontFace: FONT_BODY, color: C.midGray, align: "center", margin: 0,
});

// Key features list
slide4.addText([
  { text: "Schools publish grades, fees, or attendance → KlassApp formats and delivers instantly.", options: { bullet: true, breakLine: true } },
  { text: "Parents reply with keywords (\"grades\", \"fees\", \"absent\") → instant AI response.", options: { bullet: true, breakLine: true } },
  { text: "Multi-child support: one parent, multiple students, separate notification streams.", options: { bullet: true, breakLine: true } },
  { text: "AI-powered import: Excel/CSV parsing, smart delivery routing, failure escalation.", options: { bullet: true, breakLine: true } },
  { text: "LIN integration: connects to Uganda's national Learner Identification Number system.", options: { bullet: true, breakLine: true } },
  { text: "SchoolPay integration: automated fee status, payment confirmations, balance checks.", options: { bullet: true } },
], {
  x: 0.5, y: 3.5, w: 9, h: 1.6,
  fontSize: 11.5, fontFace: FONT_BODY, color: C.textDark, margin: 0,
  lineSpacingMultiple: 1.25, paraSpaceAfter: 4,
});

addFooter(slide4, 4);

// ══════════════════════════════════════════════════
// SLIDE 5 — DIFFERENTIATORS
// ══════════════════════════════════════════════════
let slide5 = pres.addSlide();
slide5.background = { color: C.offWhite };
addSectionTitle(slide5, "Why KlassApp Wins");

const cols = [
  { title: "WhatsApp-First", icon: "💬", color: C.green, bullets: [
    "Works inside the app parents already use daily",
    "Zero friction: no download, no account, no password",
    "98% penetration in target market",
    "Zero-rated on MTN & Airtel — free to receive",
  ]},
  { title: "LIN Integration", icon: "🆔", color: C.blue, bullets: [
    "Connects to Uganda's national student ID database",
    "Parents self-register by texting their child's LIN",
    "Creates a nationwide parent network — not just per-school",
    "Moat: no competitor connects to the national LIN system",
  ]},
  { title: "Platform, Not Point Solution", icon: "⚙️", color: C.amber, bullets: [
    "Layers on top of existing systems — no rip-and-replace",
    "Roles: admin, teacher, bursar, librarian, nurse, secretary",
    "Expanding: guidance counsellor, sports, transport, PTA",
    "Student data follows the child across school transfers",
  ]},
];

cols.forEach((col, i) => {
  const x = 0.4 + i * 3.15;
  const y = 1.35;

  // Card background
  slide5.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w: 2.95, h: 3.6,
    fill: { color: C.white }, rectRadius: 0.1,
    shadow: { type: "outer", color: "000000", blur: 3, offset: 1, angle: 135, opacity: 0.06 },
  });

  // Top accent bar
  slide5.addShape(pres.shapes.RECTANGLE, {
    x, y, w: 2.95, h: 0.05,
    fill: { color: col.color },
  });

  // Icon circle
  slide5.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: x + 1.0, y: y + 0.2, w: 0.9, h: 0.9,
    fill: { color: col.color, transparency: 90 }, rectRadius: 0.45,
  });
  slide5.addText(col.icon, {
    x: x + 1.0, y: y + 0.2, w: 0.9, h: 0.9,
    fontSize: 24, align: "center", valign: "middle", margin: 0,
  });

  // Column title
  slide5.addText(col.title, {
    x: x + 0.2, y: y + 1.2, w: 2.55, h: 0.4,
    fontSize: 14, fontFace: FONT_TITLE, color: C.dark, bold: true, align: "center", margin: 0,
  });

  // Bullets
  const bulletItems = col.bullets.map((b, bi) => ({
    text: b,
    options: { bullet: true, ...(bi < col.bullets.length - 1 ? { breakLine: true } : {}) },
  }));

  slide5.addText(bulletItems, {
    x: x + 0.2, y: y + 1.7, w: 2.55, h: 1.7,
    fontSize: 9.5, fontFace: FONT_BODY, color: C.textDark, margin: 0,
    lineSpacingMultiple: 1.3, paraSpaceAfter: 6,
  });
});

addFooter(slide5, 5);

// ══════════════════════════════════════════════════
// SLIDE 6 — TRACTION
// ══════════════════════════════════════════════════
let slide6 = pres.addSlide();
slide6.background = { color: C.white };
addSectionTitle(slide6, "Early Traction");

// Big stat cards row
addStatCard(slide6, 0.5, 1.4, 2.8, "5", "schools live\nin testnet");
addStatCard(slide6, 3.6, 1.4, 2.8, "150+", "teachers active\non the platform");
addStatCard(slide6, 6.7, 1.4, 2.8, "2,500+", "students on\nthe system");

// Phase progress
slide6.addText("Product Progress", {
  x: 0.5, y: 2.9, w: 9, h: 0.35,
  fontSize: 14, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

const phases = [
  { label: "Phase 1 — Core", status: "Complete", desc: "Grade publishing, fee reminders, attendance alerts, interactive bot menu", color: C.green },
  { label: "Phase 2 — Engage", status: "Complete", desc: "Smart message delivery, notification queue, delivery dashboard, multi-child parent flow", color: C.green },
  { label: "Phase 3 — Scale", status: "In Progress", desc: "LIN integration, CSV bulk import, parent self-registration, nationwide network", color: C.amber },
];

phases.forEach((p, i) => {
  const y = 3.4 + i * 0.6;
  // Status dot
  slide6.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.5, y: y + 0.05, w: 0.3, h: 0.3,
    fill: { color: p.color }, rectRadius: 0.15,
  });
  slide6.addText(p.label, {
    x: 1.0, y, w: 2.5, h: 0.4,
    fontSize: 11, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
  });
  slide6.addText(p.status, {
    x: 3.6, y, w: 1.0, h: 0.4,
    fontSize: 9, fontFace: FONT_BODY, color: p.color, bold: true, margin: 0,
    align: "center",
  });
  slide6.addText(p.desc, {
    x: 4.8, y, w: 4.7, h: 0.4,
    fontSize: 10, fontFace: FONT_BODY, color: C.textGray, margin: 0,
  });
});

// Bottom highlight
slide6.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 0.5, y: 5.0, w: 9, h: 0.1,
  fill: { color: C.green }, rectRadius: 0.05,
});

addFooter(slide6, 6);

// ══════════════════════════════════════════════════
// SLIDE 7 — MARKET
// ══════════════════════════════════════════════════
let slide7 = pres.addSlide();
slide7.background = { color: C.offWhite };
addSectionTitle(slide7, "Market Opportunity");

// Left: market sizing
slide7.addText("Market Sizing — Uganda", {
  x: 0.5, y: 1.3, w: 5, h: 0.35,
  fontSize: 14, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

const marketRows = [
  ["TAM", "74,000 schools × 500 avg parents", "37M connections"],
  ["SAM", "30,000 schools (urban + peri-urban)", "15M connections"],
  ["SOM (Y3)", "5,000 schools adopted", "2.5M connections"],
];

// Market sizing table
const tableHeader = [
  { text: "", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 9 } },
  { text: "Description", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 9 } },
  { text: "Parent Connections", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 9 } },
];

const tableData = [tableHeader];
marketRows.forEach((row) => {
  tableData.push([
    { text: row[0], options: { bold: true, fontSize: 11, color: C.blue } },
    { text: row[1], options: { fontSize: 11, color: C.textDark } },
    { text: row[2], options: { fontSize: 11, color: C.textDark, bold: true } },
  ]);
});

slide7.addTable(tableData, {
  x: 0.5, y: 1.7, w: 5.0, h: 1.2,
  border: { pt: 0.5, color: "E2E8F0" },
  colW: [1.2, 2.5, 1.3],
  rowH: [0.3, 0.3, 0.3, 0.3],
  fill: { color: C.white },
  autoPage: false,
});

// Revenue at SOM
slide7.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 0.5, y: 3.2, w: 5.0, h: 0.55,
  fill: { color: C.blue }, rectRadius: 0.06,
});
slide7.addText("At SOM: 5,000 schools × $22.30/mo blended = $1.34M ARR", {
  x: 0.5, y: 3.2, w: 5.0, h: 0.55,
  fontSize: 11, fontFace: FONT_BODY, color: C.white, bold: true, align: "center", valign: "middle", margin: 0,
});

// Right: key drivers
slide7.addText("Key Growth Drivers", {
  x: 6.0, y: 1.3, w: 3.5, h: 0.35,
  fontSize: 14, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

const drivers = [
  ["LIN Mandate", "Every student already in a national database — we just connect the parent."],
  ["Smartphone Adoption", "$40 devices now common; WhatsApp is the de facto OS for most Ugandans."],
  ["Mobile Money", "70%+ of school fees paid via mobile money — notifications drive action."],
  ["No Direct Competitor", "No WhatsApp-first, LIN-integrated school platform exists in East Africa."],
];

drivers.forEach((d, i) => {
  const y = 1.8 + i * 0.8;
  slide7.addShape(pres.shapes.RECTANGLE, {
    x: 6.0, y, w: 0.06, h: 0.6,
    fill: { color: C.green },
  });
  slide7.addText(d[0], {
    x: 6.25, y, w: 3.25, h: 0.25,
    fontSize: 11, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
  });
  slide7.addText(d[1], {
    x: 6.25, y: y + 0.25, w: 3.25, h: 0.35,
    fontSize: 9, fontFace: FONT_BODY, color: C.textGray, margin: 0,
  });
});

addFooter(slide7, 7);

// ══════════════════════════════════════════════════
// SLIDE 8 — FINANCIALS
// ══════════════════════════════════════════════════
let slide8 = pres.addSlide();
slide8.background = { color: C.white };
addSectionTitle(slide8, "KlassNomics — Revenue Model");

// Revenue table
const finHeader = [
  { text: "Metric", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 10, align: "left" } },
  { text: "25 Schools", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 10, align: "right" } },
  { text: "50 Schools", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 10, align: "right" } },
  { text: "100 Schools", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 10, align: "right" } },
  { text: "200 Schools", options: { bold: true, color: C.white, fill: { color: C.dark }, fontSize: 10, align: "right" } },
];

const finData = [finHeader];
const finRows = [
  ["Gross Revenue (USD/yr)", "$6,689", "$13,378", "$26,757", "$53,514"],
  ["Infra & AI Costs (~17%)", "$1,137", "$2,274", "$4,549", "$9,097"],
  ["VAT (18%)", "$1,204", "$2,408", "$4,816", "$9,632"],
  ["Net Revenue (USD/yr)", "$4,348", "$8,696", "$17,392", "$34,785"],
  ["Gross Margin", "65%", "65%", "65%", "65%"],
];

finRows.forEach((row, ri) => {
  const isNet = ri === 2;
  const bgColor = isNet ? "EFF6FF" : C.white;
  finData.push([
    { text: row[0], options: { fontSize: 10, color: isNet ? C.blue : C.textDark, bold: isNet, fill: { color: bgColor } } },
    { text: row[1], options: { fontSize: 10, color: C.textDark, bold: isNet, align: "right", fill: { color: bgColor } } },
    { text: row[2], options: { fontSize: 10, color: C.textDark, bold: isNet, align: "right", fill: { color: bgColor } } },
    { text: row[3], options: { fontSize: 10, color: C.textDark, bold: isNet, align: "right", fill: { color: bgColor } } },
    { text: row[4], options: { fontSize: 10, color: C.textDark, bold: isNet, align: "right", fill: { color: bgColor } } },
  ]);
});

slide8.addTable(finData, {
  x: 0.4, y: 1.4, w: 9.2, h: 1.8,
  border: { pt: 0.5, color: "E2E8F0" },
  colW: [3.0, 1.55, 1.55, 1.55, 1.55],
  rowH: [0.35, 0.35, 0.35, 0.35, 0.35],
  autoPage: false,
});

// Pricing boxes
slide8.addText("Pricing Tiers", {
  x: 0.5, y: 3.4, w: 9, h: 0.35,
  fontSize: 13, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

const tiers = [
  { name: "Basic", price: "UGX 50K/mo", usd: "$13.50", color: C.blue, desc: "Manual entry, CSV import, attendance, parent portal" },
  { name: "Pro", price: "UGX 100K/mo", usd: "$27.00", color: C.green, desc: "AI Excel import, WhatsApp alerts, analytics, push notifications" },
  { name: "Enterprise", price: "UGX 150K/mo", usd: "$40.50", color: C.amber, desc: "Register scanning, full AI suite, custom branding, SSO" },
];

tiers.forEach((t, i) => {
  const x = 0.4 + i * 3.15;
  slide8.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y: 3.8, w: 2.95, h: 1.2,
    fill: { color: C.offWhite }, rectRadius: 0.08,
    line: { color: t.color, width: 1.5 },
  });
  slide8.addText(t.name, {
    x: x + 0.15, y: 3.85, w: 1.0, h: 0.3,
    fontSize: 12, fontFace: FONT_TITLE, color: t.color, bold: true, margin: 0,
  });
  slide8.addText(t.price, {
    x: x + 0.15, y: 4.1, w: 1.6, h: 0.25,
    fontSize: 14, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
  });
  slide8.addText(`(${t.usd})`, {
    x: x + 1.7, y: 4.12, w: 1.1, h: 0.22,
    fontSize: 9, fontFace: FONT_BODY, color: C.textGray, margin: 0,
  });
  slide8.addText(t.desc, {
    x: x + 0.15, y: 4.45, w: 2.65, h: 0.45,
    fontSize: 8.5, fontFace: FONT_BODY, color: C.textGray, margin: 0,
  });
});

addFooter(slide8, 8);

// ══════════════════════════════════════════════════
// SLIDE 9 — GO-TO-MARKET
// ══════════════════════════════════════════════════
let slide9 = pres.addSlide();
slide9.background = { color: C.offWhite };
addSectionTitle(slide9, "How We Grow");

slide9.addText([
  { text: "Land: Free Starter tier for small schools (up to 200 students). Zero-risk trial.", options: { bullet: true, breakLine: true } },
  { text: "Expand: Schools upgrade as they grow. Pro and Enterprise tiers add AI, analytics, custom branding.", options: { bullet: true, breakLine: true } },
  { text: "Network effects: Every parent onboarded is a referral. Parents with children at multiple schools demand KlassApp.", options: { bullet: true, breakLine: true } },
  { text: "LIN integration: Government mandate means every student has an ID. We connect it to WhatsApp — at national scale.", options: { bullet: true, breakLine: true } },
  { text: "Partnerships: Ministry of Education, school associations, education NGOs, mobile money providers, School ERPs, and SchoolPay.", options: { bullet: true, breakLine: true } },
  { text: "Low CAC: Word-of-mouth through teachers and parents. WhatsApp groups are organic distribution channels.", options: { bullet: true } },
], {
  x: 0.5, y: 1.4, w: 5.5, h: 3.5,
  fontSize: 12.5, fontFace: FONT_BODY, color: C.textDark, margin: 0,
  lineSpacingMultiple: 1.35, paraSpaceAfter: 12,
});

// Growth flywheel visual
slide9.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 6.5, y: 1.3, w: 3.2, h: 1.7,
  fill: { color: C.white }, rectRadius: 0.1,
  shadow: { type: "outer", color: "000000", blur: 3, offset: 1, angle: 135, opacity: 0.06 },
});

const flywheelSteps = [
  "Free schools onboard",
  "Parents engaged on WhatsApp",
  "Parents become advocates",
  "More schools join",
];

flywheelSteps.forEach((step, i) => {
  const y = 1.4 + i * 0.38;
  slide9.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 7.2, y, w: 0.22, h: 0.22,
    fill: { color: C.green }, rectRadius: 0.11,
  });
  slide9.addText(`${i + 1}`, {
    x: 7.2, y, w: 0.22, h: 0.22,
    fontSize: 9, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", valign: "middle", margin: 0,
  });
  slide9.addText(step, {
    x: 7.55, y, w: 2.0, h: 0.22,
    fontSize: 10, fontFace: FONT_BODY, color: C.textDark, margin: 0,
  });
  if (i < 3) {
    slide9.addText("↓", {
      x: 7.25, y: y + 0.2, w: 0.15, h: 0.18,
      fontSize: 10, color: C.midGray, align: "center", margin: 0,
    });
  }
});

// Target box
slide9.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 6.5, y: 3.4, w: 3.2, h: 1.1,
  fill: { color: C.dark }, rectRadius: 0.1,
});
slide9.addText("18-Month Target", {
  x: 6.5, y: 3.5, w: 3.2, h: 0.3,
  fontSize: 10, fontFace: FONT_BODY, color: C.midGray, align: "center", margin: 0,
});
slide9.addText("200 schools", {
  x: 6.5, y: 3.8, w: 3.2, h: 0.25,
  fontSize: 18, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", margin: 0,
});
slide9.addText("$53K ARR  ·  $35K net", {
  x: 6.5, y: 4.1, w: 3.2, h: 0.25,
  fontSize: 10, fontFace: FONT_BODY, color: C.green, align: "center", margin: 0,
});

addFooter(slide9, 9);

// ══════════════════════════════════════════════════
// SLIDE 10 — TEAM & ASK
// ══════════════════════════════════════════════════
let slide10 = pres.addSlide();
slide10.background = { color: C.white };
addSectionTitle(slide10, "Team & The Ask");

// Left: Team
slide10.addText("Team", {
  x: 0.5, y: 1.3, w: 4.5, h: 0.35,
  fontSize: 16, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

slide10.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 0.5, y: 1.75, w: 4.5, h: 1.3,
  fill: { color: C.offWhite }, rectRadius: 0.08,
});
slide10.addText("Mucunguzi Moses — Founder", {
  x: 0.7, y: 1.85, w: 4.1, h: 0.3,
  fontSize: 13, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});
slide10.addText("2x Founder. 5 years engineering experience. Deep understanding of Uganda's education and payments ecosystem.", {
  x: 0.7, y: 2.15, w: 4.1, h: 0.4,
  fontSize: 10, fontFace: FONT_BODY, color: C.textGray, margin: 0,
});

slide10.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 0.5, y: 3.2, w: 4.5, h: 0.8,
  fill: { color: C.offWhite }, rectRadius: 0.08,
});
slide10.addText("Mugisha Elijah — Co-founder & CTO", {
  x: 0.7, y: 3.3, w: 4.1, h: 0.3,
  fontSize: 12, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});
slide10.addText("Fullstack engineer. Built the KlassApp platform from the ground up. Leads technical architecture and product.", {
  x: 0.7, y: 3.6, w: 4.1, h: 0.3,
  fontSize: 10, fontFace: FONT_BODY, color: C.textGray, margin: 0,
});

// Right: The Ask
slide10.addText("The Ask", {
  x: 5.5, y: 1.3, w: 4, h: 0.35,
  fontSize: 16, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

slide10.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 5.5, y: 1.75, w: 4.0, h: 0.55,
  fill: { color: C.dark }, rectRadius: 0.08,
});
slide10.addText("Raising $30K (UGX 100M) — Pre-Seed", {
  x: 5.5, y: 1.75, w: 4.0, h: 0.55,
  fontSize: 14, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", valign: "middle", margin: 0,
});

slide10.addText("Use of Funds", {
  x: 5.5, y: 2.5, w: 4.0, h: 0.3,
  fontSize: 12, fontFace: FONT_TITLE, color: C.dark, bold: true, margin: 0,
});

const alloc = [
  ["50%", "Sales & School Onboarding — dedicated onboarding team"],
  ["20%", "AI, Infra, Integrations & Security"],
  ["20%", "Legal, Compliance & Operations"],
  ["10%", "Operations & Working Capital"],
];

alloc.forEach((a, i) => {
  const y = 2.85 + i * 0.45;
  slide10.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 5.5, y, w: 0.8, h: 0.35,
    fill: { color: i === 0 ? C.green : i === 1 ? C.blue : i === 2 ? C.amber : C.midGray },
    rectRadius: 0.05,
  });
  slide10.addText(a[0], {
    x: 5.5, y, w: 0.8, h: 0.35,
    fontSize: 10, fontFace: FONT_TITLE, color: C.white, bold: true, align: "center", valign: "middle", margin: 0,
  });
  slide10.addText(a[1], {
    x: 6.45, y, w: 3.05, h: 0.35,
    fontSize: 9.5, fontFace: FONT_BODY, color: C.textDark, margin: 0, valign: "middle",
  });
});

// Target bar at bottom
slide10.addShape(pres.shapes.ROUNDED_RECTANGLE, {
  x: 0.5, y: 4.6, w: 9.0, h: 0.45,
  fill: { color: C.offWhite }, rectRadius: 0.06,
  line: { color: C.green, width: 1 },
});
slide10.addText("Target: 200 schools in 18 months  →  $53K ARR  →  $35K net  →  Path to Series A", {
  x: 0.5, y: 4.6, w: 9.0, h: 0.45,
  fontSize: 11, fontFace: FONT_BODY, color: C.dark, align: "center", valign: "middle", margin: 0, bold: true,
});

addFooter(slide10, 10);

// ══════════════════════════════════════════════════
// SLIDE 11 — CLOSING
// ══════════════════════════════════════════════════
let slide11 = pres.addSlide();
slide11.background = { color: C.dark };

slide11.addShape(pres.shapes.RECTANGLE, {
  x: 0, y: 0, w: 10, h: 0.06,
  fill: { color: C.green },
});

slide11.addText("Thank You", {
  x: 0.8, y: 1.3, w: 8.4, h: 1.0,
  fontSize: 48, fontFace: FONT_TITLE, color: C.white, bold: true, margin: 0,
  charSpacing: 3,
});

slide11.addShape(pres.shapes.RECTANGLE, {
  x: 0.8, y: 2.4, w: 1.6, h: 0.05,
  fill: { color: C.green },
});

slide11.addText("Let's put a school in every parent's pocket.", {
  x: 0.8, y: 2.7, w: 8.4, h: 0.6,
  fontSize: 20, fontFace: FONT_TITLE, color: C.midGray, margin: 0,
});

// Contact info
slide11.addText([
  { text: "KlassApp — Smarter schools start here.", options: { breakLine: true, fontSize: 13 } },
  { text: "", options: { breakLine: true, fontSize: 8 } },
  { text: "klassapp.xyz  ·  hello@klassapp.xyz  ·  WhatsApp: +256 767 538805", options: { fontSize: 11, color: C.textGray } },
], {
  x: 0.8, y: 3.8, w: 8.4, h: 1.0,
  fontSize: 11, fontFace: FONT_BODY, color: C.midGray, margin: 0,
});

slide11.addText("Confidential — June 2026", {
  x: 0.8, y: 4.8, w: 8.4, h: 0.35,
  fontSize: 9, color: C.textGray, fontFace: FONT_BODY, margin: 0,
});

// ══════════════════════════════════════════════════
// SAVE
// ══════════════════════════════════════════════════
pres.writeFile({ fileName: "/Users/mac/projects/KlassApp/docs/KlassApp_Pitch_Deck.pptx" })
  .then(() => console.log("✅ Pitch deck saved to docs/KlassApp_Pitch_Deck.pptx"))
  .catch((err) => console.error("❌ Error:", err));

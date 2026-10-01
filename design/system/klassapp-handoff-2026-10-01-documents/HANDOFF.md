# Handoff: formal document template, 2026-10-01

This template covers policies, terms, data processing agreements (DPAs), letters and invoices, on **A4 and US Letter**. There are two builds:
1. **HTML/PDF:** the design-system template `templates/formal-document/FormalDocument.dc.html`. It has switches for paper (A4 or Letter), draft (on or off) and **signed** (on or off; section 12), and the sample is a 3-page DPA.
2. **Word:** a `.dotx` built from the spec below, so staff can write documents in Word with the same look.

**Not measured:** contrast ratios are calculated from hex. Page fit is designed to the smaller of each dimension (210mm wide, A4; 279.4mm tall, Letter), but **I didn't measure it in this pass.** The design-system check will confirm it.

## 1. Page
| | A4 | US Letter |
|---|---|---|
| Sheet | 210 × 297 mm | 215.9 × 279.4 mm (8.5 × 11 in) |
| Margins: left, right | 18 mm | 18 mm (0.71 in) |
| Margins: top, page 1 | 16 mm | 16 mm |
| Margins: top, later pages | 12 mm (running header) + 8 mm gap | same |
| Margin: bottom | 14 mm (footer sits inside) | same |
| Text width | 174 mm | 179.9 mm |

**Same layout on both sizes:** one layout serves both papers. Nothing is positioned from the bottom except the footer, so Letter's shorter sheet only shortens the text block.

## 2. Type
Word can't use a font stack, so each Word style gets **one primary font** plus a **substitute** to set if the font is missing.
- **Embedding:** **embed Sora and DM Sans in the `.dotx`** (File → Options → Save → *Embed fonts in the file*, with *Do not embed common system fonts* checked). Both are SIL OFL fonts, which allows embedding.
- **Without embedding:** in an environment that blocks embedded fonts, use the Word fallback column.

| Role (Word style) | HTML font stack | Word: primary → fallback | Size | Weight | Colour | Space before / after | Line spacing |
|---|---|---|---|---|---|---|---|
| Body (`Normal`) | `'DM Sans', Calibri, Arial, sans-serif` | DM Sans → **Calibri** | 10.5 pt | Regular | #1E293B | 0 / 6 pt | 1.5 (multiple 1.25 in Word ≈ 15.75 pt) |
| Title (`Title`) | `Sora, 'Segoe UI', Arial, sans-serif` | Sora → **Segoe UI** (Mac: **Arial**) | 24 pt | Bold | #0F172A | 0 / 12 pt | 1.15 |
| Eyebrow above the title (`KA Eyebrow`) | DM Sans | DM Sans → Calibri | 9 pt, all caps, 8% tracking (Word: Expanded 0.7 pt) | Bold | #15803D | 0 / 6 pt | single |
| Lede (`Subtitle`) | DM Sans | DM Sans → Calibri | 11.5 pt | Regular | #334155 | 0 / 20 pt | 1.5 |
| `Heading 1` | Sora | Sora → Segoe UI Semibold (Mac: Arial Bold) | 14 pt | Semibold (600) | #0F172A; the number in **#15803D** | 18 / 7 pt | 1.25 |
| `Heading 2` | Sora | as Heading 1 | 11 pt | Semibold | #0F172A | 11 / 4 pt | 1.3 |
| `Heading 3` | DM Sans | DM Sans → Calibri | 10.5 pt | Bold | #0F172A | 8 / 2 pt | 1.3 |
| `List Bullet`, `List Number` | DM Sans | DM Sans → Calibri | 10.5 pt | Regular | #1E293B | 0 / 3 pt | 1.5; indent 6 mm, hanging 4 mm |
| `Caption` (table and figure) | DM Sans | DM Sans → Calibri | 8.5 pt | Regular | #475569 | 4 / 16 pt | single |
| `Header`, `Footer` | DM Sans | DM Sans → Calibri | 8 pt | Regular; the document title is Semibold #0F172A | #475569 | 0 / 0 | single |
| Labels: signature, parties (`KA Label`) | DM Sans | DM Sans → Calibri | 8.5 pt | Bold, all caps, 6% tracking | #475569; "For KlassApp" in #15803D | 0 / 2 pt | single |

**Heading numbers:** use multilevel list numbering "1." / "1.1" attached to Heading 1 and 2, with the number styled #15803D through the list level's font.

## 3. Colours (hex only; no theme colours in the `.dotx`)
| Use | Hex | Contrast on white |
|---|---|---|
| Headings, title, table header text | #0F172A | 17.9:1 |
| Body text | #1E293B | 14.6:1 |
| Lede | #334155 | 10.4:1 |
| Header, footer, captions, labels | #475569 | 7.6:1 |
| Accent: heading numbers, eyebrow, rule under the first-page header | #15803D | 5.0:1 |
| Hairlines, row rules | #E2E8F0 | (rule only) |
| Table header rule, signature line | #0F172A | (rule only) |
| Table header fill | #F1F5F9 | #0F172A on it: 16.3:1 |
| Note callout | text #1E3A8A on #EFF6FF, border #BFDBFE | 9.9:1 |
| Draft band | text #78350F on #FEF3C7, border #FDE68A | 9.4:1 |
| Draft watermark | #FDE68A at 55% opacity | (decorative) |

In Word, set these as **custom colours** in each style; don't remap the Office theme. The template must look the same on any machine.

## 4. First page
- **Header:** different from later pages (Word: *Different first page*).
  - **Left:** the **stacked logo** `klassapp-stacked-light.svg` (icon above the wordmark), 19 mm tall (about 31 mm wide). In Word, insert the PNG export at 600 dpi. The SVG renders inconsistently in older Word.
  - **Right:** a two-column metadata block, 8.5 pt: **Document**, **Reference**, **Version**, **Effective**. The labels are #475569 and the values #0F172A. The values come from Word document properties (section 7).
  - **Rule:** a 1.5 pt #15803D rule under the whole header, 5 mm below the logo.
- **Title block, 9 mm below the rule:** the eyebrow (document type), Title, Lede.
- **Parties** (agreements only): two boxes side by side, Processor/Controller or Provider/School. Each has a 1px #E2E8F0 border, a 6 px radius (square corners in Word) and 4 × 5 mm padding.
  - Letters replace this with the addressee block.
  - Invoices replace it with Bill to and Invoice details (section 8).

## 5. Later pages: running header and footer
- **Running header:**
  - **Left:** the KlassApp icon mark (5 mm) and the document title, in Semibold #0F172A.
  - **Right:** "Version {n}".
  - **Rule:** a 1 px #E2E8F0 rule below it.
  - **Draft:** when on, an amber "DRAFT FOR REVIEW" tag sits before the version.
- **Footer on every page, page 1 included:**
  - **Left:** "KlassApp · {Title} · v{Version} · {Reference}".
  - **Right:** "Page X of Y".
  - **Rule:** a 1 px #E2E8F0 rule above it.
- **Word fields:**
  - title: `{ DOCPROPERTY Title }`
  - version: `{ DOCPROPERTY "KA Version" }`
  - reference: `{ DOCPROPERTY "KA Reference" }`
  - page: `Page { PAGE } of { NUMPAGES }`
- **HTML/PDF:** the template is explicitly paginated, with "Page X of Y" written on each sheet. For long generated documents, build them as one **flowing** `<doc-page>` with `slot="header"` and `slot="footer"`, and get X of Y from the PDF renderer. In Browsershot or Chrome headless, use `headerTemplate`/`footerTemplate` with `<span class="pageNumber">` and `<span class="totalPages">`; dompdf's page-script equivalent works too. **The current invoice and report PDFs use dompdf.** Check which renderer before choosing.

## 6. Tables (`KA Table`, a Word table style)
- **Width:** full text width. Cell padding 2.2 mm top and bottom, 3 mm left and right (Word: 1.5 mm/0.06 in default cell margins top and bottom, 3 mm left and right).
- **Header row:** fill #F1F5F9, text 9.5 pt Bold #0F172A, with a **1.5 pt #0F172A rule above** and a 1 px #CBD5E1 rule below. Mark it *Repeat as header row* so it repeats on every page.
- **Body rows:** 9.5 pt #1E293B, with a 1 px #E2E8F0 rule below each row and a **1.5 pt #0F172A rule under the last row**.
- **No vertical rules and no zebra stripes.**
- **Numbers:** right-aligned and tabular (DM Sans has tabular figures; in Word, set *Number spacing: Tabular*).
- **Rows:** *Allow row to break across pages* is **off**.
- **Caption:** below the table, "Table n. …", in the `Caption` style.

## 7. Draft for review
- **What's affected:**
  - **Page 1:** a full-width amber band above the header: "**DRAFT FOR REVIEW**", then "Not binding until signed. Comments to {email} by {date}".
  - **Later pages:** the amber tag in the running header.
  - **Every page:** a diagonal "DRAFT" watermark, 64 pt Sora Bold, #FDE68A at 55%, rotated 30°.
- **Word:** a custom property `KA Status` = "Draft" or "Final".
  - **Banner and tag:** use `{ IF { DOCPROPERTY "KA Status" } = "Draft" "DRAFT FOR REVIEW" "" }` fields, so updating the property (Ctrl A, F9) removes them.
  - **Watermark:** a separate step: Design → Watermark → Custom → "DRAFT", Sora Bold, #FDE68A, semitransparent, diagonal. **Remove it by hand when finalising.** A "Finalise" checklist item covers this (section 10).
- **HTML:** the template's `draft` prop.
- **Never** send a final document with the draft state on. The PDF generator should refuse `status=final` when `draft=true`.

## 8. Variants (same styles, different first-page block)
| Type | First-page block below the title | Extra parts |
|---|---|---|
| Policy | Lede plus "Owner / Applies to / Review date" metadata | Version history table at the end |
| Terms | Lede plus effective date | Contents list after the lede, when longer than 4 pages |
| DPA | Parties boxes | Sub-processor table, signature blocks |
| Letter | Addressee block (left), date and reference (right), "Dear {name}," | Single signature block on the left, no running-header title |
| Invoice | Bill to (left), with Invoice no., Issue date, Due date and Currency (right) | Line-item table (`KA Table`, numbers right-aligned), a totals block right-aligned with the total in Bold 12 pt, and payment details |

**Currency:** invoices show the school's own currency code (UGX, KES, USD…) from settings. Never hard-code one.

## 9. Signature blocks
- **Layout:** two columns, 10 mm apart. Each column stacks:
  - the party label in #15803D caps;
  - a **16 mm** signature space with a 1 px #0F172A line;
  - then Name, Title and Date, each a 6 mm line with a 1 px #CBD5E1 rule and an 8.5 pt #475569 label beneath.
- **Keep together:** the block never breaks across pages (Word: *Keep with next* on every paragraph in the block, or a single-row table with *Allow row to break across pages* off).
- **Letters** use one column, on the left.

## 10. `.dotx` build checklist
1. Start from a blank document. Set the page size and margins for A4 (section 1). Make a **second `.dotx` for Letter**; Word templates can't switch paper by themselves.
2. Create or modify the styles exactly as in section 2: `Normal`, `Title`, `Subtitle`, `Heading 1`–`3`, the two list styles, `Caption`, `Header` and `Footer`, plus the custom `KA Eyebrow` and `KA Label`. Base everything on `Normal`.
3. Attach a multilevel list to Heading 1 and 2, with the number in #15803D.
4. Create the `KA Table` table style (section 6). Set it as the default table style for the template.
5. **Insert → Header:** turn on *Different first page*. Build the first-page header (section 4) and the later-page header and footer (section 5) with the fields.
6. **File → Info → Properties → Advanced → Custom:** add `KA Version` (text), `KA Reference` (text) and `KA Status` (text: Draft or Final). Set Title under Summary.
7. Add a building block (Quick Parts) for each: the signature block, the parties block, the note callout, the version history table and the invoice totals.
8. Embed the fonts (section 2) and save as `KlassApp-Document-A4.dotx` and `KlassApp-Document-Letter.dotx`.
9. **Finalise** (a note on page 1 of the template, deleted in use): set `KA Status` to Final, update the fields, remove the watermark, and check that "Page X of Y" is right.

**Accept:**
- **Font check:** the `.dotx` opens on a machine without Sora or DM Sans and looks the same (fonts embedded). With embedding off, the fallbacks apply with no layout break.
- **Fields:** changing `KA Version` and updating the fields changes it in the header, footer and page-1 metadata.
- **Header row:** a 3-page table repeats its header row.
- **Signatures:** the signature block never splits across pages.
- **PDF export:** PDF/A-1b export works.

## 11. HTML build (in the app, optional)
**Generated documents:** generate documents (invoices, DPAs) from Blade with the same styles. Use the hex values above, not CSS variables, because the PDF renderer may not resolve them (the same rule as the ID card).

**Accept:**
- A4 and Letter PDFs of the 3-page sample, before and after, with nothing clipped and the footer on every page.
- "Page X of Y" is correct on a generated 5-page invoice.

## 12. Digital acceptance: click-to-accept and the signed PDF (added 2026-10-01)
Contracts (DPA first; Terms and other agreements later) are accepted **digitally inside KlassApp**. Nobody signs by hand. **Concepts:**
- **In-app:** `concepts/contracts/dpa-accept.html`, with five states: read and accept, errors, accepted, not the main admin, and new version.
- **PDF:** the formal template's new **`signed`** state.

### 12.1 The accept page (Settings → Agreements → {agreement}, also linked from setup)
- **Header:** the eyebrow "Agreement · Version {n}", the title, then reference, effective date and estimated reading time. There's a **Download PDF** button (the unsigned version).
- **Agreement text:** the **full agreement rendered on the page** in a white card, 15.5px/1.65, with the same numbered headings and table style as the PDF.
  - Don't use a small inner scroll box.
  - **No scroll-to-end gate:** it fails with screen readers and zoom.
- **Accept form:**
  - **Placement:** a sticky right column (380px) at 1280. At 375 it sits below the text, with a **Go to acceptance** link at the top.
  - **Fields:**
    1. **Checkbox** (a 22px box in a 44px+ label row): "I have read this agreement and accept it on behalf of **{school name}**." The school name comes from settings.
    2. **Your full name:** text, `autocomplete="name"`, **not prefilled**. Hint: "Type it as it should appear on the agreement. This is your electronic signature."
    3. **Your title:** text, `autocomplete="organization-title"`. Hint: "Your role at the school, for example Head teacher or Director."
    4. **Accept agreement:** primary, 48px, full width, **never disabled**.
    5. Small print: "We record your name, your title, the date and time, and your account with this acceptance. You'll get the signed PDF by email."
  - **Errors** (after pressing, not while typing):
    - an error summary at the top of the form ("2 things to fix before you can accept.", `role="alert"`, which takes focus);
    - each field gets a red border, `aria-invalid` and a message linked with `aria-describedby`;
    - the messages are "Tick the box to confirm you accept." and "Type your full name." (title too).
- **Accepted (on the same page):** a green check, "Agreement accepted", and the acceptance record: Name, Title, School, Version, Date and time, Reference, Acceptance ID. Then **Download signed PDF** (primary), **Back to setup**, and "A copy was emailed to {email}."
- **Not the main admin:** the text is readable and downloadable, but there's no form. A note says "Only the school's main admin, **{name}**, can accept agreements for {school}." The main admin gets a notification once.
- **New version:** an amber note: "**Version {new} replaces {old}.** Changes: …", listing the sections changed. Then "Version {old} stays in force until you accept {new} or until {date}." **Your call:** the grace period, and what happens at the date (keep {old}, or restrict).
- **Who can accept:** the school's **main admin** only (the SchoolAdmin who owns the school), the same rule as bank details.
- **Gating (your call):** recommended: setup shows "Accept the data processing agreement" as a step, and **adding the first student is blocked until the DPA is accepted**, since that's the first personal data.

### 12.2 What's stored
**`agreement_versions`:**
- columns: `id`, `type` (`dpa`, `terms`…), `version`, `reference`, `published_at`, `effective_at`, `html_path`, `pdf_path`, `sha256` (of the published text);
- **published versions can't be edited.** A change is a new version.

**`agreement_acceptances`** (insert-only; no update or delete in the app, enforced by policy and a DB trigger or revoked privileges):

| Column | Value |
|---|---|
| `public_id` | e.g. `ACC-7F3K-92QD`, shown to users |
| `school_id` | the school accepting |
| `agreement_version_id` | the version accepted, and `document_sha256` (copied at acceptance) |
| `user_id` | the account that accepted |
| `typed_name` | exactly as typed |
| `typed_title` | exactly as typed |
| `school_name_snapshot` | the school name at the moment of acceptance |
| `accepted_at` | **UTC**, plus `school_timezone` (shown as "1 October 2026, 14:32 (UTC+03:00)", from school settings; never assume a country) |
| `ip`, `user_agent` | of the request that accepted |
| `method` | `click_to_accept` |
| `signed_pdf_path`, `signed_pdf_sha256` | the generated signed PDF and its hash |

### 12.3 The signed PDF (formal template, `signed` state)
**Generated once, server-side, immediately after acceptance** (queued job). It's built from the accepted version's HTML plus the acceptance record, then stored, hashed and emailed. **It's never regenerated with different content.** If the template changes later, old PDFs stay as issued.

**Changes from the unsigned document:**
- **Page 1:** a green band replaces the draft band: "**ACCEPTED ELECTRONICALLY**", then "{date, time (UTC±hh:mm)} · {Acceptance ID}" (#14532D on #F0FDF4, border #BFDBFE→**#BBF7D0**, 9pt).
- **Running header:** a green "ACCEPTED {D MON YYYY}" tag before the version.
- **The draft state is forced off:** `signed` overrides `draft`.
- **The signature section becomes "{n}. Acceptance":** a statement ("This agreement was accepted electronically in KlassApp by an authorised representative of the School…"), then two boxes:
  - **Offered by KlassApp:** 1px #E2E8F0 border. Entity, Document, Version, Published.
  - **Accepted for the School:** 1.5pt **#15803D** border. **Name**, **Title**, **School**, **Version**, **Date and time** (with UTC offset), **Reference**, **Acceptance ID** (monospace), **Method** ("Click-to-accept in KlassApp, signed in as {email}").
  - **Both boxes:** labels in #475569 and values in #0F172A, 9.5pt. Never split across pages.
  - **Fingerprint line**, 8.5pt: "Document fingerprint (SHA-256 of the version accepted): {hash, in groups of 4}", plus "KlassApp keeps the acceptance record for as long as the agreement is in force. Keep this PDF with the School's records."
- **Word:** signed PDFs are produced by the app only. The `.dotx` keeps the handwritten signature block (section 9) for agreements signed outside KlassApp.
- **Later option (not in scope): a PAdES digital signature** applied to the PDF with a KlassApp certificate, so tampering is detectable in PDF readers. The hash line is the interim integrity check.

### 12.4 Legal check before launch
Click-to-accept with a typed name is an ordinary electronic signature. **Have counsel confirm it's sufficient for a DPA in each market you sell into**, and whether any market needs a qualified or advanced signature. Don't name markets in product copy (brand rule).

### PRs
**DA1 · `feat(agreements): versions + acceptance records`:** the tables, the policy (main admin only), the insert-only guard, and the endpoints `GET /settings/agreements/{type}` and `POST …/accept`.
- **Accept:**
  - A non-main-admin gets 403 on POST.
  - A missing tick, name or title gets 422 with field errors.
  - A second acceptance of the same version is a no-op that returns the existing record.
  - A test asserts the row can't be updated.

**DA2 · `feat(agreements): accept page`:** section 12.1, all five states.
- **Accept:**
  - Screenshots at 1280 and 375.
  - Every control ≥ 44px; no sideways scroll.
  - A keyboard-only accept works.
  - On error, focus moves to the summary.

**DA3 · `feat(documents): signed PDF`:** section 12.3. The queued job, storage, hash and email.
- **Accept:**
  - A PDF on A4 and Letter shows the acceptance block with the stored values, word for word.
  - `signed_pdf_sha256` matches the stored file.
  - A second request returns the stored file, not a regenerated one.
  - The draft band never appears on a signed PDF.

**DA4 · `feat(agreements): new version flow`:** the notice, the grace date and re-acceptance.
- **Accept:** publishing 1.3 shows the notice to the main admin only. Accepting 1.3 keeps the 1.2 record and PDF intact.

## Brand rule (applies to this and all later work)
KlassApp is a **global** product for schools from **nursery to secondary**:
- No country-only or level-only framing in marketing, blog, social or docs designs.
- Templates use neutral sample names ("Demo Junior School", "Demo Senior School"), and currency comes from settings.
- The founding-schools offer has **no stated limit**: never write "first N schools".

# Handoff: default avatar (initials) for `<x-profile-photo>`, 2026-09-30

- **Scope:** one small PR that extends `<x-profile-photo>` (PR6 in `handoff-2026-09-27-fixes.md`).
- **Branch:** `feat/ds-avatar-initials`, off the latest `main`. Record the SHA in the PR description.
- **Colour tokens:** exist in the design system (`tokens/colors.css`), not yet in the repo. Add them upstream.
- **Card:** `guidelines/brand-avatar-default.card.html`.
- **Not browser-measured:** all contrast ratios below are calculated from hex.

## 1. When it shows
Show initials only when the photo test from PR6 is false: `$user->userprofile && $user->userprofile->avatar != null`.

This replaces the `default-user.jpg` fallback in the component. **Don't delete the file yet:** the ID card and bus pass still reference it. Removing it is follow-up F1.

## 2. Initials (decided 2026-09-30)
**Initials are the given name's first letter, then the surname's first letter.** Use the separate given-name and surname fields wherever they exist; the admission form stores them separately.

**Fallback:** only when a user type has no separate fields, split `FullName`:
- the first letter of the first word, plus the first letter of the last word;
- uppercased with `mb_strtoupper`/`mb_substr`, so "Élise" works.

**Why fields come first:** Ugandan names are often written surname first. With the fields, "Mugisha" (surname) and "John" (given name) give **JM**. Splitting "Mugisha John" by position gives **MJ**, the wrong order. The fallback is a known approximation; don't guess the order from the string.

**Open:** the coding agent is confirming which fields exist for **staff, students and parents**. Record the answer per type in the PR description, and use the fields for every type that has them.

| Input | Initials |
|---|---|
| given "John", surname "Mugisha" | **JM** |
| given "Sarah Achieng", surname "Nakato" | **SN** (first word of the given names only) |
| only `FullName` "Nakato Sarah Achieng" | **NA** (fallback: first and last words) |
| only one word, "Mugisha" | **M** |
| no name at all | no letters: see section 4 |

- **Never** derive initials from an email address or phone number, because that leaks personal data into the UI.

## 3. Colour: stable, from the id
Pick the colour with `$user->id % 4`:

| id % 4 | Token | Hex | White letters |
|---|---|---|---|
| 0 | `--d-avatar-1` → `--d-blue` | #1E6FD9 | 4.85:1 |
| 1 | `--d-avatar-2` → `--d-accent` | #15803D | 5.02:1 |
| 2 | `--d-avatar-3` → `--d-warning` | #B45309 | 5.02:1 |
| 3 | `--d-avatar-4` → `--d-dark-surface` | #1E293B | 14.63:1 |

- **Why the id, not a hash of the name:** the colour stays the same when a name is corrected, and every page computes the same colour with no extra state.
- **Excluded:** red (`--d-red`) reads as an error, and there's no pink/blue gender coding. All four colours are existing tokens, aliased; no new colours are added.

## 4. No name
- **What shows:** a blank `--d-avatar-none` (#64748B) tile with no letters and no image.
- **Markup:** `aria-hidden="true"`, and **no silhouette or person image of any kind**. The image the component used to fall back to, `default-user.jpg`, has never been checked for this: I haven't viewed it, so I can't say whether it's gendered.
- **Null user:** `$user` null (e.g. a deleted parent on feedback) is treated the same way.

## 5. Sizes

| Where | Size | Shape | Letters | Implementation |
|---|---|---|---|---|
| Nav trigger (`profile-dropdown`) | 32 | **circle** + 2px `--d-avatar-ring` | 13px | `<x-profile-photo size="xs" shape="circle">` |
| Table rows | 32 | square, radius 8px | 13px | `size="xs"` (**new size**) |
| Lists, dropdown header `.user-avatar` | 40 | square, 12px | 16px | `size="sm"` |
| Feedback view | 64 | square, 12px | 26px | `size="md"` |
| Member/Teacher/Staff profile | 128 / 192 | square, 12px | 51 / 77px | `size="lg"` / `"xl"` |
| ID card (print) | 100 | square, 12px | 40px | **inline hex, not the component** |
| Bus pass (print) | 75 | square, 12px | 30px | **inline hex, not the component** |
| Report card | none | none | none | **I found no photo in the report templates.** If one is added, follow the print rule at 80px / 32px letters. |

- **Letter size:** `round(size × 0.4)`, Sora 600, letter-spacing 0.02em, line-height 1.
- **The new `xs` (32) size:** at 32px the 12px radius looks like a circle, so `xs` squares use **8px**. Circle shape is still reserved for the nav trigger.
- **Print templates:** keep the inline-style rule from PR6, because the PDF renderer may not resolve CSS variables. Use `style="background:#1E6FD9;color:#FFFFFF"` etc. from the table in section 3, chosen by the same `id % 4`.

## 6. Markup
```blade
{{-- resources/views/components/profile-photo.blade.php (initials branch) --}}
@php
  $has = $user && $user->userprofile && $user->userprofile->avatar != null;
  // Field names are placeholders until confirmed per user type (§2).
  $given   = trim($user?->given_name ?? '');
  $surname = trim($user?->surname ?? '');
  if ($given !== '' || $surname !== '') {
      $g = preg_split('/\s+/u', $given, -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';
      $ini = mb_strtoupper(mb_substr($g, 0, 1) . mb_substr($surname, 0, 1));
  } else {
      $words = preg_split('/\s+/u', trim($user?->FullName ?? ''), -1, PREG_SPLIT_NO_EMPTY);
      $ini = $words ? mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : '')) : '';
  }
  $px = ['xs'=>32,'sm'=>40,'md'=>64,'lg'=>128,'xl'=>192][$size];
  $bg = $ini ? 'var(--d-avatar-'.(($user->id % 4) + 1).')' : 'var(--d-avatar-none)';
@endphp
@unless($has)
<span class="ds-avatar ds-avatar--{{ $size }} {{ $shape === 'circle' ? 'ds-avatar--circle' : '' }}"
      style="background:{{ $bg }}"
      @if($ini) role="img" aria-label="{{ $user->FullName }}" @else aria-hidden="true" @endif>{{ $ini }}</span>
@endunless
```

```css
.ds-avatar{display:inline-flex;align-items:center;justify-content:center;flex:none;box-sizing:border-box;
  color:var(--d-avatar-fg);font-family:var(--d-font-display);font-weight:600;letter-spacing:.02em;line-height:1;
  border-radius:var(--d-radius-lg);user-select:none}
.ds-avatar--xs{width:32px;height:32px;font-size:13px;border-radius:8px}
.ds-avatar--sm{width:40px;height:40px;font-size:16px}
.ds-avatar--md{width:64px;height:64px;font-size:26px}
.ds-avatar--lg{width:128px;height:128px;font-size:51px}
.ds-avatar--xl{width:192px;height:192px;font-size:77px}
.ds-avatar--circle{border-radius:50%;box-shadow:0 0 0 2px var(--d-avatar-ring)}
```
The `box-sizing:border-box` is needed because this bundle ships no global reset (see readme, rule 1).

## Acceptance checks
1. **A user with a photo** is unchanged, as a before/after screenshot shows.
2. **Initials:**
   - A user without a photo shows the correct initials at every size in section 5.
   - The same user gets the same colour on every page.
   - `getBoundingClientRect` equals the declared size (32/40/64/128/192) with no 2px drift.
3. **Examples:** the section 2 table as a unit test, covering fields, fallback, one word and an empty name. In particular, given "John" + surname "Mugisha" → **JM**, and an empty name → a blank slate tile with `aria-hidden`.
4. **Accessibility:**
   - The initials tile is announced as the person's name.
   - No page has two announcements for one person. Where the name is printed right next to the avatar, pass `decorative` to render `aria-hidden` instead.
5. **Contrast:** axe shows no contrast failures on the avatar letters.
6. **Print:** a PDF export of the ID card and bus pass for a user without a photo shows initials in the inline hex colour.
7. **Regression checks:**
   - The mobile menu opens exactly once.
   - The Toshi split-layout still collapses and resizes.
   - The sidebar footer still works.
   - Design-system feature tests pass.
8. **knowledge.md** is stamped.

## Decisions (2026-09-30, user)
- **Shape:** match the photo frame everywhere. Square with an 8px radius at 32 and 12px otherwise; round only for the 32px nav button. A photo and its initials never change shape.
- **Name fields:** use the separate given-name and surname fields wherever they exist, and fall back to splitting `FullName` only where they don't (section 2). The coding agent is confirming per user type.
- **`default-user.jpg`:** stays until the ID card and bus pass switch to initials (follow-up F1).
- **Approved as specified:** the four colours, `id % 4`, the grey no-name tile, the sizes, and fixed hex colours for PDFs.

## Follow-up F1 · ID card and bus pass switch to initials, then remove `default-user.jpg`
A separate PR, after this one merges.
1. **Photo test:** in `admin/id-card/id-card-new` L135, `admin/id-card/idcard-print` L107 and `admin/buspass/bus_pass` L115, replace the `default-user.jpg` fallback with an inline-styled initials tile. Use the same photo test.
2. **Tile styles:** ID card 100px with 40px letters; bus pass 75px with 30px letters. Both square with a 12px radius, white Sora 600.
   - **Colour:** `background` is the section 3 hex for `id % 4`, or #64748B with no letters when there's no name.
   - **Fixed colours only:** no CSS variables and no component (the PDF renderer may not resolve them).
   - **Fallback font:** if Sora isn't available to the renderer, fall back to `Arial, sans-serif` bold. Check in the exported PDF.
3. **Remove the old image:** `grep -rn "default-user" resources/ app/ packages/ public/`. When the only hits left are the file itself (and the commented-out `buspass/print` L94, which stays untouched), delete `public/uploads/user/avatar/default-user.jpg`.

**Accept:**
- A PDF export of the ID card and bus pass, before and after, for a user with a photo (unchanged) and without one (initials).
- The `grep` is clean apart from the commented-out line.
- knowledge.md is stamped.

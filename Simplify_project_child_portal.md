# Simplify Project — Part 2: Child Portal (Usage & Experience)

This document extends [Simplify_project.md](./Simplify_project.md). Part 1 focuses on parent setup, device registration, quizzes, and policy apply. **Part 2** focuses on the **child captive portal**: making usage easier to navigate while staying **interactive and visually engaging** for children, without adding complexity that blocks quick access to learning activities.

---

## Goal

- Make the child portal **easy to understand and navigate** for young users (including ~6 and below where applicable).
- Keep the experience **playful and attention-grabbing** (motion, color, icons, clear hierarchy) while remaining readable and calm enough for sustained use.
- **Primary UX objective:** after the child opens the portal from the **device’s captive / Wi‑Fi flow** (see below), they reach **Start** on **either a recommended quiz or a recommended video** in **exactly two taps on the portal itself** (Quiz or Video → Start), with a clear path to **more choices** when they want variety.

### Captive entry — not a “Sign in” button on the portal page

On many phones and tablets, when time is exhausted the **OS shows a network / captive-portal notification** (wording varies: e.g. **“Sign in to Wi‑Fi”**, **“Open network login page”**, or similar). The child **taps that system notification** (or follows the OS prompt); the browser then **loads the child portal URL**. That system UI is **not** implemented inside the Laravel portal — it is **device/OS chrome**.

**Plan rule:** For **recognized child devices**, do **not** treat **“Sign in to Wi‑Fi”** as a literal primary button that must appear **on** the portal HTML for the happy path. Optional microcopy on the portal may *remind* the child (“Opened from your Wi‑Fi sign-in page?”) if helpful, but the **first meaningful portal screen** is the **Quiz | Video** choice, not a duplicate captive CTA.

---

## Relationship to Part 1

- Part 1’s **`Simplify_project.md`** now includes a short **Child captive portal — first experience** section (OS notification vs portal UI, chooser-first landing); keep it in sync when Part 2 changes.
- Reuse Part 1 principles: **plain language**, **no technical jargon**, **offline-first** on Raspberry Pi (local CSS/JS/fonts for critical portal pages).
- Parent-assigned content (quizzes/videos) remains the source of truth; the child portal only **surfaces** and **prioritizes** what parents already assigned.
- **Quiz content taxonomy** in Part 1 (question bank, quiz create/import) must use the same **age brackets** as this document so assignments and portal filtering stay consistent.

---

## Local-only resources (mandatory)

The child portal must **not depend on the public internet** for anything required to render, navigate, or complete an activity. Until the child earns time, **WAN is often unavailable**; every critical asset and media file must load from the **Laravel app / Raspberry Pi** (same host as the portal).

**Hard rules**

- **Styles and scripts:** only `/public/...` (or equivalent `asset()` paths) bundled with the app — **no** `https://` to third-party CSS/JS (no CDN frameworks, widgets, or polyfill CDNs on portal routes).
- **Fonts:** self-hosted under `public` (or reliable **system font stack** only). **No** Google Fonts, Adobe Fonts, or other remote font URLs on portal views.
- **Images and icons:** SVG/PNG served locally (`public`, `asset()`); **no** hotlinked images or sprite sheets from external hosts.
- **Video:** quiz/video flows must use **files stored on the Pi** (or local network path the app serves). **No** mandatory embedded players that require external hosts to start playback (e.g. no required YouTube/Vimeo iframe for the default path). If optional external links exist later, they must not block the offline default.
- **Analytics and chat:** **no** third-party scripts (Google Analytics, Tag Manager, Intercom, etc.) on child portal pages. If events are logged, use **server-side** or local endpoints only.
- **Captive detection / health checks:** portal HTML must not trigger required fetches to unrelated external domains for the page to function.

**Checklist (implementation + release gate)**

- [ ] Audit all `resources/views/portal/*.blade.php` (and any portal layout) for `http://`, `https://`, `//`, `@import url(...)`, and `<link href>` / `<script src>` — only same-origin or relative URLs for critical UI.
- [ ] Audit `public/css/portal-captive.css` (and any portal JS) for `@import` or `url()` pointing off-host.
- [ ] Add a deployment or CI note: “Child portal: local assets only” with a grep recipe or automated check.
- [ ] Manual test: disable WAN on the Pi, open every portal step (landing → chooser → recommended → quiz/video → result); confirm **no failed network requests** for UI (video file may be the only large local fetch).

---

## Guiding Principles (Child Portal)

- **Local resources only** for everything the child needs to see and tap (see [Local-only resources](#local-only-resources-mandatory)); stylish UI must come from **local CSS**, **inline SVG**, and **bundled assets** — not remote libraries.
- **Two taps on the portal to start:** first tap chooses **Quiz** or **Video**; second tap **Start** on the recommended item (or deep-link equivalent). The OS captive notification that **opens** the portal is outside this count.
- **Big targets:** large buttons, generous spacing, high contrast; avoid dense lists as the default first screen.
- **Focal first screen:** the default view for a known child device must **not** mirror the legacy layout that lists **every assigned quiz** as a full-width grid of cards (see [Landing layout (replace legacy)](#landing-layout-replace-legacy)). The **only** primary choices on first paint are **Quiz** and **Video**, presented as a **compact, centered focal block** that uses **only a fraction of the viewport** (roughly a “card stage” or hero band — not a full-page inventory). Device name / time remaining stay **secondary** (smaller strip, footer, or collapsible), so visual **weight and attention** sit on the two actions.
- **Progressive disclosure:** after **Quiz** or **Video**, show one **recommended** item with **Start**; “See more” / “Pick another” reveals lists (assigned quizzes, videos, subject chips including **Other**, random quiz where applicable).
- **Consistent mental model:** “Earn time” → “Quiz or Video” → “Do it” → “Success / try again” — same words everywhere.
- **Respect attention:** short celebratory feedback after success; avoid loud autoplay audio unless parent-enabled and clearly indicated.

---

## Target Flow: “Two Clicks” Definition

| Step | Child action | What they see |
|------|----------------|-----------------|
| **0** | **OS / device:** captive or Wi‑Fi notification appears; child taps it (or equivalent) so the browser **opens the portal URL** | System notification / browser — **not** a required “Sign in” button rendered on the portal page for known devices. |
| **1** | **Portal — tap 1:** tap **Quiz** *or* **Video** | **Only** those two choices, as the **visual focus**: a **stylish, centered focal region** occupying a **modest fraction** of the screen (not a dense full-viewport grid of activities). Large icons + short labels; plenty of **negative space** around the pair. Device name and time remaining are **peripheral** (small header strip, subtle card, or bottom metadata — not competing with the two CTAs). **No** list of individual quizzes or videos on this step. |
| **2** | **Portal — tap 2:** tap **Start** (or equivalent) on the recommended item | **Recommended** quiz or video: title, reward, primary **Start** (or deep-link to first question / player). Secondary: **More quizzes** / **More videos**. |

**Counting taps (portal only):** Tap 1 = **Quiz** vs **Video**. Tap 2 = **Start** the recommended quiz or recommended video. **“More”** is optional and does not count toward the two-tap promise. **Step 0** is the **device/OS** step that loads the page, not a third portal tap.

### Landing layout (replace legacy)

**Legacy (to remove as default):** A first screen that stacks **“Your Device”** plus a large **“Available Quizzes”** section with a **multi-tile grid of every quiz** (including internal titles like “Random Quiz Mode Settings”) fills the view, pushes cognitive load onto the child, and hides the **Quiz vs Video** mental model.

**Target (default for known child devices):** One **composition-forward** screen: **Quiz** | **Video** as the **hero**, centered, **fraction-of-display** focal layout; **overall visual refresh** (new palette, typography rhythm, shadows/shapes, subtle motion) while staying **local-only** for assets. Individual quizzes, videos, category/**Other**/random-quiz controls appear **only** after the child has chosen **Quiz** or **Video** (or via **More**).

---

## Quiz age brackets (replaces school "level" labels)

Quizzes are classified by **age bracket**, not by labels like “Elementary / High School / Senior High School.” The captive portal **must only recommend and list quizzes** whose bracket **matches** the child’s computed **whole-year age** (see boundary rule). Videos are **not** split by age unless product later adds the same field.

**Important (thesis / ethics wording):** Brackets do **not** claim that every child in a band has the **same IQ** or identical mental ability. They claim **enough overlap in typical cognitive-development patterns** that one **quiz difficulty tier** can be justified for **research and instructional design**, with the usual caveat that **individual differences**, disability, multilingual background, and out-of-school learning must be respected (parent override, accessible items, and questionnaire items should ask about exceptions).

**Minimum age:** **5 years** — the lowest bracket starts at **5** (see **RA 10533** link below for the national **K to 12** structure that includes kindergarten as part of basic education). Ages **under 5** remain out of scope for this quiz module until policy extends downward (younger children sit largely in **sensorimotor** stages per developmental summaries in the cognitive references below).

**Alignment with Philippine basic education (structural context only):** **RA 10533** describes the **13-year** cycle (**Kindergarten + 12 years**: 6 elementary, 4 JHS, 2 SHS). That law explains **how many years** of schooling exist—not the psychological cut points below. Cognitive brackets are justified mainly from **StatPearls** summaries of **Piaget** (NBK448206, NBK537095) and from **RA 10410** (early-years policy window).

### How the four core references “complete” the brackets

| Source | What it contributes |
|--------|---------------------|
| **RA 10533** (Official Gazette) | **National basic education architecture** (K + 12 years): anchors why the product starts quiz targeting at school-related ages from **Kindergarten onward**, aligned with lawful basic education structure—not a substitute for psychology, but the **Philippine schooling frame** your instrument sits in. |
| **RA 10410** (Supreme Court E-Library) | **Policy recognition** of **ages 0–8** as the first crucial stage of educational development and **DepEd’s** role for **ages 5–8**—supports treating **5–6** as a **distinct, developmentally protected** “early” quiz tier (gentle items). |
| **NBK537095** (*Cognitive Development*, Malik & Marwaha) | **Milestone-level** description (e.g. skills around **age five**, **six-to-twelve** school-age learning shifts) to justify **fine splits inside** Piaget's broad stages when building item banks. |
| **NBK448206** (*Piaget*, Scott & Cogburn) | **Canonical age bounds** for Piaget's periods used in clinical/education discourse: **pre-operational 2–7**, **concrete operations 7–11**, **formal operations beginning around age 11** through adolescence—this is the main **theoretical completion** for where boundaries **7**, **11**, and adolescent formal thought **naturally fall**. |

**Thesis note on “11 vs 12”:** NBK448206 states formal operations **generally begin around age 11**; NBK537095 elsewhere summarizes formal operational descriptions **from about age 12**. Treat **11–12** as a **transition window** in prose; the **`AGES_10_12`** band keeps **one quiz pool** for late concrete work **and** that transition (defend as **instructional convenience**, with individual assessment caveats).

### Canonical age brackets (inclusive years, cognitive rationale)

Brackets are **non-overlapping** and cover **5 through 17** for child-portal quizzes. They are chosen so you can defend **homogeneous quiz difficulty** in a thesis as “bands where mainstream developmental descriptions cluster similarly enough for one item bank,” **not** as proof of equal intelligence.

| Bracket code | Ages (inclusive) | Cognitive / developmental rationale (summary) |
|--------------|------------------|-----------------------------------------------|
| `AGES_5_6` | **5 – 6** | **Late pre-operational** stage (**Piaget ages 2–7**, **NBK448206**; milestone detail **NBK537095**). Semiotic / symbolic skills expand, but **conservation** and full logical operations on quantity are still **emerging**. **RA 10410** frames **0–8** as the first crucial educational stage and **5–8** as a **DepEd**-emphasis window—supporting a **narrow** “Kinder–early Grade 1” style quiz tier. |
| `AGES_7_9` | **7 – 9** | **Concrete operational** stage begins **at age 7** and runs **to about 11** (**NBK448206**). Early concrete mastery (e.g. conservation of quantity; **weight** conservation often strengthens **around age 9**, **NBK448206**) supports splitting concrete thought into **early (7–9)** vs **late (10–12)** quiz banks rather than one 7–11 pool—better item discrimination while staying **inside one Piaget stage**. |
| `AGES_10_12` | **10 – 12** | **Late concrete operations** through **age 11**, overlapping the **start of formal operations “around age 11”** (**NBK448206**) while **NBK537095** also discusses **twelve and older** adolescent formal thought—use this band for **upper-primary / early JHS** difficulty and **explicitly discuss** the 11–12 transition in your thesis limitations. |
| `AGES_13_15` | **13 – 15** | **Formal operational** adolescence (**NBK448206**: hypothetical-deductive and propositional thought with **abstract** material). Separates **mid-adolescence** quiz demands from the **10–12** transition band and from **16–17** “near-adult” framing. |
| `AGES_16_17` | **16 – 17** | Continued **formal operational** thought and identity/future-oriented reasoning (**NBK448206** adolescent framing; **NBK537095** “late teens” themes). One **SHS-oriented** quiz tier without opening an **18+** child-portal band. |

**Boundary rule:** `age` = whole years, inclusive endpoints (`5 <= age <= 6` for `AGES_5_6`, etc.). Document **Asia/Manila** vs parent timezone once in code.

**Legacy data migration (from old `level` strings)**

| Old quiz `level` | Default mapped bracket |
|------------------|-------------------------|
| `Elementary` | `AGES_7_9` (typical mid-elementary cognitive band); parents may move items down to `AGES_5_6` or up to `AGES_10_12` after review |
| `High School` | `AGES_13_15` |
| `Senior High School` | `AGES_16_17` |

### Child age on the device (required for quiz filtering)

- Store **`child_birth_date`** (preferred) or **`child_age_years`** (integer, parent-maintained) on **child-role devices** (or on a linked child profile if refactored later).
- Portal + recommendation code compute `age` from `child_birth_date` when present; else use `child_age_years`.
- If age is **unknown** for a child device: **do not show quizzes** in the portal (or show a parent-only message: “Set your child’s age to unlock quizzes”); still allow **videos** if policy permits. Never guess an age.

### Using this section in a thesis or questionnaire

- **Operational definition:** “Age bracket” = discrete quiz-difficulty tier based on **Piaget's periods** as summarized in **StatPearls** (**NBK448206**, **NBK537095**), bounded below by school-entry policy (**RA 10410**, **RA 10533** context).
- **Survey items:** Ask parent/guardian for **birth date** or **age**, **grade level**, and **any diagnosed learning or developmental condition** so you can analyze heterogeneity and justify limitations.
- **Limitations paragraph (suggested):** Stage theories **oversimplify** cultural and individual variation; Philippine **grade-for-age** rules (cut-offs) mean chronological age may not equal **grade placement**—your app uses **age**, so thesis should note that mismatch as a delimitation or control variable.

### References — core related studies and law (complete set for this document)

Use this block as your **primary** bibliography for the age-bracket decision in thesis chapter 2 / instruments. All URLs use **HTTPS**; **`.gov.ph`** endpoints are usually the most reliable on Philippine residential and school ISPs. **NCBI Bookshelf** (`ncbi.nlm.nih.gov`) is a **U.S. National Library of Medicine** service and is typically reachable from the Philippines for literature reviews.

1. **Republic Act No. 10533** (*Enhanced Basic Education Act of 2013*) — **Official Gazette of the Philippines** (K to 12: kindergarten + 12 years of basic education):  
   https://www.officialgazette.gov.ph/2013/05/15/republic-act-no-10533/  

2. **Republic Act No. 10410** (*Early Years Act of 2013*) — **Supreme Court of the Philippines E-Library** (ages **0–8** as first crucial stage of educational development; **DepEd** role for ages **5–8**):  
   https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/2/54560  
   Printer-friendly view: https://elibrary.judiciary.gov.ph/thebookshelf/showdocsfriendly/2/54560  

3. **Malik F, Marwaha R.** *Cognitive Development.* In: **StatPearls** [Internet]. Treasure Island (FL): StatPearls Publishing; 2026. **NCBI Bookshelf** ID **NBK537095** (milestone narrative, school-age **6–12**, and adolescent cognitive themes used alongside Piaget):  
   https://www.ncbi.nlm.nih.gov/books/NBK537095/  

4. **Scott HK, Cogburn M.** *Piaget.* In: **StatPearls** [Internet]. Treasure Island (FL): StatPearls Publishing; 2026. **NCBI Bookshelf** ID **NBK448206** (**canonical Piaget stage ages**: pre-operational **2–7**, concrete **7–11**, formal **from ~11** through adolescence—primary source for **completing** bracket boundaries in this plan):  
   https://www.ncbi.nlm.nih.gov/books/NBK448206/  

**Suggested APA-style short citations (adapt to your department's style manual):**  
*Republic of the Philippines* (2013) [RA 10533]; *Republic of the Philippines* (2013) [RA 10410 via Supreme Court E-Library]; Malik & Marwaha (StatPearls, NBK537095); Scott & Cogburn (StatPearls, NBK448206).

**If your institution requires more “related literature” rows:** add peer-reviewed empirical papers (e.g. conservation tasks, executive function by age) in a **separate** table—do not dilute the four URLs above, because they are the **explicit** statutory + developmental anchors for **this** specification.

**Access (Philippines):** Prefer on-campus or home networks that allow **HTTPS** to `ncbi.nlm.nih.gov` and `officialgazette.gov.ph` / `judiciary.gov.ph`. If **NCBI** is blocked, use an **institutional VPN** or access StatPearls through your library’s discovery layer (same articles, different resolver).

---

## Recommendation selection logic

This is the **canonical** definition for how the system chooses **one** recommended quiz and **one** recommended video for a known child device. Implement as a small dedicated service or static selector so portal and tests share one code path.

### 1) Eligibility (build the candidate sets)

**Quizzes**

- Start from quizzes **attached** to the device (`device_quiz` pivot / `$device->quizzes()`).
- Keep only rows where `quizzes.is_active === true`.
- **Age bracket (mandatory):** keep only quizzes whose **`age_bracket`** (or renamed column replacing `level`) **matches** the device’s computed child age per [Quiz age brackets](#quiz-age-brackets-replaces-school-level-labels). **Do not** include quizzes from other brackets in the candidate set — recommendation and “More” for quizzes both draw from this filtered set only.
- If the child’s age is **unknown**, the quiz candidate set is **empty** (no quizzes shown).
- If the pivot stores `created_at` (or similar) for when the assignment was made, that timestamp is preferred for “newest assignment”; if the pivot has **no** usable timestamp, use the quiz row’s `updated_at` as a single documented fallback (see §3).

**Videos**

- Start from videos **attached** to the device (`device_video` pivot / `$device->videos()`).
- Keep only rows where `videos.is_active === true`.
- Same pivot-vs-quiz `updated_at` rule as above if assignment time is unavailable.

**Empty set**

- If the candidate set is empty after filtering, there is **no** recommendation for that type (hide the Quiz or Video branch or show the empty-state copy). Do not invent a fallback from unassigned content.

### 2) Phase A — Optional parent preference (when implemented)

If the schema includes a parent-controlled pin (e.g. `preferred_quiz_id` / `preferred_video_id` on `devices`, or a `is_portal_preferred` flag on the pivot):

1. Load the pinned quiz (or video) id for this device.
2. **Valid** only if that id is still in the **eligible** set (§1) — for quizzes, eligibility **includes** the age-bracket filter.  
3. If valid → **return that quiz (or video)** as the recommendation. **Stop.**
4. If missing, null, or invalid (inactive, unassigned, deleted) → continue to **Phase B** as if no pin existed.

Until this feature ships, **always** run Phase B.

### 3) Phase B — System default (deterministic, no parent input)

Apply to the **eligible** collection only. Sort with a **total order** (full comparator), then take the **single first** row after sorting. **No** `ORDER BY RAND()` and no session-based rotation in v1.

**Documented default: Option 1 — “Newest assignment first”**

Use this **ordered comparison** (first differing column wins). All comparisons are stable across requests.

| Order | Quiz column(s) | Video column(s) | Direction / rule |
|-------|----------------|------------------|-------------------|
| 1 | Pivot `device_quiz.created_at` (assignment time). If null on all compared rows, use `quizzes.updated_at` as stand-in for “freshness.” | Pivot `device_video.created_at`; else `videos.updated_at` | **Descending** (newest first) |
| 2 | `question_count` (null or `0` treated as **largest** so bad data sorts last) | `duration_seconds` (null or `0` last) | **Ascending** (shortest first among same assignment freshness) |
| 3 | `quizzes.updated_at` | `videos.updated_at` | **Descending** (parent last-edited surfaces after same assignment time + length) |
| 4 | `title` | `title` | **Ascending** (A→Z, documented collation) |
| 5 | `id` | `id` | **Ascending** (final deterministic tie-break) |

**Alternative: Option 2 — “Shortest first”**  
Swap the intent of columns **1** and **2**: primary key becomes effort (`question_count` / `duration_seconds` ascending with nulls/zeros last), then assignment recency descending, then continue with rows **3–5** as above. Only adopt Option 2 if product explicitly chooses it; tests must match.

**Quiz-specific notes**

- Prefer `question_count` if it is maintained whenever `questions` changes; otherwise use `count(questions)` consistently — pick one and document.

**Video-specific notes**

- `duration_seconds` is the length signal for Option 1 row **2** and for Option 2 primary sort.

### 4) Pseudocode summary

```text
function recommendQuiz(Device $device): ?Quiz
  age = device.computedChildAgeYears()  // null if unknown → no quizzes
  candidates = device.quizzes
    .where(is_active)
    .where(age_bracket matches age)   // strict: same bracket only; see Quiz age brackets
  if candidates empty → return null

  if parentPinQuizId exists AND parentPinQuizId in candidates → return that quiz

  return candidates.sort(by SECTION_3_RULES).first()
```

Same for `recommendVideo` with the video relation and pin.

### 5) Properties the logic must satisfy

- **Deterministic:** same device + same DB rows ⇒ same recommendation across HTTP requests (no `ORDER BY RAND()`).
- **Stable:** adding an unrelated quiz must not reorder recommendations for other devices.
- **Age-safe:** never recommend a quiz outside the child’s bracket; unknown child age ⇒ quiz list hidden, not wrong bracket.
- **Safe fallbacks:** invalid parent pin never blanks the UI if other eligible items exist.
- **Documented:** one short comment block above the comparator in code naming Option 1 vs 2 and pivot fallback.

---

## Recommended vs “More” (Behavior)

### Parent preference vs system default (summary)

Whenever a device has at least one eligible quiz (or video), the portal **must** show exactly **one** recommended item — never empty because the parent skipped configuration (except quizzes when **child age is unknown** — then show zero quizzes until age is set). Full ordering and tie-breakers: [Recommendation selection logic](#recommendation-selection-logic).

- **Parent pin** (when implemented): if valid per §2 → use it; else run **Phase B** (§3).
- **No pin:** always run **Phase B** (§3).

### “More” / browse

- Expands inline **or** navigates to a **browse** view listing assigned quizzes or videos (current grid/tile pattern is fine here). **Quizzes:** list only those in the child’s **eligible** set (same **age bracket** filter as recommendation — no cross-bracket browsing).
- Optional filters later (e.g. “Quick” vs “Long”) — not required for v1 if it risks adding taps.

### Random question quiz (child portal)

Parents already configure **Time Reward Mode (Random Quiz)** on the quiz index: one synthetic “Random Quiz Mode Settings” quiz per parent, device allow-list, `minutes_per_correct`, optional cooldowns/limits. At runtime the portal builds a session from **all active `QuestionBankItem` rows** in **random order** (up to `question_count`), and **grants time per correct answer** — no single pass score. That path is implemented in the app backend (`scoring_mode === 'time_reward'` / global bank branch in `PortalController`).

**Child portal UX (plan):** wherever the quiz-selection UI exposes subject/category choices including an **Other** catch-all tile or chip, add a **second, equally prominent control immediately beside Other** dedicated to **Random question quiz** (child-friendly label, e.g. “Surprise mix” or “All topics, mixed,” plus a short line that each right answer earns time). Tapping it starts the **same** random global-bank flow the parent enabled — not a separate question source.

**Visibility rules**

- Show the button **only** when this device is included in the parent’s Random Quiz Mode device list **and** that mode is effectively usable (e.g. synthetic quiz active, bank has eligible items). If the device is not on the list, **hide** the control so children are not offered a path that 403s or errors.
- **Age bracket:** once quiz browsing is filtered by bracket, decide explicitly whether random global mode respects the same bracket filter (recommended: filter bank items by the device’s bracket when that metadata exists on bank rows) or remains “all bank” only when product policy allows; document the choice in code and here.

**Implementation note:** Prefer reusing the existing random-mode quiz record and routes (`portal.quiz.show` with that quiz’s id) rather than duplicating selection logic; the new work is mostly **portal layout + eligibility flags** passed from `PortalController` (or a small view-model).

---

## UI / Visual Direction (Stylish but usable)

- **Distinct visual identity** for the child portal vs parent dashboard — **deliberately move away** from the current default look (flat yellow page fill + many same-sized white/yellow-bordered tiles). Aim for a **refined child-friendly** aesthetic: a **cohesive new palette** (e.g. deep or soft base + one accent), **clear hierarchy**, rounded geometry or soft cards, and **one focal “stage”** for the two primary actions instead of a dashboard grid. Deliver this using **only locally served** styles and assets (see [Local-only resources](#local-only-resources-mandatory)).
- **Focal layout:** the **Quiz | Video** pair sits in a **centered cluster** that uses roughly **35–55%** of the viewport height on phones (order-of-magnitude guide — tune per breakpoint), with **letterboxing-style** calm margins; avoid stretching those buttons to full viewport width unless touch targets still read as a **pair**, not a wall of tiles.
- **Secondary chrome:** device name, time remaining, and legal/helper lines use **smaller type** and **lower contrast** than the two primaries so attention stays on **Quiz** and **Video**.
- **Motion:** subtle entrance for the focal card (CSS), micro-celebration on successful completion (already partially present on result pages); avoid infinite distracting animations on the main choice screen — implement with **CSS** (or tiny inline JS from `public`), not remote animation libraries.
- **Iconography:** simple metaphors — book / question bubble for quiz; play triangle / screen for video — prefer **inline SVG** or local sprite files.
- **Typography:** one strong short line above the choices; large, legible labels on the two actions; body/helper text smaller. Use a **self-hosted** font face under `public` or a documented **system stack** — never a remote font stylesheet for portal routes.

---

## Implementation Plan (Checklist)

### A) Entry and two-step chooser

- [ ] Document and implement **captive entry** correctly: the child reaches the portal via the **device/OS Wi‑Fi or captive notification** (or manual URL) — **not** by tapping a duplicate **“Sign in to Wi‑Fi”** button that is required on the portal page for known devices. Optional helper text may reference that they arrived from sign-in.
- [ ] **Replace legacy landing:** for recognized child devices, the **first portal screen** is **only** **Quiz | Video** — **no** default grid listing every quiz (or video) tile. Lists appear **after** type choice or under **More**.
- [ ] **Layout:** present **Quiz | Video** in a **stylish, centered focal block** using a **fraction of the display** with strong visual focus; demote device stats to secondary placement per [UI / Visual Direction](#ui--visual-direction-stylish-but-usable) and [Landing layout (replace legacy)](#landing-layout-replace-legacy).
- [ ] **Visual refresh:** update `portal-captive.css` (and related Blade) to the new overall style — still **local-only** assets.
- [ ] Ensure this chooser is the **default path** when time is exhausted / portal loads for a known child device (same URL or dedicated route, as long as behavior is consistent).

### B) Recommended activity + “More”

- [ ] Schema: replace quiz `level` (Elementary / …) with **`age_bracket`** enum matching [Quiz age brackets](#quiz-age-brackets-replaces-school-level-labels); migrate legacy rows; add **`child_birth_date`** or **`child_age_years`** on child devices.
- [ ] Backend: resolve **recommended quiz** and **recommended video** for the device: apply **parent pin** (Phase A) when implemented and valid; otherwise **always** apply **Phase B** from [Recommendation selection logic](#recommendation-selection-logic). Quiz candidates **must** be pre-filtered by age bracket **before** Phase B sorting.
- [ ] Present **one** highlighted card per type after type selection, with **Start** as the dominant action.
- [ ] Add **More quizzes** / **More videos** (wording tuned for reading level) linking to full list or modal.
- [ ] **Random question quiz button:** on the quiz-selection surface that includes **Other**, add a dedicated tile/chip **beside Other** that starts **Time Reward / global random bank** mode for this device when the parent has enabled Random Quiz Mode for it (same behavior as parent-configured random quiz: all active bank questions in random order, reward **per correct answer**). Hide when the device is not eligible. Wire copy and icon to match [Random question quiz (child portal)](#random-question-quiz-child-portal).

### C) Polish and accessibility

- [ ] Touch targets ≥ 44×44 px equivalent; focus states for keyboard / external controllers if applicable.
- [ ] Clear empty states: no quizzes → hide Quiz path or show friendly message + video-only; same if only quizzes; **unknown child age** → explain that a parent must set age/birth date before quizzes appear.
- [ ] Keep **device registration** flow separate and obvious when `showDeviceRegistration` is true (do not trap unknown devices behind “Sign in” without explanation).

### D) Local-only / offline-first (mandatory, Part 1 alignment)

- [ ] Meet every item under [Local-only resources](#local-only-resources-mandatory) — child portal uses **local resources only** for all critical UI and default video playback.
- [ ] No CDN, remote font, remote icon pack, or third-party analytics on any portal route (`landing`, chooser, recommended, `quiz`, `video`, result pages).
- [ ] Verify portal pages after CSS/JS changes on a **WAN-down** Pi profile (full flow, including result redirect).

### E) Tests

- [ ] Feature tests: device with quizzes+videos → **two taps on the portal** (after simulated portal load) resolve to expected recommended IDs; assert **first paint** is chooser-first (no default full quiz grid) where applicable.
- [ ] **No parent pin:** assert recommended IDs match **Phase B** ordering (documented Option 1 or 2 + tie-breakers from [Recommendation selection logic](#recommendation-selection-logic)).
- [ ] **Invalid parent pin** (inactive or unassigned): assert fallback to **Phase B**, not empty UI.
- [ ] **Age bracket:** assigned quizzes from other brackets never appear in recommendation or “More”; unknown device age ⇒ zero quizzes.
- [ ] **Random question quiz:** when the device is on the parent’s Random Quiz Mode list, the portal exposes the dedicated control beside **Other** and starting it loads questions from the global bank path; when not on the list, that control is absent. Assert time grants match `minutes_per_correct` for correct answers only.
- [ ] Edge cases: single quiz, single video, none of either, only one type.

---

## Recommendations for Your Plan

1. **Lock the “two clicks” metric to “start activity”** — not “finish and get time.” Finishing is a separate success flow; over-promising “two clicks to internet” causes frustration if a quiz is long.
2. **Use one verb on the second screen:** **Start** (or **Go**) for both quiz and video so the child learns one action.
3. **Put “More” below the fold of attention** — visually smaller or text link so the primary path stays obvious, but power users (older kids) still find variety.
4. **Let parents optionally pin a recommendation** with a simple “Prefer this on portal” (or per-device) control — when unset, the **system default** always runs so the two-tap path still works with zero parent configuration.
5. **Shortest-first inside the default ladder** (after newest) often matches child patience; parents who want a specific activity use the optional pin; everyone else gets predictable automatic picks.
6. **Avoid extra modals** for the Quiz vs Video step — use **large touch targets** inside the **centered focal region** (not necessarily edge-to-edge full width); tiny text links alone are insufficient for captive portals on small screens.
7. **Analytics-lite:** log `portal_open` → `type_quiz|type_video` → `start_recommended|start_from_more` (privacy-preserving, device-level) so you can verify the two-tap funnel without third-party scripts.
8. **Registration path:** when the device is unknown, keep **Request to Register** prominent. For known devices, the portal’s primary story is **Quiz | Video** — not a duplicate of the **OS** “Sign in to Wi‑Fi” notification as the only portal action. If both unknown and known flows share a URL, use clear labels (“New tablet? Register” vs “Already set up? Get time”) where both appear.
9. **Consistency with docs:** update [docs/VIDEO_CAPTIVE_PORTAL.md](./docs/VIDEO_CAPTIVE_PORTAL.md) navigation diagram once this flow ships so parents and installers see one story.
10. **Local-only is non-negotiable for the child path:** any new “stylish” feature (Lottie, extra fonts, confetti libraries) must ship as **vendored files in `public`** or be dropped; do not add a quick CDN link for demos.
11. **Prompt for birth date or age** when a device is approved as **child** (or on first quiz assignment) so the portal is not stuck with “no quizzes” for a missing field.
12. **Random question quiz discoverability:** keep the random-mode entry **beside Other** on the quiz picker so it reads as a first-class activity, not only as one more row in an overflow list — parity with how strongly parents see “Time Reward Mode (Random Quiz)” in the dashboard.

---

## Acceptance Criteria (Part 2)

- After opening the portal on a known child device (via **OS captive / Wi‑Fi notification** or equivalent) that has at least one quiz **and** one video: the **first paint** shows **only** **Quiz** and **Video** as the dominant focal UI (not a full-page grid of all quizzes). The child can reach **Start** on a **recommended** quiz in **two taps on the portal** (Quiz → Start; same for Video), **only** when the child’s age is set and at least one assigned quiz matches that device’s **age bracket**.
- With **no** parent-configured pin, **recommended** quiz/video still resolve via **Phase B** in [Recommendation selection logic](#recommendation-selection-logic); with a valid pin, that item is recommended instead (Phase A in the same section), and the pin **must** respect the age-bracket filter.
- **“More”** lists **all assigned items in scope**: for quizzes, that means **same age bracket only**; for videos, unchanged unless age is added later.
- When Random Quiz Mode applies to the device, the quiz-selection UI shows the **Random question quiz** control **next to Other**; starting it uses the existing global-bank random flow and awards time **per correct answer** per parent settings.
- Child portal remains fully usable with **WAN disabled**: **all** styles, scripts, fonts, icons, and default activity media load from **local** app/Pi resources only — **no** reliance on external CDNs, font hosts, or third-party scripts for critical paths.
- Empty and single-type assignments show **clear, kind** messaging without dead ends.

---

## Notes for Technical Breakdown

- Likely touchpoints: `PortalController` (landing data: add `recommendedQuiz`, `recommendedVideo`), `resources/views/portal/landing.blade.php` (new steps or partials), `routes/web.php` (only if new routes for chooser/recommended views), `public/css/portal-captive.css` (layout + motion).
- **Age brackets:** `Quiz` model + migrations + validation (`StoreQuizRequest` / update requests), `Device` fields for birth date or age, `BuiltInQuizSeeder` / question bank seeders, parent quiz create/edit Blade selects, and a `PortalQuizRecommendation` (or similar) service that applies **bracket filter → pin → Phase B sort**.
- **Random question quiz:** parent side uses `QuizController::getOrCreateRandomModeQuiz` and `updateRandomModeSettings`; portal runtime uses the `time_reward` / global `QuestionBankItem` branch in `PortalController`. Child UI only needs eligibility (device on random-mode sync) and the **beside Other** entry control.
- **Legacy:** the current landing lists all quizzes (and videos) in grid sections on first load. **Part 2** replaces that with **chooser-first** + **focal fraction-of-viewport** layout for known child devices; assignment and recommendation models stay as defined elsewhere in this doc.

---

## Optional Future Enhancements (Out of Scope for Minimal Part 2)

- Avatar or sticker rewards for streaks.
- “Quick mode” quiz with fewer questions when time is low.
- Read-aloud for instructions (accessibility + young children).

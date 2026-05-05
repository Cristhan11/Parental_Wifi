# Agent prompt: Child portal (Part 2)

**How to use:** In a new agent chat, paste **from the heading `## Your task` through the end of the `### Final reply` section** (last bullet of that section) as your user message. Optionally prepend one line: `Follow this specification exactly.`

If your clone path differs, mentally replace `parental_wifi` / file paths with yours.

---

## Your task

You are implementing **Part 2: Child captive portal** for the Laravel project **parental_wifi**.

### Authoritative documents (read before coding)

1. **`Simplify_project_child_portal.md`** (repo root) — **primary spec.** Read it end-to-end. Pay special attention to:
   - **Goal** and **Captive entry — not a “Sign in” button on the portal page**
   - **Local-only resources (mandatory)** and its checklist
   - **Guiding principles** (focal first screen, progressive disclosure)
   - **Target flow: “Two Clicks” Definition** and **Landing layout (replace legacy)**
   - **Quiz age brackets** (device age, unknown age ⇒ no quizzes)
   - **Recommendation selection logic** (eligibility → optional pin → Phase B; **no `ORDER BY RAND()`** for picking the recommendation)
   - **Random question quiz (child portal)** (beside **Other**, eligibility, reuse backend)
   - **UI / Visual Direction** (focal layout ~35–55% viewport height guide, visual refresh, local assets only)
   - **Implementation Plan A–E**, **Acceptance Criteria (Part 2)**, **Notes for Technical Breakdown**

2. **`Simplify_project.md`** — Part 1 alignment; read section **Child captive portal — first experience** so parent/child story stays consistent.

Do **not** invent product rules that contradict these docs. If the spec is silent on a fork, choose one approach, document it in a short code comment, and state it in your final summary.

### Likely code touchpoints

`PortalController`, `resources/views/portal/*.blade.php`, `public/css/portal-captive.css`, portal/quiz/video routes in `routes/web.php`, `Quiz` / `Device` models and migrations as required by the plan, `QuizController` (`getOrCreateRandomModeQuiz`, random mode device sync), existing `tests/Feature/*` for portal/quiz. Match existing Laravel, Blade, and CSS conventions; **no drive-by refactors** outside this scope.

---

### Non-negotiable requirements

1. **Local-only portal**  
   Critical child-portal UI (all steps through quiz/video/results) must load with **WAN disabled**: same-origin CSS/JS/fonts/icons only (`public` / `asset()`). No CDN frameworks, no remote fonts, no third-party analytics on portal routes. See plan *Local-only resources*.

2. **Captive entry vs portal UI**  
   **“Sign in to Wi‑Fi”** (and similar) is **OS / device UI** that opens the portal URL. For **recognized child devices**, do **not** require a duplicate “Sign in to Wi‑Fi” as the **primary** action on the portal happy path. First meaningful portal screen = **Quiz | Video** (optional helper microcopy is fine).

3. **First paint (known child device)**  
   **Remove the legacy default:** a full-page **“Available Quizzes”** grid listing every assigned quiz (and exposing internal titles like “Random Quiz Mode Settings”) on initial load.  
   **Replace with:** **only two primary actions — Quiz and Video** — as the **visual hero**, in a **centered focal region** using a **modest fraction of the viewport** (plan suggests ~**35–55%** of viewport height on phones as an order-of-magnitude guide). Generous **negative space**; **large touch targets** inside that focal area (not necessarily stretched edge-to-edge). **Device name** and **time remaining** are **secondary** (smaller strip, footer, or subtle card — lower visual weight than Quiz/Video).

4. **Progressive disclosure**  
   After the child taps **Quiz** or **Video**, show the **recommended** item with a dominant **Start** (same verb family as plan). **More quizzes** / **More videos** and full browse lists appear from **More** (or equivalent), not on first paint. Quiz browse surfaces that include **Other** must also support the **Random question quiz** control **immediately beside Other** when the device is eligible for parent **Random Quiz Mode**; reuse existing `time_reward` / global bank / `portal.quiz.show` behavior — **do not** duplicate question assembly logic.

5. **Two taps on the portal to reach Start**  
   **Tap 1:** Quiz **or** Video. **Tap 2:** **Start** on the recommended quiz or video. The OS step that opens the browser is **not** counted. Optional **More** does not count toward the two-tap promise. The plan’s success metric is **start activity**, not “finish quiz.”

6. **Age brackets and recommendation**  
   Implement quiz eligibility and recommendation per **Recommendation selection logic**: assigned + active + **age_bracket** matches device’s computed age; **unknown age ⇒ no quizzes** (videos per plan). Deterministic **Phase B** sort (documented Option 1 or 2 — match the plan and tests). **No `ORDER BY RAND()`** for **which** quiz is recommended.

7. **Visual refresh**  
   Move away from the current flat yellow + many equal tiles look; implement a **cohesive new child-portal aesthetic** (palette, hierarchy, focal card/stage, subtle CSS motion) while respecting (1).

8. **Registration / unknown device**  
   When `showDeviceRegistration` is true, keep **Request to Register** obvious; do not hide it behind captive-only wording. Known vs unknown flows may share a URL — use clear labels if both appear.

---

### Deliverables

- Application changes that satisfy **Acceptance Criteria (Part 2)** and the **Implementation Plan** checklists in `Simplify_project_child_portal.md`, in dependency-aware order (e.g. schema/age/brackets before recommendation UI if the plan requires it).
- **Feature tests** that assert, where applicable:
  - **Chooser-first first paint** (no default full quiz grid for known child path).
  - **Two portal taps** to intended **Start** / recommended targets.
  - Phase B ordering, age-bracket filtering, unknown age ⇒ no quizzes.
  - Random quiz control visibility beside **Other** and eligibility rules per plan §E.
- Run the relevant tests (e.g. `php artisan test` with appropriate filters) and fix failures you introduce.

### Out of scope

- Items under **Optional Future Enhancements** in the plan doc.
- Unrelated refactors, new markdown docs, or dependency upgrades not required by the plan.

### Final reply

Reply to the user with: files changed; mapping to plan sections; tests run and outcomes; any intentional spec forks and where you documented them in code.

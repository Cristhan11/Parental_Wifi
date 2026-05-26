# Chapter 4 Data Gathering (Collected Responses)

This file holds the responses gathered during the user testing of the Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting. Each section gives the demographics, the methodology, the results matrix, and the cohort summary for one survey instrument. The figures here are the source of the tables in section 4.3 of [`docs/chapter4.md`](docs/chapter4.md).

## Overview

- Total respondents: 9 (3 Child, 2 Parent, 4 IT Specialist).
- Three instruments were used. The questions follow the survey PDFs `CHILD_SURVEY.pdf`, `PARENT_SURVEY.pdf`, and `IT-SPECIALIST_SURVEY.pdf`.
- Item codes used below: `I1`-`I5` for the Child UI Portal items, `P1`-`P9` for the Parent Dashboard items, and `S1`-`S6` for the IT Specialist Security items.
- Acceptance bases reported in Chapter 4:
  - Child instrument: at least 90% Yes per item.
  - Parent and IT Specialist instruments: per-item mean of at least 4.00 on the 1 to 5 Likert scale, with the pooled mean for the instrument at or above the same line.

---

## Section A — Child Survey Dataset

The Child instrument captures the child's view of the captive portal screens. The five items follow the wording in `CHILD_SURVEY.pdf` and are coded `I1` to `I5` for compact reference in Chapter 4.

### A.1 Item Reference (Child)

| Code | Item summary |
|------|--------------|
| I1 | The child realizes that completing a quiz or a video adds more internet time. |
| I2 | The portal shows the amount of time that is still available for the device. |
| I3 | The landing screen separates the quiz area from the video area in a way the child can read. |
| I4 | Pictures and buttons react right away when the child presses them. |
| I5 | A wrong press still lets the child step back to the previous view or to the main choices. |

### A.2 Respondent Demographics (Child)

| Respondent | Age | Device Used |
|------------|-----|-------------|
| Aron Axis O. Cabico | 17 | — |
| Nhaigel Dave P. Obillo | 15 | — |
| Cecelio O. Cabico Jr. | 7 | — |
| Session Date | 2026-05-25 | — |

### A.3 Methodology Note (Child)

The respondent in the 7 to 9 age bracket (age 7) was guided using the Meloncon et al. layout. A parent stayed in a separate room while a neutral researcher read the opening script aloud and read each item again only when the child asked for help with a hard word. The child chose `Yes` or `No` on the printed form without parent coaching during the items. The two older respondents (ages 15 and 17) self-administered the same form, with the researcher present only to clarify wording if asked.

### A.4 Results Matrix (Child)

| Item | Cabico, A. | Obillo | Cabico Jr. | Yes Count | Yes % |
|------|------------|--------|------------|-----------|-------|
| I1 | Yes | Yes | Yes | 3/3 | 100% |
| I2 | Yes | Yes | Yes | 3/3 | 100% |
| I3 | Yes | Yes | Yes | 3/3 | 100% |
| I4 | Yes | Yes | Yes | 3/3 | 100% |
| I5 | Yes | Yes | Yes | 3/3 | 100% |

### A.5 Cohort Summary (Child)

- Total scored cells: 15 (5 items × 3 respondents).
- Yes cells: 15.
- No cells: 0.
- Cohort Yes rate: `15 / 15 × 100 = 100%`.
- Acceptance threshold for this instrument in Chapter 4: at least 90% Yes per item.
- Result against threshold: every item is at 100%, so every item passes.

---

## Section B — Parent Survey Dataset

The Parent instrument captures the parent's view of the dashboard while operating the live system. The nine items follow the wording in `PARENT_SURVEY.pdf` and are coded `P1` to `P9` for compact reference in Chapter 4. Ratings use a 1 to 5 Likert scale, where `5` means Strongly Agree.

### B.1 Item Reference (Parent)

| Code | Item summary |
|------|--------------|
| P1 | Parent can review the child's site history and add chosen sites to a flag list or a block list. |
| P2 | The captive portal sends the child device to a quiz or video, and access resumes only after the activity is finished or the passing mark is reached. |
| P3 | Parent can set a daily window and a session length per child device, and the system enforces both. |
| P4 | Parent is alerted as it happens when the time runs out, when a flagged site is opened, when a blocked site is attempted, or when an unfamiliar device joins. |
| P5 | Parent can see the running total of time each child device has spent online. |
| P6 | The dashboard delivers easy-to-read summaries by day, by week, and by month, covering usage, sites seen, flagged or blocked attempts, and bandwidth. |
| P7 | Parent can move a connected device between the block list and the whitelist from the dashboard. |
| P8 | Parent can create, update, or remove quizzes and educational videos that the captive portal will use. |
| P9 | The system runs in a stable way at home when paired with the PLDT modem and the local network setup. |

### B.2 Respondent Demographics (Parent)

| Respondent | Device Used | Notes |
|------------|-------------|-------|
| Romeo O. Garcia | Android phone | Parent respondent |
| Margarita Tibayan | Android phone | Parent respondent |
| Session Date | 2026-05-25 | — |

### B.3 Methodology Note (Parent)

Each parent acted as the direct respondent while using the deployed dashboard against the Raspberry Pi appliance. The researcher read items aloud only when the parent asked for help with wording. No rating was suggested by the researcher. Forms were filled in the same session as the dashboard walkthrough so each rating reflects an action the parent had just performed.

### B.4 Results Matrix (Parent)

| Item | Garcia | Tibayan | Sum | Mean |
|------|--------|---------|-----|------|
| P1 | 4 | 4 | 8 | 4.00 |
| P2 | 4 | 4 | 8 | 4.00 |
| P3 | 4 | 4 | 8 | 4.00 |
| P4 | 4 | 4 | 8 | 4.00 |
| P5 | 3 | 4 | 7 | 3.50 |
| P6 | 4 | 4 | 8 | 4.00 |
| P7 | 4 | 4 | 8 | 4.00 |
| P8 | 4 | 4 | 8 | 4.00 |
| P9 | 3 | 3 | 6 | 3.00 |

### B.5 Cohort Summary (Parent)

- Total scored cells: 18 (9 items × 2 respondents).
- Sum of ratings: 69.
- Pooled mean: `69 / 18 ≈ 3.83`.
- Per-item mean range: 3.00 to 4.00.
- Standard deviation across all 18 cells: `≈ 0.37`.
- Acceptance threshold for this instrument in Chapter 4: item mean of at least 4.00 and pooled mean of at least 4.00.
- Result against threshold: P1, P2, P3, P4, P6, P7, and P8 each land at 4.00 and pass. P5 (mean 3.50) and P9 (mean 3.00) fall below the 4.00 line, and the pooled mean (3.83) also sits below it. The two items below threshold mark areas that need follow-up: the dashboard view of total online time per device (P5) and the perceived reliability of the deployment over the PLDT modem (P9).

---

## Section C — IT Specialist Survey Dataset

The IT Specialist instrument captures a reviewer's view of the system security measures after a guided demonstration. The six items follow the wording in `IT-SPECIALIST_SURVEY.pdf` and are coded `S1` to `S6` for compact reference in Chapter 4. Ratings use a 1 to 5 Likert scale, where `5` means Strongly Agree.

### C.1 Item Reference (IT Specialist)

| Code | Item summary |
|------|--------------|
| S1 | Login and identity checks cover both inside-the-home access and outside-the-home access. |
| S2 | The firewall blocks traffic by default and only opens the paths that the service really needs. |
| S3 | A MAC allowlist decides which devices are trusted, and any device not on the list is screened before it can pass. |
| S4 | Every form that changes data carries a server-issued token that guards it against cross-site requests. |
| S5 | The session identifier is rotated when the user moves to a higher access level, including after sign-in. |
| S6 | Security event records are checked on a routine schedule so that unusual or unauthorized activity is caught early. |

### C.2 Respondent Demographics (IT Specialist)

| Respondent | Background label | Device Used |
|------------|------------------|-------------|
| Leo Gabriel Villanueva | — | — |
| Rhenel Bernisca | — | — |
| Paul Andrew Roa | — | — |
| Robert John Nazareno | — | — |
| Session Date | 2026-05-25 | — |

### C.3 Methodology Note (IT Specialist)

Each IT respondent observed a live walkthrough of the deployed system. The researcher demonstrated the parent dashboard, the child portal, and the security-relevant controls, and explained how scheduling, device handling, filtering, and logging fit together. After the walkthrough, the respondent completed the Section B checklist based on what was observed and explained, not on a code audit. Where wording was unclear, the researcher restated the item without suggesting a rating.

### C.4 Results Matrix (IT Specialist)

| Item | Villanueva | Bernisca | Roa | Nazareno | Sum | Mean |
|------|------------|----------|-----|----------|-----|------|
| S1 | 5 | 5 | 4 | 4 | 18 | 4.50 |
| S2 | 5 | 5 | 4 | 3 | 17 | 4.25 |
| S3 | 5 | 5 | 4 | 3 | 17 | 4.25 |
| S4 | 5 | 5 | 5 | 3 | 18 | 4.50 |
| S5 | 5 | 4 | 4 | 3 | 16 | 4.00 |
| S6 | 5 | 4 | 4 | 3 | 16 | 4.00 |

### C.5 Cohort Summary (IT Specialist)

- Total scored cells: 24 (6 items × 4 respondents).
- Sum of ratings: 102.
- Pooled mean: `102 / 24 = 4.25`.
- Per-item mean range: 4.00 to 4.50.
- Standard deviation across all 24 cells: `≈ 0.78`.
- Acceptance threshold for this instrument in Chapter 4: item mean of at least 4.00 and pooled mean of at least 4.00.
- Result against threshold: every per-item mean reaches 4.00 or higher and the pooled mean (4.25) also clears the 4.00 line, so every item passes.

---

## Notes on Missing Fields

A few cells in the demographic tables are written as `—` because the source data did not include them at the time of capture. When those fields are confirmed by the testers, update the cells in place; the cohort summaries already use the rating cells only, so the totals and means in this file and in `chapter4.md` will not need to be recomputed.

- Child Section A.2: Device Used for each child respondent.
- IT Specialist Section C.2: Background label and Device Used for each respondent.

If extra respondents are recruited later, append new columns to the matching results matrix, then refresh the per-item Sum and Mean, the cohort total cells, the pooled sum and pooled mean, and the standard deviation. Mirror the same updates in `docs/chapter4.md` sections 4.2.1 and 4.3.1, and re-check the outcome lines in section 4.3.2.

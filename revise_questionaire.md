# Revised Questionnaire and Security Evidence Guide

This document provides a revised, consultation-ready questionnaire package for validating:

1. Security objective attainment from `docs/Chapter1_4.md` (basic protection against unauthorized access), and
2. Child portal age suitability (recommended supported age range).

It is designed for inclusion in Chapter 4 evidence gathering and adviser consultation.

---

## A. Questionnaire Title

**User Evaluation Questionnaire for Security Measures and Child Portal Age Suitability**  
Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management

---

## B. Purpose of the Questionnaire

This questionnaire aims to:

1. Measure whether the system demonstrates practical security measures from the user perspective.
2. Produce evidence that core security controls are visible and effective during normal use.
3. Determine the most suitable child age range for independent child portal use.
4. Identify usability and safety improvements for younger children.

---

## C. Respondent Information Sheet

Fill out before answering the survey.

- Date: ____________________
- Role:
  - [ ] Parent/Guardian
  - [ ] Child User
  - [ ] Parent Observer (co-observing child)
- Device Used:
  - [ ] Android phone
  - [ ] iPhone
  - [ ] Tablet/iPad
  - [ ] Laptop/Desktop
- For child respondents:
  - Child age (exact): ______
  - Age bracket:
    - [ ] 8-10
    - [ ] 11-13
    - [ ] 14-17
- Test session code (if used): ____________________

---

## D. Consent Statement

I voluntarily participated in this test session. I understand that:

- My responses will be used only for academic/project evaluation.
- No sensitive personal credentials should be entered in open text fields.
- Results may be summarized in grouped form (no public exposure of private identity).

Name/Signature (optional): ____________________  
Date: ____________________

---

## E. Instructions for Respondents

1. Complete the assigned test flow first (parent dashboard flow and/or child portal flow).
2. Answer each item based on actual experience during the session.
3. Use the scale below for rating items:

- 5 - Strongly Agree
- 4 - Agree
- 3 - Neutral
- 2 - Disagree
- 1 - Strongly Disagree

4. If an item is not applicable to your role, mark **N/A**.

---

## F. Section 1: Security Measures Evaluation (Parent-Focused)

Rate each statement from 1 to 5.

1. I can sign in securely, and I can only access my own account data.  
Score: ___

2. The system clearly separates parent/admin pages from child portal pages.  
Score: ___

3. Device access control (for enrolled or recognized devices) works as expected.  
Score: ___

4. Website blocking and policy enforcement remain consistent during use.  
Score: ___

5. Session behavior appears secure (no unexpected account switching or unauthorized exposure).  
Score: ___

6. Form submissions behave safely and predictably (no suspicious redirects or unsafe prompts).  
Score: ___

7. Activity logs and access-attempt records are sufficient for monitoring unusual behavior.  
Score: ___

8. Overall, the system provides adequate protection against unauthorized access.  
Score: ___

Optional comments for security evaluation:  
__________________________________________________________________  
__________________________________________________________________

---

## G. Section 2: Child Portal Age Suitability (Child + Parent Observer)

Rate each statement from 1 to 5 (or N/A if not applicable).

1. The child portal instructions are easy to understand for the child.  
Score: ___

2. The portal language and wording are age-appropriate.  
Score: ___

3. The child can complete quiz/video tasks with minimal assistance.  
Score: ___

4. Feedback messages (pass/fail/retry/time granted) are clear to the child.  
Score: ___

5. The child understands what to do next after time expiration.  
Score: ___

6. The portal process feels fair and not confusing for the child user.  
Score: ___

7. Overall, this portal is suitable for the child's current age.  
Score: ___

Observed support needed:

- Did the child need adult assistance?
  - [ ] Yes
  - [ ] No
- If yes, what kind of help? (reading, navigation, understanding, technical help)  
  ________________________________________________________________
- Approximate completion time for one full portal cycle: ______ minutes

Optional comments for age suitability:  
__________________________________________________________________  
__________________________________________________________________

---

## H. Section 3: Open-Ended Improvement Feedback

1. Which security-related feature gave you the most confidence in the system, and why?  
__________________________________________________________________  
__________________________________________________________________

2. Which part of the child portal is hardest for younger users?  
__________________________________________________________________  
__________________________________________________________________

3. What changes do you recommend before wider deployment?  
__________________________________________________________________  
__________________________________________________________________

---

## I. Scoring and Interpretation Guide (For Researchers)

### I.1 Security Objective Attainment Rule

Compute the mean per security item and the overall security mean.

- **Feature-level threshold:** Mean >= 4.00 = Achieved
- **Overall security objective achieved:** All core security items meet threshold, and overall security mean >= 4.00

Suggested core mapping to Objective 1.5 #9:

- Authentication and account isolation -> Items F1, F2
- Firewall/policy enforcement -> Item F4
- MAC/device control -> Item F3
- Session management -> Item F5
- Log monitoring -> Item F7

### I.2 Age Suitability Decision Rule

Group results by age bracket:

- 8-10
- 11-13
- 14-17

For each bracket, compute:

1. Mean of age-suitability scores (Section G)
2. Percentage completing with **no assistance**
3. Average completion time

Suggested interpretation:

- **Recommended for independent use:** mean >= 4.00 and no-assistance rate >= 80%
- **Recommended with guidance:** mean >= 3.50 but no-assistance rate < 80%
- **Needs redesign for that bracket:** mean < 3.50

---

## J. Supporting Security Controls (System Evidence Notes)

Use this section to support questionnaire findings during consultation and Chapter 4 reporting.

1. **Authentication and account boundaries**
   - Parent/admin routes require authenticated sessions.
   - Account-scoped dashboard views reduce cross-account data exposure.

2. **Laravel CSRF protection**
   - State-changing form submissions include CSRF token validation.
   - This helps prevent forged requests from unauthorized third-party pages.

3. **Input validation and safe form handling**
   - Server-side validation checks enforce expected formats and required fields.
   - Validation errors are returned in a controlled way to prevent unsafe processing.

4. **Password protection**
   - Passwords are handled using framework hashing mechanisms (not stored as plain text).

5. **Session management**
   - Session-backed authentication protects parent dashboard access.
   - Session isolation supports role and account boundaries during active use.

6. **Command execution safeguards (allowlisted scripts)**
   - Privileged network actions are executed only through allowlisted/whitelisted scripts.
   - Arguments are sanitized and execution is logged to reduce command injection risk.
   - This is aligned with controlled use of elevated commands only for approved operations.

7. **MAC-based device control**
   - Device enrollment and policy application use MAC identity in operational flow.
   - Supports supervised-device control for child access management.

8. **Firewall and policy enforcement**
   - Network policies (allow/deny/redirect) are applied at network control layer.
   - Helps enforce schedule expiry, policy states, and restricted access conditions.

9. **DNS-based blocking and monitoring support**
   - Domain-level controls and DNS query monitoring contribute to blocking and visibility.

10. **Log monitoring and reporting**
   - Browsing and access-attempt logs provide traceability for supervision.
   - Periodic reports support parent review and incident awareness.

---

## K. Ready-to-Use Summary Table Template (For Chapter 4)

- **User authentication**
  - Evidence source: Login + account-scoped dashboard behavior
  - Related questionnaire items: F1, F2
  - Mean score: ____
  - Status: Achieved / Needs Improvement

- **Firewall/policy enforcement**
  - Evidence source: Blocking and schedule enforcement behavior
  - Related questionnaire items: F4
  - Mean score: ____
  - Status: Achieved / Needs Improvement

- **MAC/device control**
  - Evidence source: Device enrollment and control behavior
  - Related questionnaire items: F3
  - Mean score: ____
  - Status: Achieved / Needs Improvement

- **Session management**
  - Evidence source: Secure session behavior during use
  - Related questionnaire items: F5
  - Mean score: ____
  - Status: Achieved / Needs Improvement

- **Log monitoring**
  - Evidence source: Logs/access attempts/report readability
  - Related questionnaire items: F7
  - Mean score: ____
  - Status: Achieved / Needs Improvement

### Mean Score Computation Guide

Use the following formulas when filling the mean score fields above.

1. **Per-item mean score**  
   For each security requirement, compute:
   `Mean score = (sum of all respondent ratings for that item) / (number of valid respondents for that item)`

2. **Handling N/A responses**  
   Exclude N/A answers from both numerator and denominator.  
   Use only valid numeric ratings (1 to 5).

3. **Overall security mean (optional summary line)**  
   After computing each item mean, compute:
   `Overall security mean = (mean_auth + mean_firewall + mean_mac + mean_session + mean_logs) / 5`

4. **Status decision rule (recommended)**  
   - If mean score >= 4.00 -> **Achieved**
   - If mean score < 4.00 -> **Needs Improvement**

5. **Quick example (per item)**  
   If User authentication has ratings `5, 4, 4, 5, 3` from 5 valid respondents:  
   `Mean = (5 + 4 + 4 + 5 + 3) / 5 = 21 / 5 = 4.20` -> **Achieved**

---

## L. Notes for Adviser Consultation

During consultation, present:

1. The questionnaire instrument (Sections A-H),
2. The scoring rules (Section I), and
3. The security-controls evidence notes (Section J).

This makes the chapter claim defensible because each objective is tied to measurable user-facing evidence plus concrete technical controls.

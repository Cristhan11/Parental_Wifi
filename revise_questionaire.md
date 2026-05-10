# Concise Chapter 4 Questionnaire (Strictly Reference-Based)

This instrument is concise and limited to your study scope/objectives only.

Use response scale:

- 5 = Strongly Agree
- 4 = Agree
- 3 = Neutral
- 2 = Disagree
- 1 = Strongly Disagree
- N/A = Not Applicable

## Informed consent

I confirm that my participation is voluntary. I acknowledge the following:

- My responses will be used exclusively for this thesis and related academic assessment, and for no other purpose.
- I consent to the use of all information gathered through this instrument—including numeric ratings, written answers, and session or observer notes where applicable—for analysis, interpretation, and inclusion in the thesis document, oral defense, supervisory review, grading, and other outputs required or customary for completion of this academic program.
- I grant permission for that material to be retained, reproduced, and cited in thesis chapters, appendices, presentation materials, and institutional thesis archives or repositories, for scholarly and degree-related purposes, without separate approval for each routine academic reuse.

Name / signature (optional): _________________________________  

Date: _________________________________

## Reference Codes (used per question)

- **[R1]** Cloudflare website security checklist
- **[R2]** Arphost website security best practices
- **[R3]** HackerOne 9-point website security checklist
- **[R4]** Dev.to essential security measures
- **[R5]** Bitcatcha secure website standard
- **[R6]** Cybersecurity Assessment Questionnaire (Scribd)
- **[R7]** Vendict security questionnaire items
- **[R8]** Copla cybersecurity risk questionnaire guide
- **[R9]** NCBI developmental age bracket reference 1
- **[R10]** NCBI developmental age bracket reference 2
- **[R11]** OWASP CSRF Prevention Cheat Sheet
- **[R12]** OWASP Session Management Cheat Sheet
- **[R13]** Philippines National Privacy Commission (NPC) Circular 16-01
- **[R14]** Philippines DICT National Cybersecurity Plan 2023-2028
- **[R15]** Aufait UX — UI/UX design principles for child-friendly interfaces
- **[R16]** Gapsy Studio — UX design for kids (age-segmented practices)

---

## A. Child Portal Age-Bracket Questionnaire (Ages 7-11)

### A.1 Screening

- Exact age: ______
- Bracket: [ ] 7-8  [ ] 9-11  [ ] 12+ (comparison only)
- Observer present: [ ] Yes [ ] No

Primary analysis group: **7-11 years old**. [R9][R10]

### A.1.1 Bracket Justification (Defensible Source Basis)

- **7-8 bracket (early school-age):** grouped separately because this is the early part of the concrete-operational/school-age period, where children begin logical operations but still need simpler, concrete instructions and guided flow. Basis:  
  - R10 states the concrete operational stage is **7-11**.  
  - R9 states **6-12** as early school years and notes limits in abstract thinking.  
  Sources: [R9](https://www.ncbi.nlm.nih.gov/books/NBK537095/), [R10](https://www.ncbi.nlm.nih.gov/books/NBK448206/)

- **9-11 bracket (later school-age):** grouped separately because this is the later part of the same concrete-operational period, where children typically show stronger logical handling of rules, conservation, and task sequencing compared with younger peers. Basis:  
  - R10 describes continuing concrete-operational development up to about age 11 and increasing logical manipulation skills.  
  - R9 describes school-age development where reasoning and rule-based understanding become more established before abstract/formal operations.  
  Sources: [R9](https://www.ncbi.nlm.nih.gov/books/NBK537095/), [R10](https://www.ncbi.nlm.nih.gov/books/NBK448206/)

- **12+ (comparison only):** included only as an external comparator because R9 and R10 place ages 12+ in/near formal operations with greater abstract reasoning, which is outside your primary user target.  
  Sources: [R9](https://www.ncbi.nlm.nih.gov/books/NBK537095/), [R10](https://www.ncbi.nlm.nih.gov/books/NBK448206/)

### A.2 Questions (Child + Parent Observer)

*Source basis for this subsection: child-friendly UI/UX guidance (simplicity, navigation, feedback, clear guidance with visuals, touch targets).*

1. (Source-selected from R15 — Simplicity and Clarity)  
   "Children benefit from interfaces that are straightforward and easy to navigate... uncluttered with minimal text, using large, tappable areas for interaction."  
   The portal screen feels simple and easy to scan; the child can see what to do next without overload. [R15]  
Score: ___
2. (Source-selected from R15 — Intuitive Navigation)  
   "Design navigation paths that children can easily follow. Use familiar icons and imagery... and avoid complex menus or hidden functions."  
   The child can move through the portal flow (e.g., time shown → choose activity → continue) without getting lost in menus or unclear steps. [R15]  
Score: ___
3. (Source-selected from R15 — Visual and Auditory Feedback)  
   "Children respond well to immediate feedback. Use visual cues like color changes and animations, as well as auditory feedback... to guide and encourage them."  
   After taps or choices, the portal gives clear feedback (visual and/or sound) so the child knows the action worked. [R15]  
Score: ___
4. (Source-selected from R15 — Clear Instructions and Guidance; R16 — emerging readers)  
   "Provide clear, age-appropriate instructions... Use visual aids like arrows, icons, and animations to guide them through tasks." [R15] For early elementary users, text can be used "but sparingly and strategically," labels work best when "paired with clear, recognizable icons," and navigation should not "rely on reading alone." [R16]  
   The child understands what the portal is asking them to do (which activity, how to proceed) from icons, layout, and short on-screen cues—not from long blocks of text. [R15][R16]  
Score: ___
5. (Source-selected from R16 — touch targets and motor fit; R15 — large tappable areas)  
   "Interactive elements should start at a minimum of 60×80 points for young users... spacing between elements is equally critical" (younger children); older children in the range can use progressively smaller but still forgiving targets.  
   Buttons and tap areas on the portal are big enough and spaced well enough for this child to use without constant wrong taps. [R16][R15]  
Score: ___

Observer note: Assistance needed? [ ] Yes [ ] No; completion time: ____ min.

---

## B. IT Specialist Security Questionnaire

1. User authentication controls are implemented for internal and external access. [R7]  
Source basis: "What authentication methods do you use for internal and external access?"  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions  
Score: ___
2. Firewall rules follow a default-deny approach and allow only necessary traffic. [R8]  
Source basis: "Are firewalls configured with a default deny rule set, only allowing necessary traffic?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Score: ___
3. MAC whitelisting/device allowlist controls are applied to handle unauthorized devices. [R7]  
Source basis: "How are unauthorized devices or shadow IT identified and handled?"  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions  
Score: ___
4. CSRF protection is implemented for state-changing requests using server-side tokens. [R11]  
Source basis: "CSRF tokens should be generated on the server-side and ... once per user session or each request."  
Source: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html  
Score: ___
5. Session management is secured by renewing/regenerating session IDs after privilege changes. [R12]  
Source basis: "The session ID must be renewed or regenerated ... after any privilege level change."  
Source: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet  
Score: ___
6. Security logs are reviewed regularly to detect suspicious or unauthorized activities. [R8]  
Source basis: "Are network traffic logs reviewed regularly for suspicious or unauthorized activities?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Score: ___

Philippine policy alignment references for Chapter 4 discussion (optional):
- [R13] National Privacy Commission (NPC) Circular 16-01 (security of personal data): https://privacy.gov.ph/npc-circular-16-01-security-of-personal-data-in-government-agencies/
- [R14] DICT National Cybersecurity Plan 2023-2028: https://cms-cdn.e.gov.ph/DICT/pdf/NCSP-2023-2028-FINAL-DICT.pdf

---

## C. Parent Dashboard Survey (Scope/Objectives-Focused)

1. I can monitor visited websites of assigned child devices, and I can manually flag and block selected websites. [R8][R7]  
Source basis: "Are network traffic logs reviewed regularly for suspicious or unauthorized activities?" (R8), "How do you handle firewall rule reviews and lifecycle management?" (R7)  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/ ; https://vendict.com/blog/50-essential-security-questionnaire-questions  
Scope: 1, 7 | Objective: 1, 3, 5  
Score: ___
2. The system redirects the child device to quiz/video activity and only continues internet after required completion/passing. [R7]  
Source basis: "What controls are in place to enforce least privilege and need-to-know principles?"  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions  
Scope: 2, 7 | Objective: 2, 3, 5  
Score: ___
3. I can set and enforce internet schedule and duration limits for each assigned child device. [R7]  
Source basis: "How are network changes reviewed and authorized?"  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions  
Scope: 3, 7 | Objective: 3, 5  
Score: ___
4. I receive real-time notifications for time limit reached, flagged-site visit, blocked-site attempt, and new-device connection. [R8]  
Source basis: "Are real-time alerts configured for critical events or threshold breaches?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Scope: 4 | Objective: 1, 3  
Score: ___
5. I can monitor the total time each child device spends online. [R8]  
Source basis: "Are key security metrics ... tracked and reported to management?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Scope: 5 | Objective: 1, 3, 5  
Score: ___
6. The dashboard provides clear daily, weekly, and monthly reports (usage, visited sites, flagged/blocked attempts, and bandwidth). [R8]  
Source basis: "Does the organization have defined KPIs or KRIs ... for cybersecurity?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Scope: 6, 7 | Objective: 1, 3, 5  
Score: ___
7. I can manage connected devices using block and whitelist controls. [R7]  
Source basis: "How are unauthorized devices or shadow IT identified and handled?"  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions  
Scope: 8, 7 | Objective: 3, 4, 5  
Score: ___
8. Parent/admin dashboard access is secure through authentication and secure session handling. [R7][R12]  
Source basis: "What authentication methods do you use for internal and external access?" (R7), "The session ID must be renewed or regenerated..." (R12)  
Source: https://vendict.com/blog/50-essential-security-questionnaire-questions ; https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet  
Scope: 9 | Objective: 4, 7  
Score: ___
9. Network protection controls (firewall rules and MAC/device allowlist behavior) prevent unauthorized access attempts. [R8][R7]  
Source basis: "Are firewalls configured with a default deny rule set...?" (R8), "How are unauthorized devices or shadow IT identified and handled?" (R7)  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/ ; https://vendict.com/blog/50-essential-security-questionnaire-questions  
Scope: 9, 8 | Objective: 4, 7  
Score: ___
10. The system logs and monitors security-relevant activity for review and incident response. [R8]  
Source basis: "Are network traffic logs reviewed regularly for suspicious or unauthorized activities?"  
Source: https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/  
Scope: 9 | Objective: 7  
Score: ___
11. The system works reliably at home using our local setup and PLDT modem connection for internet control and child portal access. [R14]  
Source basis: national cybersecurity planning emphasizes layered, network-level controls.  
Source: https://cms-cdn.e.gov.ph/DICT/pdf/NCSP-2023-2028-FINAL-DICT.pdf  
Scope: 7 | Objective: 1, 6  
Score: ___

---

## D. Objective/Scope Mapping (for Chapter 4)

- Scope 1, 7: C1, C2
- Scope 2: C8, C9, A2(1-5)
- Scope 3: C3
- Scope 4: C4
- Scope 5: C5
- Scope 6: C6
- Scope 8: C7
- Scope 9: B1-B6, C10

---

## E. Quick Analysis Rules

- Compute mean per section (A, B, C).
- Decision rule:
  - Mean >= 4.00 = achieved/acceptable
  - Mean 3.50-3.99 = partially achieved, improve
  - Mean < 3.50 = not achieved, needs revision
- For age bracket, report 7-8 and 9-11 separately plus assistance rate.

---

## F. Full References

- [R1] https://www.cloudflare.com/learning/security/glossary/website-security-checklist/
- [R2] https://arphost.com/website-security-best-practices/
- [R3] https://www.hackerone.com/knowledge-center/ultimate-9-point-website-security-checklist
- [R4] https://dev.to/adityabhuyan/essential-security-measures-for-safeguarding-your-system-and-protecting-user-data-49c5
- [R5] https://www.bitcatcha.com/secure-website/standard/
- [R6] https://www.scribd.com/document/836479530/Cybersecurity-Assessment-Questionnaire-V1
- [R7] https://vendict.com/blog/50-essential-security-questionnaire-questions
- [R8] https://copla.com/blog/cybersecurity/the-complete-guide-to-information-and-cybersecurity-risk-assessment-questionnaire/
- [R9] https://www.ncbi.nlm.nih.gov/books/NBK537095/
- [R10] https://www.ncbi.nlm.nih.gov/books/NBK448206/
- [R11] https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- [R12] https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet
- [R13] https://privacy.gov.ph/npc-circular-16-01-security-of-personal-data-in-government-agencies/
- [R14] https://cms-cdn.e.gov.ph/DICT/pdf/NCSP-2023-2028-FINAL-DICT.pdf
- [R15] https://www.aufaitux.com/blog/ui-ux-designing-for-children/
- [R16] https://gapsystudio.com/blog/ux-design-for-kids/

# Consultation Sheet — Manuscript Documentation Adjustments

Based on the panel suggestions from Engr. Zarate's
feedback, mapped to the corresponding sections in the
manuscript (Chapter1_4.md).

---

## Chapter I: The Problem

| Section/Page          | Comments / Suggestions          | Compliance |
|-----------------------|---------------------------------|------------|
| **1.1 The Problem**   | Emphasize that the main problem | |
|                       | addressed is unsupervised       | |
|                       | children browsing the internet  | |
|                       | Specify that the system targets | |
|                       | children below 18 years old     | |
| **1.2 The Client**    | Clearly state the target        | |
|                       | beneficiaries — both parents    | |
|                       | and children                    | |
|                       | Specify the target age group    | |
|                       | (below 18) for children users   | |
| **1.3 The Project/**  | Differentiate and clearly       | |
| **Solution**          | define the roles of Admin,      | |
|                       | Parent, Child Device, and Guest | |
|                       | throughout the description      | |
|                       | Document the admin intervention | |
|                       | justification for account       | |
|                       | creation (security purposes,    | |
|                       | prevent children from creating  | |
|                       | parent accounts)                | |
|                       | Document the authentication     | |
|                       | method — MAC address-based      | |
|                       | device identification approved  | |
|                       | by admin                        | |
|                       | Document the time reduction     | |
|                       | mechanism — countdown starts on | |
|                       | child device connection, pauses | |
|                       | on disconnect if remaining time | |
|                       | exists                          | |
|                       | Change references of quiz       | |
|                       | passing score to passing        | |
|                       | percentage                      | |
|                       | Include activity logs —         | |
|                       | document that the system        | |
|                       | records all admin activities    | |
|                       | through logs                    | |
| **1.4 The Project**   | Add an objective for admin      | |
| **Objectives**        | activity logging functionality  | |
|                       | Add an objective for role       | |
|                       | differentiation (Admin, Parent, | |
|                       | Child Device, Guest)            | |
|                       | Document the authentication     | |
|                       | process — admin approves        | |
|                       | devices via MAC address for     | |
|                       | role assignment                 | |
| **1.5 Scope and**
| **Delimitation**
|                       | Document the auto-detection     | |
|                       | limitation — system can detect  | |
|                       | MAC address on first connection | |
|                       | but cannot determine who the    | |
|                       | user is; admin must assign      | |
|                       | roles                           | |
| **1.6 Design**        | Document the auto-detection     | |
| **Constraints**       | limitation as a design          | |
|                       | constraint — device identity    | |
|                       | cannot be auto-determined,      | |
|                       | requires admin role assignment  | |


---

## Chapter IV: Final Design

| Section/Page          | Comments / Suggestions          | Compliance |
|-----------------------|---------------------------------|------------|
| **4.1.2 Software**    | Differentiate the roles of      | |
| **Design**            | Admin, Parent, Child Device,    | |
|                       | and Guest in the software       | |
|                       | architecture description        | |
|                       | Document the authentication     | |
|                       | flow — MAC address-based        | |
|                       | identification with admin       | |
|                       | approval                        | |
|                       | Document admin activity logs    | |
|                       | in the software design          | |
|                       | Document the time reduction     | |
|                       | mechanism in the time tracking  | |
|                       | and enforcement section         | |
|                       | Update quiz system references   | |
|                       | to use passing percentage       | |
|                       | instead of passing score        | |
|                       | Add instructions for device     | |
|                       | naming conventions in the       | |
|                       | device management section       | |
|                       | Add label for roles in          | |
|                       | different device lists           | |
|                       | described in the design         | |

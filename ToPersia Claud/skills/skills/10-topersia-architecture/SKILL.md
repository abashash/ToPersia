---
name: topersia-architecture
description: Decision framework for long-lived ToPersia architecture, including seven-effect evaluation and five-year scalability.
---

# Architecture Decision Framework

## First question
«آیا با آنچه هست می‌شود؟»

## Option evaluation
For each viable option provide a table with:
- SEO
- GEO
- AEO
- Multilingual 3→9 languages
- Maintenance cost
- Development speed
- 5-year scalability

Then rank options by 5-year scalability only.

## Reject
Reject approaches that create a foreseeable broad migration or redesign later, even if faster today.

## First-option failure modes
For the top option, list:
- failure mode
- trigger
- detection
- silent vs visible
- mitigation
- rollback

## Evidence discipline
Separate:
- documented fact
- [قضاوت من]
- unknown / «در اسناد یافت نشد»

## Output
Any build/import schema must be a complete table, including exact:
Post Type Slug, Rewrite Slug, Meta Key, Relation, Query and relevant UI setting names.

📄 منبع: `00-INDEX-AND-RULES`, `00-CORE-INSTRUCTIONS.md`,
`05-TOPERSIA-DATA.md`, `06-JSON-GENERATION.md` و snapshot واقعی پروژه.

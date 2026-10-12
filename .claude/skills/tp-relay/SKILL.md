---
name: tp-relay
description: رلهٔ کار ToPersia بین Claude Code محلی (WAMP) و جلسهٔ ابری Claude Code — آغاز و پایان نوبت ابری، به‌روزکردن CURRENT.md، گیت‌ها، commit و push شاخه، و آماده‌سازی تحویل پیش از تمام شدن سهمیه. Use when the user mentions cloud session, relay, handoff, teleport, «ابر»، «تحویل»، «برگشت از ابر» or when CLAUDE_CODE_REMOTE=true.
---

# tp-relay — نوبت‌دهی محلی ⇄ ابری

مرجع: `ToPersia Claud/workspace/docs/relay/RELAY.md` (تصمیم‌های OPS-D001…OPS-D007).
ابزار: `php "ToPersia Claud/workspace/scripts/relay/tp-relay.php" <فرمان>` — از ریشهٔ مخزن.
نوبت جاری: `ToPersia Claud/workspace/docs/relay/CURRENT.md` — دو بخش جدا؛ **هر طرف فقط بخش خودش را ویرایش می‌کند**.

## در جلسهٔ ابری (`CLAUDE_CODE_REMOTE=true`)

**آغاز**
1. `ToPersia Claud/workspace/CLAUDE.md` و `CURRENT.md` را بخوان. کار همان «دستور این جلسهٔ ابری» در بخش ابری است؛ اگر «(خالی)» است، «قدم بعدی» بخش محلی.
2. `tp-relay.php status` — شاخه و آخرین تحویل و گیت را ببین. روی شاخه‌ای کار کن که جلسه ساخته؛ main نه.
3. مسیرهای lane ابری در جدول CURRENT.md مرز کار است.

**حین کار**
- پس از هر گام معنادار: `tp-relay.php gates`. قرمز ← همان‌جا درست کن.
- آزمونی که این‌جا اجرا نمی‌شود (contrast · acceptance · behaviour · user-flows · `*-local.php` · WPML · Lighthouse · فایرفاکس) را هرگز سبز اعلام نکن؛ در «در انتظار تأیید محلی» بخش ابری بنویس کدام آزمون و چرا.

**پایان نوبت** (پیش از توقف، یا وقتی کاربر گفت «جمع کن»)
1. `tp-relay.php gates --record` — باید سبز باشد؛ یک سطر در `GATES.md` می‌نشیند.
2. بخش ابری CURRENT.md: «انجام‌شده» (با نام پرونده‌ها)، «قدم بعدی» (دقیق و اجراشدنی برای عامل بعدی)، «در انتظار تأیید محلی».
3. commit با پیام دسته‌ای فارسی، سپس `git push` همان شاخه. بدون PR مگر کاربر بخواهد.
4. پاسخ آخر: نام شاخه، hash آخرین commit، خلاصهٔ گیت‌ها، و فرمان برگشت محلی:
   `php "ToPersia Claud\workspace\scripts\relay\tp-relay.php" from-cloud <شاخه> --gates`

## روی ماشین محلی

- بخش محلی CURRENT.md را در هر نقطهٔ عطف به‌روز نگه دار (OPS-D003) — اگر سهمیه ناگهان تمام شد، کاربر بی تو تحویل می‌دهد و جلسهٔ ابری از «قدم بعدی» تو ادامه می‌دهد.
- کار موازی (OPS-D005): پیش از تحویل، جدول lane را پر کن تا دو طرف روی یک پرونده نروند.
- تحویل: کاربر (یا تو، اگر خواست) اجرا می‌کند `to-cloud [--task="…"]` — WIP روی `relay/…` commit و push می‌شود و `claude --cloud` باز می‌شود.
- برگشت: `from-cloud` ← بازبینی روی `review/…` ← آزمون‌های «در انتظار تأیید محلی» ← `from-cloud <شاخه> --gates --squash` ← commit دسته‌ای و ثبت hash در `docs/COMMIT-LOG.md`.

# خطة إصلاح أخطاء الـ API

## الهدف

- [x] إغلاق العيوب المؤكدة بالترتيب من Critical إلى Low دون تغيير عقد الـ API دون قرار موثق.
- [x] إعادة اختبار البنود غير المحسومة وتصنيفها بأدلة قابلة للتكرار.
- [x] فصل أخطاء الـ endpoint والـ contract والبيئة عن عيوب الـ backend.

## لقطة الحالة الحالية

- التاريخ: 2026-07-20
- إطار العمل: Laravel 13 / PHP 8.3 / PHPUnit 12
- المصدر: `pasted-text.txt` المرفق (Updated Triage)
- العيوب المؤكدة: 10
- تحتاج إعادة اختبار: 6
- Endpoint / Contract / Environment: 11
- مغلق بالتحديث: 1 (`API-BUG-API-BUG-018`)
- الحالة: مكتملة — أغلقت حالات triage الأصلية الـ 29 وتحقق suite كاملة بنجاح.
- آخر خطوة تم التحقق منها: نجاح 95 اختبارًا و426 assertions مع exit code 0، ونجاح Pint وroute/Postman audits.

## مسارات العمل

- كود التطبيق: `app/`
- المسارات: `routes/`
- اختبارات الـ API: `tests/Feature/`
- ملف المتابعة: `BUG_FIX_PLAN.md`

## قواعد قبل التنفيذ

- [x] إنشاء أو تحديث PHPUnit Feature Test يعيد إنتاج البلاغ قبل كل إصلاح.
- [x] عدم اعتبار المهمة مكتملة إلا بعد نجاح اختبارها المنفرد بـ `php artisan test --compact`.
- [x] استخدام Policy أو Gate لأي قرار وصول، وعدم وضع ownership check متفرق داخل controller.
- [x] تثبيت عقود البنود المبهمة (`024`, `026`, `017`) صراحة داخل هذه الخطة قبل تغيير السلوك.
- [x] عدم تعديل البنود المصنفة Wrong Endpoint / Contract / Environment في backend دون دليل؛ اقتصرت تغييراتها على tests وPostman.
- [x] تشغيل `vendor/bin/pint --dirty --format agent` بعد تعديلات PHP.

## المرحلة 0 — تثبيت خط الأساس

- [x] تشغيل اختبارات النطاق الحالية: `PasswordResetFlowTest`, `GlobalSearchTest`, `TaskApiTest`.
- [x] تسجيل أي فشل سابق للإصلاح داخل قسم سجل التقدم أدناه.
- [x] إنشاء ملفات Feature Test خاصة بالاجتماعات والتسجيل/الملف الشخصي: `MeetingApiTest`, `RegistrationApiTest`, `ProfileApiTest`, `ProfileTaskApiTest`.
- [x] بوابة المراجعة: الاختبارات الحالية موثقة، ويمكن التمييز بين failures القديمة والجديدة.

## المرحلة 1 — Critical (P0)

### `API-TASK-DEL-009` — حذف مهمة بلا صلاحية

- [x] تحديد قاعدة الحذف المعتمدة: منشئ المشروع المرتبط بالمهمة فقط؛ الإسناد للمهمة لا يمنح حق الحذف.
- [x] إضافة/استكمال `TaskPolicy::delete` وربط `destroy` بالـ policy.
- [x] اختبار أن المستخدم المصرح له يستطيع الحذف ويحصل على الاستجابة المتوقعة.
- [x] اختبار أن مستخدمًا authenticated غير مصرح له يحصل على `403` وتبقى المهمة في قاعدة البيانات.
- [x] اختبار أن الضيف يحصل على `401`.
- [x] فحص `show` و`update` و`index` سريعًا لنفس نمط تسريب الوصول، وفتح بنود منفصلة إن ثبتت مشاكل خارج نطاق الحذف.
- [x] بوابة المراجعة: نجاح اختبار حذف المهمة منفردًا وعدم وجود مسار حذف يتجاوز الـ policy.

## المرحلة 2 — High (P1)

### `API-SEARCH-007` — global search يستخدم `tasks.name`

- [x] إضافة حالة فاشلة إلى `tests/Feature/GlobalSearchTest.php` لمشروع مرتبط بمهمة عنوانها في `tasks.title`.
- [x] تحديث fixtures القديمة في `GlobalSearchTest` التي ما زالت تنشئ task بحقل `name` حتى تطابق عقد `title` الحالي.
- [x] استبدال العمود غير الموجود في `Project::globalSearchRelations()` مع الحفاظ على الأعمدة اللازمة للعلاقة.
- [x] اختبار البحث بعنوان المهمة، والبحث بلا نتائج، وعدم حدوث SQL exception.

### `API-BUG-API-BUG-017` — المستخدم المحذوف يعود عبر عضوية الفريق

- [x] توثيق معنى الإزالة: `user_ids` المرسلة في update هي القائمة النهائية الصريحة للمشاركين مع إبقاء المنشئ؛ `team_ids` تبقى مرتبطة كسياق ولا تعيد أعضاءها في الطلب نفسه.
- [x] تنفيذ أولوية القائمة الصريحة دون إضافة جدول استثناءات: لا تُمرر `team_ids` إلى resolver عندما تكون `user_ids` موجودة.
- [x] إبقاء سلوك تحديث `team_ids` منفردة كإعادة احتساب لأعضاء الفرق؛ إزالة رابطة الفريق تتم عبر إرسال `team_ids` الجديدة ضمن نفس transaction.
- [x] اختبار مستخدم مباشر، ومستخدم قادم من فريق، ومستخدم موجود بالطريقتين، ومنشئ الاجتماع الذي يجب أن يبقى مشاركًا.
- [x] اختبار أن المشاركين والإشعارات بعد التحديث يعكسان القائمة النهائية فقط.
- [x] بوابة المراجعة: نجاح اختبارات البحث والاجتماع، واعتماد عقد إزالة المشاركين داخل هذه الخطة.

## المرحلة 3 — Medium (P2)

### `API-AUTH-RST-004` — إعادة استخدام كلمة المرور نفسها

- [x] إضافة اختبار إلى `tests/Feature/PasswordResetFlowTest.php` يثبت رفض كلمة المرور الحالية برسالة validation على `password`.
- [x] تنفيذ المقارنة باستخدام `Hash::check` قبل تحديث المستخدم، مع الإبقاء على reset token صالحًا عند الرفض.
- [x] اختبار نجاح كلمة مرور مختلفة، وفشل الكلمة نفسها، وعدم إصدار access token عند الفشل.

### `API-BUG-API-SUM-024` — summary يحسب assigned tasks فقط

- [x] اعتماد تعريف “مهامي”: اتحاد المهام المسندة، ومهام المشاريع المنشأة بواسطة المستخدم، ومهام مشاريع فرقه.
- [x] تعديل استعلام `ProfileService::getTaskSummary()` وفق العقد المعتمد؛ استخدام `whereHas` المبني على EXISTS يمنع تكرار الصفوف عند تعدد مصادر الوصول.
- [x] اختبار كل status (`pending`, `in_progress`, `in_review`, `completed`) مع حالة غير مرئية وحالة تظهر عبر مصدرين.
- [x] التحقق من ثبات مفاتيح التوافق `total`, `to_do`, `done`.

### `API-BUG-API-Meeting-026` — اجتماع بلا أي نطاق أو مشاركين

- [x] اعتماد القاعدة: طلب واحد على الأقل من `project_id`, `team_ids`, `user_ids`؛ الاجتماع الشخصي بلا نطاق غير مسموح.
- [x] تطبيق validation مشروط في `StoreMeetingRequest` وفق القرار.
- [x] اختبار payload فارغ النطاق والمصفوفات الفارغة، وكل مصدر منفرد، والمزيج بين المصادر.

### `API-BUG-API-Meeting-027` — تحديث الاجتماع بتاريخ قديم

- [x] إضافة validation يمنع `starts_at` الماضي عند إرساله في update.
- [x] معالجة تحديث `ends_at` منفردًا مقابل `starts_at` المخزن حتى لا تمر نهاية أقدم من البداية الحالية.
- [x] اختبار التاريخ الماضي، والمستقبل، وتحديث النهاية فقط، وعدم إرسال حقول التاريخ.
- [x] بوابة المراجعة: نجاح اختبارات reset/profile/meeting المنفردة واعتماد عقود `024` و`026` داخل الخطة.

## المرحلة 4 — Low (P3)

### `API-AUTH-001` و`API-AUTH-002` — اسم بلا حروف فعلية

- [x] إضافة حالات تسجيل للاسم الرقمي فقط، والرموز فقط، واسم عربي، واسم لاتيني، واسم يحتوي مسافات/علامات مقبولة.
- [x] إضافة قاعدة Unicode-aware تفرض وجود حرف واحد على الأقل دون منع الأسماء العربية أو الشرطات والفواصل المسموح بها.
- [x] الحفاظ على قواعد الطول الحالية دون تغيير.

### `API-BUG-API-Meeting-028` — `days` بلا حد أعلى

- [x] نقل validation إلى `UpcomingMeetingsRequest` بدل القراءة المباشرة من `Request`.
- [x] اعتماد نطاق integer من 1 إلى 365.
- [x] اختبار القيم السالبة، والصفر، والحدين، وما فوق الحد، والقيمة الافتراضية 7 ونافذة النتائج.
- [x] بوابة المراجعة: نجاح اختبارات التسجيل وupcoming meetings؛ تشغيل الاختبارات المتأثرة مجتمعة مؤجل لمرحلة التحقق النهائي.

## المرحلة 5 — إعادة الاختبار قبل أي إصلاح

- [x] `API-TEAM-005`: نجح DELETE بـ JSON وبـ form-encoded body ووصلت `user_ids` وتم حذف العضوين؛ التصنيف Closed / Not Reproducible.
- [x] `API-TASK-UPD-006`: نجح تحديث `start_date` وحده ثم مع `title` وثبتت القيم في database والـ resource؛ التصنيف Closed / Not Reproducible.
- [x] `API-BUG-API-BUG-020`: نجح تحديث اسم الفريق وثبت في response وdatabase؛ التصنيف Closed / Not Reproducible.
- [x] `API-BUG-API-BUG-021`: أعاد `show` مصفوفة `members` وبداخلها العضو المعروف؛ التصنيف Closed / Data-specific.
- [x] `API-BUG-API-BUG-022`: نجح تحديث اسم profile في response وdatabase وGET لاحق؛ التصنيف Closed / Not Reproducible.
- [x] `API-BUG-API-CHAT-023`: نجح direct chat لمستخدمين في نفس `team_user` ورُفض 403 عند اشتراكهما في مشروع دون فريق مشترك؛ التصنيف Closed / Contract Confirmed.
- [x] لكل بند: Feature Test يحفظ method/URL/body/status والتحقق من response/database كدليل قابل للتكرار.
- [x] بوابة المراجعة: أغلقت البنود الستة بأدلة مستقرة ولم ينتقل أي منها إلى مسار الإصلاح.

## المرحلة 6 — تصحيح الاختبارات والعقود والبيئة

- [x] تدقيق وتصحيح base URL للبنود `008`, `010`, `011`, `012`, `013`, `014`, `015`, `016`؛ modular collections تستخدم base بلا `/api` والطلبات تضيف `/api` مرة واحدة، كما صُححت Auth URLs في collection القديمة.
- [x] تثبيت عقد task على `title` بدل `name` للبند `019`؛ جميع أجسام Tasks collection تستخدم `title` ولا يوجد body يستخدم `name`.
- [x] تثبيت بيئة الإشعارات على المنفذ `8000` وتصحيح methods في Meetings collection من POST إلى PATCH للبند `025`.
- [x] تثبيت `GET` لمحادثة المشروع في Chat collection للبند `029` بما يطابق route الحالي.
- [x] توثيق `018` كمغلق بعد نجاح Feature Test لـ `/api/projects/{project}/analytics/tasks` (4 assertions).
- [x] بوابة المراجعة: جميع ملفات Postman الستة JSON صالحة، ولا يوجد `api/api`، ونجح تدقيق method/contract/environment الآلي.

## المرحلة 7 — التحقق النهائي

- [x] تشغيل ملفات الاختبار المتأثرة منفردة أولًا، ثم مجتمعة: 39 اختبارًا و195 assertions.
- [x] تشغيل مجموعة الاختبارات كاملة: 95 اختبارًا و426 assertions، exit code 0.
- [x] تشغيل `vendor/bin/pint --dirty --format agent` و`git diff --check` بنجاح.
- [x] إعادة تدقيق حالات الـ triage الـ 29 وتحديث status والدليل لكل مجموعة أدناه.
- [x] التأكد من الحفاظ على التغييرات السابقة غير المرتبطة في `Team.php` وmigrations دون استبدالها أو حذفها.
- [x] بوابة الإغلاق: صفر عيوب Confirmed غير معالجة ضمن triage الأصلي، وصفر Needs Re-test بلا دليل، وكل تغيير مرتبط باختبار ناجح.

## سجل التقدم

- 2026-07-20: إنشاء الخطة من Updated Triage.
- 2026-07-20: رصد تغييرات موجودة مسبقًا وغير مرتبطة في `app/Models/Team.php` وmigration notifications وmigration workflow؛ يجب الحفاظ عليها أثناء التنفيذ.
- 2026-07-20: baseline (`PasswordResetFlowTest`, `GlobalSearchTest`, `TaskApiTest`) = 20 اختبارًا؛ 17 ناجحًا، 1 failure، و2 errors.
- 2026-07-20: أخطاء baseline السابقة: حالتان في `GlobalSearchTest` تنشئان `tasks.name` غير الموجود، وحالة seeder تتوقع 100 مستخدم بينما قاعدة الاختبار تحتوي 0.
- 2026-07-20: إصلاح `API-TASK-DEL-009` عبر `app/Policies/TaskPolicy.php` وربطه بـ `TaskController::destroy`؛ نجح `TaskApiTest` كاملًا (6 اختبارات، 18 assertions)، ثم نجحت اختبارات الحذف المقواة (3 اختبارات، 7 assertions).
- 2026-07-20: فحص الصلاحيات كشف بنودًا منفصلة تحتاج triage: `TASK-AUTH-INDEX`, `TASK-AUTH-SHOW`, `TASK-AUTH-UPDATE`, `TASK-AUTH-STORE`, `PROJECT-TASK-AUTH`؛ لا توجد حاليًا قيود وصول كافية في هذه المسارات.
- 2026-07-20: إصلاح `API-SEARCH-007` بتغيير relation إلى `tasks.title` وإصلاح fixtures وربط الاختبار بـ `GlobalSearchDemoSeeder` مباشرة؛ نجح `GlobalSearchTest` كاملًا (15 اختبارًا، 66 assertions).
- 2026-07-20: إصلاح `API-BUG-API-BUG-017` بجعل `user_ids` الصريحة هي القائمة النهائية في update؛ نجح `MeetingApiTest` (اختباران، 9 assertions) بما يشمل الإزالة والإشعارات وعدم التكرار.
- 2026-07-20: إصلاح `API-AUTH-RST-004`؛ نجح `PasswordResetFlowTest` (3 اختبارات، 19 assertions) ويثبت بقاء reset session وعدم إصدار token عند الرفض.
- 2026-07-20: إصلاح `API-BUG-API-SUM-024` عبر `Task::accessibleToUser`؛ نجح `ProfileTaskApiTest` (اختبار واحد، 8 assertions) للمصادر الثلاثة ومنع التكرار.
- 2026-07-20: إصلاح `026` و`027` في Form Requests؛ نجح `MeetingApiTest` كاملًا (4 اختبارات، 24 assertions).
- 2026-07-20: إصلاح `001` و`002` بقاعدة Unicode letters؛ نجح `RegistrationApiTest` (اختباران، 10 assertions). أُصلح كذلك فشل التسجيل المكتشف بوضع القيم الابتدائية لـ `job_title` و`exp`.
- 2026-07-20: إصلاح `028` عبر `UpcomingMeetingsRequest` بنطاق 1..365؛ نجح `MeetingApiTest` كاملًا (5 اختبارات، 44 assertions).
- 2026-07-20: إعادة اختبار `005`, `006`, `020`, `021`, `022`, `023`؛ نجحت 12 حالة ضمن `TeamApiTest`, `TaskApiTest`, `ProfileApiTest`, `ChatApiTest` بإجمالي 44 assertions، وأغلقت البنود كلها.
- 2026-07-20: تدقيق 6 Postman collections وتصحيح Auth URLs القديمة وnotification methods؛ جميع الملفات صالحة ولا يوجد `api/api`، ونجح contract audit بالكامل.
- 2026-07-20: إعادة اختبار analytics للبند `018` نجحت (اختبار واحد، 4 assertions).
- 2026-07-20: إصلاح اكتشاف PHPUnit 12 لأربعة ملفات Project tests قديمة؛ أصبحت 19 حالة تعمل وتنجح بدل warnings مخفية.
- 2026-07-20: التحقق النهائي = 95/95 اختبارًا، 426 assertions، exit code 0؛ Pint و`git diff --check` ناجحان.
- 2026-07-20: ملخص الـ 29: Fixed/Verified = `001, 002, 003, 004, 007, 009, 017, 018, 024, 026, 027, 028`؛ Closed after re-test = `005, 006, 020, 021, 022, 023`؛ Endpoint/Contract/Environment corrected = `008, 010, 011, 012, 013, 014, 015, 016, 019, 025, 029`.
- 2026-07-20: بنود أمنية جديدة خارج triage الأصلي تحتاج خطة لاحقة: `TASK-AUTH-INDEX`, `TASK-AUTH-SHOW`, `TASK-AUTH-UPDATE`, `TASK-AUTH-STORE`, `PROJECT-TASK-AUTH`.
- الخطوة التالية: مراجعة البنود الأمنية الجديدة في مهمة مستقلة قبل النشر.

# CollabSpace — وصف كامل للمشروع (Project Blueprint)

هذا المستند يشرح **منصة CollabSpace** كما بُنيت في هذا الريبو، بحيث يمكن إعادة بنائها أو تعديلها مع فريق آخر بنفس الفكرة.

---

## 1. فكرة المشروع

**CollabSpace** منصة تعاون (Collaboration Platform) لإدارة:

- المشاريع (Projects)
- المهام (Tasks)
- الفرق (Teams)
- الشات (Project group + Direct 1-to-1)
- الملفات (مرتبطة بمشروع/مهمة)
- البروفايل (Profile + نشاط + مهامي + ملفاتي)
- التقارير (Reports)
- البحث العام (Global Search)

الواجهة الأمامية **غير موجودة في هذا الريبو** — المشروع **API-first** (Laravel REST API + Sanctum tokens).

---

## 2. التقنيات (Tech Stack)

| Layer | Technology |
|--------|------------|
| Framework | Laravel **13.x** |
| PHP | **8.3+** |
| Auth | Laravel **Sanctum** (Bearer token) |
| Roles/Permissions | **Laratrust** (teams enabled) |
| Media (legacy على Project) | **Spatie Media Library** |
| Search | **Spatie Searchable** + custom registry |
| Real-time Chat | **Pusher** + Laravel Broadcasting |
| API Docs | **dedoc/scramble** |
| DB | MySQL (افتراضي) |

---

## 3. هيكل المجلدات والأنماط (Architecture Patterns)

```
app/
├── Actions/          # Auth + Project mutations (thin orchestration)
├── Concerns/         # ApiResponse trait (auth responses)
├── Contracts/        # GlobalSearchable interface
├── DTOs/             # ProjectDTO
├── Enums/            # Status, priority, file types, etc.
├── Events/           # MessageSent (Pusher broadcast)
├── Filters/          # ProjectFilter (query filters)
├── Http/
│   ├── Controllers/Api/   # منظم حسب الموديول
│   ├── Requests/          # Form Request validation
│   └── Resources/         # API Resources (JSON shape)
├── Models/
├── Repositories/     # ProjectRepository (+ Interface)
├── Services/         # Business logic (Chat, File, Profile, Search, Project)
routes/
├── api.php           # Main entry + require modules
├── projects.php, tasks.php, team.php, chat.php, profile.php, files.php, report.php
postman/              # Collections جاهزة للاستيراد
```

**أنماط مستخدمة:**

- **Projects:** Controller → Service → Repository → Actions + DTO
- **Tasks, Teams, Chat, Profile, Files:** Controller → Service (أو مباشرة Model)
- **Auth:** Controller → Action classes
- **Responses:** مزيج من `ApiResponse` trait (auth) و `JsonResource` (CRUD modules)

---

## 4. المستخدمين والأدوار (Roles)

من `UserSeeder`:

| Role (Laratrust) | Email | Password |
|------------------|-------|----------|
| `admin` | admin@example.com | password |
| `Project` | manager@example.com | password |
| `member` | member@example.com | password |

**ملاحظة مهمة:** في `routes/projects.php` middleware يستخدم `role:Member` (حرف M كبير) بينما الـ role المُسجّل هو `member` — قد يسبب 403 لمسارات Member.

**Laratrust Teams:** جدول `teams` يُستخدم لـ:

1. فرق التطبيق (members عبر `team_user`)
2. Laratrust team-scoped RBAC (منفصل عن `team_user`)

---

## 5. قاعدة البيانات — الجداول والعلاقات

### 5.1 Core Entities

```
users
├── id, name, email, password, job_title, exp
├── phone, country_code, about, availability_status
├── current_team_id → teams
├── current_project_id → projects
└── email_verified_at, timestamps

projects
├── id, created_by → users
├── name, description, start_date, deadline
├── priority: low|medium|high|critical
├── status: pending|in_progress|on_hold|completed|cancelled
└── timestamps

tasks
├── id, project_id → projects
├── title, description, start_date, due_date
├── progress (0-100), status, priority
└── timestamps

teams (Laratrust + app)
├── id, name, display_name, description
└── timestamps
```

### 5.2 Pivot Tables

| Table | العلاقة |
|-------|---------|
| `team_user` | user ↔ team (members) |
| `project_team` | project ↔ team |
| `task_user` | task ↔ user (assignees) |
| `conversation_user` | conversation ↔ user (chat participants) |
| `meeting_user` | meeting ↔ user |
| `role_user` | Laratrust roles (optional team_id) |

### 5.3 Chat

```
conversations
├── type: project | direct
├── project_id (nullable, للشات الجماعي)
└── timestamps

messages
├── conversation_id, user_id, body
└── timestamps
```

### 5.4 Files (Model مخصص — ليس Spatie فقط)

```
files
├── user_id (uploader)
├── name, original_name, file_name, disk, mime_type, extension
├── file_type: pdf|doc|docx|xls|xlsx|ppt|pptx|txt|image|other
├── size, status: attached|detached
├── attachable_type + attachable_id (morph → project | task)
└── timestamps
```

### 5.5 Meetings (Model موجود — بدون APIs حالياً)

```
meetings: title, description, scheduled_at, starts_at, ends_at
meeting_user: pivot
```

### 5.6 Reports

```
reports: report_type, note, start_date, end_date, user_id
```

### 5.7 Spatie Media (`media` table)

مستخدم لـ:

- **Project attachments** (collection: `attachments`)
- **User profile files** (collection: `profile_files`) — من Profile module

---

## 6. Morph Map (`AppServiceProvider`)

```php
'user' => User::class
'project' => Project::class
'task' => Task::class
'team' => Team::class
'role' => Role::class
'file' => File::class
```

---

## 7. المصادقة (Authentication)

### 7.1 Register / Login / Logout

- `POST /api/register`
- `POST /api/login` → يرجع `{ user, token }` داخل `data`
- `DELETE /api/logout` (auth:sanctum)

### 7.2 Forgot Password (OTP + DB reset token)

1. `POST /api/forgot-password` `{ email }`
2. `POST /api/verify-otp` `{ email, otp, purpose: "password_reset" }` → `reset_token`
3. `POST /api/reset-password` `{ email, reset_token, password, password_confirmation }`

**تفاصيل:**

- OTP في **Cache** (مع normalized email keys في `AuthCacheKeys`)
- `reset_token` في جدول **`password_reset_tokens`** (ليس cache) — TTL 15 دقيقة
- `purpose` في verify-otp: `password_reset` أو alias `forgot_password`

### 7.3 كل الـ APIs (عدا auth العامة) تحتاج:

```
Authorization: Bearer {sanctum_token}
```

---

## 8. كل الـ API Modules

### 8.1 Projects (`routes/projects.php`)

| Method | Endpoint | الوصف |
|--------|----------|--------|
| GET/POST | `/api/projects` | CRUD (PM — بدون role middleware حالياً) |
| GET/PUT/DELETE | `/api/projects/{id}` | |
| GET | `/api/admin/projects` | Admin CRUD (`role:admin`) |
| GET | `/api/Member/projects` | قراءة فقط (`role:Member` ⚠️) |
| GET/POST/DELETE | `/api/projects/{id}/teams` | ربط/فك فرق من المشروع |
| DELETE | `/api/projects/{id}/teams/{teamId}` | |

**Filters على Projects:** `status`, `priority`, `start_date`, `deadline`, `search` (via `ProjectFilter`)

**Project update/delete:** فقط `created_by === auth user` (لـ PM controller)

**Media:** رفع attachments عند create/update عبر Spatie (`attachments` collection)

### 8.2 Tasks (`routes/tasks.php`)

| Method | Endpoint |
|--------|----------|
| GET | `/api/tasks` |
| POST | `/api/tasks` |
| GET/PUT/PATCH/DELETE | `/api/tasks/{id}` |

**Fields:** `project_id`, `title`, `description`, `start_date`, `due_date`, `progress`, `status`, `priority`, `user_ids[]` (assignees)

**TaskStatus:** `pending`, `in_progress`, `in_review`, `completed`

### 8.3 Teams (`routes/team.php`)

| Method | Endpoint |
|--------|----------|
| apiResource | `/api/teams` |
| GET | `/api/teams/{team}/members` |
| POST | `/api/teams/{team}/members` `{ user_ids: [] }` |
| DELETE | `/api/teams/{team}/members` `{ user_ids: [] }` |
| DELETE | `/api/teams/{team}/members/{userId}` |

### 8.4 Chat (`routes/chat.php`) + Pusher

| Method | Endpoint |
|--------|----------|
| GET | `/api/conversations` |
| POST | `/api/conversations/direct` `{ user_id }` |
| GET | `/api/projects/{project}/conversation` |
| GET | `/api/conversations/{id}` |
| GET/POST | `/api/conversations/{id}/messages` |
| POST | `/api/broadcasting/auth` |

**قواعد الشات:**

- **Project chat:** كل أعضاء فرق المشروع + منشئ المشروع
- **Direct chat:** لازم المستخدمين في **نفس team** (team_user)
- Event: `MessageSent` → channel `private-conversation.{id}`, event `.message.sent`

### 8.5 Profile (`routes/profile.php`)

| Method | Endpoint |
|--------|----------|
| GET/PUT/PATCH | `/api/profile` |
| GET | `/api/profile/activity` |
| GET | `/api/profile/tasks` + `?classification=to_do|done|...` |
| GET | `/api/profile/tasks/summary` |
| GET/POST/DELETE | `/api/profile/files` |

### 8.6 Files (`routes/files.php`) — Project/Task attachments

| Method | Endpoint |
|--------|----------|
| GET/POST | `/api/files` |
| GET/DELETE | `/api/files/{id}` |
| POST | `/api/files/{id}/attach` `{ attachable_type, attachable_id }` |
| POST | `/api/files/{id}/detach` |
| GET/POST | `/api/projects/{project}/files` |
| GET/POST | `/api/tasks/{task}/files` |

**أنواع:** pdf, doc, docx, xls, xlsx, ppt, pptx, txt, images — max 20MB

### 8.7 Reports (`routes/report.php`)

| Method | Endpoint |
|--------|----------|
| GET/POST | `/api/reports` |
| GET | `/api/reports/projects` |
| GET | `/api/reports/tasks` |
| GET | `/api/reports/teams/{teamId}` |
| GET | `/api/reports/users/{userId}` |

### 8.8 Global Search

| Method | Endpoint |
|--------|----------|
| GET | `/api/search?q=...&type=...&field=...` |

**Models searchable:** User, Project, Task, Team (via `GlobalSearchable` interface + `GlobalSearchModelRegistry` auto-discovery من `app/Models`)

---

## 9. Enums Reference

| Enum | Values |
|------|--------|
| ProjectStatus | pending, in_progress, on_hold, completed, cancelled |
| ProjectPriority | low, medium, high, critical |
| TaskStatus | pending, in_progress, in_review, completed |
| TaskPriority | low, medium, high, critical |
| UserAvailability | available, unavailable |
| ConversationType | project, direct |
| FileStatus | attached, detached |
| FileType | pdf, doc, docx, xls, xlsx, ppt, pptx, txt, image, other |
| ReportType | (في Report model) |

---

## 10. Services Layer

| Service | المسؤولية |
|---------|-----------|
| `ProjectService` | Queries عبر Repository |
| `ChatService` | conversations, access checks, sync participants |
| `FileService` | upload, attach, detach, delete, authorization |
| `ProfileService` | profile load, recent activity, task summary |
| `GlobalSearchService` | unified search across models |
| `GlobalSearchModelRegistry` | discovers GlobalSearchable models |

---

## 11. Authorization Logic (مختصر)

| المورد | القاعدة |
|--------|---------|
| Project update/delete | `created_by` أو admin |
| Project teams assign | `created_by` أو admin |
| Project chat / files | member في team مربوط بالمشروع أو creator |
| Direct chat | shared team في `team_user` |
| Task files | assignee أو project access |
| File delete | uploader أو admin أو project/task access |

---

## 12. Seeders

```php
DatabaseSeeder → UserSeeder, ProjectSeeder, GlobalSearchDemoSeeder
```

**Default passwords:** `password` لكل seeded users

---

## 13. Postman Collections

في `postman/`:

- `CollabSpace-Chat-API.postman_collection.json`
- `CollabSpace-Profile-API.postman_collection.json`
- `CollabSpace-Files-API.postman_collection.json`
- `Round12-collabase-team-one.postman_collection.json` (legacy — reports, auth, projects)

**Variables شائعة:** `url`, `token`, `project_id`, `task_id`, `conversation_id`, `file_id`

---

## 14. إعداد البيئة (.env)

```env
APP_URL=...
DB_*=...

BROADCAST_CONNECTION=pusher   # أو log للتطوير بدون Pusher
PUSHER_APP_ID, KEY, SECRET, CLUSTER
```

```bash
composer install
php artisan migrate
php artisan storage:link
php artisan db:seed
php artisan serve
```

**ملاحظة:** `AppServiceProvider` يحوّل `BROADCAST_CONNECTION=pusher` إلى `log` تلقائياً إذا Pusher keys فارغة (لتجنب أخطاء artisan).

---

## 15. ما هو مكتمل vs ناقص / TODO

| Module | Status |
|--------|--------|
| Auth + OTP reset | ✅ |
| Projects CRUD + filters + media | ✅ |
| Project ↔ Teams assign | ✅ |
| Tasks CRUD + assignees | ✅ |
| Teams CRUD + members | ✅ |
| Chat + Pusher | ✅ |
| Profile | ✅ |
| Files (project/task) | ✅ |
| Reports | ✅ (basic) |
| Global Search | ✅ |
| Meetings | ⚠️ Model + migration فقط — لا APIs |
| Notifications | ❌ غير مُنفّذ |
| Policies | ❌ معظم الصلاحيات inline في controllers |
| Frontend | ❌ خارج الريبو |
| Member role middleware | ⚠️ case mismatch `Member` vs `member` |
| PSR-4 filenames على Linux | ⚠️ تأكد من case-sensitive paths (Api vs api) |

---

## 16. Entity Relationship Diagram (مبسّط)

```
users ──creates──> projects
users <──team_user──> teams
users <──task_user──> tasks
users ──uploads──> files
users <──conversation_user──> conversations
users <──meeting_user──> meetings

projects <──project_team──> teams
projects ──has──> tasks
projects ──has──> conversations (project chat)
projects ──morph──> files

tasks ──morph──> files

conversations ──has──> messages
```

---

## 17. ترتيب بناء المشروع من الصفر (للفريق الجديد)

1. Laravel + Sanctum + Laratrust + migrations core (users, roles, teams, projects, tasks, pivots)
2. Auth (register, login, OTP reset)
3. Teams + members APIs
4. Projects CRUD + repository pattern + filters
5. Project ↔ Teams linking
6. Tasks CRUD + assignees
7. Files model + attach/detach
8. Profile module
9. Chat (conversations, messages) + Pusher
10. Global Search
11. Reports
12. Postman collections
13. (Optional) Meetings APIs
14. Frontend

---

## 18. ملاحظات للـ AI / الفريق عند إعادة البناء

1. **افصل** `team_user` (membership) عن Laratrust `role_user` (permissions).
2. **وحّد** response format: إما كل الـ APIs تستخدم `ApiResponse` أو كلها `JsonResource`.
3. **ثبّت** role names و middleware (`member` lowercase everywhere).
4. **استخدم** morph map من أول يوم على Linux production.
5. **Files:** قرّر بين Spatie Media فقط أو File model مخصص — حالياً **كلاهما** موجود.
6. **Chat:** conversation_id مطلوب قبل send message — ليس `/api/chat` مباشرة.
7. **PUT tasks/projects:** URL يجب أن يحتوي ID: `/api/tasks/1` وليس `/api/tasks`.

---

## 19. Chat — كيف يعمل (خطوات سريعة)

1. `POST /api/login` → احفظ token
2. افتح محادثة:
   - مشروع: `GET /api/projects/{id}/conversation`
   - direct: `POST /api/conversations/direct` `{ user_id }` (نفس team)
3. احفظ `conversation_id`
4. `GET /api/conversations/{id}/messages` — اقرأ
5. `POST /api/conversations/{id}/messages` `{ body }` — اكتب
6. Real-time: Pusher channel `private-conversation.{id}`, event `.message.sent`

---

## 20. Profile — endpoints سريعة

- `GET /api/profile` — البروفايل كامل
- `PUT /api/profile` — تحديث name, email, phone, country_code, about, job_title, experience_years, availability_status, current_team_id, current_project_id
- `GET /api/profile/activity` — recent activity
- `GET /api/profile/tasks/summary` — to_do / done counts
- `GET /api/profile/tasks?classification=to_do` — مهامي مصنّفة

---

*آخر تحديث: يعكس حالة الريبو `collabSpace-team-one` — Bootcamp Round 12.*

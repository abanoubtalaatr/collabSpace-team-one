import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(here, '..');
const outputPath = resolve(here, 'CollabSpace-Full-API.postman_collection.json');
const environmentPath = resolve(here, 'CollabSpace-Local.postman_environment.json');

const routeRows = JSON.parse(execFileSync('php', ['artisan', 'route:list', '--path=api', '--json'], {
    cwd: projectRoot,
    encoding: 'utf8',
})).filter((route) => route.uri.startsWith('api/'));

const folders = new Map();
const covered = new Set();
const variables = [
    ['url', 'http://127.0.0.1:8000'], ['admin_email', 'admin@example.com'],
    ['manager_email', 'manager@example.com'], ['member_email', 'member@example.com'],
    ['admin_password', ''], ['manager_password', ''], ['member_password', ''],
    ['token', ''], ['admin_token', ''], ['manager_token', ''], ['member_token', ''],
    ['team_id', '1'], ['project_id', '1'], ['admin_project_id', '1'], ['task_id', '1'],
    ['meeting_id', '1'], ['file_id', '1'], ['project_file_id', '1'], ['task_file_id', '1'],
    ['profile_file_id', '1'], ['conversation_id', '1'], ['direct_conversation_id', '1'],
    ['notification_id', '00000000-0000-0000-0000-000000000000'], ['member_id', '3'],
    ['qa_start_date', ''], ['qa_due_date', ''], ['qa_deadline', ''],
    ['qa_meeting_starts_at', ''], ['qa_meeting_ends_at', ''],
    ['qa_yesterday', ''], ['qa_past_datetime', ''],
];

const jsonHeaders = [
    { key: 'Accept', value: 'application/json' },
    { key: 'Content-Type', value: 'application/json' },
];

function script(lines, listen = 'test') {
    return { listen, script: { type: 'text/javascript', exec: lines } };
}

function defaultTests(expected, extra = []) {
    return [
        `pm.test("status is one of: ${expected.join(', ')}", () => pm.expect(${JSON.stringify(expected)}).to.include(pm.response.code));`,
        'pm.test("response time is below 3000ms", () => pm.expect(pm.response.responseTime).to.be.below(3000));',
        'pm.test("response is not an unexpected 500", () => pm.expect(pm.response.code).to.not.equal(500));',
        ...extra,
    ];
}

function rawBody(value) {
    return { mode: 'raw', raw: JSON.stringify(value, null, 2), options: { raw: { language: 'json' } } };
}

function add(folder, name, method, path, options = {}) {
    if (! folders.has(folder)) folders.set(folder, []);
    const expected = options.expected ?? [200];
    const request = {
        method,
        header: options.headers ?? jsonHeaders,
        url: { raw: `{{url}}/${path.replace(/^\//, '')}`, host: ['{{url}}'], path: path.replace(/^\//, '').split('/') },
    };
    if (options.auth === 'none') request.auth = { type: 'noauth' };
    else if (options.token) request.auth = { type: 'bearer', bearer: [{ key: 'token', value: `{{${options.token}}}`, type: 'string' }] };
    if (options.body !== undefined) request.body = rawBody(options.body);
    if (options.form) request.body = { mode: 'formdata', formdata: options.form };
    const events = [script(defaultTests(expected, options.tests ?? []))];
    if (options.pre) events.unshift(script(options.pre, 'prerequest'));
    folders.get(folder).push({ name, request, event: events });
    if (options.cover !== false) covered.add(`${method} ${path.replace(/\?.*$/, '').replace(/{{([^}]+)_id}}/g, '{$1}').replace('{{member_id}}', '{userId}').replace('{{notification_id}}', '{notification}').replace('{{conversation_id}}', '{conversation}').replace('{{file_id}}', '{file}').replace('{{task_id}}', '{task}').replace('{{team_id}}', '{team}').replace('{{meeting_id}}', '{meeting}').replace('{{project_id}}', '{project}')}`);
}

function routePath(uri) {
    return uri.replaceAll('{fileId}', '{{profile_file_id}}')
        .replaceAll('{teamId}', '{{team_id}}').replaceAll('{userId}', '{{member_id}}')
        .replaceAll('{notification}', '{{notification_id}}').replaceAll('{conversation}', '{{conversation_id}}')
        .replaceAll('{meeting}', '{{meeting_id}}').replaceAll('{project}', '{{project_id}}')
        .replaceAll('{task}', '{{task_id}}').replaceAll('{team}', '{{team_id}}').replaceAll('{file}', '{{file_id}}');
}

// Every declared API operation gets an executable contract/guard check.
for (const route of routeRows) {
    const methods = route.method.split('|').filter((method) => method !== 'HEAD');
    for (const method of methods) {
        const protectedRoute = route.middleware.some((middleware) => middleware.includes('Authenticate:sanctum'));
        let expected;
        if (protectedRoute) expected = [401];
        else if (method === 'GET') expected = [200, 404];
        else if (route.uri === 'api/login') expected = [422];
        else expected = [422, 429];
        add('00 - Route Contract Matrix', `${method} /${route.uri}`, method, routePath(route.uri), {
            auth: 'none', body: method === 'GET' ? undefined : {}, expected,
            tests: protectedRoute ? ['pm.test("protected route rejects anonymous access", () => pm.response.to.have.status(401));'] : [],
        });
    }
}

const loginTests = (tokenName) => [
    'pm.test("login returns a token", () => pm.expect(pm.response.json().data.token).to.be.a("string").and.not.empty);',
    `pm.collectionVariables.set("${tokenName}", pm.response.json().data.token);`,
];
add('01 - Authentication', 'Login as admin', 'POST', 'api/login', { auth: 'none', body: { email: '{{admin_email}}', password: '{{admin_password}}' }, expected: [200], tests: loginTests('admin_token') });
add('01 - Authentication', 'Login as manager', 'POST', 'api/login', { auth: 'none', body: { email: '{{manager_email}}', password: '{{manager_password}}' }, expected: [200], tests: loginTests('manager_token') });
add('01 - Authentication', 'Login as member', 'POST', 'api/login', { auth: 'none', body: { email: '{{member_email}}', password: '{{member_password}}' }, expected: [200], tests: loginTests('member_token') });
add('01 - Authentication', 'Current user', 'GET', 'api/user', { token: 'admin_token', tests: ['pm.test("admin identity is returned", () => pm.expect(pm.response.json().email).to.eql(pm.collectionVariables.get("admin_email")));'] });

const stampPre = ['if (!pm.collectionVariables.get("qa_stamp")) pm.collectionVariables.set("qa_stamp", Date.now().toString());'];
add('02 - Teams', 'Create QA team', 'POST', 'api/teams', { token: 'admin_token', pre: stampPre, body: { name: 'qa-postman-{{qa_stamp}}', display_name: 'QA Postman {{qa_stamp}}', description: 'Automated QA team' }, expected: [201], tests: ['const j=pm.response.json(); pm.collectionVariables.set("team_id", j.data.id); pm.test("team was created",()=>pm.expect(j.data.name).to.include("qa-postman-"));'] });
add('02 - Teams', 'List teams', 'GET', 'api/teams', { token: 'admin_token' });
add('02 - Teams', 'Show QA team', 'GET', 'api/teams/{{team_id}}', { token: 'admin_token', tests: ['pm.test("team id matches",()=>pm.expect(pm.response.json().data.id).to.eql(Number(pm.collectionVariables.get("team_id"))));'] });
add('02 - Teams', 'Update team with PUT', 'PUT', 'api/teams/{{team_id}}', { token: 'admin_token', body: { display_name: 'QA Team PUT' } });
add('02 - Teams', 'Update team with PATCH', 'PATCH', 'api/teams/{{team_id}}', { token: 'admin_token', body: { description: 'QA Team PATCH' } });
add('02 - Teams', 'Add admin and member', 'POST', 'api/teams/{{team_id}}/members', { token: 'admin_token', body: { user_ids: [1, '{{member_id}}'] } });
add('02 - Teams', 'List members', 'GET', 'api/teams/{{team_id}}/members', { token: 'admin_token' });

const projectBody = { name: 'QA Project {{qa_stamp}}', description: 'Postman end-to-end QA project', start_date: '{{qa_start_date}}', deadline: '{{qa_deadline}}', priority: 'high', status: 'pending', type: 'qa' };
add('03 - Projects', 'Create QA project', 'POST', 'api/projects', { token: 'admin_token', body: projectBody, expected: [201], tests: ['const j=pm.response.json(); pm.collectionVariables.set("project_id",j.data.id); pm.test("project created",()=>pm.expect(j.data.name).to.include("QA Project"));'] });
add('03 - Projects', 'Reject past project dates', 'POST', 'api/projects', { token: 'admin_token', body: { ...projectBody, name: 'Invalid past project', start_date: '{{qa_yesterday}}', deadline: '{{qa_yesterday}}' }, expected: [422], tests: ['pm.test("past project dates are rejected",()=>pm.expect(pm.response.json().errors).to.have.keys("start_date","deadline"));'], cover: false });
add('03 - Projects', 'List projects', 'GET', 'api/projects', { token: 'admin_token' });
add('03 - Projects', 'Show project', 'GET', 'api/projects/{{project_id}}', { token: 'admin_token' });
add('03 - Projects', 'Update project PUT', 'PUT', 'api/projects/{{project_id}}', { token: 'admin_token', body: { ...projectBody, name: 'QA Project PUT {{qa_stamp}}', status: 'in_progress' } });
add('03 - Projects', 'Update project PATCH', 'PATCH', 'api/projects/{{project_id}}', { token: 'admin_token', body: { ...projectBody, name: 'QA Project PATCH {{qa_stamp}}', status: 'in_progress' } });
add('03 - Projects', 'Add QA team to project', 'POST', 'api/projects/{{project_id}}/teams', { token: 'admin_token', body: { team_ids: ['{{team_id}}'] } });
add('03 - Projects', 'List project teams', 'GET', 'api/projects/{{project_id}}/teams', { token: 'admin_token' });
add('03 - Projects', 'List project guests', 'GET', 'api/projects/guests', { token: 'admin_token' });
add('03 - Projects', 'Member project index', 'GET', 'api/Team/projects', { token: 'member_token' });
add('03 - Projects', 'Member project show', 'GET', 'api/Team/projects/{{project_id}}', { token: 'member_token' });

add('04 - Admin Projects', 'Admin create project', 'POST', 'api/admin/projects', { token: 'admin_token', body: { ...projectBody, name: 'QA Admin Project {{qa_stamp}}' }, expected: [201], tests: ['pm.collectionVariables.set("admin_project_id",pm.response.json().data.id);'] });
add('04 - Admin Projects', 'Admin index', 'GET', 'api/admin/projects', { token: 'admin_token' });
add('04 - Admin Projects', 'Admin show', 'GET', 'api/admin/projects/{{admin_project_id}}', { token: 'admin_token', cover: false });
add('04 - Admin Projects', 'Admin update PUT', 'PUT', 'api/admin/projects/{{admin_project_id}}', { token: 'admin_token', body: { ...projectBody, name: 'QA Admin PUT {{qa_stamp}}', status: 'in_progress' }, cover: false });
add('04 - Admin Projects', 'Admin update PATCH', 'PATCH', 'api/admin/projects/{{admin_project_id}}', { token: 'admin_token', body: { ...projectBody, name: 'QA Admin PATCH {{qa_stamp}}', status: 'completed' }, cover: false });
add('04 - Admin Projects', 'Wrong role is forbidden', 'GET', 'api/admin/projects', { token: 'member_token', expected: [403], tests: ['pm.test("role middleware denies member",()=>pm.response.to.have.status(403));'], cover: false });

const taskBody = { title: 'QA Task {{qa_stamp}}', description: 'Postman task', start_date: '{{qa_start_date}}', due_date: '{{qa_due_date}}', progress: 10, status: 'pending', priority: 'high', user_ids: ['{{member_id}}'] };
add('05 - Tasks', 'Create nested project task', 'POST', 'api/projects/{{project_id}}/tasks', { token: 'admin_token', body: taskBody, expected: [201], tests: ['pm.collectionVariables.set("task_id",pm.response.json().data.id);'] });
add('05 - Tasks', 'Reject past task start date', 'POST', 'api/projects/{{project_id}}/tasks', { token: 'admin_token', body: { ...taskBody, title: 'Invalid past task', start_date: '{{qa_yesterday}}' }, expected: [422], tests: ['pm.test("past task date is rejected",()=>pm.expect(pm.response.json().errors).to.have.property("start_date"));'], cover: false });
add('05 - Tasks', 'List project tasks', 'GET', 'api/projects/{{project_id}}/tasks', { token: 'admin_token' });
add('05 - Tasks', 'Project task analytics', 'GET', 'api/projects/{{project_id}}/analytics/tasks', { token: 'admin_token' });
add('05 - Tasks', 'List all tasks', 'GET', 'api/tasks?project_id={{project_id}}', { token: 'admin_token' });
add('05 - Tasks', 'Show task', 'GET', 'api/tasks/{{task_id}}', { token: 'admin_token' });
add('05 - Tasks', 'Update task PUT', 'PUT', 'api/tasks/{{task_id}}', { token: 'admin_token', body: { title: 'QA Task PUT', progress: 50, status: 'in_progress' } });
add('05 - Tasks', 'Update task PATCH', 'PATCH', 'api/tasks/{{task_id}}', { token: 'admin_token', body: { progress: 75 } });
add('05 - Tasks', 'Reject invalid progress', 'PATCH', 'api/tasks/{{task_id}}', { token: 'admin_token', body: { progress: 101 }, expected: [422], tests: ['pm.test("progress validation error exists",()=>pm.expect(pm.response.json().errors).to.have.property("progress"));'], cover: false });

add('06 - Profile Dashboard Search', 'Get profile', 'GET', 'api/profile', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Update profile PUT', 'PUT', 'api/profile', { token: 'admin_token', body: { name: 'Admin User' } });
add('06 - Profile Dashboard Search', 'Update profile PATCH', 'PATCH', 'api/profile', { token: 'admin_token', body: { job_title: 'QA Admin' } });
add('06 - Profile Dashboard Search', 'Profile activity', 'GET', 'api/profile/activity?limit=15', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Profile tasks', 'GET', 'api/profile/tasks?status=pending', { token: 'member_token' });
add('06 - Profile Dashboard Search', 'Profile task summary', 'GET', 'api/profile/tasks/summary', { token: 'member_token' });
add('06 - Profile Dashboard Search', 'Global search', 'GET', 'api/search?q=QA', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Dashboard overview', 'GET', 'api/dashboard/overview', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Dashboard stats', 'GET', 'api/dashboard/stats', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Dashboard recent files', 'GET', 'api/dashboard/recent-files', { token: 'admin_token' });
add('06 - Profile Dashboard Search', 'Dashboard project overview', 'GET', 'api/dashboard/project-overview?project_id={{project_id}}', { token: 'admin_token' });

const uploadForm = (extra = []) => [{ key: 'file', type: 'file', src: 'postman/qa-sample.txt' }, ...extra];
add('07 - Files', 'Upload detached file', 'POST', 'api/files', { token: 'admin_token', headers: [{ key: 'Accept', value: 'application/json' }], form: uploadForm(), expected: [201], tests: ['pm.collectionVariables.set("file_id",pm.response.json().data.id);'] });
add('07 - Files', 'List files', 'GET', 'api/files?mine=1', { token: 'admin_token' });
add('07 - Files', 'Show file', 'GET', 'api/files/{{file_id}}', { token: 'admin_token' });
add('07 - Files', 'Download file', 'GET', 'api/files/{{file_id}}/download', { token: 'admin_token', expected: [200], tests: ['pm.test("download has content",()=>pm.expect(pm.response.stream.length).to.be.above(0));'] });
add('07 - Files', 'Attach file to project', 'POST', 'api/files/{{file_id}}/attach', { token: 'admin_token', body: { attachable_type: 'project', attachable_id: '{{project_id}}' } });
add('07 - Files', 'Detach file', 'POST', 'api/files/{{file_id}}/detach', { token: 'admin_token', body: {} });
add('07 - Files', 'Upload project file', 'POST', 'api/projects/{{project_id}}/files', { token: 'admin_token', headers: [{ key: 'Accept', value: 'application/json' }], form: uploadForm(), expected: [201], tests: ['pm.collectionVariables.set("project_file_id",pm.response.json().data.id);'] });
add('07 - Files', 'List project files', 'GET', 'api/projects/{{project_id}}/files', { token: 'admin_token' });
add('07 - Files', 'Upload task file', 'POST', 'api/tasks/{{task_id}}/files', { token: 'admin_token', headers: [{ key: 'Accept', value: 'application/json' }], form: uploadForm(), expected: [201], tests: ['pm.collectionVariables.set("task_file_id",pm.response.json().data.id);'] });
add('07 - Files', 'List task files', 'GET', 'api/tasks/{{task_id}}/files', { token: 'admin_token' });
add('07 - Files', 'Upload profile file', 'POST', 'api/profile/files', { token: 'admin_token', headers: [{ key: 'Accept', value: 'application/json' }], form: uploadForm(), expected: [201], tests: ['pm.collectionVariables.set("profile_file_id",pm.response.json().data.id);'] });
add('07 - Files', 'List profile files', 'GET', 'api/profile/files', { token: 'admin_token' });

const meetingBody = { title: 'QA Meeting {{qa_stamp}}', description: 'Postman meeting', starts_at: '{{qa_meeting_starts_at}}', ends_at: '{{qa_meeting_ends_at}}', project_id: '{{project_id}}', user_ids: ['{{member_id}}'], team_ids: ['{{team_id}}'] };
add('08 - Meetings', 'Create meeting', 'POST', 'api/meetings', { token: 'admin_token', body: meetingBody, expected: [201], tests: ['pm.collectionVariables.set("meeting_id",pm.response.json().data.meeting.id);'] });
add('08 - Meetings', 'Reject past meeting start time', 'POST', 'api/meetings', { token: 'admin_token', body: { ...meetingBody, title: 'Invalid past meeting', starts_at: '{{qa_past_datetime}}' }, expected: [422], tests: ['pm.test("past meeting time is rejected",()=>pm.expect(pm.response.json().errors).to.have.property("starts_at"));'], cover: false });
add('08 - Meetings', 'List meetings', 'GET', 'api/meetings', { token: 'admin_token' });
add('08 - Meetings', 'Meeting calendar', 'GET', 'api/meetings/calendar?start_date=2026-11-01&end_date=2026-12-31', { token: 'admin_token' });
add('08 - Meetings', 'Upcoming meetings', 'GET', 'api/meetings/upcoming?days=365', { token: 'admin_token' });
add('08 - Meetings', 'Show meeting', 'GET', 'api/meetings/{{meeting_id}}', { token: 'admin_token' });
add('08 - Meetings', 'Update meeting PUT', 'PUT', 'api/meetings/{{meeting_id}}', { token: 'admin_token', body: { title: 'QA Meeting PUT' } });
add('08 - Meetings', 'Update meeting PATCH', 'PATCH', 'api/meetings/{{meeting_id}}', { token: 'admin_token', body: { location: 'QA Room' } });

add('09 - Chat', 'Project conversation', 'GET', 'api/projects/{{project_id}}/conversation', { token: 'admin_token', tests: ['pm.collectionVariables.set("conversation_id",pm.response.json().data.id);'] });
add('09 - Chat', 'Direct conversation', 'POST', 'api/conversations/direct', { token: 'admin_token', body: { user_id: '{{member_id}}' }, expected: [200, 201], tests: ['pm.collectionVariables.set("direct_conversation_id",pm.response.json().data.id);'] });
add('09 - Chat', 'List conversations', 'GET', 'api/conversations', { token: 'admin_token' });
add('09 - Chat', 'Show conversation', 'GET', 'api/conversations/{{conversation_id}}', { token: 'admin_token' });
add('09 - Chat', 'Send message', 'POST', 'api/conversations/{{conversation_id}}/messages', { token: 'admin_token', body: { body: 'QA automated message {{qa_stamp}}' }, expected: [201] });
add('09 - Chat', 'List messages', 'GET', 'api/conversations/{{conversation_id}}/messages', { token: 'admin_token' });
add('09 - Chat', 'Broadcasting auth POST', 'POST', 'api/broadcasting/auth', { token: 'admin_token', body: { channel_name: 'private-conversation.{{conversation_id}}', socket_id: '1234.5678' }, expected: [200] });
add('09 - Chat', 'Broadcasting auth rejects empty GET', 'GET', 'api/broadcasting/auth', { token: 'admin_token', expected: [422] });

add('10 - Notifications', 'List notifications', 'GET', 'api/notifications', { token: 'member_token', tests: ['const d=pm.response.json().data||[]; if(d.length) pm.collectionVariables.set("notification_id",d[0].id);'] });
add('10 - Notifications', 'Unread notifications', 'GET', 'api/notifications/unread', { token: 'member_token' });
add('10 - Notifications', 'Unread count', 'GET', 'api/notifications/unread-count', { token: 'member_token' });
add('10 - Notifications', 'Show notification', 'GET', 'api/notifications/{{notification_id}}', { token: 'member_token', expected: [200, 404] });
add('10 - Notifications', 'Mark notification read', 'PATCH', 'api/notifications/{{notification_id}}/read', { token: 'member_token', expected: [200, 404] });
add('10 - Notifications', 'Mark all read', 'PATCH', 'api/notifications/read-all', { token: 'member_token' });
add('10 - Notifications', 'Delete read notifications', 'DELETE', 'api/notifications/read', { token: 'member_token' });

add('11 - Reports AI', 'Reports index', 'GET', 'api/reports', { token: 'admin_token' });
add('11 - Reports AI', 'Create report', 'POST', 'api/reports', { token: 'admin_token', body: { report_type: 'project', start_date: '2026-01-01', end_date: '2026-12-31', note: 'QA report {{qa_stamp}}' }, expected: [201] });
add('11 - Reports AI', 'Project report', 'GET', 'api/reports/projects', { token: 'admin_token' });
add('11 - Reports AI', 'Task report', 'GET', 'api/reports/tasks', { token: 'admin_token' });
add('11 - Reports AI', 'Team report', 'GET', 'api/reports/teams/{{team_id}}', { token: 'admin_token' });
add('11 - Reports AI', 'User report', 'GET', 'api/reports/users/{{member_id}}', { token: 'admin_token' });
add('11 - Reports AI', 'AI ask', 'POST', 'api/ai/ask', { token: 'admin_token', body: { question: 'Summarize the current QA project status.' }, expected: [200, 422, 503], tests: ['pm.test("AI endpoint fails gracefully",()=>pm.expect(pm.response.code).to.not.equal(500));'] });

add('99 - Cleanup', 'Delete meeting', 'DELETE', 'api/meetings/{{meeting_id}}', { token: 'admin_token' });
for (const [name, variable] of [['profile file','profile_file_id'],['task file','task_file_id'],['project file','project_file_id'],['detached file','file_id']]) {
    add('99 - Cleanup', `Delete ${name}`, 'DELETE', `api/${variable === 'profile_file_id' ? 'profile/files' : 'files'}/{{${variable}}}`, { token: 'admin_token', cover: false });
}
add('99 - Cleanup', 'Delete task', 'DELETE', 'api/tasks/{{task_id}}', { token: 'admin_token' });
add('99 - Cleanup', 'Delete admin project', 'DELETE', 'api/admin/projects/{{admin_project_id}}', { token: 'admin_token', cover: false });
add('99 - Cleanup', 'Delete QA project', 'DELETE', 'api/projects/{{project_id}}', { token: 'admin_token' });
add('99 - Cleanup', 'Delete QA team', 'DELETE', 'api/teams/{{team_id}}', { token: 'admin_token' });
add('99 - Cleanup', 'Logout member', 'DELETE', 'api/logout', { token: 'member_token', cover: false });
add('99 - Cleanup', 'Logout manager', 'DELETE', 'api/logout', { token: 'manager_token', cover: false });
add('99 - Cleanup', 'Logout admin', 'DELETE', 'api/logout', { token: 'admin_token' });

const collection = {
    info: { _postman_id: 'f1510e62-708b-4a77-a852-collabspaceqa', name: 'CollabSpace - Full API QA', description: 'Generated from Laravel routes. Includes full route contract matrix, functional flows, validation, RBAC, file uploads, and cleanup.', schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json' },
    auth: { type: 'bearer', bearer: [{ key: 'token', value: '{{admin_token}}', type: 'string' }] },
    event: [script([
        'pm.request.headers.upsert({key:"Accept",value:"application/json"});',
        'const qaDateOnly = (offset) => new Date(Date.now() + offset * 86400000).toISOString().slice(0, 10);',
        'pm.collectionVariables.set("qa_start_date", qaDateOnly(1));',
        'pm.collectionVariables.set("qa_due_date", qaDateOnly(14));',
        'pm.collectionVariables.set("qa_deadline", qaDateOnly(60));',
        'pm.collectionVariables.set("qa_yesterday", qaDateOnly(-1));',
        'pm.collectionVariables.set("qa_meeting_starts_at", new Date(Date.now() + 2 * 86400000).toISOString());',
        'pm.collectionVariables.set("qa_meeting_ends_at", new Date(Date.now() + 2 * 86400000 + 3600000).toISOString());',
        'pm.collectionVariables.set("qa_past_datetime", new Date(Date.now() - 3600000).toISOString());',
    ], 'prerequest')],
    variable: variables.map(([key, value]) => ({ key, value, type: 'string' })),
    item: [...folders].map(([name, item]) => ({ name, item })),
};

const environment = {
    id: '09ff9e4b-collabspace-local-qa', name: 'CollabSpace Local QA',
    values: [
        { key: 'url', value: 'http://127.0.0.1:8000', enabled: true },
        { key: 'admin_email', value: 'admin@example.com', enabled: true },
        { key: 'manager_email', value: 'manager@example.com', enabled: true },
        { key: 'member_email', value: 'member@example.com', enabled: true },
        { key: 'admin_password', value: '', enabled: true, type: 'secret' },
        { key: 'manager_password', value: '', enabled: true, type: 'secret' },
        { key: 'member_password', value: '', enabled: true, type: 'secret' },
    ], _postman_variable_scope: 'environment', _postman_exported_using: 'Codex QA generator',
};

writeFileSync(outputPath, `${JSON.stringify(collection, null, 2)}\n`);
writeFileSync(environmentPath, `${JSON.stringify(environment, null, 2)}\n`);

const declared = new Set();
for (const route of routeRows) for (const method of route.method.split('|').filter((m) => m !== 'HEAD')) declared.add(`${method} ${route.uri}`);
console.log(JSON.stringify({ outputPath, environmentPath, declaredOperations: declared.size, generatedRequests: [...folders.values()].reduce((sum, items) => sum + items.length, 0), matrixRequests: folders.get('00 - Route Contract Matrix').length }, null, 2));

import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PRISM_PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_URL;
const password = process.env.PRISM_QA_PASSWORD;
const fixtures = JSON.parse(await readFile(process.env.PRISM_QA_FIXTURES, 'utf8'));
const output = process.argv[2];
const sha = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || undefined });
const results = [];
const sessions = new Map();
const mode = process.env.PRISM_QA_BROWSER_MODE || 'final';

async function show(page, text) {
    await page.getByText(text, { exact: true }).first().waitFor({ state: 'visible' });
}

async function open(page, path, status = 200) {
    const response = await page.goto(base + path, { waitUntil: 'domcontentloaded' });
    assert.equal(response?.status(), status);
    return response;
}

async function login(page, role) {
    if (sessions.has(role)) {
        await page.context().addCookies(sessions.get(role));
        await open(page, '/admin');
        await page.getByRole('heading', { name: 'Dashboard' }).first().waitFor();
        return;
    }
    await open(page, '/admin/login');
    await page.getByLabel('Email address', { exact: false }).fill(`qa-${role}@example.test`);
    await page.locator('input[type=password]').fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL(url => url.pathname === '/admin', { timeout: 7000 });
    await page.getByRole('heading', { name: 'Dashboard' }).first().waitFor();
    sessions.set(role, await page.context().cookies());
}

async function scenario(tc, name, role, expected, action) {
    const context = await browser.newContext({ viewport: { width: 1440, height: 950 } });
    await context.route('**/*', route => new URL(route.request().url()).origin === base
        ? route.continue() : route.abort());
    const page = await context.newPage();
    page.setDefaultTimeout(4500);
    const browserErrors = [];
    page.on('console', message => {
        if (message.type() === 'error') browserErrors.push(message.text());
    });
    page.on('pageerror', error => browserErrors.push(error.message));
    const result = { tc, name, role, expected, sha, status: 'PASS' };
    try {
        await action(page);
        result.actual = expected;
    } catch (error) {
        result.status = 'FAIL';
        result.actual = String(error.message).replaceAll(password, '[redacted]');
    } finally {
        result.errors = [...new Set(browserErrors)]
            .map(message => message.replaceAll(password, '[redacted]'));
        const screenshot = `${tc}_${name}_${result.status}.png`;
        // Clear fields which could display credentials even with CSS or controls disabled.
        await page.locator('input[type=password]').evaluateAll(
            inputs => inputs.forEach(input => { input.value = ''; }),
        ).catch(() => {});
        if ((await page.content()).includes(password)) {
            result.status = 'FAIL';
            result.actual = 'Screenshot withheld because the page contains a credential.';
        } else {
            await page.screenshot({ path: join(output, 'screenshots', screenshot), timeout: 7000 });
            result.screenshot = 'screenshots/' + screenshot;
            result.capturedAt = new Date().toISOString();
        }
        await context.close();
        results.push(result);
        console.log(tc, name, result.status, result.actual);
    }
}

try {
    if (mode === 'baseline') {
        await scenario('TC-06', 'DIVISION_BASELINE', 'admin', 'Seeded admin can edit the fixture division', async page => {
            await login(page, 'admin');
            await open(page, '/admin/divisions/1/edit');
            assert.equal(await page.getByRole('textbox', { name: /^Name/ }).inputValue(), 'QA Engineering');
        });
    } else {
        await scenario('TC-24', 'NATIVE_SMOKE', 'guest', 'Homepage and compiled login theme return HTTP 200', async page => {
            await open(page, '/');
            await open(page, '/admin/login');
            const theme = await page.locator('link[rel=stylesheet][href*="/build/assets/theme-"]').first().getAttribute('href');
            assert.equal((await page.request.get(theme)).status(), 200);
            await page.getByRole('button', { name: 'Sign in' }).waitFor();
        });
        await scenario('TC-02', 'LOGIN_VALID', 'admin', 'Valid login opens the dashboard', page => login(page, 'admin'));
        await scenario('TC-02', 'LOGIN_INVALID', 'guest', 'Invalid credentials display an error and keep the user on login', async page => {
            await open(page, '/admin/login');
            await page.getByLabel('Email address', { exact: false }).fill('qa-admin@example.test');
            await page.locator('input[type=password]').fill('invalid-test-credential');
            await page.getByRole('button', { name: 'Sign in' }).click();
            await page.getByText(/credentials do not match/i).first().waitFor();
            assert.equal(new URL(page.url()).pathname, '/admin/login');
        });
        await scenario('TC-03', 'RESET_REQUEST', 'guest', 'Forgot password opens the reset request form', async page => {
            await open(page, '/admin/login');
            await page.getByRole('link', { name: /forgot password/i }).click();
            await page.getByRole('heading', { name: /forgot password/i }).waitFor();
        });
        await scenario('TC-04', 'PROFILE', 'member', 'Profile displays the member’s own email', async page => {
            await login(page, 'member');
            await open(page, '/admin/profile');
            assert.equal(await page.getByLabel('Email address', { exact: false }).inputValue(), 'qa-member@example.test');
        });
        await scenario('TC-05', 'ORGANIZATION', 'admin', 'Admin sees the seeded organization profile', async page => {
            await login(page, 'admin');
            await open(page, '/admin/organization-information');
            assert.equal(await page.getByLabel('Organization Name').inputValue(), 'QA Northstar');
        });
        await scenario('TC-06', 'DIVISION_EDIT', 'admin', 'Admin saves the edit and sees the new division name', async page => {
            await login(page, 'admin');
            await open(page, '/admin/divisions');
            await show(page, 'QA Engineering');
            await open(page, '/admin/divisions/1/edit');
            const name = page.getByRole('textbox', { name: /^Name/ });
            assert.equal(await name.inputValue(), 'QA Engineering');
            await name.fill('QA Engineering Updated');
            await Promise.all([
                page.waitForResponse(response => response.url().endsWith('/livewire/update') && response.ok()),
                page.getByRole('button', { name: 'Save changes', exact: true }).click(),
            ]);
            await open(page, '/admin/divisions');
            await show(page, 'QA Engineering Updated');
        });
        await scenario('TC-07', 'MEMBERS_LIST', 'admin', 'Admin sees member management and the seeded member', async page => {
            await login(page, 'admin');
            await open(page, '/admin/users');
            await show(page, 'QA Member');
        });
        await scenario('TC-08', 'RBAC_DENIED', 'member', 'Member receives HTTP 403 on division management', async page => {
            await login(page, 'member');
            await open(page, '/admin/divisions', 403);
            assert.equal(await page.getByText('QA Engineering', { exact: true }).count(), 0);
        });
        await scenario('TC-09', 'PROJECTS_MEMBER', 'member', 'Member listing includes only their project', async page => {
            await login(page, 'member');
            await open(page, '/admin/projects');
            await show(page, 'QA Visible Project');
            assert.equal(await page.getByText('QA Restricted Project', { exact: true }).count(), 0);
        });
        await scenario('TC-10', 'TASKS_MEMBER', 'member', 'Task listing shows the assigned task and hides the other project’s task', async page => {
            await login(page, 'member');
            await open(page, '/admin/tasks');
            await show(page, 'QA Visible Task');
            assert.equal(await page.getByText('QA Restricted Task', { exact: true }).count(), 0);
        });
        await scenario('TC-10', 'BOARD', 'member', 'Project board shows the permitted task in To Do', async page => {
            await login(page, 'member');
            await open(page, '/admin/project-board/' + fixtures.visibleProject);
            await show(page, 'QA Visible Task');
            await show(page, 'To Do');
        });
        await scenario('TC-10', 'BOARD_MOVE', 'member', 'Dragging the assigned task to Done persists after reloading', async page => {
            await login(page, 'member');
            const path = '/admin/project-board/' + fixtures.visibleProject;
            await open(page, path);
            const task = page.locator('[data-ticket-id="' + fixtures.visibleTask + '"][draggable="true"]');
            const done = page.locator('[data-status-id="' + fixtures.visibleDone + '"]');
            await task.waitFor();
            assert.equal(await done.locator('[data-ticket-id]').count(), 0);
            await task.dragTo(done, { targetPosition: { x: 40, y: 50 } });
            await done.getByText('QA Visible Task', { exact: true }).waitFor({ timeout: 7000 });
            await open(page, path);
            await done.getByText('QA Visible Task', { exact: true }).waitFor();
        });
        await scenario('TC-11', 'ASSIGNEE', 'member', 'Task detail shows the assigned member', async page => {
            await login(page, 'member');
            await open(page, '/admin/tasks/' + fixtures.visibleTask);
            await show(page, 'QA Member');
        });
        await scenario('TC-12', 'COMMENT', 'member', 'The task detail shows the fixture comment', async page => {
            await login(page, 'member');
            await open(page, '/admin/tasks/' + fixtures.visibleTask);
            await show(page, 'QA comment on the visible task');
        });
        await scenario('TC-13', 'ATTACHMENT_VIEW', 'member', 'The authorized member can open the attachment', async page => {
            await login(page, 'member');
            await open(page, '/attachments/' + fixtures.attachment + '/view');
            await show(page, 'QA attachment content');
        });
        await scenario('TC-14', 'NOTIFICATION_LIST', 'member', 'Member sees the notification assigned to their account', async page => {
            await login(page, 'member');
            await open(page, '/admin/notifications');
            await show(page, 'QA notification for member');
        });
        await scenario('TC-15', 'DASHBOARD', 'admin', 'Authenticated dashboard renders its heading', page => login(page, 'admin'));
        await scenario('TC-16', 'REPORTING', 'member', 'Report shows the member’s task without the restricted task', async page => {
            await login(page, 'member');
            await open(page, '/admin/reporting');
            await show(page, 'QA Visible Task');
            assert.equal(await page.getByText('QA Restricted Task', { exact: true }).count(), 0);
        });
        await scenario('TC-21', 'PROJECT_DENIED', 'member', 'Direct access returns 403 or 404 without restricted content', async page => {
            await login(page, 'member');
            const response = await page.goto(base + '/admin/projects/' + fixtures.restrictedProject);
            assert.ok([403, 404].includes(response?.status()), `Status: ${response?.status()}`);
            assert.equal(await page.getByText('QA Restricted Project', { exact: true }).count(), 0);
        });
        await scenario('TC-22', 'PROJECT_REQUIRED_FIELDS', 'admin', 'Empty project form reports required fields and stays on create page', async page => {
            await login(page, 'admin');
            await open(page, '/admin/projects/create');
            await page.getByRole('button', { name: 'Create', exact: true }).click();
            await page.locator('form input:invalid').first().waitFor();
            assert.equal(await page.locator('form input:invalid').first().evaluate(input => input.validity.valueMissing), true);
            assert.equal(new URL(page.url()).pathname, '/admin/projects/create');
        });
        await scenario('TC-09', 'PROJECT_CREATE', 'admin', 'Admin creates a project that appears in the project list', async page => {
            await login(page, 'admin');
            await open(page, '/admin/projects/create');
            await page.getByRole('textbox', { name: /^Name/ }).fill('QA Created Project');
            await page.getByLabel('Ticket prefix', { exact: false }).fill('CQA');
            await page.getByRole('button', { name: 'Create', exact: true }).click();
            await page.waitForURL(url => /^\/admin\/projects\/\d+/.test(url.pathname), { timeout: 7000 });
            await open(page, '/admin/projects');
            await show(page, 'QA Created Project');
        });
        await scenario('TC-04', 'LOGOUT', 'member', 'Logout invalidates access to the protected profile', async page => {
            await login(page, 'member');
            // Submit the Filament logout form, including its native CSRF field.
            const form = page.locator('form[action$="/admin/logout"]');
            await form.evaluate(element => element.requestSubmit());
            await page.waitForURL(url => url.pathname.endsWith('/login') || url.pathname === '/');
            await page.goto(base + '/admin/profile');
            await page.waitForURL(url => url.pathname === '/admin/login');
        });
    }
} finally {
    await browser.close();
    await writeFile(join(output, `browser-${mode}.json`), JSON.stringify(results, null, 2) + '\n');
}
process.exitCode = results.some(result => result.status === 'FAIL') ? 1 : 0;

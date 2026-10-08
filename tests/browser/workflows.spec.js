import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

const screenshots = 'storage/app/qa-screenshots';

async function login(page, username, password) {
    await page.goto('/login');
    await page.locator('[name="username"]').fill(username);
    await page.locator('[name="password"]').fill(password);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

async function healthy(page) {
    await expect(page.locator('body')).not.toContainText('Internal Server Error');
    const size = await page.evaluate(() => ({ viewport: innerWidth, page: document.documentElement.scrollWidth, images: [...document.images].filter(i => !i.complete || i.naturalWidth === 0).map(i => i.src) }));
    expect(size.page).toBeLessThanOrEqual(size.viewport + 1);
    expect(size.images).toEqual([]);
}

test('student learning, autosave, sticky timer, result, and Japanese mobile interface', async ({ page, context }) => {
    await mkdir(screenshots, { recursive: true });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.screenshot({ path: `${screenshots}/01-login-desktop.png`, fullPage: true });
    await login(page, 'STU-001', 'StudentDemo2026!');
    await healthy(page);
    await expect(page.locator('main')).not.toContainText('Professional Skills');
    await page.screenshot({ path: `${screenshots}/02-student-desktop.png`, fullPage: true });
    for (const path of ['/programs', '/programs/1', '/materials', '/materials/1', '/packages/1', '/history', '/profile']) {
        await page.goto(path);
        await healthy(page);
    }
    await page.goto('/materials/1');
    await expect(page.locator('.prose-content')).toContainText('おはようございます');
    await page.screenshot({ path: `${screenshots}/03-material-reader.png`, fullPage: true });
    await page.goto('/packages/1');
    await page.locator('form[action$="/start"] button').click();
    await expect(page).toHaveURL(/\/practice\/\d+$/);
    const attemptPath = new URL(page.url()).pathname;
    await page.locator('.question-block').first().locator('input[value="A"]').check();
    await expect(page.getByText('All answers saved', { exact: true })).toBeVisible();
    await page.reload();
    await expect(page.locator('.question-block').first().locator('input[value="A"]')).toBeChecked();
    await context.setOffline(true);
    await page.locator('.question-block').nth(1).locator('input[value="B"]').check();
    await context.setOffline(false);
    await expect(page.getByText('All answers saved', { exact: true })).toBeVisible({ timeout: 25000 });
    await page.reload();
    await expect(page.locator('.question-block').nth(1).locator('input[value="B"]')).toBeChecked();
    await page.locator('.question-block').nth(6).scrollIntoViewIfNeeded();
    const desktopTimer = await page.locator('.timer').boundingBox();
    expect(desktopTimer.y).toBeGreaterThanOrEqual(70);
    expect(desktopTimer.y + desktopTimer.height).toBeLessThan(200);
    await page.screenshot({ path: `${screenshots}/04-practice-desktop-scrolled.png` });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator('.question-block').nth(8).scrollIntoViewIfNeeded();
    await healthy(page);
    const mobileTimer = await page.locator('.timer').boundingBox();
    expect(mobileTimer.y).toBeGreaterThanOrEqual(60);
    expect(mobileTimer.y + mobileTimer.height).toBeLessThan(180);
    await page.screenshot({ path: `${screenshots}/05-practice-mobile-scrolled.png` });
    await page.locator('.question-sidebar button').filter({ hasText: 'Finish practice' }).click();
    await page.getByRole('button', { name: 'Submit answers', exact: true }).click();
    await expect(page).toHaveURL(/\/results\/\d+$/);
    await expect(page.locator('.result-summary')).toContainText('17');
    await healthy(page);
    await page.screenshot({ path: `${screenshots}/06-results-mobile.png`, fullPage: true });
    await page.goto(attemptPath);
    await expect(page).toHaveURL(/\/results\//);
    for (const path of ['/dashboard', '/programs', '/programs/1', '/materials', '/materials/1', '/packages/1', '/history', '/profile']) {
        await page.goto(path);
        await healthy(page);
    }
    await page.goto('/dashboard');
    await page.locator('.topbar select[name="locale"]').selectOption('ja');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ja');
    await expect(page.locator('h1')).toContainText('おかえりなさい');
    await page.screenshot({ path: `${screenshots}/07-student-mobile-japanese.png`, fullPage: true });
    await page.locator('.topbar select[name="locale"]').selectOption('en');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await page.locator('.mobile-toggle').click();
    await expect(page.locator('.sidebar')).toHaveClass(/is-open/);
    await page.locator('.sidebar a[href$="/materials"]').click();
    await expect(page).toHaveURL(/\/materials$/);
    expect(errors).toEqual([]);
});

test('admin screens, material preview, question editing, access assignment, and mobile layout', async ({ page }) => {
    await mkdir(screenshots, { recursive: true });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page, 'admin', 'AdminDemo2026!');
    await healthy(page);
    await page.screenshot({ path: `${screenshots}/08-admin-dashboard.png`, fullPage: true });
    const paths = ['/admin/content/programs', '/admin/content/modules', '/admin/content/packages', '/admin/content/questions', '/admin/content/materials', '/admin/students', '/admin/students/2/edit', '/admin/devices', '/admin/results', '/admin/import', '/profile'];
    for (const path of paths) { await page.goto(path); await healthy(page); }
    await page.goto('/admin/content/questions');
    await page.screenshot({ path: `${screenshots}/09-admin-question-bank.png`, fullPage: true });
    await page.goto('/admin/content/materials/1/edit');
    await page.getByRole('button', { name: 'Preview', exact: true }).click();
    await expect(page.locator('.material-preview')).toContainText('Start with a greeting');
    await page.screenshot({ path: `${screenshots}/10-admin-material-editor.png`, fullPage: true });
    await page.getByRole('button', { name: 'Write', exact: true }).click();
    await page.locator('[name="content"]').fill('<img src=x onerror="window.__xss=true"><script>window.__xss=true</script>\n\n## Safe preview');
    await page.getByRole('button', { name: 'Preview', exact: true }).click();
    await expect(page.locator('.material-preview h2')).toHaveText('Safe preview');
    expect(await page.evaluate(() => window.__xss)).toBeUndefined();
    await page.goto('/admin/students/2/edit');
    await page.screenshot({ path: `${screenshots}/11-admin-access-settings.png`, fullPage: true });
    const form = page.locator('form[action$="/access"]');
    const checkedBefore = await form.locator('input:checked').evaluateAll(nodes => nodes.map(n => n.value).sort());
    await form.getByRole('button', { name: 'Save access', exact: true }).click();
    await expect(page.locator('.alert.success')).toContainText('Learning access updated');
    expect(await page.locator('form[action$="/access"] input:checked').evaluateAll(nodes => nodes.map(n => n.value).sort())).toEqual(checkedBefore);
    await page.goto('/admin/content/questions/1/edit');
    const original = await page.locator('[name="question"]').inputValue();
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.locator('.alert.success')).toContainText('Changes saved');
    await expect(page.locator('[name="question"]')).toHaveValue(original);
    await page.setViewportSize({ width: 375, height: 812 });
    for (const path of paths) { await page.goto(path); await healthy(page); }
    await page.goto('/admin/content/materials/1/edit');
    await healthy(page);
    await page.screenshot({ path: `${screenshots}/12-admin-mobile-editor.png`, fullPage: true });
    expect(errors).toEqual([]);
});

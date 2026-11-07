import { expect, test } from '@playwright/test';

for (const framework of ['vue', 'livewire']) {
    test(`${framework} captures a PNG and submits comments`, async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        let payload: Record<string, unknown> | undefined;
        if (framework === 'vue') {
            await page.route('**/screen-feedback', async (route) => {
                payload = route.request().postDataJSON();
                await route.fulfill({ json: { message: 'Feedback submitted successfully.' } });
            });
        }
        await page.goto(framework === 'vue' ? 'http://127.0.0.1:5199/tests/fixtures/vue.html' : 'http://127.0.0.1:8199/demo');
        await page.getByRole('button', { name: 'Give feedback' }).click();
        await page.getByRole('button', { name: 'Take Screenshot' }).click();
        const preview = page.getByAltText('Screenshot preview');
        await expect(preview).toBeVisible({ timeout: 20000 });
        const pixels = await preview.evaluate(async (element: HTMLImageElement) => {
            await element.decode();
            const canvas = document.createElement('canvas');
            canvas.width = element.naturalWidth; canvas.height = element.naturalHeight;
            const context = canvas.getContext('2d')!;
            context.drawImage(element, 0, 0);
            const data = context.getImageData(0, 0, canvas.width, canvas.height).data;
            let colored = 0, red = 0;
            for (let i = 0; i < data.length; i += 4) {
                if (data[i + 3] > 0 && data[i] < 250) colored++;
                if (data[i] > 250 && data[i + 1] < 10 && data[i + 2] < 10) red++;
            }
            return { width: canvas.width, colored, red };
        });
        expect(pixels.width).toBeGreaterThan(100);
        expect(pixels.colored).toBeGreaterThan(100);
        expect(pixels.red).toBe(0);
        await page.getByRole('button', { name: 'Information', exact: true }).click();
        await page.getByLabel('Message', { exact: true }).fill('Feedback from a real browser');
        await page.getByRole('button', { name: 'Send Feedback' }).click();
        await expect(page.getByRole('status')).toHaveText('Feedback submitted successfully.');
        if (framework === 'vue') {
            expect(payload?.message).toBe('Feedback from a real browser');
            expect(payload?.type).toBe('information');
            expect(payload?.screenshot).toMatch(/^iVBOR/);
        }
        expect(errors).toEqual([]);
    });
}

test('Vue displays screenshot validation errors and preserves the comment', async ({ page }) => {
    await page.route('**/screen-feedback', (route) => route.fulfill({ status: 422, json: { message: 'Invalid screenshot.', errors: { screenshot: ['The screenshot is too large.'] } } }));
    await page.goto('http://127.0.0.1:5199/tests/fixtures/vue.html');
    await page.getByRole('button', { name: 'Give feedback' }).click();
    await page.getByLabel('Message', { exact: true }).fill('Keep this comment');
    await page.getByRole('button', { name: 'Send Feedback' }).click();
    await expect(page.getByText('The screenshot is too large.')).toBeVisible();
    await expect(page.getByLabel('Message', { exact: true })).toHaveValue('Keep this comment');
});

test('Livewire displays server validation errors without closing the dialog', async ({ page }) => {
    await page.goto('http://127.0.0.1:8199/demo');
    await page.getByRole('button', { name: 'Give feedback' }).click();
    await page.getByLabel('Message', { exact: true }).fill('Keep this comment');
    await page.evaluate(() => {
        const widget = document.querySelector('[data-screen-feedback]');
        (window as any).Alpine.$data(widget).type = 'invalid';
    });
    await page.getByRole('button', { name: 'Send Feedback' }).click();
    await expect(page.getByText('The selected type is invalid.')).toBeVisible();
    await expect(page.getByLabel('Message', { exact: true })).toHaveValue('Keep this comment');
});

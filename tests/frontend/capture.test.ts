import { afterEach, expect, it, vi } from 'vitest';
import { captureScreenshot } from '../../frontend/core';
import { toPng } from 'html-to-image';

vi.mock('html-to-image', () => ({ toPng: vi.fn() }));
afterEach(() => { vi.clearAllMocks(); document.body.innerHTML = ''; });

it('excludes only opted out content and the feedback widget', async () => {
    document.body.innerHTML = '<main><div data-screen-feedback></div><div data-feedback-exclude></div><input type="password"><dialog open>Keep application dialogs</dialog></main>';
    vi.mocked(toPng).mockResolvedValue('data:image/png;base64,YQ==');
    await captureScreenshot({ target: 'main' });
    const options = vi.mocked(toPng).mock.calls[0][1]!;
    expect(options.filter!(document.querySelector('[data-screen-feedback]')! as HTMLElement)).toBe(false);
    expect(options.filter!(document.querySelector('[data-feedback-exclude]')! as HTMLElement)).toBe(false);
    expect(options.filter!(document.querySelector('input')!)).toBe(false);
    expect(options.filter!(document.querySelector('dialog')!)).toBe(true);
});

it('rejects PNGs above the byte limit', async () => {
    vi.mocked(toPng).mockResolvedValue('data:image/png;base64,YWJj');
    await expect(captureScreenshot({ maxBytes: 2 })).rejects.toThrow('too large');
});

it('reports a missing capture target', async () => {
    await expect(captureScreenshot({ target: '#missing' })).rejects.toThrow('not found');
    expect(toPng).not.toHaveBeenCalled();
});

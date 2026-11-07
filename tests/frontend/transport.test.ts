import { afterEach, expect, it, vi } from 'vitest';
import { createFeedbackClient, FeedbackError } from '../../frontend/core';

const payload = { type: 'issue', message: 'Hello', screenshot: null, page_url: 'https://example.com' };
afterEach(() => { vi.unstubAllGlobals(); document.head.innerHTML = ''; });

it('reads the latest CSRF token and sends the expected payload', async () => {
    const fetch = vi.fn().mockResolvedValue({ ok: true, redirected: false });
    vi.stubGlobal('fetch', fetch);
    let token = 'first';
    const send = createFeedbackClient({ endpoint: '/feedback', csrfToken: () => token });
    token = 'updated';
    await send(payload);
    expect(fetch).toHaveBeenCalledWith(expect.stringContaining('/feedback'), expect.objectContaining({
        credentials: 'same-origin', body: JSON.stringify(payload), headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'updated' }),
    }));
});

it('preserves screenshot validation errors', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 422, json: async () => ({ message: 'Invalid', errors: { screenshot: ['Too large'] } }) }));
    await expect(createFeedbackClient({ endpoint: '/feedback' })(payload)).rejects.toMatchObject({ errors: { screenshot: ['Too large'] } });
});

it('does not send CSRF tokens across origins', async () => {
    const fetch = vi.fn();
    vi.stubGlobal('fetch', fetch);
    await expect(createFeedbackClient({ endpoint: 'https://elsewhere.invalid/feedback' })(payload)).rejects.toBeInstanceOf(FeedbackError);
    expect(fetch).not.toHaveBeenCalled();
});

it('does not mistake a login redirect for successful feedback', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, redirected: true, status: 200, json: async () => { throw new Error('HTML'); } }));
    await expect(createFeedbackClient({ endpoint: '/feedback' })(payload)).rejects.toThrow('Please sign in');
});

export interface FeedbackPayload {
    type: string;
    message: string;
    page_url: string;
    /** Raw PNG base64, without the data URL prefix. */
    screenshot: string | null;
}

export interface FeedbackType {
    value: string;
    label: string;
    recipientLabel?: string;
}

export interface CaptureOptions {
    target?: HTMLElement | string;
    exclude?: string;
    maxBytes?: number;
    maxPixels?: number;
}

export const defaultTypes: FeedbackType[] = [
    { value: 'issue', label: 'Issue' },
    { value: 'information', label: 'Information' },
];

export class FeedbackError extends Error {
    constructor(message: string, public errors: Record<string, string[]> = {}) {
        super(message);
        this.name = 'FeedbackError';
    }
}

/** Capture the page DOM, excluding feedback widgets and explicitly marked elements. */
export async function captureScreenshot(options: CaptureOptions = {}): Promise<string> {
    const target = typeof options.target === 'string'
        ? document.querySelector<HTMLElement>(options.target)
        : options.target ?? document.querySelector<HTMLElement>('[data-page]') ?? document.getElementById('app') ?? document.body;
    if (!target) throw new Error('Screenshot target was not found.');

    const excluded = '[data-screen-feedback], [data-feedback-exclude], input[type="password"]';
    const selector = options.exclude ? `${excluded}, ${options.exclude}` : excluded;
    if (target.matches(selector)) throw new Error('Screenshot target is excluded.');
    const width = Math.max(target.scrollWidth, target.clientWidth, 1);
    const height = Math.max(target.scrollHeight, target.clientHeight, 1);
    const scale = Math.min(1, Math.sqrt((options.maxPixels ?? 4_000_000) / (width * height)), 8192 / width, 8192 / height);
    const { toPng } = await import('html-to-image');
    let timer: ReturnType<typeof setTimeout> | undefined;

    try {
        const dataUrl = await Promise.race([
            toPng(target, {
                width, height,
                canvasWidth: Math.max(1, Math.floor(width * scale)),
                canvasHeight: Math.max(1, Math.floor(height * scale)),
                pixelRatio: 1,
                filter: (node) => !(node instanceof Element) || !node.matches(selector),
            }),
            new Promise<never>((_, reject) => {
                timer = setTimeout(() => reject(new Error('Screenshot capture timed out.')), 15000);
            }),
        ]);
        if (!dataUrl.startsWith('data:image/png;base64,')) throw new Error('Unable to render the screenshot.');
        const base64 = dataUrl.slice('data:image/png;base64,'.length);
        const bytes = Math.floor(base64.length * 3 / 4) - (base64.endsWith('==') ? 2 : base64.endsWith('=') ? 1 : 0);
        if (bytes > (options.maxBytes ?? 2_000_000)) throw new Error('The screenshot is too large. Try capturing a smaller area.');
        return base64;
    } finally {
        clearTimeout(timer);
    }
}

/** Same-origin Laravel transport; reads the current CSRF token on every submission. */
export function createFeedbackClient(options: {
    endpoint: string;
    csrfToken?: () => string | undefined;
}): (payload: FeedbackPayload) => Promise<void> {
    return async (payload) => {
        const url = new URL(options.endpoint, window.location.href);
        if (url.origin !== window.location.origin) throw new FeedbackError('Feedback endpoint must use the same origin.');
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        const token = options.csrfToken?.() ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        const cookie = document.cookie.split('; ').find((value) => value.startsWith('XSRF-TOKEN='));
        if (token) headers['X-CSRF-TOKEN'] = token;
        else if (cookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie.slice('XSRF-TOKEN='.length));

        const response = await fetch(url.href, {
            method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload),
        });
        if (!response.ok || response.redirected) {
            const data = await response.json().catch(() => ({}));
            throw new FeedbackError(
                response.status === 419 ? 'Your session expired. Refresh the page and try again.'
                    : response.status === 401 || response.status === 403 || response.redirected ? 'Please sign in to send feedback.'
                    : data.message ?? 'Unable to send feedback. Please try again.',
                response.status === 422 ? data.errors ?? {} : {},
            );
        }
    };
}

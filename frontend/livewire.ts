import { captureScreenshot } from './core';

declare global {
    interface Window {
        ScreenFeedback: { captureScreenshot: typeof captureScreenshot };
    }
}

if (typeof window !== 'undefined') {
    window.ScreenFeedback = { captureScreenshot };
}

export { captureScreenshot };

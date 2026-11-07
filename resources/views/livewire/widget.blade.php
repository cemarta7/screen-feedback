<div class="sf-widget" data-screen-feedback x-data="{
    type: @js(array_key_first($types)), message: '', screenshot: null,
    capturing: false, submitting: false, captureError: '', error: '', notice: '', generation: 0,
    reset() {
        this.generation++; this.capturing = false; this.message = ''; this.screenshot = null;
        this.type = @js(array_key_first($types)); this.captureError = ''; this.error = '';
    },
    async capture() {
        if (this.capturing || this.submitting) return;
        const current = ++this.generation;
        this.capturing = true; this.captureError = '';
        try {
            if (!window.ScreenFeedback) throw new Error('Screenshot assets are not loaded.');
            const image = await window.ScreenFeedback.captureScreenshot({ maxBytes: @js($maxScreenshotBytes) });
            if (current === this.generation) this.screenshot = image;
        } catch (e) {
            if (current === this.generation) this.captureError = e.message;
        } finally { if (current === this.generation) this.capturing = false; }
    },
    async send() {
        if (this.submitting || this.capturing || !this.message.trim()) return;
        this.submitting = true; this.error = '';
        try {
            const success = await $wire.send({ type: this.type, message: this.message.trim(), screenshot: this.screenshot, page_url: window.location.href });
            if (success === true) { this.$refs.dialog.close(); this.notice = 'Feedback submitted successfully.'; }
        } catch (e) { this.error = 'Unable to send feedback. Please try again.'; }
        finally { this.submitting = false; }
    }
}">
    <p x-show="notice" x-text="notice" x-cloak class="sf-notice" role="status"></p>
    <button type="button" class="sf-trigger" @click="notice = ''; $refs.dialog.showModal()">Give feedback</button>
    <dialog wire:ignore.self x-ref="dialog" class="sf-dialog" aria-label="Share Feedback" @close="reset()" @cancel="if (submitting) $event.preventDefault()">
        <form class="sf-form" @submit.prevent="send()">
            <h2>Share Feedback</h2>
            <button type="button" :disabled="capturing || submitting" @click="capture()" x-text="capturing ? 'Capturing…' : screenshot ? 'Retake Screenshot' : 'Take Screenshot'"></button>
            <p x-show="captureError" x-cloak class="sf-error" role="alert"><span x-text="captureError"></span> You can still send feedback without a screenshot.</p>
            <template x-if="screenshot">
                <div class="sf-preview">
                    <img :src="'data:image/png;base64,' + screenshot" alt="Screenshot preview" />
                    <button type="button" :disabled="submitting" @click="screenshot = null">Remove screenshot</button>
                </div>
            </template>
            <fieldset :disabled="submitting">
                <legend>Feedback Type</legend>
                <div class="sf-types">
                    @foreach ($types as $value => $label)
                        <button type="button" @click="type = @js($value)" :aria-pressed="type === @js($value)" data-feedback-type="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </fieldset>
            <label for="feedback-message-{{ $this->getId() }}">Message</label>
            <textarea id="feedback-message-{{ $this->getId() }}" x-model="message" name="message" rows="4" required maxlength="{{ $maxMessageLength }}" :disabled="submitting" placeholder="Describe the issue or share your information…"></textarea>
            @foreach ($errors->all() as $error)
                <p class="sf-error" role="alert">{{ $error }}</p>
            @endforeach
            <p x-show="error" x-text="error" x-cloak class="sf-error" role="alert"></p>
            <div class="sf-actions">
                <button type="button" :disabled="submitting" @click="$refs.dialog.close()">Cancel</button>
                <button type="submit" class="sf-primary" :disabled="submitting || capturing || !message.trim()" x-text="submitting ? 'Sending…' : 'Send Feedback'"></button>
            </div>
        </form>
    </dialog>
</div>

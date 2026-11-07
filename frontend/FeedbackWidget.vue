<script setup lang="ts">
import { computed, onBeforeUnmount, ref, useId } from 'vue';
import { captureScreenshot, defaultTypes, FeedbackError, type CaptureOptions, type FeedbackPayload, type FeedbackType } from './core';

const props = withDefaults(defineProps<{
    submit: (payload: FeedbackPayload) => Promise<void>;
    types?: FeedbackType[];
    capture?: CaptureOptions;
    title?: string;
    maxMessageLength?: number;
}>(), { types: () => defaultTypes, title: 'Share Feedback', maxMessageLength: 5000 });

const emit = defineEmits<{ submitted: [payload: FeedbackPayload] }>();
const id = useId();
const dialog = ref<HTMLDialogElement>();
const message = ref('');
const type = ref(props.types[0]?.value ?? 'issue');
const screenshot = ref<string | null>(null);
const capturing = ref(false);
const submitting = ref(false);
const errors = ref<Record<string, string[]>>({});
const error = ref('');
const captureError = ref('');
const notice = ref('');
const recipient = computed(() => props.types.find((item) => item.value === type.value)?.recipientLabel);
let generation = 0;

function reset() {
    generation++;
    capturing.value = false;
    message.value = '';
    screenshot.value = null;
    type.value = props.types[0]?.value ?? 'issue';
    errors.value = {};
    error.value = '';
    captureError.value = '';
}

function open() {
    notice.value = '';
    dialog.value?.showModal();
}

async function capture() {
    if (capturing.value || submitting.value) return;
    const current = ++generation;
    capturing.value = true;
    captureError.value = '';
    try {
        const image = await captureScreenshot(props.capture);
        if (current === generation) screenshot.value = image;
    } catch (exception) {
        if (current === generation) captureError.value = exception instanceof Error ? exception.message : 'Unable to capture this page.';
    } finally {
        if (current === generation) capturing.value = false;
    }
}

async function send() {
    if (submitting.value || capturing.value || !message.value.trim()) return;
    submitting.value = true;
    errors.value = {};
    error.value = '';
    const payload: FeedbackPayload = {
        type: type.value, message: message.value.trim(), page_url: window.location.href, screenshot: screenshot.value,
    };
    try {
        await props.submit(payload);
        dialog.value?.close();
        notice.value = 'Feedback submitted successfully.';
        emit('submitted', payload);
    } catch (exception) {
        if (exception instanceof FeedbackError) errors.value = exception.errors;
        error.value = exception instanceof Error ? exception.message : 'Unable to send feedback. Please try again.';
    } finally {
        submitting.value = false;
    }
}

onBeforeUnmount(() => generation++);
defineExpose({ open });
</script>

<template>
    <div class="sf-widget" data-screen-feedback>
        <p v-if="notice" class="sf-notice" role="status">{{ notice }}</p>
        <slot name="trigger" :open="open">
            <button type="button" class="sf-trigger" @click="open">Give feedback</button>
        </slot>
        <dialog ref="dialog" class="sf-dialog" :aria-labelledby="`${id}-title`" @close="reset" @cancel="submitting && $event.preventDefault()">
            <form class="sf-form" @submit.prevent="send">
                <h2 :id="`${id}-title`">{{ title }}</h2>
                <button type="button" :disabled="capturing || submitting" @click="capture">
                    {{ capturing ? 'Capturing…' : screenshot ? 'Retake Screenshot' : 'Take Screenshot' }}
                </button>
                <p v-if="captureError" class="sf-error" role="alert">{{ captureError }} You can still send feedback without a screenshot.</p>
                <div v-if="screenshot" class="sf-preview">
                    <img :src="`data:image/png;base64,${screenshot}`" alt="Screenshot preview" />
                    <button type="button" :disabled="submitting" @click="screenshot = null">Remove screenshot</button>
                </div>
                <fieldset :disabled="submitting">
                    <legend>Feedback Type</legend>
                    <div class="sf-types">
                        <button v-for="item in types" :key="item.value" type="button" :data-feedback-type="item.value" :aria-pressed="type === item.value" @click="type = item.value">{{ item.label }}</button>
                    </div>
                </fieldset>
                <p v-if="recipient">Email sent to: {{ recipient }}</p>
                <label :for="`${id}-message`">Message</label>
                <textarea :id="`${id}-message`" v-model="message" name="message" rows="4" required :maxlength="maxMessageLength" :disabled="submitting" placeholder="Describe the issue or share your information…" />
                <p v-if="error" class="sf-error" role="alert">{{ error }}</p>
                <ul v-if="Object.keys(errors).length" class="sf-error" role="alert">
                    <template v-for="(messages, field) in errors" :key="field">
                        <li v-for="text in messages" :key="text">{{ text }}</li>
                    </template>
                </ul>
                <div class="sf-actions">
                    <button type="button" :disabled="submitting" @click="dialog?.close()">Cancel</button>
                    <button type="submit" class="sf-primary" :disabled="submitting || capturing || !message.trim()">{{ submitting ? 'Sending…' : 'Send Feedback' }}</button>
                </div>
            </form>
        </dialog>
    </div>
</template>

<script setup lang="ts">
import { FeedbackWidget, createFeedbackClient } from '@cemarta7/screen-feedback/vue';
import '@cemarta7/screen-feedback/style.css';
import { usePage } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/FeedbackController';

const page = usePage();
const submitFeedback = createFeedbackClient({
    endpoint: store().url,
    csrfToken: () => page.props.csrf_token as string,
});
const types = [
    { value: 'issue', label: 'Issue', recipientLabel: 'Auto Key Manager support.' },
    { value: 'information', label: 'Information', recipientLabel: 'Your company administrator.' },
];
</script>

<template>
    <FeedbackWidget :submit="submitFeedback" :types="types" />
</template>

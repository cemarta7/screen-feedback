import { createApp, h } from 'vue';
import { FeedbackWidget, createFeedbackClient } from '../../frontend/vue';
import '../../frontend/style.css';

createApp({
    render() {
        return h('div', [
            h('main', { 'data-page': '', style: 'padding:32px;background:oklch(96% 0.02 260);color:#111827' }, [
                h('h1', 'Vue application'), h('p', 'Screenshot content with modern colors.'),
                h('div', { 'data-feedback-exclude': '', style: 'width:100px;height:100px;background:#ff0000' }, 'Excluded'),
            ]),
            h(FeedbackWidget, { submit: createFeedbackClient({ endpoint: '/screen-feedback', csrfToken: () => 'test-token' }) }),
        ]);
    },
}).mount('#app');

# Screen Feedback

Reusable **Laravel + Vue 3** and **Laravel + Livewire 3/4** screenshot feedback. One PHP backend and one shared browser capture engine, with framework-specific widgets.

Includes a floating feedback button, comments, optional PNG screenshot/preview/removal, configurable feedback categories, validation, authenticated submissions, rate limiting, private temporary image storage, and queued email attachments. No database tables, Inertia, Tailwind, AKM models, or UI library are required.

The screenshot renders the webpage DOM, not the operating system or other browser tabs. Capture uses `html-to-image`, which handles embedded assets and computed styles without temporarily modifying the live page.

## GitHub installation

After the `v0.1.1` tag is pushed:

```sh
composer config repositories.screen-feedback vcs git@github.com:cemarta7/screen-feedback.git
composer require cemarta7/screen-feedback:0.1.1
npm install "git+ssh://git@github.com/cemarta7/screen-feedback.git#v0.1.1"
```

The npm package is named `@cemarta7/screen-feedback`. Its `prepare` hook builds the JavaScript and TypeScript declarations during Git installation. Keep npm lifecycle scripts enabled and provide GitHub SSH access in environments installing this dependency. The `v0.1.0` Git tag lacks this hook and installs without the required `dist/` files; use `v0.1.1` for Git-based npm installs.

## Local installation

In this repository:

```sh
composer install
npm ci
npm pack
```

In a sibling Laravel application, add the Composer path repository, then install:

```sh
composer config repositories.screen-feedback '{"type":"path","url":"../screen-feedback","options":{"versions":{"cemarta7/screen-feedback":"dev-main"}}}'
composer require cemarta7/screen-feedback:dev-main
npm install ../screen-feedback/cemarta7-screen-feedback-0.1.1.tgz
php artisan vendor:publish --tag=screen-feedback-config
```

These paths are for local development. Before deploying, distribute the Composer package through a reachable VCS/private registry and the npm package through a registry or an artifact included in the deployment. Do not deploy a lockfile that depends on a missing sibling directory. Package names are provisional and have not been reserved in registries.

Set the recipient in the consuming application's `.env`:

```dotenv
SCREEN_FEEDBACK_TO=support@example.com
SCREEN_FEEDBACK_DISK=local
SCREEN_FEEDBACK_QUEUE=default
```

Configure Laravel's mail transport and queue normally. Run a queue worker for the selected queue. A `sync` connection sends during the request; an asynchronous connection requires a worker. The storage disk must be accessible from both the web process and queue worker. Images are deleted after successful email delivery; failed jobs retain their images for retries. After abandoning failed jobs, remove their retained `screen-feedback/` files according to your application's retention policy.

## Laravel + Vue

Mount the widget once in your authenticated layout:

```vue
<script setup lang="ts">
import { FeedbackWidget, createFeedbackClient } from '@cemarta7/screen-feedback/vue';
import '@cemarta7/screen-feedback/style.css';

// Supply this URL from Laravel's route('screen-feedback.store'),
// an Inertia shared prop, or your application's typed route generator.
const props = defineProps<{ feedbackEndpoint: string }>();
const submitFeedback = createFeedbackClient({ endpoint: props.feedbackEndpoint });
</script>

<template>
    <FeedbackWidget :submit="submitFeedback" />
</template>
```

The transport reads `<meta name="csrf-token" content="{{ csrf_token() }}">` or Laravel's `XSRF-TOKEN` cookie on each submission. You can supply `csrfToken: () => currentToken` when creating the client. A session cookie and the normal Laravel `web` middleware are required. Expired sessions and field validation errors are displayed in the widget.

Vue props:

| Prop | Purpose |
| --- | --- |
| `submit` | Required async callback. Resolve on success; throw `FeedbackError(message, errors)` for field errors. |
| `types` | `{ value, label, recipientLabel? }[]`; defaults to issue/information. Must match server config. |
| `capture` | `{ target?, exclude?, maxBytes?, maxPixels? }`; target is an element or CSS selector. |
| `title` | Dialog title. |
| `maxMessageLength` | Client limit; defaults to 5,000. Match the server limit. |

The `submitted` event emits the payload after success. The `trigger` slot receives an `open` function for a custom launcher. The widget has a native modal dialog, keyboard focus handling, responsive layout, and light/dark styles. Override `--sf-*` CSS properties on `.sf-widget` for branding.

The core helpers can be imported without Vue:

```ts
import { captureScreenshot, createFeedbackClient } from '@cemarta7/screen-feedback';
```

## Laravel + Livewire

Install Livewire 3.6+ or 4.x in the consuming application if it is not already installed. Livewire is optional; Vue applications do not install it as a package dependency.

In your Vite JavaScript entry:

```js
import '@cemarta7/screen-feedback/livewire';
import '@cemarta7/screen-feedback/style.css';
```

In the application's authenticated Blade layout, with Vite and Livewire assets loaded:

```blade
<livewire:screen-feedback />
```

Use the Alpine runtime supplied by Livewire, rather than loading a second Alpine instance. The widget calls a Livewire action that uses the same PHP submission service as the Vue endpoint. Authentication, validation, recipient selection, and rate limiting apply to both entrypoints. Livewire reads categories and limits from the server config automatically.

Publish the Blade widget/email for UI customization:

```sh
php artisan vendor:publish --tag=screen-feedback-views
```

The Livewire view's capture call accepts the same `target`/`exclude` options as Vue when customized.

## Laravel configuration and extension points

`config/screen-feedback.php` configures the recipient, disk, queue, endpoint path, middleware, category labels, size limits, and per-user submissions per minute. Authentication is enforced inside the shared submission service, including Livewire calls. `middleware` config applies to the HTTP endpoint; host-specific authorization in both interfaces belongs in a custom handler. Set `register_routes` to `false` when using only Livewire or an existing application endpoint.

The wire format is compatible with AKM's existing endpoint:

```json
{"type":"issue","message":"Something went wrong","page_url":"https://example.com/page","screenshot":null}
```

`screenshot`, when present, is raw PNG base64 without a `data:` prefix. The default decoded-image limit is 2 MB. The server verifies PNG metadata and pixel dimensions. It never accepts a recipient or sender identity from the browser.

### Application-specific recipients

Bind `ScreenFeedback\Contracts\RecipientResolver` to a class implementing:

```php
public function resolve(
    \ScreenFeedback\FeedbackData $feedback,
    \Illuminate\Contracts\Auth\Authenticatable $user,
): string;
```

Register the binding in the host application's service provider. Resolve company administrators, support teams, or categories there. Throw a Laravel `ValidationException` when there is no valid recipient. This runs server-side before an image is stored.

### Application-specific storage and delivery

Bind `ScreenFeedback\Contracts\FeedbackHandler` to replace the default email handler. Its `handle(FeedbackData $feedback, Authenticatable $user): void` receives validated feedback. This is the integration point for tenant authorization, a database inbox, tenant-aware mail jobs, or another ticket system. A custom handler owns its persistence, screenshot lifecycle, and delivery.

The default handler deliberately has no tenant switching or user-model assumptions beyond reading `name`/`email` for the email. It queues a small DTO and a storage path rather than serializing image bytes or Eloquent models into the queue.

## AKM integration

AKM's `resources/js/components/FeedbackBubble.vue` now consumes this package's `captureScreenshot` and `createFeedbackClient` helpers. Its existing UI and onboarding selectors remain application-owned, and submissions still use its `/feedback` controller, database inbox, company-recipient rules, and queued tenant-aware mail. The application reads its current Inertia CSRF prop and generates the endpoint with Wayfinder.

AKM carries the npm archive in `resources/js/vendor/` and references it from `package.json`/`package-lock.json`, so installation does not require a sibling checkout. For a package update, bump the npm version, run `npm pack` here, copy the new archive into that directory, and install the new file in AKM. Keep the archive with the application changes until registry distribution is configured.

`examples/akm/FeedbackBubble.vue` shows the alternative integration using the complete default Vue widget. The Composer backend is for other applications, or a later migration of AKM's backend behind `FeedbackHandler`. Installing the generic backend does not automatically migrate existing submissions or admin responses.

## Capture boundaries

- Default target: `[data-page]`, then `#app`, then `document.body`.
- Feedback widgets, `[data-feedback-exclude]` elements, and password inputs are excluded. Use that attribute or `capture.exclude` for other content that should not be captured.
- Capture is limited to four million output pixels and 8,192 pixels per dimension by default; oversized PNGs produce a visible error and comments can still be sent.
- Cross-origin images/fonts require appropriate CORS access; videos, external iframes, and some canvas content may not render. Chromium is browser-tested. Test your specific pages before relying on screenshot fidelity in other browsers.
- The supplied page URL includes its query/fragment. Customize the submit callback or handler if your application needs a different URL policy.

## Development

```sh
composer test
composer analyse
composer format
npm run build
npm test
npx playwright install chromium
npm run test:browser
```

Browser tests run isolated Vue and Livewire fixtures. They exercise real PNG capture, nonblank image pixels, exclusions, submission, and server validation. The Livewire fixture uses a test-only identity and an in-memory mailer; it is not registered in consuming applications.

Verified locally on PHP 8.4 with Laravel 12.71 + Livewire 3.8 and Laravel 13.40 + Livewire 4.4. Each combination passes the PHP suite and real-browser widget tests. CI also defines a PHP 8.3 compatibility job for Laravel 12.

This repository starts private/unlicensed. Choose a distribution license before public publication.

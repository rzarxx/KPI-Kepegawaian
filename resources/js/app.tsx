import '../css/app.css';
import './bootstrap';
import '@fontsource-variable/inter/wght.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import PwaUpdatePrompt from './Components/PwaUpdatePrompt';
import PwaInstallPrompt from './Components/PwaInstallPrompt';
import ConnectivityStatus from './Components/ConnectivityStatus';
import ConfirmationDialogHost from './Components/ConfirmationDialogHost';

const appName = import.meta.env.VITE_APP_NAME || 'KPI Kepegawaian';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        void navigator.serviceWorker.register('/sw.js');
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<><App {...props} /><ConfirmationDialogHost /><ConnectivityStatus /><PwaUpdatePrompt /><PwaInstallPrompt /></>);
    },
    progress: {
        color: '#4B5563',
    },
});

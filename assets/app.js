import './styles/app.css';
import './styles/flags.css';
// import './styles/forest.css';
import './styles/map.css';

import './bootstrap.js';

import { startStimulusApp } from '@symfony/stimulus-bundle';
import LiveController from '@symfony/ux-live-component';

export const app = startStimulusApp();
app.register('live', LiveController); 
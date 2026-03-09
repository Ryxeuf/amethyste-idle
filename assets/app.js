import './styles/app.css';
import './styles/flags.css';
// import './styles/forest.css';
import './styles/map.css';
import './styles/map/world-1.css';
// import './styles/map/collisions.css';
// import './styles/map/forest.css';
// import './styles/map/basechip_pipo.css';

import './bootstrap.js';

import { startStimulusApp } from '@symfony/stimulus-bundle';
import LiveController from '@symfony/ux-live-component';

export const app = startStimulusApp();
app.register('live', LiveController); 
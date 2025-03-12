import './bootstrap.js';
import './styles/app.css';
import './styles/flags.css';
import './styles/forest.css';
import './styles/map.css';

import { startStimulusApp } from '@symfony/stimulus-bundle';
import LiveController from '@symfony/ux-live-component';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

export const app = startStimulusApp();
app.register('live', LiveController);

// Vous pouvez ajouter d'autres imports ou code JavaScript ici
// console.log('Le fichier app.js est chargé'); 
import './bootstrap';

import Alpine from 'alpinejs';
import registerMessenger from './messenger';

window.Alpine = Alpine;
registerMessenger(Alpine);

Alpine.start();

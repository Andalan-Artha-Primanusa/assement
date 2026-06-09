import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import { initAdminCharts } from './charts';
window.initAdminCharts = initAdminCharts;

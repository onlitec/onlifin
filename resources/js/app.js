import './bootstrap';
import Alpine from 'alpinejs';
import { initMoneyMask } from './money-mask';

window.Alpine = Alpine;
window.initMoneyMask = initMoneyMask;

Alpine.start();
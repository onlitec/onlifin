import './bootstrap';
import Alpine from 'alpinejs';
<<<<<<< HEAD
import { initMoneyMask } from './money-mask';

=======
import mask from '@alpinejs/mask';
import focus from '@alpinejs/focus';
import 'flowbite';
import './notification';

Alpine.plugin(mask);
Alpine.plugin(focus);
>>>>>>> Beta1
window.Alpine = Alpine;
window.initMoneyMask = initMoneyMask;

Alpine.start();
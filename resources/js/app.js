import './bootstrap';
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import focus from '@alpinejs/focus';
import 'flowbite';
import './notification';

Alpine.plugin(mask);
Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();

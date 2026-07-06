import './bootstrap';

import Alpine from 'alpinejs';
import { chatApp } from './chat';

window.Alpine = Alpine;

// Register the chat component globally so Blade can use x-data="chatApp()"
Alpine.data('chatApp', chatApp);

Alpine.start();

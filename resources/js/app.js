import Alpine from 'alpinejs';
import { createGameApp } from './game/GameApp';

window.Alpine = Alpine;

Alpine.data('gameApp', createGameApp);

Alpine.start();

import Alpine from 'alpinejs';
import { createGameApp } from './game/GameApp';
import { createSiteMenu } from './siteMenu';

window.Alpine = Alpine;

Alpine.data('gameApp', createGameApp);
Alpine.data('siteMenu', createSiteMenu);

Alpine.start();

const botonMenu = document.querySelector('.menu-toggle');
const menuPrincipal = document.querySelector('#main-menu');

if (botonMenu && menuPrincipal) {
    botonMenu.addEventListener('click', () => {
        const abierto = menuPrincipal.classList.toggle('open');
        botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
}

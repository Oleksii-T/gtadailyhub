(() => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#primary-nav');
  const search = document.querySelector('.header-search');
  const closeMenu = () => {
    toggle?.setAttribute('aria-expanded', 'false');
    nav?.classList.remove('is-open');
  };
  toggle?.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(isOpen));
    nav?.classList.toggle('is-open', isOpen);
    if (search) search.open = false;
  });
  search?.addEventListener('toggle', () => {
    if (search.open) {
      closeMenu();
      search.querySelector('input')?.focus();
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      if (search?.open) {
        search.open = false;
        search.querySelector('summary')?.focus();
      }
      if (toggle?.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        toggle.focus();
      }
    }
  });
  document.addEventListener('click', event => {
    if (search?.open && !search.contains(event.target)) search.open = false;
  });
  window.matchMedia('(min-width: 901px)').addEventListener('change', closeMenu);
})();

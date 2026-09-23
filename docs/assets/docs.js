(() => {
  const base = document.documentElement.dataset.base || './';
  const current = document.body.dataset.page || 'home';
  const groups = [
    ['Getting Started', [
      ['home', 'Introduction', 'index.html'],
      ['getting-started', 'Installation', 'getting-started/'],
      ['configuration', 'Configuration', 'configuration/'],
      ['upgrade', 'Upgrade from 1.x', 'upgrade/']
    ]],
    ['Publishing', [
      ['publishing', 'Publishing & scheduling', 'publishing/'],
      ['platforms', 'Platforms', 'platforms/'],
      ['ai-mcp', 'AI & MCP', 'ai-mcp/']
    ]],
    ['Project', [
      ['github', 'GitHub', 'https://github.com/prateekbhujel/larapost'],
      ['packagist', 'Packagist', 'https://packagist.org/packages/prateekbhujel/larapost']
    ]]
  ];

  const hrefFor = (href) => href.startsWith('http') ? href : base + href;
  const html = groups.map(([title, items]) => {
    const links = items.map(([id, label, href]) =>
      '<a class="nav-link ' + (id === current ? 'active' : '') + '" href="' + hrefFor(href) + '">' + label + '</a>'
    ).join('');
    return '<div class="nav-group"><div class="nav-title">' + title + '</div>' + links + '</div>';
  }).join('');

  document.querySelectorAll('[data-docs-nav]').forEach((el) => { el.innerHTML = html; });
})();

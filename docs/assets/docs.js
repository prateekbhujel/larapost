(() => {
  const base = document.documentElement.dataset.base || './';
  const current = document.body.dataset.page || 'home';

  const pages = [
    { id: 'home', title: 'Introduction', section: 'Getting Started', href: 'index.html', description: 'What LaraPost is and the shortest path to your first post.', keywords: 'overview introduction package social publishing' },
    { id: 'getting-started', title: 'Installation', section: 'Getting Started', href: 'getting-started/', description: 'Install LaraPost, choose platforms, connect an account, and run diagnostics.', keywords: 'composer artisan install requirements doctor' },
    { id: 'configuration', title: 'Configuration', section: 'Getting Started', href: 'configuration/', description: 'Environment variables, routes, queues, scheduler, dashboard, providers, and TikTok mode.', keywords: 'env config queue scheduler routes dashboard tiktok' },
    { id: 'upgrade', title: 'Upgrade Guide', section: 'Getting Started', href: 'upgrade/', description: 'Move a LaraPost 1.x application to the 2.x runtime and security contract.', keywords: 'upgrade migration breaking changes 2.0' },
    { id: 'publishing', title: 'Publishing', section: 'Publishing', href: 'publishing/', description: 'Immediate publishing, queues, scheduling, retries, media, and account targeting.', keywords: 'post publish queue schedule retry accounts media' },
    { id: 'platforms', title: 'Platforms', section: 'Publishing', href: 'platforms/', description: 'Facebook, X, LinkedIn, and TikTok provider behavior and limitations.', keywords: 'facebook twitter x linkedin tiktok oauth' },
    { id: 'ai-mcp', title: 'AI & MCP', section: 'Publishing', href: 'ai-mcp/', description: 'Optional authenticated MCP access for compatible AI clients.', keywords: 'mcp ai chatgpt claude codex tools token' },
    { id: 'agents', title: 'Agent Guide', section: 'Reference', href: 'agents/', description: 'Version-aware instructions for coding agents integrating or modifying LaraPost.', keywords: 'agent agents llm codex guidelines source map' },
    { id: 'security', title: 'Security', section: 'Reference', href: 'security/', description: 'Credential handling, route protection, OAuth, MCP security, and disclosure.', keywords: 'security auth oauth token credentials disclosure' },
    { id: 'release', title: 'Release & Maintenance', section: 'Reference', href: 'release/', description: 'Versioning, release workflow, tags, support, and backports.', keywords: 'release semver tag support maintenance backport' },
    { id: 'contributing', title: 'Contribution Guide', section: 'Reference', href: 'contributing/', description: 'Development workflow, tests, pull requests, and documentation expectations.', keywords: 'contribute tests pull request development' },
    { id: 'code-of-conduct', title: 'Code of Conduct', section: 'Reference', href: 'code-of-conduct/', description: 'Community participation expectations.', keywords: 'community conduct' }
  ];

  const groups = [
    ['Getting Started', ['home', 'getting-started', 'configuration', 'upgrade']],
    ['Publishing', ['publishing', 'platforms', 'ai-mcp']],
    ['Reference', ['agents', 'security', 'release', 'contributing', 'code-of-conduct']],
    ['Project', [
      { title: 'GitHub', href: 'https://github.com/prateekbhujel/larapost' },
      { title: 'Packagist', href: 'https://packagist.org/packages/prateekbhujel/larapost' }
    ]]
  ];

  const pageById = new Map(pages.map((page) => [page.id, page]));
  const hrefFor = (href) => href.startsWith('http') ? href : base + href;

  const navHtml = groups.map(([title, items]) => {
    const links = items.map((item) => {
      if (typeof item === 'object') {
        return '<a class="nav-link" href="' + item.href + '">' + item.title + '</a>';
      }

      const page = pageById.get(item);
      return '<a class="nav-link ' + (item === current ? 'active' : '') + '" href="' + hrefFor(page.href) + '">' + page.title + '</a>';
    }).join('');

    return '<div class="nav-group"><div class="nav-title">' + title + '</div>' + links + '</div>';
  }).join('');

  document.querySelectorAll('.sidebar[data-docs-nav]').forEach((el) => {
    el.innerHTML = navHtml;
  });

  // Never render the complete documentation tree inline above the mobile article.
  document.querySelectorAll('.mobile-nav[data-docs-nav]').forEach((el) => {
    el.innerHTML = '';
    el.setAttribute('aria-hidden', 'true');
  });

  const topbar = document.querySelector('.topbar');
  const topLinks = document.querySelector('.top-links');

  if (topbar && topLinks) {
    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'mobile-menu-button';
    menuButton.setAttribute('aria-label', 'Open documentation navigation');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
    topLinks.appendChild(menuButton);

    const drawer = document.createElement('div');
    drawer.className = 'mobile-drawer';
    drawer.hidden = true;
    drawer.innerHTML = '<div class="mobile-drawer-backdrop" data-nav-close></div>' +
      '<aside class="mobile-drawer-panel" aria-label="Documentation navigation">' + navHtml + '</aside>';
    document.body.appendChild(drawer);

    const closeDrawer = () => {
      drawer.hidden = true;
      document.documentElement.classList.remove('nav-open');
      menuButton.setAttribute('aria-expanded', 'false');
    };

    const openDrawer = () => {
      drawer.hidden = false;
      document.documentElement.classList.add('nav-open');
      menuButton.setAttribute('aria-expanded', 'true');
    };

    menuButton.addEventListener('click', () => drawer.hidden ? openDrawer() : closeDrawer());
    drawer.querySelector('[data-nav-close]')?.addEventListener('click', closeDrawer);
    drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeDrawer));

    const searchButton = document.createElement('button');
    searchButton.type = 'button';
    searchButton.className = 'docs-search-trigger';
    searchButton.innerHTML = '<span>Search documentation</span><kbd>⌘K</kbd>';
    searchButton.setAttribute('aria-label', 'Search documentation');
    topLinks.insertBefore(searchButton, topLinks.firstChild);

    const dialog = document.createElement('div');
    dialog.className = 'docs-search-dialog';
    dialog.hidden = true;
    dialog.innerHTML = '<div class="docs-search-backdrop" data-search-close></div>' +
      '<section class="docs-search-panel" role="dialog" aria-modal="true" aria-label="Search LaraPost documentation">' +
      '<div class="docs-search-input-row"><span aria-hidden="true">⌕</span><input type="search" placeholder="Search documentation..." autocomplete="off" aria-label="Search documentation"><button type="button" data-search-close>Esc</button></div>' +
      '<div class="docs-search-results"></div>' +
      '<div class="docs-search-help">Search LaraPost 2.x documentation</div>' +
      '</section>';
    document.body.appendChild(dialog);

    const input = dialog.querySelector('input');
    const results = dialog.querySelector('.docs-search-results');

    const renderResults = (query = '') => {
      const normalized = query.trim().toLowerCase();
      const matches = pages.filter((page) => {
        if (!normalized) return true;

        return [page.title, page.section, page.description, page.keywords]
          .join(' ')
          .toLowerCase()
          .includes(normalized);
      }).slice(0, 9);

      results.innerHTML = '';

      if (!matches.length) {
        const empty = document.createElement('div');
        empty.className = 'docs-search-empty';
        empty.textContent = 'No matching documentation page.';
        results.appendChild(empty);
        return;
      }

      matches.forEach((page) => {
        const link = document.createElement('a');
        link.className = 'docs-search-result';
        link.href = hrefFor(page.href);

        const meta = document.createElement('span');
        meta.className = 'docs-search-result-section';
        meta.textContent = page.section;

        const title = document.createElement('strong');
        title.textContent = page.title;

        const description = document.createElement('span');
        description.textContent = page.description;

        link.append(meta, title, description);
        results.appendChild(link);
      });
    };

    const openSearch = () => {
      closeDrawer();
      renderResults('');
      dialog.hidden = false;
      document.documentElement.classList.add('search-open');
      requestAnimationFrame(() => input.focus());
    };

    const closeSearch = () => {
      dialog.hidden = true;
      document.documentElement.classList.remove('search-open');
    };

    searchButton.addEventListener('click', openSearch);
    input.addEventListener('input', () => renderResults(input.value));
    dialog.querySelectorAll('[data-search-close]').forEach((el) => el.addEventListener('click', closeSearch));

    document.addEventListener('keydown', (event) => {
      const target = event.target;
      const isTyping = target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target?.isContentEditable;

      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        dialog.hidden ? openSearch() : closeSearch();
      } else if (event.key === '/' && !isTyping && dialog.hidden) {
        event.preventDefault();
        openSearch();
      } else if (event.key === 'Escape') {
        if (!dialog.hidden) closeSearch();
        if (!drawer.hidden) closeDrawer();
      }
    });
  }

  const article = document.querySelector('.article');
  const shell = document.querySelector('.shell');

  if (article && shell) {
    // Defensive cleanup for accidental escaped newline artifacts in static HTML.
    const walker = document.createTreeWalker(article, NodeFilter.SHOW_TEXT);
    const textNodes = [];

    while (walker.nextNode()) textNodes.push(walker.currentNode);

    textNodes.forEach((node) => {
      if (node.nodeValue?.includes('\\n')) {
        node.nodeValue = node.nodeValue.replace(/\\n/g, '');
      }
    });

    const headings = [...article.querySelectorAll('h2, h3')];

    headings.forEach((heading) => {
      if (!heading.id) {
        heading.id = heading.textContent
          .trim()
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-');
      }

      if (!heading.querySelector(':scope > a.heading-anchor')) {
        const anchor = document.createElement('a');
        anchor.className = 'heading-anchor';
        anchor.href = '#' + heading.id;
        anchor.textContent = heading.textContent;

        while (heading.firstChild) heading.removeChild(heading.firstChild);
        heading.appendChild(anchor);
      }
    });

    if (headings.length) {
      const toc = document.createElement('aside');
      toc.className = 'toc-panel';
      toc.setAttribute('aria-label', 'On this page');
      toc.innerHTML = '<div class="toc-title">On this page</div><nav>' + headings.map((heading) =>
        '<a class="toc-link ' + (heading.tagName === 'H3' ? 'toc-h3' : '') + '" href="#' + heading.id + '">' +
        heading.textContent +
        '</a>'
      ).join('') + '</nav>';
      shell.appendChild(toc);

      if ('IntersectionObserver' in window) {
        const links = new Map([...toc.querySelectorAll('.toc-link')].map((link) => [link.hash.slice(1), link]));
        const observer = new IntersectionObserver((entries) => {
          const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

          if (!visible.length) return;

          toc.querySelectorAll('.toc-link').forEach((link) => link.classList.remove('active'));
          links.get(visible[0].target.id)?.classList.add('active');
        }, { rootMargin: '-96px 0px -72% 0px', threshold: 0 });

        headings.forEach((heading) => observer.observe(heading));
      }
    }

    article.querySelectorAll('pre').forEach((pre) => {
      if (pre.parentElement?.classList.contains('code-block')) return;

      const wrapper = document.createElement('div');
      wrapper.className = 'code-block';
      pre.parentNode.insertBefore(wrapper, pre);
      wrapper.appendChild(pre);

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'copy-code';
      button.textContent = 'Copy';
      button.setAttribute('aria-label', 'Copy code');

      button.addEventListener('click', async () => {
        const code = pre.querySelector('code')?.innerText || pre.innerText;

        try {
          await navigator.clipboard.writeText(code);
          button.textContent = 'Copied';
          setTimeout(() => { button.textContent = 'Copy'; }, 1200);
        } catch {
          button.textContent = 'Select & copy';
        }
      });

      wrapper.appendChild(button);
    });

    const currentIndex = pages.findIndex((page) => page.id === current);
    const footer = article.querySelector('.footer');

    if (currentIndex >= 0 && footer) {
      const previous = pages[currentIndex - 1];
      const next = pages[currentIndex + 1];

      if (previous || next) {
        const pager = document.createElement('nav');
        pager.className = 'page-nav';
        pager.setAttribute('aria-label', 'Documentation pages');
        pager.innerHTML =
          (previous
            ? '<a class="page-nav-item prev" href="' + hrefFor(previous.href) + '"><span>Previous</span><strong>← ' + previous.title + '</strong></a>'
            : '<span></span>') +
          (next
            ? '<a class="page-nav-item next" href="' + hrefFor(next.href) + '"><span>Next</span><strong>' + next.title + ' →</strong></a>'
            : '<span></span>');

        footer.parentNode.insertBefore(pager, footer);
      }
    }
  }
})();

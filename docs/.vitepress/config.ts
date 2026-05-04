import { defineConfig } from 'vitepress'

function guideSidebar() {
  return [
    {
      text: 'Guide',
      items: [
        { text: 'Overview', link: '/guide/' },
        { text: 'Basic Setup', link: '/guide/basic-setup' },
        { text: 'Queueing Jobs', link: '/guide/queueing-jobs' },
        { text: 'Custom Tasks', link: '/guide/custom-tasks' },
      ],
    },
    {
      text: 'Operating',
      items: [
        { text: 'Configuration', link: '/guide/configuration' },
        { text: 'Cron Setup', link: '/guide/cron' },
        { text: 'Multi-Connection', link: '/guide/multi-connection' },
        { text: 'Real-Time Progress', link: '/guide/realtime-progress' },
      ],
    },
    {
      text: 'Email',
      items: [
        { text: 'Mailing', link: '/guide/mailing' },
      ],
    },
    {
      text: 'Admin',
      items: [
        { text: 'Dashboard', link: '/admin/' },
        { text: 'Statistics', link: '/admin/statistics' },
      ],
    },
  ]
}

export default defineConfig({
  title: 'cakephp-queue',
  description: 'Reliable database-backed job queue for CakePHP with admin dashboard, retries, scheduling, and built-in tasks.',
  base: '/cakephp-queue/',
  lastUpdated: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-queue/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-queue/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/(guide|admin)/' },
      { text: 'Tasks', link: '/tasks/', activeMatch: '/tasks/' },
      { text: 'Reference', link: '/reference/', activeMatch: '/reference/' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-queue' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-queue' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-queue/issues' },
        ],
      },
    ],
    sidebar: {
      '/guide/': guideSidebar(),
      '/admin/': guideSidebar(),
      '/tasks/': [
        {
          text: 'Built-in Tasks',
          items: [
            { text: 'Overview', link: '/tasks/' },
            { text: 'Execute', link: '/tasks/execute' },
            { text: 'Email', link: '/tasks/email' },
            { text: 'Mailer', link: '/tasks/mailer' },
          ],
        },
      ],
      '/reference/': [
        {
          text: 'Reference',
          items: [
            { text: 'Overview', link: '/reference/' },
            { text: 'Tips', link: '/reference/tips' },
            { text: 'Misc', link: '/reference/misc' },
            { text: 'Limitations', link: '/reference/limitations' },
            { text: 'Upgrading', link: '/reference/upgrading' },
          ],
        },
      ],
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-queue' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-queue/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})

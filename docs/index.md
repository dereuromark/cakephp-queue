---
layout: home

hero:
  name: cakephp-queue
  text: Background Queue for CakePHP
  tagline: Reliable database-backed job queue with admin dashboard, retries, scheduling, multi-connection support, and built-in tasks for shell commands and email.
  image:
    src: /logo.svg
    alt: cakephp-queue
  actions:
    - theme: brand
      text: Get Started
      link: /guide/
    - theme: alt
      text: Built-in Tasks
      link: /tasks/
    - theme: alt
      text: Admin Dashboard
      link: /admin/
    - theme: alt
      text: View on GitHub
      link: https://github.com/dereuromark/cakephp-queue

features:
  - icon: 💾
    title: Database-Backed
    details: No extra infrastructure — uses your existing database. Survives reboots, deploys, and worker crashes.
  - icon: ⚙️
    title: Admin Dashboard
    details: Self-contained Bootstrap 5 admin UI for monitoring jobs, workers, and processes — with auth gate, multi-connection switcher, and stats.
  - icon: 🔁
    title: Retries and Scheduling
    details: Configurable retries, priorities, notBefore scheduling, unique reference checks, and per-task timeout overrides.
  - icon: 🧩
    title: Built-in Tasks
    details: Shipped Email, Mailer, and Execute tasks cover the common cases — drop in and queue.
  - icon: 🌐
    title: Multi-Connection
    details: Run separate queues against multiple database connections, each with its own worker pool.
  - icon: 📡
    title: Real-Time Progress
    details: Push live progress to the browser via Mercure / Server-Sent Events for long-running jobs.
---

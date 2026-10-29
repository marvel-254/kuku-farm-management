# 🤝 Contributing Guidelines

This document explains how to contribute to the KUKU Farm Management System and what is needed for development work.

---

## How to Contribute

1. Fork or clone the repository.
2. Create a feature branch (`git checkout -b feature/your-feature`).
3. Make changes with clear, tested code.
4. Commit with descriptive messages (`git commit -m 'Add feature: description'`).
5. Push to your branch and open a Pull Request.

---

## Development Setup

```bash
npm install
cp .env.example .env
php database/create_db.php
npm start
```

---

## Code Style

- Use 2 spaces for indentation (JavaScript and PHP).
- Use descriptive variable and function names.
- Add comments for complex logic (e.g., `sanitize()` function in `server.js`).
- Keep routes organized by feature (`chickens`, `inventory`, `sales`).
- Use `requireAuth` and `requireRole` middleware for protected endpoints.

---

## Testing Before Contribution

- [ ] `npm start` runs without errors
- [ ] Web server starts (`./start.sh` or `node server.js`)
- [ ] Database connects and tables exist
- [ ] Login works with default credentials
- [ ] New feature works on desktop (`npm start`) and web (`public/*.php`)
- [ ] Audit logs record actions correctly

---

## Areas for Contribution

- **Desktop UI**: `desktop/` renderer files, animations (`ANIMATIONS.md`)
- **Web Interfaces**: `public/*.php` and `public/*.html`
- **Backend**: `server.js`, `public/api.php`, database migrations
- **Documentation**: This `docs/` directory needs updates as features grow
- **Security**: Improve `sanitize()`, session settings, role checks
- **Mobile / Responsive**: Improve CSS in `public/assets/` for tablet/mobile

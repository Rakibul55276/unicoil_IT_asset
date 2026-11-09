# Project Handover Checklist

## Source and documentation

- [ ] Repository URL and default branch supplied.
- [ ] Release/tag identifies the delivered version.
- [ ] README and all linked documents reviewed.
- [ ] No local-only or legacy folder is described as the production source.
- [ ] Disabled/demo controllers are documented.
- [ ] Build/install artifacts are attached to a release, not mixed with source.

## Credentials and security

- [ ] `config.php` created privately on each environment.
- [ ] Database password changed for production.
- [ ] Mail/API/Agent tokens rotated and stored outside Git.
- [ ] SaaS setup/encryption keys are unique and private if enabled.
- [ ] No private key, employee export, customer database dump, upload, or log is committed.
- [ ] HTTPS, `.htaccess`, upload protection, and correct permissions verified.

## Database and files

- [ ] Approved schema and migration list supplied.
- [ ] Production backup completed and restore-tested.
- [ ] Upload/evidence backup completed.
- [ ] Migration status is clean.
- [ ] Indexes for large-scale lists are installed.

## Functional acceptance

- [ ] Login/logout/profile and role navigation.
- [ ] Employee, department, category, location, unit, vendor, and user administration.
- [ ] Receipt and batch asset creation.
- [ ] In-stock search/filter/edit/condition audit.
- [ ] Manual assignment, combined PDF, and custody listing.
- [ ] Manual return, per-asset condition, combined PDF, and stock restoration.
- [ ] Request/approval/issue/acceptance when enabled.
- [ ] Return request and scrap workflow when enabled.
- [ ] QR, single label, and bulk label printing.
- [ ] Assigned, return, in-stock, expiry, and audit reports/exports.
- [ ] Email notification when configured.
- [ ] Agent health/sync when enabled.
- [ ] SaaS company/voucher isolation when enabled.

## Operational ownership

- [ ] Named system owner and backup administrator.
- [ ] Support escalation contacts.
- [ ] Backup and retention schedule.
- [ ] Password/token rotation schedule.
- [ ] Change, test, approval, and release process.
- [ ] Training completed for Administrator, Issuer, approvers, HR, and employees.

# MediVault
An inventory and ordering system for a local pharmacy or university health center.

## Database schema

The project schema is defined in `/home/runner/work/MediVault/MediVault/schema.sql`.

It includes:
- admins (with `shop_banner_photo_url`)
- users
- medicines (single consolidated inventory + catalog table)
- drug conflicts
- order statuses
- orders and order items
- prescription reviews for restricted medicine orders
- activity logs linked to prescription review actions

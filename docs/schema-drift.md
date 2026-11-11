# Database schema drift — proposed cleanup (NOT APPLIED)

Status: **documented only**. No schema or data changes were applied.

> Exception: the narrow `DoctrineMigrations\Version20261111000000` migration
> adds the administrator/staff account columns `admins.email`, `admins.role`,
> `admins.status` and `admins.updated_at`, plus the unique email index. It does
> not touch the remaining drift described below.

`php bin/console doctrine:schema:validate` reports the Doctrine mapping as valid,
but the live database is not in sync with the mapping. This file records the
drift and the exact SQL that would align it so the change can be reviewed and
approved before anything is executed.

## Duplicate foreign keys on `bookings`

The live table has two foreign keys for each of `customer_id` and `package_id`:

| Constraint | Column | References | ON DELETE |
| --- | --- | --- | --- |
| `bookings_ibfk_1` | `customer_id` | `customers.customer_id` | CASCADE |
| `bookings_ibfk_2` | `package_id` | `packages.package_id` | CASCADE |
| `fk_bookings_admin` | `admin_id` | `admins.admin_id` | SET NULL |
| `fk_bookings_customer` | `customer_id` | `customers.customer_id` | CASCADE |
| `fk_bookings_package` | `package_id` | `packages.package_id` | CASCADE |

`bookings_ibfk_1`/`bookings_ibfk_2` are legacy duplicates of the
Doctrine-named `fk_bookings_customer`/`fk_bookings_package`. They have identical
rules and share the same indexes, so they are redundant.

## SQL proposed by Doctrine

Output of `php bin/console doctrine:schema:update --dump-sql` (not applied):

```sql
ALTER TABLE admins CHANGE created_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE blockout_dates CHANGE created_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE blockout_dates RENAME INDEX fk_blockout_admin TO IDX_7D750CB9642B8210;
ALTER TABLE bookings DROP FOREIGN KEY `fk_bookings_customer`;
ALTER TABLE bookings DROP FOREIGN KEY `fk_bookings_package`;
ALTER TABLE bookings CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE booking_status booking_status ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending', CHANGE created_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE bookings RENAME INDEX fk_bookings_customer TO IDX_7A853C359395C3F3;
ALTER TABLE bookings RENAME INDEX fk_bookings_admin TO IDX_7A853C35642B8210;
ALTER TABLE bookings RENAME INDEX fk_bookings_package TO IDX_7A853C35F44CABFF;
ALTER TABLE customers CHANGE created_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE customers RENAME INDEX email TO UNIQ_62534E21E7927C74;
ALTER TABLE packages CHANGE description description LONGTEXT DEFAULT NULL, CHANGE inclusions inclusions LONGTEXT DEFAULT NULL, CHANGE status status ENUM('active','inactive') DEFAULT 'active', CHANGE created_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
```

Doctrine does not see the legacy `bookings_ibfk_*` names, so its dump drops the
Doctrine-named constraints instead of the true duplicates. Do **not** run that
dump verbatim if the goal is to remove duplicates.

## Recommended cleanup order (still not applied)

1. Back up the database and test on a copy first.
2. Drop the two duplicate legacy constraints, keeping the Doctrine-named ones:

```sql
ALTER TABLE bookings DROP FOREIGN KEY `bookings_ibfk_1`;
ALTER TABLE bookings DROP FOREIGN KEY `bookings_ibfk_2`;
```

3. Re-run `doctrine:schema:update --dump-sql` on the copy. If the only remaining
   differences are index names and column type/default declarations, apply the
   renames/type alignment separately after reviewing them.
4. Run `doctrine:schema:validate` again and confirm the app's booking,
   cancellation and customer-deletion flows still work.

## Why this was not applied automatically

- Foreign-key and column-type changes are schema changes; approval was not given.
- The live tables contain real bookings and customer records.
- The application currently works with the duplicate constraints, so the risk
  of an unapproved change is higher than the benefit of removing them now.

## Verification commands

```bash
docker exec philo_php php bin/console doctrine:schema:validate
docker exec philo_php php bin/console doctrine:schema:update --dump-sql
docker exec philo_php php bin/console doctrine:query:sql \
  "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
   FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA='philostudio' AND TABLE_NAME='bookings'
     AND REFERENCED_TABLE_NAME IS NOT NULL
   ORDER BY CONSTRAINT_NAME"
```

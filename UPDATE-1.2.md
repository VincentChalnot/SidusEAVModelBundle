## This is just a memo

Migrating the new column in eav_data
```sql
UPDATE `eav_value` SET family_code = (SELECT d.family_code FROM eav_data AS d WHERE d.id = data_id)
```

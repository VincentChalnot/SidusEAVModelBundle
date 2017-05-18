## Performances

The EAV model can store several millions of entities with hundreds of attributes before performances starts to degrades.
The major limit will be on the values table that will potentially reach billions of entries:

If you have 1 000 000 entities in your database, your Data table will have 1 000 000 entries.
If each family (data type) has, let's say, 100 attributes in average and about 70% of them will be filled (30% left
blank), the Value table will have around : 1 000 000 * 100 * 0.7 = 70 000 000 entries.

With this amount of data, ordering data by one of their EAV attribute can potentially take a huge load on your MySQL
engine, especially if you have only one family.

The key concept here will be the amount of Data you will try to manipulate in a single query. For example if you have
several millions of data in your database but only 200 000 data in a given family, any query on this data set will be
really fast compared to a query on the whole set.

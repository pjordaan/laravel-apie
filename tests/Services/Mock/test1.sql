SELECT enum_column AS `id`, MAX(value) AS `size`, count(status.id) AS `count` FROM `status` GROUP BY enum_column

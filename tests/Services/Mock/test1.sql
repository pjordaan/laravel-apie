SELECT identifier AS `id`, MAX(value) AS `size`, count(status.id) AS `count` FROM `status` GROUP BY identifier

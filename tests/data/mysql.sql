DROP TABLE IF EXISTS `product`;

CREATE TABLE `product`
(
	`id`            int unsigned NOT NULL AUTO_INCREMENT,
	`name`          varchar(255) NOT NULL,
	`category`      varchar(64) NULL,
	`created_at`    date NOT NULL,
	`created_at_dt` datetime NOT NULL,
	`created_at_ts` int unsigned NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (1, 'Phone', 'mobile', '2026-01-01', '2026-01-01 10:15:00', 1767225600);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (2, 'Tablet', 'mobile', '2026-01-02', '2026-01-02 10:15:00', 1767312000);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (3, 'Laptop', 'computer', '2026-01-03', '2026-01-03 10:15:00', 1767398400);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (4, 'Monitor', 'computer', '2026-01-04', '2026-01-04 10:15:00', 1767484800);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (5, 'Keyboard', 'accessory', '2026-01-05', '2026-01-05 10:15:00', 1767571200);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (6, 'Mouse', 'accessory', '2026-01-06', '2026-01-06 10:15:00', 1767657600);
INSERT INTO `product` (`id`, `name`, `category`, `created_at`, `created_at_dt`, `created_at_ts`) VALUES (7, 'Headphones', 'accessory', '2026-01-07', '2026-01-07 10:15:00', 1767744000);

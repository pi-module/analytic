CREATE TABLE `{user}` (
  `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_images` TEXT,
  `description`     TEXT,
  PRIMARY KEY (`id`)
);

CREATE TABLE `{comment}` (
  `id`          INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT,
  `uid`         INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `by`          INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `time_create` INT(10) UNSIGNED NOT NULL  DEFAULT '0',
  `note`        TEXT,
  PRIMARY KEY (`id`)
);
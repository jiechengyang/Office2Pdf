CREATE TABLE `office_pdf`.`data_info` (
  `id` INT(11) UNSIGNED NOT NULL,
  `file_id` INT(10) NOT NULL DEFAULT 0 COMMENT '关联数据表id',
  `app_key` CHAR(16) NOT NULL DEFAULT '' COMMENT '应用key',
  `file_path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  `created_at` INT(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`)
ENGINE = MyISAM
COMMENT = '文档转换数据表';
ALTER TABLE `office_pdf`.`data_info` 
CHANGE COLUMN `file_path` `pdf_path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '文件路径' ,
ADD COLUMN `file_name` VARCHAR(200) NULL COMMENT '待转换文件名' AFTER `app_key`;

ALTER TABLE `office_pdf`.`data_info` 
CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ;

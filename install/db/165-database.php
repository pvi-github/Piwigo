<?php
// +-----------------------------------------------------------------------+
// | This file is part of Piwigo.                                          |
// |                                                                       |
// | For copyright and license information, please view the COPYING.txt    |
// | file that was distributed with this source code.                      |
// +-----------------------------------------------------------------------+

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

$upgrade_description = 'Add status_privilege table for the new privilege system.';

include_once(PHPWG_ROOT_PATH.'include/constants.php');

// +-----------------------------------------------------------------------+
// |                            Upgrade content                            |
// +-----------------------------------------------------------------------+

pwg_query('
CREATE TABLE IF NOT EXISTS `'.STATUS_PRIVILEGE_TABLE.'` (
  `sp_id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(100) NOT NULL,
  `privilege` varchar(255) NOT NULL,
  `context` varchar(100) NOT NULL,
  `target_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`sp_id`),
  UNIQUE KEY `status_privilege_UN` (`status`,`privilege`,`context`,`target_id`),
  KEY `status_privilege_status_IDX` (`status`,`privilege`),
  KEY `status_privilege_context_IDX` (`context`),
  KEY `status_privilege_target_id_IDX` (`target_id`)
) ENGINE=InnoDB AUTO_INCREMENT DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
;');

pwg_query("
ALTER TABLE `".USER_INFOS_TABLE."` MODIFY COLUMN status VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'guest' NOT NULL;
;");

$default_statuses['webmaster'][]='access_front';
$default_statuses['webmaster'][]='add_photos';
$default_statuses['webmaster'][]='do_maintenance';
$default_statuses['webmaster'][]='extend_for_templates';
$default_statuses['webmaster'][]='manage_albums';
$default_statuses['webmaster'][]='manage_batch_global';
$default_statuses['webmaster'][]='manage_batch_unit';
$default_statuses['webmaster'][]='manage_categories';
$default_statuses['webmaster'][]='manage_cat_options';
$default_statuses['webmaster'][]='manage_cat_perm';
$default_statuses['webmaster'][]='manage_comments';
$default_statuses['webmaster'][]='manage_configuration';
$default_statuses['webmaster'][]='manage_groups';
$default_statuses['webmaster'][]='manage_menus';
$default_statuses['webmaster'][]='manage_picture_coi';
$default_statuses['webmaster'][]='manage_picture_formats';
$default_statuses['webmaster'][]='manage_plugins';
$default_statuses['webmaster'][]='manage_ratings';
$default_statuses['webmaster'][]='manage_site';
$default_statuses['webmaster'][]='manage_sync';
$default_statuses['webmaster'][]='manage_themes';
$default_statuses['webmaster'][]='manage_updates';
$default_statuses['webmaster'][]='manage_users';
$default_statuses['webmaster'][]='modify_photo';
$default_statuses['webmaster'][]='notify_albums';
$default_statuses['webmaster'][]='notify_users';
$default_statuses['webmaster'][]='rank_images';
$default_statuses['webmaster'][]='see_admin_help';
$default_statuses['webmaster'][]='see_history_stats';
$default_statuses['webmaster'][]='see_user_activity';

$default_statuses['admin'][]='access_front';
$default_statuses['admin'][]='add_photos';
$default_statuses['admin'][]='manage_albums';
$default_statuses['admin'][]='manage_batch_global';
$default_statuses['admin'][]='manage_batch_unit';
$default_statuses['admin'][]='manage_categories';
$default_statuses['admin'][]='manage_picture_coi';
$default_statuses['admin'][]='manage_picture_formats';
$default_statuses['admin'][]='manage_ratings';
$default_statuses['admin'][]='modify_photo';
$default_statuses['admin'][]='notify_albums';
$default_statuses['admin'][]='rank_images';
$default_statuses['admin'][]='see_admin_help';
$default_statuses['admin'][]='see_history_stats';
$default_statuses['admin'][]='see_user_activity';

$default_statuses['guest'][]='access_front';

foreach ( $default_statuses as $default_status => $default_privileges )
{
  foreach ( $default_privileges as $default_privilege )
  {
    $query = "
    INSERT IGNORE INTO ".STATUS_PRIVILEGE_TABLE." (status,privilege,context,target_id) VALUES ('".$default_status."','".$default_privilege."','global', 0);
    ";
    echo $query."\n";
    pwg_query($query);
  }
}

// +-----------------------------------------------------------------------+
// |                            End upgrade                                |
// +-----------------------------------------------------------------------+


echo "\n".$upgrade_description."\n";

?>

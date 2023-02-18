<?php
// +-----------------------------------------------------------------------+
// | This file is part of Piwigo.                                          |
// |                                                                       |
// | For copyright and license information, please view the COPYING.txt    |
// | file that was distributed with this source code.                      |
// +-----------------------------------------------------------------------+

// +-----------------------------------------------------------------------+
// | Basic constants and includes                                          |
// +-----------------------------------------------------------------------+

define('PHPWG_ROOT_PATH','./');
define('IN_ADMIN', true);

include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
include_once(PHPWG_ROOT_PATH.'admin/include/functions_plugins.inc.php');
include_once(PHPWG_ROOT_PATH.'admin/include/add_core_tabs.inc.php');

trigger_notify('loc_begin_admin');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

// PVIACL TODO : check if that is correct
if(!(user_can('add_photos')
  || user_can('manage_albums')
  || user_can('manage_batch_global')
  || user_can('manage_batch_unit')
  || user_can('manage_categories')
  || user_can('manage_picture_coi')
  || user_can('manage_picture_formats')
  || user_can('manage_ratings')
  || user_can('modify_photo')
  || user_can('notify_albums')
  || user_can('rank_images')
  || user_can('see_history_stats')
  || user_can('see_user_activity')
  || user_can('do_maintenance')
  || user_can('extend_for_templates')
  || user_can('manage_cat_options')
  || user_can('manage_cat_perm')
  || user_can('manage_comments')
  || user_can('manage_configuration')
  || user_can('manage_groups')
  || user_can('manage_menus')
  || user_can('manage_plugins')
  || user_can('manage_site')
  || user_can('manage_sync')
  || user_can('manage_themes')
  || user_can('manage_updates')
  || user_can('manage_users')
  || user_can('notify_users'))
)
{
  access_denied();
}
//check_status(ACCESS_ADMINISTRATOR);

check_input_parameter('page', $_GET, false, '/^[a-zA-Z\d_-]+$/');
check_input_parameter('section', $_GET, false, '/^[a-z]+[a-z_\/-]*(\.php)?$/i');

// +-----------------------------------------------------------------------+
// | Filesystem checks                                                     |
// +-----------------------------------------------------------------------+

if ($conf['fs_quick_check_period'] > 0)
{
  $perform_fsqc = false;
  if (isset($conf['fs_quick_check_last_check']))
  {
    if (strtotime($conf['fs_quick_check_last_check']) < strtotime($conf['fs_quick_check_period'].' seconds ago'))
    {
      $perform_fsqc = true;
    }
  }
  else
  {
    $perform_fsqc = true;
  }

  if ($perform_fsqc)
  {
    fs_quick_check();
  }
}

// +-----------------------------------------------------------------------+
// | Direct actions                                                        |
// +-----------------------------------------------------------------------+

// save plugins_new display order (AJAX action)
if (isset($_GET['plugins_new_order']))
{
  pwg_set_session_var('plugins_new_order', $_GET['plugins_new_order']);
  exit;
}

// theme changer
if (isset($_GET['change_theme']))
{
  $admin_themes = array('roma', 'clear');
  $admin_theme_array = array(userprefs_get_param('admin_theme', 'clear'));
  $result = array_diff(
      $admin_themes,
      $admin_theme_array
    );

  $new_admin_theme = array_pop(
      $result
    );

  userprefs_update_param('admin_theme', $new_admin_theme);

  $url_params = array();
  foreach (array('page', 'tab', 'section') as $url_param)
  {
    if (isset($_GET[$url_param]))
    {
      $url_params[] = $url_param.'='.$_GET[$url_param];
    }
  }
  
  $redirect_url = 'admin.php';
  if (count($url_params) > 0)
  {
    $redirect_url.= '?'.implode('&amp;', $url_params);
  }

  redirect($redirect_url);
}

// +-----------------------------------------------------------------------+
// | Synchronize user informations                                         |
// +-----------------------------------------------------------------------+

// sync_user() is only useful when external authentication is activated
if ($conf['external_authentification'])
{
  sync_users();
}

// +-----------------------------------------------------------------------+
// | Variables init                                                        |
// +-----------------------------------------------------------------------+

$change_theme_url = PHPWG_ROOT_PATH.'admin.php?';
$test_get = $_GET;
unset($test_get['page']);
unset($test_get['section']);
unset($test_get['tag']);
if (count($test_get) == 0 and !empty($_SERVER['QUERY_STRING']))
{
  $change_theme_url.= str_replace('&', '&amp;', $_SERVER['QUERY_STRING']).'&amp;';
}
$change_theme_url.= 'change_theme=1';

// ?page=plugin-community-pendings is an clean alias of
// ?page=plugin&section=community/admin.php&tab=pendings
if (isset($_GET['page']) and preg_match('/^plugin-([^-]*)(?:-(.*))?$/', $_GET['page'], $matches))
{
  $_GET['page'] = 'plugin';

  if (preg_match('/^piwigo_(videojs|openstreetmap)$/', $matches[1]))
  {
    $matches[1] = str_replace('_', '-', $matches[1]);
  }

  $_GET['section'] = $matches[1].'/admin.php';
  if (isset($matches[2]))
  {
    $_GET['tab'] = $matches[2];
  }
}

// ?page=album-134-properties is an clean alias of
// ?page=album&cat_id=134&tab=properties
if (isset($_GET['page']) and preg_match('/^album-(\d+)(?:-(.*))?$/', $_GET['page'], $matches))
{
  $_GET['page'] = 'album';
  $_GET['cat_id'] = $matches[1];
  if (isset($matches[2]))
  {
    $_GET['tab'] = $matches[2];
  }
}

// ?page=photo-1234-properties is an clean alias of
// ?page=photo&image_id=1234&tab=properties
if (isset($_GET['page']) and preg_match('/^photo-(\d+)(?:-(.*))?$/', $_GET['page'], $matches))
{
  $_GET['page'] = 'photo';
  $_GET['image_id'] = $matches[1];
  if (isset($matches[2]))
  {
    $_GET['tab'] = $matches[2];
  }
}

if (isset($_GET['page'])
    and preg_match('/^[a-z_]*$/', $_GET['page'])
    and is_file(PHPWG_ROOT_PATH.'admin/'.$_GET['page'].'.php'))
{
  $page['page'] = $_GET['page'];
}
else
{
  $page['page'] = 'intro';
}

$link_start = PHPWG_ROOT_PATH.'admin.php?page=';
$conf_link = $link_start.'configuration&amp;section=';

// $_GET['tab'] is often used to perform and
// include('admin_page_'.$_GET['tab'].'.php') : we need to protect it to
// avoid any unexpected file inclusion
check_input_parameter('tab', $_GET, false, '/^[a-zA-Z\d_-]+$/');

// +-----------------------------------------------------------------------+
// | Template init                                                         |
// +-----------------------------------------------------------------------+

$title = l10n('Piwigo Administration'); // for include/page_header.php
$page['page_banner'] = '<h1>'.l10n('Piwigo Administration').'</h1>';
$page['body_id'] = 'theAdminPage';

$template->set_filenames(array('admin' => 'admin.tpl'));

$menu_links= array(
    'USERNAME' => $user['username'],
    'U_SITE_MANAGER'=> $link_start.'site_manager',
    'U_FAQ'=> $link_start.'help',
    'U_SITES'=> $link_start.'remote_site',
    'U_CONFIG_DISPLAY'=> $conf_link.'default',
    'U_CONFIG_EXTENTS'=> $link_start.'extend_for_templates',
    'U_CONFIG_LANGUAGES' => $link_start.'languages',
    'U_CATEGORIES'=> $link_start.'cat_list',
    'U_ALBUMS'=> $link_start.'albums',
    'U_CAT_SEARCH'=> $link_start.'cat_search',
    'U_RATING'=> $link_start.'rating',
    'U_RECENT_SET'=> $link_start.'batch_manager&amp;filter=prefilter-last_import',
    'U_BATCH'=> $link_start.'batch_manager',
    'U_TAGS'=> $link_start.'tags',
    'U_RETURN'=> get_gallery_home_url(),
    'U_ADMIN'=> PHPWG_ROOT_PATH.'admin.php',
    'U_LOGOUT'=> PHPWG_ROOT_PATH.'index.php?act=logout',
    'U_ADD_PHOTOS' => $link_start.'photos_add',
    'U_CHANGE_THEME' => $change_theme_url,
    'ADMIN_PAGE_TITLE' => 'Piwigo Administration Page',
    'ADMIN_PAGE_OBJECT_ID' => '',
    'U_SHOW_TEMPLATE_TAB' => $conf['show_template_in_side_menu'],
    'SHOW_RATING' => $conf['rate'],
  );

if (user_can('manage_sync'))
  $menu_links['ENABLE_SYNCHRONIZATION'] = $conf['enable_synchronization'];

if (user_can('manage_cat_options'))
  $menu_links['U_CAT_OPTIONS'] = $link_start.'cat_options';

if (user_can('manage_users'))
  $menu_links['U_USERS'] = $link_start.'user_list';

if (user_can('manage_groups'))
  $menu_links['U_GROUPS'] = $link_start.'group_list';

if (user_can('notify_users'))
  $menu_links['U_NOTIFICATION_BY_MAIL'] = $link_start.'notification_by_mail';

if (user_can('manage_plugins'))
  $menu_links['U_PLUGINS'] = $link_start.'plugins';

if (user_can('manage_configuration'))
  $menu_links['U_CONFIG_GENERAL'] = $link_start.'configuration';

if (user_can('manage_themes'))
  $menu_links['U_CONFIG_THEMES'] = $link_start.'themes';

if (user_can('manage_menus'))
  $menu_links['U_CONFIG_MENUBAR'] = $link_start.'menubar';

if (user_can('manage_sync'))
  $menu_links['U_CAT_UPDATE'] = $link_start.'site_update&amp;site=1';

if (user_can('see_history_stats'))
  $menu_links['U_HISTORY_STAT'] = $link_start.'stats&amp;year='.date('Y').'&amp;month='.date('n');

if (user_can('do_maintenance'))
  $menu_links['U_MAINTENANCE'] = $link_start.'maintenance';


$template->assign($menu_links);

// PVIACL TODO : Rework this
if ($conf['enable_core_update'])
{
  if (user_can('manage_updates'))
    $template->assign('U_UPDATES', $link_start.'updates');
}

// PVIACL TODO : Rework this
if ($conf['activate_comments'])
{
  if (user_can('manage_comments'))
    $template->assign('U_COMMENTS', $link_start.'comments');
  
  // pending comments
  $query = '
SELECT COUNT(*)
  FROM '.COMMENTS_TABLE.'
  WHERE validated=\'false\'
;';
  list($nb_comments) = pwg_db_fetch_row(pwg_query($query));

  if ($nb_comments > 0)
  {
    $template->assign('NB_PENDING_COMMENTS', $nb_comments);
    $page['nb_pending_comments'] = $nb_comments;
  }
}

// any photo in the caddie?
$query = '
SELECT COUNT(*)
  FROM '.CADDIE_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
list($nb_photos_in_caddie) = pwg_db_fetch_row(pwg_query($query));

if ($nb_photos_in_caddie > 0)
{
  $template->assign(
    array(
      'NB_PHOTOS_IN_CADDIE' => $nb_photos_in_caddie,
      'U_CADDIE' => $link_start.'batch_manager&amp;filter=prefilter-caddie',
      )
    );
} else {
  $template->assign(
    array(
      'NB_PHOTOS_IN_CADDIE' => 0,
      'U_CADDIE' => '',
      )
    );
}

// any photos with no md5sum ?
if (in_array($page['page'], array('site_update', 'batch_manager')))
{
  $nb_no_md5sum = count(get_photos_no_md5sum());

  if ($nb_no_md5sum > 0)
  {
    $page['no_md5sum_number'] = $nb_no_md5sum;
  }
}

// only calculate number of orphans on all pages if the number of images is "not huge"
$page['nb_orphans'] = 0;

list($page['nb_photos_total']) = pwg_db_fetch_row(pwg_query('SELECT COUNT(*) FROM '.IMAGES_TABLE));
if ($page['nb_photos_total'] < 100000) // 100k is already a big gallery
{
  $page['nb_orphans'] = count_orphans();
}

$template->assign(
  array(
    'NB_ORPHANS' => $page['nb_orphans'],
    'U_ORPHANS' => $link_start.'batch_manager&amp;filter=prefilter-no_album',
    )
  );

// +-----------------------------------------------------------------------+
// | Refresh permissions                                                   |
// +-----------------------------------------------------------------------+

// Only for pages witch change permissions
if (
    in_array($page['page'],
      array(
        'site_manager', // delete site
        'site_update',  // ?only POST
      )
    )
    or ( !empty($_POST) and in_array($page['page'],
        array(
          'album',        // public/private; lock/unlock, permissions
          'albums',
          'cat_options',  // public/private; lock/unlock
          'user_list',    // group assoc; user level
          'user_perm',
        )
      )
    )
  )
{
  invalidate_user_cache();
}

// +-----------------------------------------------------------------------+
// | Include specific page                                                 |
// +-----------------------------------------------------------------------+

trigger_notify('loc_begin_admin_page');
include(PHPWG_ROOT_PATH.'admin/'.$page['page'].'.php');

$template->assign('ACTIVE_MENU', get_active_menu($page['page']));

// +-----------------------------------------------------------------------+
// | Sending html code                                                     |
// +-----------------------------------------------------------------------+

// Add the Piwigo Official menu
$template->assign( 'pwgmenu', pwg_URL() );

include(PHPWG_ROOT_PATH.'include/page_header.php');

trigger_notify('loc_end_admin');

flush_page_messages();

$template->pparse('admin');

include(PHPWG_ROOT_PATH.'include/page_tail.php');
?>

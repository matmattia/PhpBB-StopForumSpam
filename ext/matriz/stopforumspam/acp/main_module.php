<?php
namespace matriz\stopforumspam\acp;

class main_module {
	const API_KEY = ''; // API Key - ex.: const API_KEY = 'abcdef123';
	const IP_BAN = -1; // IP ban length in minutes (0: permanent ban, -1: no IP ban)
	const USERS_PER_PAGE = 15;
	
	/**
	 * Numero degli utenti
	 * @var integer
	 */
	private $num_users;
	
	function main($id, $mode) {
		global $db, $template, $phpbb_container, $phpbb_admin_path, $phpEx;
		$delete_user = request_var('delete_user', 0);
		if (is_numeric($delete_user) && $delete_user > 0) {
			echo json_encode(array('ok' => $this->deleteUser($delete_user) ? 1 : 0));
			exit();
		}
		$this->page_title = 'StopForumSpam';
		$this->tpl_name = 'acp_stopforumspam';
		$p = array();
		$users = array();
		$start = request_var('start', 0);
		$sql_arr = array(
			'SELECT' => 'u.user_id AS USER_ID, u.username AS USERNAME, u.user_email AS USER_EMAIL, u.user_ip AS USER_IP',
			'FROM' => array(
				USERS_TABLE => 'u'
			),
			'WHERE' => 'u.user_type <> 2',
			'ORDER_BY' => 'u.user_regdate DESC'
		);
		$sql = $db->sql_build_query('SELECT', $sql_arr);
		$res = $db->sql_query_limit($sql, self::USERS_PER_PAGE, $start);
		while ($r = $db->sql_fetchrow($res)) {
			$p[] = 'email[]='.rawurlencode($r['USER_EMAIL']);
			$users[] = $r;
			$template->assign_block_vars('users', array_merge($r, array(
				'URL' => append_sid($phpbb_admin_path.'index.'.$phpEx, 'i=users&mode=overview&u='.$r['USER_ID'], false)
			)));
			unset($k, $r);
		}
		$db->sql_freeresult($res);
		unset($res);
		$pagination = $phpbb_container->get('pagination');
		$base_url = append_sid($phpbb_admin_path.'index.'.$phpEx, 'i='.preg_replace('/([^a-z0-9_-]+)/i', '-', $id).'&mode='.$mode, false);
		$pagination->generate_template_pagination($base_url, 'pagination', 'start', $this->countUsers(), self::USERS_PER_PAGE, $start);
		$template->assign_vars(array(
			'SFS_IS' => true,
			'SFS_USERS_JSON' => json_encode($users),
			'SFS_P' => implode('&', $p),
			'SFS_USERS_DELETE_URL' => $base_url,
			'SFS_API_KEY' => is_string(self::API_KEY) && trim(self::API_KEY) != '' ? trim(self::API_KEY) : '',
			'SFS_NUM_USERS' => $this->countUsers(),
			'SFS_PAGE_NUMBER' => $pagination->on_page($this->countUsers(), self::USERS_PER_PAGE, $start)
		));
		unset($users, $p);
	}
	
	/**
	 * Restituisce il numero degli utenti
	 * @return integer
	 */
	private function countUsers() {
		if (!is_int($this->num_users)) {
			global $db;
			$res = $db->sql_query('SELECT COUNT(user_id) AS user_count FROM '.USERS_TABLE);
			$num = $db->sql_fetchfield('user_count');
			$db->sql_freeresult($res);
			unset($res);
			$this->num_users = 0;
			if (is_numeric($num) && $num > 0) {
				$this->num_users = (int)$num;
			}
			unset($num);
		}
		return $this->num_users;
	}
	
	/**
	 * Cancella un utente e banna il suo indirizzo e-mail
	 * @param integer $user_id ID dell'utente
	 * @return boolean
	 */
	private function deleteUser($user_id) {
		global $db, $phpbb_root_path;
		$res = false;
		if (is_numeric($user_id) && $user_id > 0) {
			include($phpbb_root_path.'includes/functions_user.php');
			$user_res = $db->sql_query('SELECT user_email, user_ip FROM '.USERS_TABLE.'  WHERE user_id = '.intval($user_id).' LIMIT 1');
			$user = $db->sql_fetchrow($user_res);
			if ($user && phpbb_validate_email($user['user_email']) === false) {
				user_delete('remove', $user_id);
				user_ban('email', $user['user_email'], 0, false, false, '');
				if (self::IP_BAN >= 0) {
					user_ban('ip', $user['user_ip'], self::IP_BAN, false, false, '');
				}
				$res = true;
			}
		}
		return $res;
	}
}
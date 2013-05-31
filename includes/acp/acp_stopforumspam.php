<?php
class acp_stopforumspam {
	const USERS_PER_PAGE = 15;
	
	/**
	 * Numero degli utenti
	 * @var integer
	 */
	private $num_users;
	
	function main() {
		global $db, $template, $phpbb_admin_path, $phpEx;
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
			'SELECT' => 'u.user_id AS USER_ID, u.username AS USERNAME, u.user_email AS USER_EMAIL',
			'FROM' => array(
				USERS_TABLE => 'u'
			),
			'ORDER_BY' => 'user_regdate DESC'
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
		$template->assign_vars(array(
			'SFS_USERS_JSON' => json_encode($users),
			'SFS_P' => implode('&', $p),
			'SFS_USERS_DELETE_URL' => append_sid($phpbb_admin_path.'index.'.$phpEx, 'i=stopforumspam&mode=index', false),
			'SFS_NUM_USERS' => $this->countUsers(),
			'SFS_PAGE_NUMBER' => on_page($this->countUsers(), self::USERS_PER_PAGE, $start),
			'SFS_PAGINATION' => generate_pagination(append_sid($phpbb_admin_path.'index.'.$phpEx, 'i=stopforumspam&mode=index', false), $this->countUsers(), self::USERS_PER_PAGE, $start)
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
			$db->sql_query('SELECT user_email FROM '.USERS_TABLE.'  WHERE user_id = '.intval($user_id).' LIMIT 1');
			$user_email = $db->sql_fetchfield('user_email');
			if (validate_email($user_email)) {
				user_delete('remove', $user_id);
				user_ban('email', $user_email, 0, false, false, '');
				$res = true;
			}
		}
		return $res;
	}
}
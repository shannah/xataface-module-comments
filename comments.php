<?php
class modules_comments {


	const COMMENTS_TABLE = 'dataface__comments';
	const COMMENTS_SETTINGS_TABLE = 'dataface__comments_settings';
	
	
	
	
	/**
	 * @brief The base URL to the datepicker module.  This will be correct whether it is in the 
	 * application modules directory or the xataface modules directory.
	 *
	 * @see getBaseURL()
	 */
	private $baseURL = null;
	/**
	 * @brief Returns the base URL to this module's directory.  Useful for including
	 * Javascripts and CSS.
	 *
	 */
	public function getBaseURL(){
		if ( !isset($this->baseURL) ){
			$this->baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
		}
		return $this->baseURL;
	}
	
	
	

	public function __construct(){
		
		Dataface_Table::setBasePath(self::COMMENTS_TABLE, dirname(__FILE__));
		$conf =& Dataface_Application::getInstance()->_conf;
		if ( !isset($conf['_allowed_tables']) ){
			$conf['_allowed_tables'] = array();
		}
		$conf['_allowed_tables'][self::COMMENTS_TABLE] = self::COMMENTS_TABLE;
		Dataface_Application::getInstance()->addHeadContent(
			'<script>XATAFACE_MODULES_COMMENTS_URL="'.$this->getBaseURL().'";</script>'
		);
		try {

			self::queryComments('foo');
		} catch (Exception $ex){
			//echo $ex->getMessage();
		}
	}

	
	
	/**
	 * @brief Returns array of SQL statements that can be used to build the 
	 * appropriate tables for the comments module.
	 *
	 * @return array An array of SQL query strings.
	 */
	private static function getCreateTableSQL(){
	
		$sql[] = "create table `".self::COMMENTS_TABLE."` (
			comment_id int(11) unsigned not null auto_increment primary key,
			record_id varchar(255) not null,
			parent_id  int(11) unsigned,
			subject varchar(200),
			contents text,
			date_posted datetime,
			last_modified datetime,
			posted_by varchar(100),
			approved tinyint(1),
			attachment varchar(255),
			attachment_mimetype varchar(255),
			comment_type_id int(11) unsigned,
			index record_id (record_id),
			index parent_id (parent_id),
			index comment_type (comment_type_id)
		)";
		
		$sql[] = "create table `".self::COMMENTS_SETTINGS_TABLE."` (
			comments_setting_id int(11) unsigned not null auto_increment primary key,
			comments_setting_type_id int(11) unsigned,
			comments_setting_tablename varchar(255),
			comments_setting_username varchar(255),
			comments_setting_value int(11),
			comments_setting_priority int(5) default 0,
			index setting_type (comments_setting_type_id),
			index setting_table (comments_setting_tablename),
			index setting_user (comments_setting_username)
			)";
			
		
		return $sql;
		
		
	}
	
	
	
	
	/**
	 * @brief Performs an SQL query. Throws an exception if there is an error.
	 * @param mixed $sql Either an SQL query string, or an array of such strings.
	 * @return resource The MySQL result resource handle.
	 * @throws Exception If there is an error performing the query.
	 *
	 * @see queryComments()
	 */
	private static function q($sql){
		if ( is_array($sql) ){
			$res = null;
			foreach ($sql as $q){
				$res = self::q($q);
			}
			return $res;
		} else {
			$res = mysql_query($sql, df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()));
			return $res;
		}
	}
	
	/**
	 * @brief Performs an SQL query.  If an exception is thrown, it will try to 
	 * create the comments tables then try the query again.  Failing that
	 * it will throw an exception.
	 *
	 * @param mixed $sql Either an SQL query string, or an array of such strings.
	 * @param resource The MySQL result resource handle.
	 * @throws Exception If there is an error performing the query.
	 *
	 * @see q()
	 */
	private static function queryComments($sql){
		try {
			return self::q($sql);
		} catch ( Exception $ex){
			self::q(self::getCreateTableSQL());
			return self::q($sql);
		}
	}
	
	
	/**
	 * @brief Returns the comments for a particular record.  
	 *
	 * This will cache a reference to the record in each comment that is returned so that
	 * it is efficient to later retrieve the originating record.
	 *
	 * @param Dataface_Record $record The record for which we are retrieving comments.
	 * @param array $query A query applied against the comments table to specify a filter
	 *		or sort.
	 * @param boolean $preview If this is true (default) then the comments will be abbreviated
	 *	  so that long text fields will only return the first 255 characters.  If false, then 
	 *	  the entire comment is loaded.
	 * @return array An array of Dataface_Record objects each encapsulating a comment.
	 */
	public function loadCommentsForRecord(Dataface_Record $record, $query=array(), $preview=true){
	
		$query['-table'] = self::COMMENTS_TABLE;
		$query['record_id'] = '='.$record->getId();
		print_r($query);
		$comments = df_get_records_array(self::COMMENTS_TABLE, $query, null, null, $preview);
		foreach ($comments as $comment){
			$comment->pouch['modules_comments']['record'] = $record;
		}
		return $comments;
		
	}
	
	/**
	 * @brief Returns the record associated with a particular comment.  This makes use of a 
	 * 	a cache inside the comment record to avoid making excess database calls.  Use
	 *	this method instead of a database query whenever possible.
	 *
	 * @param Dataface_Record $comment The comment for which we are seeking the subject record.
	 * @return Dataface_Record The record that this comment is attached to.
	 */
	public function getRecordForComment(Dataface_Record $comment){
	
		if ( !isset($comment->pouch['modules_comments']['record']) ){
			$r = df_get_record_by_id($comment->val('record_id'));
			if ( PEAR::isError($r) ) throw new Exception($r->getMessage(), $r->getCode());
			$comment->pouch['modules_comments']['record'] = $r;
			
		}
		return $comment->pouch['modules_comments']['record'];
	}
	
	/**
	 * @brief A block that displays the comment threads for the current record.  This block
	 * respects the following parameters:
	 *
	 * <table>
	 *		<tr><th>-comments-limit</th><td>The number of comments to show on the page.</td></tr>
	 *		<tr><th>-comments-skip</th><td>The starting position of the comments (default 0)</td></tr>
	 *		<tr><th>-comments-sort</th><td>How to sort the comments in this view.</td></tr>
	 *	</table>
	 */
	public function block__record_comments_list($params = array()){
		if ( isset($params['record']) ){
			$record = $params['record'];
		} else {
			$record = Dataface_Application::getInstance()->getRecord();
		}
		if ( !$record ) return;
		if ( !$record->checkPermission('view comments') ) return;
		/*
		$q = array();
		$globalQuery = Dataface_Application::getInstance()->getQuery();
		
		$q['-limit'] = 30;
		$q['-skip'] = 0;
		$q['-sort'] = 'date_posted';
		$q['parent_id'] = '=';
		$q['approved'] = '=1';
		
		if ( @$globalQuery['-comments-limit'] ) $q['-limit'] = intval($globalQuery['-comments-limit']);
		if ( @$globalQuery['-comments-skip'] ) $q['-skip'] = intval($globalQuery['-comments-skip']);
		if ( @$globalQuery['-comments-sort'] ) $q['-sort'] = intval($globalQuery['-comments-sort']);
		
		//$comments = $this->loadCommentsForRecord($record, $q, false);
		*/
		$comments = array();
		$limit = $q['-limit'];
		$skip = $q['-skip'];
		
		
		
		df_register_skin('modules_comments', dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
		$context = array(
			'comments' => $comments,
			'record' => $record,
			'limit'=>$limit,
			'skip'=>$skip
			
		);
		
		$jsTool = Dataface_JavascriptTool::getInstance();
		$jsTool->addPath(dirname(__FILE__).DIRECTORY_SEPARATOR.'js',
			$this->getBaseURL().'/js');
		$cssTool = Dataface_CSSTool::getInstance();
		$cssTool->addPath(dirname(__FILE__).DIRECTORY_SEPARATOR.'css',
			$this->getBaseURL().'/css');
			
		$jsTool->import('xataface/modules/comments/comment_list.js');
		
		df_display($context, 'xataface/modules/comments/comments_list.html');
			
		
	}
	
	
	public function block__after_record_content($params = array()){
		$query = Dataface_Application::getInstance()->getQuery();

		if ( $query['-action'] == 'view' ){
			return $this->block__record_comments_list($params);
		}
	}
	
	/**
	 * @brief Loads the comment settings for a table and user.
	 * 
	 * @param string $table The name of the table for which these settings should apply.
	 * @param string $username The username of the user for which these settings should apply.
	 * @return array An associative array of comment settings.  Keys are one of the Settings 
	 *		constants.  Values are the associated value.
	 *
	 * @see loadCommentsSettings() for cached version that works on current table and user only.
	 */
	public function loadCommentsSettingsForTableUser($table, $username){

		$res = $this->queryComments("select * from `".self::COMMENTS_SETTINGS_TABLE."` 
			where (`comments_setting_tablename`='".addslashes($table)."' or `comments_setting_tablename` is null or `comments_setting_tablename`='')
				and (`comments_setting_username`='".addslashes($username)."' or `comments_setting_username` is null or `comments_setting_username`='')
			order by `comments_setting_priority`");
			
		$settings = array();
		while ($row = mysql_fetch_assoc($res) ){
			$settings[$row['comments_setting_type_id']] = $row['comments_setting_value'];
		}
		return $settings;
			
	}
	
	/**
	 * @brief Returns an associative array of comment settings for the current table
	 *   and user.
	 *
	 * @see loadCommentSettingsForTableUser()
	 */
	public function getCommentsSettings(){
		if ( !isset($this->commentsSettings) ){
			$query = Dataface_Application::getInstance()->getQuery();
			$table = $query['-table'];
			$user = '';
			if ( class_exists('Dataface_AuthenticationTool') ){
				$user = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
			}
			$this->commentsSettings = $this->loadCommentsSettingsForTableUser($table, $user);
		}
		return $this->commentsSettings;
		
	}
	
	
	
	
	
	
	
	
}
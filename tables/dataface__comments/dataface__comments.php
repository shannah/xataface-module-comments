<?php
class tables_dataface__comments {
	function init($table){
		$app = Dataface_Application::getInstance();
		
		$attachmentField =& $table->getField('attachment');
		if ( PEAR::isError($attachmentField) ){
			throw new Exception($attachmentField->getMessage(), $attachmentField->getCode());
		}
		
		$useAttachments = false;
		// See if there is an attachment directory specified
		if ( isset( $app->_conf['modules_comments'] ) ){
			$commentsConf =& $app->_conf['modules_comments'];
			if ( isset($commentsConf['upload_url']) ){
				$attachmentField['url'] = $commentsConf['upload_url'];
				if ( !isset($commentsConf['upload_path']) ){
					throw new Exception("Failed to set upload path for comments table.  The upload_url was specified, but not the upload_path.");
					
				}
				if ( !is_writable($commentsConf['upload_path']) ){
					throw new Exception("Failed to set upload path for comments table because the path is not writable.  Please make sure that the upload directory is writable.");
					
				}
				$attachmentField['savepath'] = $commentsConf['upload_path'];
				$useAttachments = true;
			}
		}
		
		if ( !$useAttachments ){
			$attachmentField['widget']['type'] = 'hidden';
		}
	}
	
	/**
	 * Gives the app developer an opportunity to define their own delegate class.
	 * It is best for custom delegate classes to extend this class.
	 * @return mixed
	 */
	function getDelegate(){
		$s = DIRECTORY_SEPARATOR;
		$path = 'conf'.$s.'tables'.$s.'dataface__comments'.$s.'dataface__comments.php';
		$class = 'conf_tables_dataface__comments';
		if ( file_exists($path) ){
			import($path);
			if ( class_exists($class) ){
				return new $class;
			}
		}
		return null;
	}
	
	/**
	 * Defines permissions for the comments table.
	 */
	function getPermissions($record){
		if ( !$record ) return null;
		try {
			$source = $this->tryGetRecordForComment($record);
		} catch (Exception $ex){
			return null;
		}
		
		$perms = $source->getPermissions();
		$out = array();
		$out['new'] = @$perms['post comment'] ? 1:0;
		$out['view'] = 0;
		$out['reply comment'] = 0;
		$out['download comment attachment'] = 0;
		$out['view comment thread'] = 0;
		
		
		$username = null;
		if ( class_exists('Dataface_AuthenticationTool') ){
			$username = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
		}
		
		/// Rules that allow user to view comments
		/// Generally if the user has permission to view comments on the
		/// source record and the comment is approved then the user can view
		/// the comment, reply to it
		if ( 
				$perms['view comments'] and (
					$record->val('approved') or 
					@$perms['manage comments'] or (
						$username and 
						@$record->val('posted_by') == $username
					)
				)
			)
		{
			$out['view'] = 1;
			$out['reply comment'] = @$perms['reply comment'] ? 1:0;
			$out['download comment attachment'] = @$perms['download comment attachment'] ? 1:0;
			$out['view comment thread'] = @$perms['view comment thread'] ? 1:0;
			
		}
		
		
		/// Rules that allow the user to edit or delete comments
		$out['edit'] = 0;
		$out['delete'] = 0;
		if (
				@$perms['manage comments'] or (
					$username and
					@$record->val('posted_by') == $username
				)
			)
		{
		
			$out['edit'] = 1;
			$out['delete'] = 1;
		}
		
		if ( @$perms['manage comments'] ){
			$out['list'] = 1;
			$out['link'] = 1;
		}
		
		return $out;
		
	}
	
	function posted_by__permissions($record){
		return array('edit'=>0, 'new'=>0);
	}
	
	function tryGetRecordForComment(Dataface_Record $comment){
		$mt = Dataface_ModuleTool::getInstance();
		$mod = $mt->loadModule('modules_comments');
		try {
			$source = $mod->getRecordForComment($comment);
		} 
		catch (Exception $ex){
			$query = Dataface_Application::getInstance()->getQuery();
			if ( $query['-action'] == 'new' and @$query['record_id'] and !$comment->val('record_id') ){
				$comment->setValue('record_id', $query['record_id']);
				$source = $mod->getRecordForComment($comment);
				
			}
		
		}
		return $source;
	}
	
	function approved__permissions($record){
		if ( !$record ) return null;
		try {
			$source = $this->tryGetRecordForComment($record);
		} catch (Exception $ex){
			return null;
		}
		
		$perms = $source->getPermissions();
		//print_r($perms);
		$out = array();
		$out['edit'] = @$perms['manage comments'] ? 1:0;
		$out['new'] = @$perms['manage comments'] ? 1:0;
		$out['view'] = @$perms['manage comments'] ? 1:0;
		//print_r($perms);exit;
		return $out;
	}
	
	function attachment__permissions($record){
		if ( !$record ) return null;
		try {
			$source = $this->tryGetRecordForComment($record);
		} catch (Exception $ex){
			return null;
		}
		
		$perms = $source->getPermissions();
		//print_r($perms);
		$out = array();
		$out['edit'] = @$perms['upload comment attachment'] ? 1:0;
		$out['new'] = @$perms['upload comment attachment'] ? 1:0;
		$out['delete'] = @$perms['upload comment attachment'] ? 1:0;
		$out['view'] = @$perms['download comment attachment'] ? 1:0;
		//print_r($perms);exit;
		return $out;
	}
	
	
	function beforeInsert(Dataface_Record $record){
		if ( !$record->val('posted_by') ){
			if ( class_exists('Dataface_AuthenticationTool') ){
				$record->setValue('posted_by', Dataface_AuthenticationTool::getInstance()->getLoggedInUserName());
				
			}
		}
		
	}
	
	function beforeSave(Dataface_Record $record){
		try {
			$source = $this->tryGetRecordForComment($record);
			if ( !$source ) throw new Exception('source record not found');
			$perms = $source->getPermissions();
			if ( !@$perms['manage comments'] and @$perms['post approved comment'] ){
				// If the user has permission to post an approved comment
				// we'll just approve it directly.
				$record->setValue('approved',1);
			} else if ( !@$perms['manage comments'] ){
				//Otherwise any changes should result in the comment becoming unapproved.
				if ( $record->valueChanged('subject') or $record->valueChanged('contents') or $record->valueChanged('attachment') ){
					$record->setValue('approved',0);
				}
			}
		} catch (Exception $ex){}
	}
	
	
	function getTitle($record){
		return $record->val('subject');
	}
	
	function field__avatar($record){
	
		$del = Dataface_Application::getInstance()->getDelegate();
		$url = null;
		if ( $del and method_exists($del, 'getAvatarForUsername') ){
			$url = $del->getAvatarForUsername($record->val('posted_by'));
		}
		if ( !@$url ){
			$mod = Dataface_ModuleTool::loadModule('modules_comments');
			$url = $mod->getBaseURL().'/images/noavatar.png';
		}
		return '<img width="32" height="32" src="'.htmlspecialchars($url).'"/>';
	}
	
	function posted_by__display($record){
		if ( !$record->val('posted_by') ) return 'Anonymous';
		return $record->val('posted_by');
	}
}
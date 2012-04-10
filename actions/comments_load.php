<?php
class actions_comments_load {
	function handle($params){
		
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		if ( !isset($query['record_id']) ){
			throw new Exception("No record ID specified");
		}
		$record = df_get_record_by_id($query['record_id']);
		$mod = Dataface_ModuleTool::getInstance()->loadModule('modules_comments');
		
		
		$comments = $mod->loadCommentsForRecord($record, $query, false);
		
		df_register_skin('modules_comments', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'templates');
		$context = array(
			'comments' => $comments,
			'record' => $record
		);
		
		print_r(array_keys($context['comments']));
		df_display($context, 'xataface/modules/comments/comments_list.html');
		
	}
}
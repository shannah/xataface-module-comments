<?php
class actions_comments_raw_comment {
	function handle($params){
	
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$record = $app->getRecord();
		header('Content-type:text/html; charset="'.$app->_conf['oe'].'"');
		if ( !$record ){
			echo "<p>Comment could not be found.</p>";
		} else if ( !($record instanceof Dataface_Record) ){
			
			echo '<p>Comment could not be loaded.</p>';
		} else if ( $record->table()->tablename != 'dataface__comments' ){
			echo '<p>Comment could not be loaded.</p>';
		}
		echo $record->htmlValue('contents');
	}
}
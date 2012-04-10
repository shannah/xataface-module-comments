<?php
/*
 * Xataface Comments Module
 * Copyright (C) 2012  Steve Hannah <steve@weblite.ca>
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Library General Public License for more details.
 * 
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301, USA.
 *
 */
class modules_comments_installer {
	
	
	
	
	public function update_1(){
		$sql[] = "CREATE TABLE `dataface__comments` (
		  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `record_id` varchar(255) NOT NULL,
		  `parent_id` int(11) unsigned DEFAULT NULL,
		  `subject` varchar(200) DEFAULT NULL,
		  `contents` text,
		  `date_posted` datetime DEFAULT NULL,
		  `last_modified` datetime DEFAULT NULL,
		  `posted_by` varchar(100) DEFAULT NULL,
		  `approved` tinyint(1) DEFAULT NULL,
		  `attachment` varchar(255) DEFAULT NULL,
		  `comment_type_id` int(11) unsigned DEFAULT NULL,
		  PRIMARY KEY (`comment_id`),
		  KEY `record_id` (`record_id`),
		  KEY `parent_id` (`parent_id`),
		  KEY `comment_type` (`comment_type_id`)
		) ENGINE=MyISAM;";
		
		$sql[] = "CREATE TABLE `dataface__comments_settings` (
		  `comments_setting_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `comments_setting_type_id` int(11) unsigned DEFAULT NULL,
		  `comments_setting_tablename` varchar(255) DEFAULT NULL,
		  `comments_setting_username` varchar(255) DEFAULT NULL,
		  `comments_setting_value` int(11) DEFAULT NULL,
		  `comments_setting_priority` int(5) DEFAULT '0',
		  PRIMARY KEY (`comments_setting_id`),
		  KEY `setting_type` (`comments_setting_type_id`),
		  KEY `setting_table` (`comments_setting_tablename`),
		  KEY `setting_user` (`comments_setting_username`)
		) ENGINE=MyISAM ;";
		
		df_q($sql);
		self::clearViews();
	}
	
	
	
	public static function clearViews(){
	
	
		$res = mysql_query("show tables like 'dataface__view_%'", df_db());
		$views = array();
		while ( $row = mysql_fetch_row($res) ){
			$views[] = $row[0];
		}
		if ( $views ) {
			$sql = "drop view `".implode('`,`', $views)."`";
			//echo $sql;
			//echo "<br/>";
			$res = mysql_query("drop view `".implode('`,`', $views)."`", df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()));
		}
		
	}

}
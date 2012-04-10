//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require <RecordDialog/RecordDialog.js>
//require-css <xataface/modules/comments/comments.css>
//require <rest.js>
(function(){
	
	var $ = jQuery;
	
	
	
	
	
	function CommentSet(el){
		this.el = el;
		
	}
	
	CommentSet.prototype.getRecordID = getRecordID;
	CommentSet.prototype.postComment = postComment;
	CommentSet.prototype.getComments = getComments;
	CommentSet.prototype.decorate = decorateCommentSet;
	CommentSet.prototype.undecorate = undecorateCommentSet;
	CommentSet.prototype.addComment = addComment;
	CommentSet.prototype.removeComment = removeComment;
	CommentSet.prototype.load = loadCommentSet;
	CommentSet.prototype.loadMore = loadMoreComments;
	CommentSet.prototype.init = initCommentSet;
	
	
	function initCommentSet(){
		this.decorate();
		this.load({});
	}
	
	
	function loadCommentSet(/*Object*/query){
		if ( typeof(query) == 'undefined' ) query = this.query;
		if ( !query['-skip'] ) query['-skip'] = 0;
		if ( !query['-limit'] ) query['-limit'] = 0;
		query['-skip'] = parseInt(query['-skip']);
		query['-limit'] = parseInt(query['-limit']);
		if ( query['-limit'] == 0 ) query['-limit'] = 30;
		this.query = $.extend({},query);
		this.query['-table'] = 'dataface__comments';
		this.query['record_id'] = this.getRecordID();
		this.query['-action'] = 'comments_load';
		
		var cs = this;
		
		
		$.get(DATAFACE_SITE_HREF, this.query, function(res){
			
			var div = document.createElement('div');
			$(div).html(res);
			cs.comments = null;
			$('ul.xf-comments-list', cs.el).empty();
			$('ul.xf-comments-list >li', div).each(function(){
				$('ul.xf-comments-list', cs.el).append(this);
			});
			
			cs.startIndex = 0;
			cs.endIndex = cs.getComments().length-1;
			$.each(cs.getComments(), function(){
				this.decorateElement();
			});
		});
	}
	
	
	function addComment(/*Comment*/ c){
		this.comments.push(c);
		$('ul.xf-comments-list').append(c.el);
		c.decorateElement();
	}
	
	function removeComment(/*Comment*/ c){
		this.getComments();
		var index = this.comments.indexOf(c);
		if ( index >=0 ){
			this.comments.splice(index, 1);
			c.undecorateElement();
			
			
			$(c.el).remove();
		}
		
	}
	
	function loadMoreComments(){
		var cs = this;
		this.query['-skip'] = this.endIndex+1;
		$.get(DATAFACE_SITE_HREF, this.query, function(res){
			var comments = [];
			$('li.xf-comments-comment', res).each(function(){
				var c = new Comment(this, cs );
				cs.addComment(c);
				
			});
			cs.endIndex = cs.getComments().length-1;
		});
		
		
	}
	
	
	
	function getRecordID(){
		var cs = this;
		return $(this.el).attr('data-xf-comments-record-id');
	}
	
	function postComment(reply_id){
		var cs = this;
		var dlg = new xataface.RecordDialog({
			table: 'dataface__comments',
			callback: function(data){
			
				
				cs.load();
				
			}
		
		});
		
		
		dlg.params = {
			record_id: cs.getRecordID()
		
		};
		if ( reply_id ) dlg.params.parent_id=reply_id;
		dlg.display();
		
		
	}
	
	function getComments(){
		var cs = this;
		if ( !this.comments ){
			var comments = [];
			$('li.xf-comments-comment', this.el).each(function(){
				var c = new Comment(this, cs );
				comments.push(c);
			});
			this.comments = comments;
		}
		return this.comments;
	
	}
	
	function decorateCommentSet(){
		var cs = this;
		$('.xf-comments-new-action a', cs.el).bind('click.xf-comments', function(){
			cs.postComment();
			return false;
		});
		$('.xf-comments-show-more-comments', cs.el).bind('click.xf-comments', function(){
			cs.loadMore();
			return false;
		});
		
	
	
		$.each(this.getComments(), function(){
			this.decorateElement();
		});
	}
	
	
	function undecorateCommentSet(){
		
	
	
		$.each(this.getComments(), function(){
			this.undecorateElement();
		});
		var cs = this;
		$('.xf-comments-new-action a', this.el).unbind('click.xf-comments');
	}
	
	
	
	
	function Comment(/*HTMLElement*/ el, /*CommentSet*/ cs){
		this.el = el;
		this.cs = cs;
	}
	
	
	Comment.prototype.edit = editComment;
	Comment.prototype.remove = deleteComment;
	Comment.prototype.getCommentID = getCommentID;
	Comment.prototype.decorateElement = decorateCommentElement;
	Comment.prototype.undecorateElement = undecorateCommentElement;
	Comment.prototype.reply=replyToComment;
	
	function editComment(){
		var c = this;
		var dlg = new xataface.RecordDialog({
			recordid: 'dataface__comments?comment_id='+this.getCommentID(),
			table: 'dataface__comments',
			callback: function(data){
			
				$('.xf-comment-body', c.el).load(DATAFACE_SITE_HREF, {
					'-action':'comments_raw_comment', 
					'-table':'dataface__comments',
					'comment_id': c.getCommentID()
				});
				return false;
			
				
			}
		
		});
		
		dlg.display();
	}
	
	function deleteComment(){
		if ( !confirm('Are you sure you want to delete this comment?') ) return;
		var c = this;
		Xataface.deleteRecord('dataface__comments?comment_id='+this.getCommentID(), function(res){
			c.cs.removeComment(c);
		});
	}
	
	function replyToComment(){
		this.cs.postComment(this.getCommentID());
		
	}
	
	function getCommentID(){
		return $(this.el).attr('data-xf-comment-id');
	}
	
	function decorateCommentElement(){
		var comment = this;
		$('.xf-comments-edit-action a', this.el).bind('click.xf-comments', function(){
			comment.edit();
			return false;
		});
		$('.xf-comments-delete-action a', this.el).bind('click.xf-comments', function(){
			comment.remove();
			return false;
		});
		$('.xf-comment-reply-action a', this.el).bind('click.xf-comments', function(){
			comment.reply();
			return false;
		});
		$('.more', this.el).bind('click.xf-comments', function(){
			$('.xf-comment-body', comment.el).load(DATAFACE_SITE_HREF, {
				'-action':'comments_raw_comment', 
				'-table':'dataface__comments',
				'comment_id': comment.getCommentID()
			});
			return false;
		});
		
	}
	
	function undecorateCommentElement(){
		var comment = this;
		$('.xf-comments-edit-action a', this.el).unbind('click.xf-comments');
		$('.xf-comments-delete-acton a', this.el).unbind('click.xf-comments');
		$('.xf-comment-reply-action a', this.el).unbind('click.xf-comments');
		
	}
	
	
	
	
	$(document).ready(function(){
	
		$('.xf-comments-section').each(function(){
			var cs = new CommentSet(this);
			cs.init();
		});
	});
	
	
	
	

})();
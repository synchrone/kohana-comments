<?php
    /** @var $comment Model_Comment **/
	echo '<dl id="comment_',$comment->id,'">';
	echo '<dt>', str_repeat('---',$comment->lvl-2),
		html::anchor($comment->user->uri(),$comment->user->name()),
		' ',
		html::chars($comment->comment_type->type),
		' ',
		$comment->state,
		' ',
		html::anchor('','Mark as Good',array('class'=>'hijack ham')),
		' ',
		html::anchor('','Mark as Spam',array('class'=>'hijack spam')),
		'</dt>';
	echo '<dd>',	
		html::chars($comment->text),
		'</dd>';
	echo '</dl>';

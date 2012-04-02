<?php

    if(!function_exists('get_mark_link')){
        function get_mark_link($id,$mark){
            return Route::get('comments')
                ->uri(array('action'=>'mark_as')).
            '?'.http_build_query(array('comment_id'=>$id,'mark'=>$mark));
        }
    }

    /** @var $comment Model_Comment **/
	echo '<dl id="comment_',$comment->id,'">';
	echo '<dt>', str_repeat('---',$comment->lvl-2),
		html::anchor(
            Route::get('default')->uri(
                array('controller'=>'user','action'=>'index','id'=>$comment->user->id)
            ),
            $comment->user->username
        ),
		' ',
		html::chars($comment->comment_type->type),
		' ',
		$comment->state,
		' ',
		html::anchor(get_mark_link($comment->id,'ham'),'Mark as Good',array('class'=>'hijack ham')),
		' ',
		html::anchor(get_mark_link($comment->id,'spam'),'Mark as Spam',array('class'=>'hijack spam')),
		'</dt>';
	echo '<dd>',	
		html::chars($comment->text),
		'</dd>';
	echo '</dl>';

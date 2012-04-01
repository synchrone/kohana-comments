<h2>Comments Admin</h2>
<?php
foreach($comments->comments as $comment) {
	echo View::factory('comments/comment')
		->bind('comment',$comment);
}
	$pagination = Pagination::factory(array(
		'current_page'	=> array('source'=>'route', 'key'=>'id'),
		'total_items'	=> $comments->total,
		'items_per_page'	=> $comments->per_page,
		'auto_hide'	=> true,
	));

	echo $pagination->render();
//echo View::factory('profiler/stats');

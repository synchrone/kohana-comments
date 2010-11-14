<?php defined('SYSPATH') OR die('No direct script access.');

Route::set('comments', 'comments/(<action>(/<id>))')
	->defaults(array(
		'controller' =>	'comments',
		'action' =>	'index',
	));

kohana-comments
===============

A comments module for kohana

Description
--------------

I was looking for a quick to use comments module for kohana, so that I could attach comments to a few different types of pages.  I found https://github.com/vimofthevine/kohana-comments which was the only comment module that I could easily find


Requirements
-------------
* kohana 3 - https://github.com/kohana/core
* database module - https://github.com/kohana/database
* orm module - https://github.com/kohana/orm
* B8 module - https://github.com/stensi/Kohana-B8


Running
----------

The module provides a controller for the admin of comments that arn't market ham or spam.  B8 refers to approved comments as ham.

the addition to a controller to load comments would look like this:

	if(isset($_POST['s'])) {
		$posted = Model_Comment::post('bot',$bot,$this->user,$_POST['text']);
		$view->bind('posted',$posted);
	}

	$comments =  Model_Comment::fetch('bot',$bot,$this->request->param('page',1));

	$this->request->response = $view
		->bind('comments',$comments);

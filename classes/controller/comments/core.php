<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Controller_Comments_Core extends Controller {
	public function before() {
		parent::before();

		$authentic= Auth::instance();
		$admin_role = new Model_Role(array('name' =>'admin'));
		if($authentic->logged_in()) {
			$this->user = $authentic->get_user();
			if($this->user->has('roles',$admin_role)) {
				//now you have access to user information stored in the database
				View::bind_global('user',$this->user);
				return;
			}
		}

		$this->request->redirect('');
	}

	public function action_index($page = 1) {
		$comments = Model_Comment::fetch(false,false,$page,'queued');

		$this->request->response = View::factory('comments/index')
			->bind('comments',$comments)
			;
	}

	public function action_mark_as() {
		$id = $_POST['comment_id'];
		$comment = ORM::factory('comment',$_POST['comment_id']);
		if(!$comment->loaded()) {
			echo 'not a comment';
			return;
		}

		$mark_as = $_POST['mark_as'];

		switch($mark_as) {
			case 'ham':
				$comment->mark_as_ham();
				$comment->save();
				break;
			case 'spam':
				$comment->mark_as_spam();
				$comment->save();
				break;
			case 'deleted':
				$comment->mark_as_deleted();
				$comment->save();
				break;
		}

		$template = 'comments/comment';
		if(isset($_POST['local_template'])) {
			$template = 'common/comment';
		}
		$this->request->response = View::factory($template)
			->bind('comment',$comment)
			->set('admin',true);
	}
}

<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Kohana_Controller_Comments extends Controller {
    /** @var Model_User */
    protected $user;

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
		$this->request->redirect('/');
	}

	public function action_index($page = 1) {
		$comments = Model_Comment::fetch(false,false,$page,'queued');

		$this->response->body(View::factory('comments/index')
			->bind('comments',$comments)
        );
	}

	public function action_mark_as() {
        /** @var $comment Model_Comment */
		$comment = ORM::factory('comment',(int)$_POST['comment_id']);

		if(!$comment->loaded())
        {
			echo 'not a comment';
			return;
		}

        $comment->mark($_POST['mark_as']);
        $comment->save();

		$this->response->body(View::factory('comments/comment')
			->bind('comment',$comment)
			->set('admin',true)
        );
	}
}
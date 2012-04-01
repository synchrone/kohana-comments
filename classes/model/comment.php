<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Model_Comment extends ORM_MPTT {
	protected $_belongs_to = array(
		'comment_type' => array(),
		'user' => array(),
	);

	protected $type;
	protected $config;
	protected $B8;


	public static function post($type_name,$scope,$user,$text) {
		$comment = new Model_Comment();
		return $comment->_post($type_name,$scope,$user,$text);
	}

	public static function fetch($type_name,$scope,$page = 1,$states = false) {
		$comment = new Model_Comment();
		return $comment->_fetch($type_name,$scope,$page,$states);
	}

	private function _fetch($type_name,$scope,$page,$states) {
		$states = $this->getPublicStates($states);
		$offset = ($page - 1) * $this->config['per_page'];

		if($type_name === false) {
			$query = ORM::factory('comment')
				->with('comment_type');
		}
		else {
			$query = $this->getType($type_name)
				->comments
				->where('scope','=',$scope);
		}

		$result = new stdClass();

		$result->per_page = $this->config['per_page'];
		$result->total = $query
			->where('state','IN',$states)
			->with('user')
			->reset(FALSE)
			->count_all();

		$result->comments = $query 
			->offset($offset)
			->limit($this->config['per_page'])
			->order_by('date', $this->config['order'])
			->find_all();

		return $result;
	}

	private function getPublicStates($states = false) {
		if($states === false) {
			$states = $this->config['public_states'];
		}
		if(!is_array($states)) {
			$states = array($states);
		}

		return $states;
	}

	public function __construct($id = NULL) {
		parent::__construct($id);

		$this->config = Kohana::config('comments.default');
	}

	private function _post($type_name,$scope,$user,$text) {
		$type = $this->getType($type_name);

		$this->comment_type_id = $type;
		$this->scope = $scope;
		$this->user_id = $user;
		$this->date = time();
		$this->text = $text;

		$this->classify();

		$this->save();

		$states = $this->getPublicStates();
		return in_array($this->state,$states);
	}

	private function setB8() {
		if(!isset($this->B8)) {
			$this->B8 = B8::factory();
		}
	}

	public function remove_mark() {
		$marks = array('ham','spam');
		if(in_array($this->state,$marks)) {
			call_user_func(array($this,'unmark_as_'.$this->state));
		}
	}

	public function mark_as_deleted() {
		$this->state = 'deleted';
	}

	public function mark_as_ham() {
		$this->remove_mark();
		$this->setB8();
		$this->B8->learn($this->text, B8::HAM);
		$this->classify();
	}

	public function mark_as_spam() {
		$this->remove_mark();
		$this->setB8();
		$this->B8->learn($this->text, B8::SPAM);
		$this->classify();
	}

	public function unmark_as_ham() {
		$this->setB8();
		$this->B8->unlearn($this->text, B8::HAM);
		$this->classify();
	}

	public function unmark_as_spam() {
		$this->setB8();
		$this->B8->unlearn($this->text, B8::SPAM);
		$this->classify();
	}

	public function classify() {
		$this->setB8();
		$this->probability = $this->B8->classify($this->text);
		$this->state = 'queued';
		if($this->probability < $this->config['lower_limit']) {
			$this->state = 'ham';
		}
		else if($this->probability > $this->config['upper_limit']) {
			$this->state = 'spam';
		}
	}

	private function getType($type_name) {
		$type = ORM::factory('comment_type')
			->where('type','=',$type_name)
			->find();
		if(!$type->loaded()) {
			$type->type = $type_name;
			$type->save();
		}
		return $type;
	}
}

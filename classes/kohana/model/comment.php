<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 * @property 	$comment_type_id
 * @property 	$state
 * @property 	$probability
 * @property 	$date
 * @property 	$user_id
 * @property 	$text
 */
class Kohana_Model_Comment extends ORM_MPTT {

    const DELETED   = 'deleted';
    const QUEUED    = 'queued';
    const HAM       = 'ham';
    const SPAM      = 'spam';

	protected $_belongs_to = array(
		'comment_type' => array(),
		'user' => array(),
	);

    /** @var $type Model_Comment_Type */
	protected $type;
    /** @var $config array  */
	protected $config;
    /** @var $B8 B8 */
	protected $B8;

    /**
     * @param $type_name
     * @return Model_Comment_Type
     */
	protected static function getTypes($type_name) {
        /** @var $type Model_Comment_Type */
		$type = ORM::factory('comment_type')
			->where('type','=',$type_name)
			->find();
		if(!$type->loaded()) {
			$type->type = $type_name;
			$type->save();
		}
		return $type;
	}

    protected static function getPublicStates($states = false) {
        if($states === false) {
            $states = $this->config['public_states'];
        }
        if(!is_array($states)) {
            $states = array($states);
        }

        return $states;
    }


	public static function post($type_name,$scope,$user,$text) {
		$comment = new Model_Comment();
		return $comment->_post($type_name,$scope,$user,$text);
	}
    protected function _post($type_name,$scope,$user,$text) {
   		$type = self::getTypes($type_name);

        //as in 'what are we adding the comment for
   		$this->comment_type_id = $type;
        //as in that stuff's ID
   		$this->scope = $scope;


   		$this->user_id = $user;
   		$this->date = time();
   		$this->text = $text;

   		$this->classify();

   		$this->save();

   		$states = $this->getPublicStates();
   		return in_array($this->state,$states);
   	}

	public static function fetch($type_name,$scope,$page = 1,$states = false) {
		$comment = new Model_Comment();
		return $comment->_fetch($type_name,$scope,$page,$states);
	}
	protected function _fetch($type_name,$scope,$page,$states) {
		$states = $this->getPublicStates($states);
		$offset = ($page - 1) * $this->config['per_page'];

		if($type_name === false) {
            /** @var $query Model_Comment */
			$query = ORM::factory('comment')
				->with('comment_type');
		}
		else {
            /** @var $query Model_Comment */
			$query = $this->getTypes($type_name)
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

	public function __construct($id = NULL) {
		parent::__construct($id);
		$this->config = Kohana::$config->load('comments.default');
	}

	protected function setB8() {
		if(!isset($this->B8)) {
            $this->B8 = B8::factory();
		}
	}

    public function mark($mark){
        if($mark == $this->state){ return $this; } //no sense, huh ?
        if(constant('self::'.strtoupper($mark)) === null){ throw new Kohana_Exception('No such state'); }

        $this->unmark();

        $this->state = $mark;
        if($mark_value = constant('B8::'.strtoupper($mark))){
            $this->setB8();
            $this->B8->learn($this->text,$mark_value);
        }
        return $this;
    }

	public function unmark() {
        if($mark_value = constant('B8::'.strtoupper($this->state))){
            $this->setB8();
            $this->B8->unlearn($this->text,$this->state);
        }
        $this->state = self::QUEUED;
        return $this;
	}

	public function classify() {
        $this->state = self::QUEUED;

        if(class_exists('B8')){ //we may just not have that module loaded
            $this->setB8();
            $this->probability = $this->B8->classify($this->text);

            if($this->probability < $this->config['lower_limit']) {
                $this->state = B8::HAM;
            }
            else if($this->probability > $this->config['upper_limit']) {
                $this->state = B8::SPAM;
            }
        }
        return $this;
	}


}

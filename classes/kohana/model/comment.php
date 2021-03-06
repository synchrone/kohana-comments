<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Comment controller
 *
 * @package     Comments
 *
 * @property    int $id
 * @property 	int $comment_type_id
 * @property 	string $state
 * @property 	float $probability
 * @property 	int $date
 * @property 	int $user_id
 * @property 	string $text
 * @property    Model_Comment_Type $comment_type
 * @property    Model_User         $user
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

    public static function factory($model='comment',$id=array()){
        // Set class name
        $model = 'Model_'.ucfirst($model);

        /** @var $model Model_Comment */
        $model = new $model();

        if(isset($id['state'])){
            $model->where('state', 'IN', self::getPublicStates($id['state']));
            unset($id['state']);
        }

        foreach ($id as $column => $value)
        {
            // Passing an array of column => values
            $model->where($column, '=', $value);
        }
        return $model;
    }

    /**
     * @param $type_name
     * @return Model_Comment_Type
     */
	protected static function getType($type_name) {
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

    public static function getPublicStates($states = false) {
        if($states === false) {
            $states = Kohana::$config->load('comments.default.public_states');
        }
        if(!is_array($states)) {
            $states = array($states);
        }

        return $states;
    }

    protected static function getRootComment($type_name=null,$scope = 1){
        $model = self::factory();
        return $model->where($model->left_column,'=',1)
            ->where('comment_type_id','=', self::getType($type_name)->id)
            ->where($model->scope_column, '=', $scope)
            ->find();
    }

    /**
     * @static
     * @param $type_name
     * @param $scope
     * @param Model_User $user
     * @param $text
     * @param $parent_id
     * @return Model_Comment
     */
	public static function post($type_name,$scope,$user,$text,$date = null,$parent_id = null) {
        /** @var $parent_comment Model_Comment */
        $parent_comment = self::factory('comment', array('id'=>$parent_id))->find();

        if(!$parent_comment->loaded()) //couldn't find parent comment
        {
            //so we take root (invisible comment), root of the tree
            $parent_comment = Model_Comment::getRootComment($type_name,$scope);
            if(!$parent_comment->loaded())
            {
                $parent_comment->comment_type_id = self::getType($type_name)->id;
                $parent_comment->user_id = $user->id;
                $parent_comment->text = '<root/>';
                $parent_comment->date = $date !== null ? $date : time();
                $parent_comment->{$parent_comment->scope_column} = $scope;
                $parent_comment->{$parent_comment->level_column} = 1;
                $parent_comment->{$parent_comment->left_column} = 1;
                $parent_comment->{$parent_comment->right_column} = 2;
                $parent_comment->{$parent_comment->parent_column} = NULL;
                $parent_comment->save(); //we should always have the tree's root
            }
        }

        $comment = self::factory();
        $comment->comment_type_id = $parent_comment->comment_type_id;
        $comment->user_id = $user->id;
        $comment->text = $text;
        $comment->date = $date !== null ? $date : time();
        $comment->classify();
        $comment->insert_as_last_child($parent_comment); //does the save() call. Scope is taken from $target

        return $comment;
	}

	public static function fetch($type_name = null, $scope = null, $page = 1, $per_page = null, $states = null, $user_id = null) {
        if($type_name === null) {
            /** @var $query Model_Comment */
            $query = ORM::factory('comment')
                ->with('comment_type');
        }
        else {
            /** @var $query Model_Comment */
            $query = self::getType($type_name)->comments;
        }
        $query->with('user');

        if($scope){
            $query->where('scope','=',$scope);
        }
        if($user_id){
            $query->where('user_id','=',$user_id);
        }

        $query
            ->where('parent_id','IS NOT',NULL)
            ->where('state','IN',self::getPublicStates($states));

        $result = new stdClass();
        $result->per_page = $per_page ? $per_page : Kohana::$config->load('comments.default.per_page');

        $query
            ->offset(($page - 1) * $result->per_page)
            ->limit($result->per_page)
            ->order_by('date', Kohana::$config->load('comments.default.order'));

        $result->comments = $query->find_all()->as_array();

        $result->total = $query->count_all();
        return $result;
    }

    /**
     * @param $type_name
     * @param $scope
     * @param array|bool $states
     * @return $this
     */
    public static function fetch_tree($type_name,$scope,$states=false){
        /** @var $query Model_Comment */
        $query = self::getRootComment($type_name,$scope);
        return $query
            ->descendants() //had to override it, as it calls find_all by default
            ->where('state','IN',self::getPublicStates($states))
        ;
    }

    public function delete_by_scope($scope)
    {
        return DB::delete($this->table_name())
            ->where($this->scope_column,'=',$scope)
            ->execute();
    }


    public function rules(){
        return array(
            'text' => array(
                array('not_empty')
            )
        );
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

    /**
     * Returns the descendants of the current node.
     *
     * @param bool   $self include the current node
     * @param string $direction direction to order the left column by.
     * @param bool   $direct_children_only include direct children only
     * @param bool   $leaves_only include leaves only
     * @param bool   $limit number of results to get
     *
     * @return  ORM_MPTT
     */
    public function descendants($self = FALSE, $direction = 'ASC', $direct_children_only = FALSE, $leaves_only = FALSE, $limit = FALSE)
    {
        $left_operator = $self ? '>=' : '>';
        $right_operator = $self ? '<=' : '<';

        $query = self::factory($this->object_name())
            ->where($this->left_column, $left_operator, $this->left())
            ->where($this->right_column, $right_operator, $this->right())
            ->where($this->scope_column, '=', $this->scope())
            ->order_by($this->left_column, $direction);

        if ($direct_children_only)
        {
            if ($self)
            {
                $query
                    ->and_where_open()
                    ->where($this->level_column, '=', $this->level())
                    ->or_where($this->level_column, '=', $this->level() + 1)
                    ->and_where_close();
            }
            else
            {
                $query->where($this->level_column, '=', $this->level() + 1);
            }
        }

        if ($leaves_only)
        {
            $query->where($this->right_column, '=', DB::expr($this->left_column.' + 1'));
        }

        if ($limit !== FALSE)
        {
            $query->limit($limit);
        }

        return $query;
    }

    public function save(Validation $validation = NULL){
        return $this->loaded() ? $this->update($validation) : $this->create($validation);
    }

}

<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @property string $type
 * @property Model_Comment $comments
 */
class Kohana_Model_Comment_Type extends ORM {
	protected $_has_many = array('comments'=>array());
}


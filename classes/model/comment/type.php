<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Comment_Type extends ORM {
	protected $_has_many = array('comments'=>array());
}


<?php
class Language extends ActiveRecord\Model
{
	static $has_many = array(
		array('snippet')
	);
}
?>

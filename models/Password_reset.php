<?php
class Password_reset extends ActiveRecord\Model
{
    static $validates_uniqueness_of = array(
        array('code','author')
    );
}

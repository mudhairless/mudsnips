<?php
class Author extends ActiveRecord\Model
{
    static $has_many = array(
        array('snippet')
    );
    static $validates_uniqueness_of = array(
        array('email')
    );
}


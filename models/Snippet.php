<?php

class Snippet extends ActiveRecord\Model
{
    static $belongs_to = array(
        array('author'),
        array('language')
    );
}


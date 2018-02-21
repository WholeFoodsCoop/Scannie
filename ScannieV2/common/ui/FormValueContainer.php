<?php
if(!class_exists('ValueContainer')) {
    include('ValueContainer.php');
}
class FormValueContainer extends ValueContainer
{
    public function __construct()
    {
        $this->setMany($_POST);
        $this->setMany($_GET);
    }
}


<?php
require('../classes/Connection.class.php');
Class Core {
	public $id;
	public $created;
	public $modified;

    public $errors = [];

    public function getById($id = null) {
		return $this;
    }

    public function validate($data) {
        $this->errors = [];
        return true;
    }

    public function findAll() {
        return [];
    }

    public function save($data) {
        if ($this->validate($data)) {
                return true;
        }
        return false;
    }
}
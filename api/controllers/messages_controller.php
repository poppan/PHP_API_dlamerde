<?php
require_once('../models/User.class.php');
require_once('../controllers/app_controller.php');

Class MessagesController extends AppController {
	private $message;
	private $user;

	public function __construct(){
		parent::__construct();
		$this->user = new User();
	}

	public function canAccessRestricted() {
		return $this->user->getUserFromToken($this->data['token']);
	}

	public function restricted_create(){
		$this->render();
	}

	public function restricted_index(){
		$this->set('messages', $this->message->findAll());
		$this->render();
	}
	public function restricted_edit(){
		$this->render();
	}
	public function restricted_delete(){
		$this->render();
	}
}

//demarrage session
session_start();
$controller = new MessagesController();
$action     = isset($_GET['action']) ? $_GET['action'] : '';
//print_r($controller);
$controller->route($action);
?>
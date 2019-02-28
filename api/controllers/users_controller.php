<?php
require_once('../models/User.class.php');
require_once('../controllers/app_controller.php');

Class UsersController extends AppController {
	private $user;

	public function __construct(){
		parent::__construct();
		$this->user = new User();
	}

	public function canAccessRestricted() {
		return $this->user->getByToken($this->data['token']);
	}

	public function index(){
		$this->render();
	}
	public function create(){
		$this->render();
	}
	public function login(){
		$token = $this->user->login($this->data['request']);
		if ($token !== false){
			$this->set('token', $token);
		}else{
			$this->set('errors', $this->user->errors);
		}
		$this->render();
	}
	public function restricted_index(){
		$this->set('users', $this->user->findAll());
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
$controller = new UsersController();
$action     = isset($_GET['action']) ? $_GET['action'] : '';
//print_r($controller);
$controller->route($action);

?>
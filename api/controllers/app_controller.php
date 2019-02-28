<?php
Class AppController{
	public $data;
	public $response_data;

	protected function __construct(){
		// input
		$this->data['post']         = $_POST;
		$this->data['get']          = $_GET;
		$this->data['session']      = $_SESSION;
		// le param true force la creation d'un tableau associatif, c'est  pratique pour remplacer directement un $_POST par un request body
		$this->data['request']      = json_decode(file_get_contents('php://input'), true);

		// le token est transmis dans le header de la requete sous la forme "Authorization : Bearer <token>"
		if( isset(apache_request_headers()['Authorization'])
			&& stristr( apache_request_headers()['Authorization'], 'bearer ') !== false){
			$this->data['token'] = "". str_ireplace('bearer ', '',  apache_request_headers()['Authorization']);
		}else{
			$this->data['token']     = '';
		}

		// output
		$this->data['response']     = [];
	}

	public function set($key, $value){
		$this->data['response'][$key] = $value;
	}

	public function route($action){
		$this->set('action', $action);
		try{
			// alternative :
			//  return call_user_func(array($this, $action));
			if (method_exists($this, $action)) {
				if (stristr($action, 'restricted_') > -1){
					if($this->canAccessRestricted()){
						return $this->$action();
					}
				}else{
					// unrestricted method
					return $this->$action();
				}
			}
			// default if not logged or method do not exists
			return $this->index();

		}catch(Exception $e){
			// dla merde ?
		}
	}

	public function canAccessRestricted(){
		return false;
	}
	public function index(){
		$this->render();
	}
	public function render(){
		// TODO : not standardized = not safe, implement a safer way ?
		// i.E check if request declare accept type  application/json or something else
//		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
//			header('Access-Control-Allow-Methods: *');
//			header('Access-Control-Allow-Origin: *');
//			header('Content-type: application/json');
//			echo json_encode($this->response_data, JSON_PRETTY_PRINT);
//			die;
//		}else{
			header('Access-Control-Allow-Methods: *');
			header('Access-Control-Allow-Origin: *');
			header('Content-type: application/json');
			echo json_encode($this->data, JSON_PRETTY_PRINT);
			//echo json_encode($this->data['response'], JSON_PRETTY_PRINT);
			die;
//		}
	}



}


?>
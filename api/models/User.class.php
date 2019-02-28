<?php
require('../models/Core.class.php');

Class User extends Core {
//	public $id;
//	public $created;
//	public $modified;

	public $login;
	public $password;
	public $firstname;
	public $lastname;

	private $token; // comme il est en privé, le mapping lors du fetchall() le rendra non visible

	// public $errors = [];

	// recupere les données d'un user par Id
	public function getById($id = null) {
		if (!is_null($id)) {
			$dbh = Connection::get();
			$stmt = $dbh->prepare("select * from users where id = :id limit 1");
			$stmt->execute(array(
				':id' => $id
			));
			// recupere les users et fout le resultat dans une variable sous forme de tableau de tableaux
			$stmt->setFetchMode(PDO::FETCH_CLASS, 'User');
			$user = $stmt->fetch();

			$this->id = $user->id;
			$this->login = $user->login;
			$this->password = $user->password;
			$this->firstname = $user->firstname;
			$this->lastname = $user->lastname;
		}
		return $this;
	}
	// recup tous les users ?
	public function findAll() {
		$dbh = Connection::get();
		$stmt = $dbh->query("select * from users");
		// recupere les users et fout le resultat dans une variable sous forme de tableau de tableaux
		$users = $stmt->fetchAll(PDO::FETCH_CLASS, 'User');
		return $users;
	}
	// recup un user par son token si il a moins de 24h
	public function getByToken($token) {
		$dbh = Connection::get();
		$stmt = $dbh->prepare("select * from users where token = :token and DATE_ADD(modified, interval 24 hour) > CURRENT_TIMESTAMP limit 1");
		$stmt->execute(array(
			':token' => $token
		));
		// recupere les users et fout le resultat dans une variable sous forme de tableau de tableaux
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'User');
		if ($user = $stmt->fetch()) {
			$this->id = $user->id;
			$this->login = $user->login;
			$this->password = $user->password;
			$this->firstname = $user->firstname;
			$this->lastname = $user->lastname;
		}
		return $this;
	}
	//validation des champs fournis
	public function validate($data) {
		$this->errors = [];

		/* required fields */
		if (!isset($data['login'])) {
			$this->errors[] = 'champ login vide';
		}
		if (!isset($data['password'])) {
			$this->errors[] = 'champ password vide';
		}
		/* tests de formats */
		if (isset($data['login'])) {

			if (empty($data['login'])) {
				$this->errors[] = 'champ login vide';
				// si name > 50 chars
			} else if (mb_strlen($data['login']) > 45) {
				$this->errors[] = 'champ login trop long (45max)';
			}
		}

		if (isset($data['password'])) {
			if (empty($data['password'])) {
				$this->errors[] = 'champ password vide';
				// si name > 50 chars
			} else if (mb_strlen($data['password']) < 8) {
				$this->errors[] = 'champ password trop court (8 min)';
			} else if (mb_strlen($data['password']) > 20) {
				$this->errors[] = 'champ password trop long (20 max)';
			}
		}

		if (isset($data['firstname'])) {
			if (empty($data['firstname'])) {
				$this->errors[] = 'champ firstname vide';
				// si name > 50 chars
			} else if (mb_strlen($data['firstname']) < 2) {
				$this->errors[] = 'champ firstname trop court (8 min)';
			} else if (mb_strlen($data['firstname']) > 45) {
				$this->errors[] = 'champ firstname trop long (20 max)';
			}
		}
		if (isset($data['lastname'])) {
			if (empty($data['lastname'])) {
				$this->errors[] = 'champ lastname vide';
				// si name > 50 chars
			} else if (mb_strlen($data['lastname']) < 2) {
				$this->errors[] = 'champ lastname trop court (8 min)';
			} else if (mb_strlen($data['lastname']) > 45) {
				$this->errors[] = 'champ lastname trop long (20 max)';
			}
		}

		if (count($this->errors) > 0) {
			return false;
		}
		return true;
	}
	// check duplicate
	private function loginExists($login = null) {
		if (!is_null($login)) {

			$dbh = Connection::get();
			$sql = "select count(id) from users where login = :login";
			$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array(
				':login' => $login
			));
			if ($sth->fetchColumn() > 0) {
				$this->errors[] = 'login deja pris blaireau';
				return true;
			}
		}
		return false;

	}

	// create or update
	public function save($data) {
		if ($this->validate($data)) {
			$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
			/* syntaxe avec preparedStatements */
			$dbh = Connection::get();

			// by default prepare to insert
			$sql = "insert into users (login, password, firstname, lastname) values (:login, :password , :firstname, :lastname)";

			if (isset($data['id']) && !empty($data['id'])) {
				// update
				$sql = "update users set login = :login, password = :password, firstname = :firstname, lastname = :lastname where id = :id";
			} elseif ($this->loginExists($data['login'])) {
				return false;
			}

			$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			if ($sth->execute(array(
				':login' => $data['login'],
				':password' => $hashedPassword,
				':firstname' => $data['firstname'],
				':lastname' => $data['lastname'],
				':id' => $data['id']
			))) {
				return true;
			} else {
				// ERROR
				// put errors in $session
				$this->errors['pas reussi a creer/modifier le user'];
			}
		}
		return false;
	}
	// login avec retour de token
	public function login($data) {
		if ($this->validate($data)) {
			$dbh = Connection::get();
			$sql = "select id, password from users where login = :login limit 1";
			$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth->execute(array(
				':login' => $data['login']
			));
			$sth->setFetchMode(PDO::FETCH_CLASS, 'User');
			$currentUser = $sth->fetch();
			$storedPassword = $currentUser->password;
			$currentUserId = $currentUser->id;
			if (password_verify($data['password'], $storedPassword)) {

				return $this->generateTokenForUser($currentUserId);

			} else {
				// ERROR
				$this->errors[] = 'CASSE TOI !';
			}
		}
		return false;
	}
	// creation d'un token et sauvegarde, retourne un token ou false
	public function generateTokenForUser($id) {
		$token = bin2hex(random_bytes(36));
		$sql = "update users set token = :token where id = :id";
		$dbh = Connection::get();
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		if ($sth->execute(array(
			':token' => $token,
			':id' => $id
		))) {
			return $token;
		} else {
			// ERROR
			$this->errors['pas reussi a generer le token'];
			return false;
		}
	}


}
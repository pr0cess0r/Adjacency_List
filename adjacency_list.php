<?php
/**
 * Class for working with wood Adjacency List
 * see down sql 'show create table'
 * 
 * @author Alexander Kapliy <prcssr@gmail.com>
 */
class AdjacencyList {
	
	protected $tbl;
	protected $dbh;
	
	public function __construct($tbl) {
		$this->tbl = $tbl;
		
		try {
			$host = 'localhost';
			$dbname = 'adjacency_list';
			$user = 'adjacency_list';
			$passw = 'adjacency_list';
			$this->dbh = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $passw);
		}
		catch(PDOException $e) {
			die($e->getMessage());
		}
	}
	
	/**
	 * Create Tree
	 * @param string $name
	 */
	public function create($name = NULL) {
		$this->dbh->exec('DELETE FROM ' . $this->tbl);
		$this->dbh->exec('ALTER TABLE ' . $this->tbl . ' AUTO_INCREMENT = 1');
		
		$sql = '
			INSERT 
			INTO ' . $this->tbl . '(name) 
			VALUES(:name)';
		
		$STH = $this->dbh->prepare($sql);
		$STH->bindParam(':name', $name);
		return $STH->execute();
	}
	
	/**
	 * Node add
	 * @param int $id
	 * @param string $name
	 * @return boolean
	 */
	public function add($id, $name = NULL) {
		$node = $this->get($id);

		if(!$node)
			return FALSE;
		
		$sql = 'INSERT INTO ' . $this->tbl . ' 
			VALUES(NULL, :pid, :name)';
		$STH = $this->dbh->prepare($sql);
		$STH->bindParam(':pid', $node['id'], PDO::PARAM_INT);
		$STH->bindParam(':name', $name);
		$STH->execute();
		return $this->dbh->lastInsertId();
	}
	
	/**
	 * Node delete
	 * @param int $id
	 * @return boolean
	 */
	public function del($id) {
		$node = $this->get($id);

		if(!$node)
			return FALSE;
		
		$sql = 'DELETE FROM '.$this->tbl.' 
			WHERE id = :id';
		$sth = $this->dbh->prepare($sql);
		$sth->bindParam('id', $id, PDO::PARAM_INT);
		return $sth->execute();
	}
	
	/**
	 * Move node
	 * @param int $id
	 * @param int $pid
	 */
	public function move($id, $pid) {
		$node = $this->get($id);

		if(!$node)
			return FALSE;
		
		$sql = 'UPDATE '.$this->tbl.' 
			SET pid = :pid 
			WHERE id = :id';
		$sth = $this->dbh->prepare($sql);
		$sth->bindParam('id', $id, PDO::PARAM_INT);
		$sth->bindParam('pid', $pid, PDO::PARAM_INT);
		return $sth->execute();
	}
	
	/**
	 * Edit node
	 * @param int $id
	 * @param string $name
	 * @return boolean
	 */
	public function edit($id, $name) {
		$node = $this->get($id);

		if(!$node)
			return FALSE;

		$sql = 'UPDATE ' . $this->tbl.' 
			SET name = :name 
			WHERE id = :id';
		
		$sth = $this->dbh->prepare($sql);
		$sth->bindParam('id', $id, PDO::PARAM_INT);
		$sth->bindParam('name', $name);
		return $sth->execute();
	}
	
	/**
	 * Get node
	 * @param int $id
	 * @return array
	 */
	public function get($id) {
		$sql = 'SELECT * 
			FROM ' . $this->tbl . ' 
			WHERE id = :id';
		$sth = $this->dbh->prepare($sql);
		$sth->bindParam('id', $id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Build array rows: id, pid, name, level
	 * @param array $cats
	 * @param int $parent_id
	 * @param int $level
	 * @return array
	 */
	protected static function build_ar_ns($cats, $parent_id, $level)
	{
		$c = array();
		if(isset($cats[$parent_id])) {
			foreach($cats[$parent_id] as $item)	{
				$item['level'] = $level;
				$c[] = $item;
				$c = array_merge($c, self::build_ar_ns($cats, $item['id'], $level + 1));
			}
		}
		return $c;
	}
	
	/**
	 * Get Tree
	 * @param int $pid
	 * @return array
	 */
	public function tree($pid = 1) {
		$sql = 'SELECT * 
			FROM ' . $this->tbl . '
			WHERE id > 1';
		$sth = $this->dbh->query($sql);
		$result = array();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
			$result[$row['pid']][] = $row;
		
		return self::build_ar_ns($result, $pid, 0);
	}
	
}

// Example
$al = new AdjacencyList('category');
$al->create();
$al->add(1, 'item');
$al->add(1, 'item2');

print_r($al->tree());

/*
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  CONSTRAINT `category_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/
?>

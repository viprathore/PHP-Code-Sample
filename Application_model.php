<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Application_model extends CI_Model
{
	#Define varaiables.
	private $result = '';
	private $query = '';
	
	public function __construct() {
		parent::__construct();
	}
	
	#Select data from database.
	public function select($fields, $table, $where='', $minLimit=0, $maxLimit=10, $orderBy='') {
		$columns = '';
		$whereArr = array();
		$whereStr = '';
		$this->query = 'SELECT ';
		
		#Check fileds
		if(is_array($fields) && !empty($fields)) {
			foreach($fields as $col) {
				$columns .= $col.',';
			}
			$columns = substr($columns, 0, strlen($columns)-1);
			$this->query .= $columns;
		} else {
			die('Please provide valid fields for SELECT Query!');
		}
		
		#Set table name.
		if(isset($table) && !empty($table)) {
			$this->query .= ' FROM tbl_'. $table;
		} else {
			die('Please provide table name!');
		}
		
		#Set where clause
		if(is_array($where) && !empty($where) && $where != '') {
			$this->query .= ' WHERE ';
			foreach($where as $key => $val) {
				$whereStr .= $key .' = :'. $key. ' AND ';
				$whereArr[':'. $key] = $val;
			}
			$whereStr = substr($whereStr, 0, strlen($whereStr)-5);
			$this->query .= $whereStr;
		}
        #Order by.
        if(isset($orderBy) && !empty($orderBy)){
            $this->query .= ' ORDER BY '. $orderBy;
        }
        #Set limit
		if($maxLimit == 'all' || $maxLimit == 'All') {}
		else {
			$whereArr[':start'] = $minLimit;
			$whereArr[':end'] = $maxLimit;
			
			#Set limit.
			$this->query .= ' LIMIT :start, :end';
		}
		#echo $this->query;die;
		
		#Execute query.
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->db->prepare($this->query);
            $stmt->execute($whereArr);
            if(!$stmt){
                echo "\nError:\n";
                echo '<pre>';print_r($stmt->errorInfo());die;
            }
            $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->result;
        } catch(PDOException $e){
            die('DataBase Error : '. $e->getMessage());
        } catch(Exception $e){
            die('General Error : '. $e->getMessage());
        }
	}
	
	#Select Left Join.
	public function selectLeftJoin($fields=array(), $table1, $table2, $joinColumn, $where='', $minLimit=0, $maxLimit=10) {
		$columns = '';
		$whereArr = array();
		$whereStr = '';
		$this->query = 'SELECT ';
		
		#Check data.
		if(is_array($fields) && !empty($fields)) {
			foreach($fields as $col) {
				$columns .= $col.',';
			}
			$columns = substr($columns, 0, strlen($columns)-1);
			$this->query .= $columns;
		} else {
			die('Please provide a valid data array for select!');
		}
		
		#Set table1 name.
		if(isset($table1) && !empty($table1)) {
			$this->query .= ' FROM tbl_'. $table1;
		} else {
			die('Please provide table1 name!');
		}
		
		#Set Left Join.
		if(!empty($table2) && !empty($joinColumn)) {
			$this->query .= ' LEFT JOIN tbl_'. $table2 .' ON tbl_'. $table1.'.'.$joinColumn.' = tbl_'. $table2 .'.'. $joinColumn;
		} else {
			die('Please provide table2 or join column name!');
		}
		
		#Set where clause
		if(is_array($where) && !empty($where) && $where != '') {
			$this->query .= ' WHERE ';
			foreach($where as $key => $val) {
				$whereStr .= $key .' = :'. $key. ' AND ';
				$whereArr[':'. $key] = $val;
			}
			$whereStr = substr($whereStr, 0, strlen($whereStr)-5);
			$this->query .= $whereStr;
		}
		
		#Check limit.
		if($maxLimit == 'all' || $maxLimit == 'All') {}
		else {
			$whereArr[':start'] = $minLimit;
			$whereArr[':end'] = $maxLimit;
			
			#Set limit.
			$this->query .= ' LIMIT :start, :end';
		}
		#echo $this->query;die;
		
		#Execute query.
		$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$stmt = $this->db->prepare($this->query);
		$stmt->execute($whereArr);
		$this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $this->result;
	}
	
	#Insert data into database.
	public function insert($data=array(), $table) {
		$column = '';
		$value = '';
		$values = array();
		$this->query = 'INSERT INTO tbl_'. $table;
		
		#check data
		if(is_array($data) && !empty($data)) {
			$i = 1;
			foreach($data as $key => $val) {
				$column .= $key.',';
				$value .= ':val'.$i.',';
				$values['val'.$i] = $val;
				$i++;
			}
			$column = substr($column, 0, strlen($column)-1);
			$value = substr($value, 0, strlen($value)-1);
			$this->query .= ' ('.$column.') VALUES('.$value.')';
		} else {
			die('Please provide valid data array for insert!');
		}
		
		#Execute query.
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$stmt = $this->db->prepare($this->query);
		$stmt->execute($values);
        if(!$stmt){
            echo "\nError:\n";
            echo '<pre>';print_r($stmt->errorInfo());die;
        }
		$this->result = $this->db->lastInsertId();
		return $this->result;
	}
	
	#Update data from database.
	public function update($data=array(), $table, $where=array()) {
		$column = '';
		$columnVal = array();
		$wheres = '';
		$whereArr = array();
		$this->query = 'UPDATE tbl_'. $table .' SET ';
		
		#check data.
		if(is_array($data) && !empty($data)) {
			foreach($data as $key => $val) {
				$column .= $key. ' = :'.$key.', ';
				$columnVal[':'.$key] = $val;
			}
			$column = substr($column, 0, strlen($column)-2);
			$this->query .= $column;
		} else {
			die('Please provide valid data array for update!');
		}
		
		#Check where.
		if(is_array($where) && !empty($where)) {
			foreach($where as $key => $val) {
				$wheres .= $key .' = :'. $key .' AND ';
				$whereArr[':'.$key] = $val;
			}
			$wheres = substr($wheres, 0, strlen($wheres)-5);
			$this->query .= ' WHERE '. $wheres;
		} else {
			die('Please provide valid where array for update!');
		}
		$dataArr = array_merge($columnVal, $whereArr);
		
		#Execute query.
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$stmt = $this->db->prepare($this->query);
        if(!$stmt){
            echo "\nError:\n";
            echo '<pre>';print_r($stmt->errorInfo());die;
        }
		$this->result = $stmt->execute($dataArr);
		return $this->result;
	}
	
	#Select plan query.
	public function selectQuery($query) {
		#Execute query.
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $stmt = $this->db->prepare($query);
        $stmt ->execute();
        if(!$stmt){
            echo "\nError:\n";
            echo '<pre>';print_r($stmt->errorInfo(), true);die;
        }
        $stmt ->setFetchMode(PDO::FETCH_CLASS, 'Post');
        $this->result = $stmt ->fetchAll();
        return $this->result;
	}
}//End of class.

/* End of application_model.php file */
/* location: application/models/application_model.php */
/* Omit PHP closing tags to help vaoid accidental output */
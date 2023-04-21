<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Common_model extends CI_Model {	

	public function insert($table_name='',  $data=''){
		$query=$this->db->insert($table_name, $data);
		if($query)
			return $this->db->insert_id();
		else
			return FALSE;
	}

	public function get_result($table_name='', $id_array= array(),$columns=array(),$order_by=array(),$limit=''){
		if(!empty($columns)):
			$all_columns = implode(",", $columns);
			$this->db->select($all_columns);
		endif;
		if(!empty($order_by)):
			foreach ($order_by as $key => $value){
				$this->db->order_by($key, $value);
			}
		endif; 
		if(!empty($id_array)):
			foreach ($id_array as $key => $value){
				$this->db->where($key, $value);
			}
		endif;	
		if(!empty($limit)):	
			$this->db->limit($limit);
		endif;	
		$query=$this->db->get($table_name);
		if($query->num_rows()>0)
			return $query->result();
		else
			return FALSE;
	}

	public function get_row($table_name='', $id_array=array(),$columns=array(),$order_by=array()){
		if(!empty($columns)):
			$all_columns = implode(",", $columns);
			$this->db->select($all_columns);
		endif; 
		if(!empty($id_array)):
			foreach ($id_array as $key => $value){
				$this->db->where($key, $value);
			}
		endif;
		if(!empty($order_by)):
			$this->db->order_by($order_by[0], $order_by[1]);
		endif;
		$query=$this->db->get($table_name);
		if($query->num_rows()>0)
			return $query->row();
		else
			return FALSE;
	}

	public function update($table_name='', $data='', $id_array=''){
		if(!empty($id_array)):
			foreach ($id_array as $key => $value){
				$this->db->where($key, $value);
			}
		endif;
		return $this->db->update($table_name, $data);
	}

	public function delete($table_name='', $id_array=''){
	 	return $this->db->delete($table_name, $id_array);
	}

	public function password_check($data=''){
		$query = $this->db->get_where('users',$data);
 		if($query->num_rows()>0)
			return TRUE;
		else{
			return FALSE;
		}
	}

    public function check_data($table_name='', $id_array= array(), $check_array= array(),$columns=array(),$order_by=array(),$limit=''){
		if(!empty($columns)):
			$all_columns = implode(",", $columns);
			$this->db->select($all_columns);
		endif;
		if(!empty($order_by)):
			foreach ($order_by as $key => $value){
				$this->db->order_by($key, $value);
			}
		endif;
		if(!empty($check_array)):
			foreach ($check_array as $key => $value){
				$this->db->where($key, $value);
			}
		endif; 
		if(!empty($id_array)):
			foreach ($id_array as $key => $value){
				$this->db->where("id !=", $value);
			}
		endif;	
		if(!empty($limit)):	
			$this->db->limit($limit);
		endif;
		$query=$this->db->get($table_name);
		if($query->num_rows()>0)
			return $query->result();
		else
			return FALSE;
	}

	public function get_count($table='',$id_array=''){
        foreach ($id_array as $key => $value){
			$this->db->where($key, $value);
		}
		$query = $this->db->get($table);
		return $query->num_rows();
	}

	// Get Details from db
	function getDetails($table='', $where='', $data='*', $query=false){
		if($query!=false){
			$results = $this->db->query($query);
		}elseif($where==''){
			$this->db->select($data);
			$results = $this->db->get($table);
		}else{
			$this->db->select($data);
			$results = $this->db->get_where($table, $where);
		}
		
		if($results->num_rows()>0){
			$dataList = array();
			foreach ($results->result_array() as $row){
				foreach($row as $k=>$v){
					$v = str_replace("\'","'",trim($v));
					$v = str_replace('\"','"',trim($v));
				//	$row[$k] = $v;
					$row[$k] = htmlentities($v);					
				}
				$dataList[] = $row;
			}
			return $dataList;
		}
		return false;
	}
}
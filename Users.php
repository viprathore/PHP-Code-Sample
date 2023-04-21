<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {
    private $administrator;

    public function __construct() {
		parent::__construct();
		#Check account login.
		$this->isAdministrator();
        $this->administrator = $this->session->userdata('administrator');
		$this->load->model('application_model');//Load application model
        $this->load->helper('datatables');//Load DataTables helper
        $this->form_validation->set_error_delimiters('<label class="error">', '</label>');
	}	
	/**
    * Load home page of the controller */
	public function index(){
		#title
		$data['title'] = 'HappyKisan';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
		
		#Load view template.
		$data['template'] = '404';
		$this->load->view('includes/template', $data);
	}
	
	 #Display list all tournament view
    public function usersView() {
        #title
		$data['title'] = 'Shop List View';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.users.js?v='. time());
       #Load view template.
		$data['template'] = 'driver_view';
		$this->load->view('includes/template', $data);
    }
	
	#Coach grid view data from database
    public function shopUsersGridView() {
        /* DataTables column count */
         $aCount = 7;
        
		/* Array of database columns */
		$aColumns = array('u.id', 'u.name', 'u.company_name', 'u.segment', 'u.mobile', 'u.status', 'u.is_deleted');
        
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "id";
		
		/* DB table to use */
		$sTable = "tbl_users as u";
        
        /* Filtering */
        $sWhere = 'WHERE u.is_deleted=0 AND user_type_id =3';
       
		/** Join **/
        $sJoin = '';
        
		/** SQL queries **/
        $result = datatables($aCount, $aColumns, $sIndexColumn, $sTable, $sWhere, $sJoin, 'No');
       # echo '<pre>';print_r($result);die;
        $rResult = $result['rResult'];
        
        /* Page Number */
        $sno = $_GET['iDisplayStart'] + 1;
		
		/** Output **/
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $result['iTotalRecords'],
			"iTotalDisplayRecords" => $result['iTotalDisplayRecords'],
			"aaData" => array()
		);
		#$genderArr = array('M'=>'Male','F'=>'Female');
		foreach($rResult as $aRow){
            $row = array();
			for ( $i=0; $i<$aCount; $i++ ){
                #Extract join column
				$column = strchr($aColumns[$i], '.');
				$column = substr($column, 1, strlen($column));
                
                if($column=='id') {
                    $row[] = $sno;
                } elseif($column=='first_name') {
                    $row[] = $aRow['first_name'];
                }elseif($column=='status') {
                    $btnTitle = $class = '';
                  
                    if($aRow['status']==1) {                        
                        $btnTitle = 'Active';
                        $class = 'btn-success';
						$status = 1;
					}else {
                        $btnTitle = 'Inactive';
                        $class = 'btn-warning';
						$status = 0;
                    } 
                    $row[] = '<button id="userStatusChange" class="btn btn-xs '.$class.'" data-userid="'.$aRow['id'].'" data-status="'. $status .'">'. $btnTitle .'</button>';
                } elseif($column=='is_deleted') {
                    #View button
                    $action = '<div class="action-box"><a href="#" id="viewUser" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-info" title="View"><i class="fa fa-align-justify"></i></a>';
                    #Edit button
                    $action .= '<a href="'. base_url('administrator-driver-section-'. base64_encode($aRow['id'])) .'.html" id="editUser" role="button" class="btn btn-smm btn-outline-warning" title="Edit"><i class="fa fa-pencil-square-o"></i></a>';
                    #Delete button
                    $action .= '<a href="#" id="deleteUser" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></a></div>';
                    
                    $row[] = $action;
                } else {
                    /* General output */
                    $row[] = $aRow[$column];
                }
			}
            $sno++;
			$output['aaData'][] = $row;
		}
		echo json_encode( $output );
    }

    public function driverSection($userId=0){
        $data['title'] = 'Shop Section';
        $data['sidebar'] = 'administrator/includes/sidebar';
        $data['administrator'] = $this->administrator;  
        $data['css'] = array('plugins/bootstrap-datepicker.css');       
        $data['js'] = array('app.users.js?v='. time());
        $data['userId'] = $userId; 
        $userId = base64_decode($userId);
        
        #Get center data.
        #Define form validation rules
        $this->form_validation->set_rules('name', 'Driver Name', 'trim|required');
        //$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required');
        //$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        if($this->form_validation->run()==TRUE) {
            $formData = array();
            $fieldsArr = array('btnSubmit');
            foreach($this->input->post() as $key=>$values) {
                if(!in_array($key, $fieldsArr)){
                    if($key == 'dob'){
                        $formData['dob'] =  $this->commonfunctions->changeDate($values);
                    }elseif($key == 'password'){
                        $password = $this->security->xss_clean($this->input->post('password'));
                        if(!empty($password)){
                            $formData['password'] = $this->commonfunctions->password($password);
                        }
                    }else{
                        $formData[$key] = $values;
                    }
                }
            }
            $formData['updated_by'] = $this->administrator['id'];
            $formData['updated_at'] = $this->commonfunctions->curDateTime();
           
            if($userId==0){
                $formData['created_by'] = $this->administrator['id'];
                $formData['created_at'] = $this->commonfunctions->curDateTime();
                
                $this->application_model->insert($formData, 'users');
                $this->commonfunctions->setFlashMessage('driver information saved successfully', 'success');
            } else {
                $this->application_model->update($formData, 'users', array('id'=>$userId));
                $this->commonfunctions->setFlashMessage('driver information updated successfully', 'success');
            }
            #Redirect to view page.
            redirect('administrator-driver-view', 'refresh');
        }
        
        $userData = $this->application_model->selectQuery('SELECT * FROM tbl_users WHERE is_deleted=0 and  id ='.$userId.'');
        if($userData !=false){
            $data['userArr'] = $userData[0]; 
            $data['cityData'] = $this->application_model->selectQuery('SELECT id,name FROM tbl_city WHERE is_deleted=0 AND state_id = '.$userData[0]['state_id'].'');    
        }
        $data['gender'] = array('M'=>'Male','F'=>'Female');
        $data['stateData'] = $this->application_model->selectQuery('SELECT id,name FROM tbl_states WHERE is_deleted=0');

        #Load view template.
        $data['template'] = 'driver_section';
        $this->load->view('includes/template', $data);   
    }

    public function manageUserStatus($statusId=0) {
        $statusArr = array('Inactive', 'Active');
        $html = 'Select Status:';
        $html .= '<select id="userStatus" name="userStatus" class="form-control">';
        $html .= '<option value="">Select</option>';
        foreach($statusArr as $key=>$value) {
            $selected = ($key==$statusId)?'selected="selected"':'';
            $html .= '<option value="'.$key.'" '. $selected .'>'. $value .'</option>';
        }
        $html .= '</select>';
        echo $html;exit;
    }
    
    public function userStatusChange(){
        #Update User status
        $result = array('status'=>false, 'message'=>'User status can\'t change, Please try again!');
        #Grab user input
        $userId = $this->security->xss_clean($this->input->post('userId'));
        $status = $this->security->xss_clean($this->input->post('status'));
        if(isset($userId) && !empty($userId)){
            $formData = array(
                'status' => $status,
                'updated_by' => $this->administrator['id'],
                'updated_at' => $this->commonfunctions->curDateTime()
            );
            $this->application_model->update($formData, 'users', array('id'=>$userId));
            $result = array('status'=>true, 'message'=>'User status updated successfully');
        }
        echo json_encode($result);exit;
    }

    public function userDetails($id){
        $genderArr = array('M'=>'Male', 'F'=>'Female');
        #Get Coaches data.
        $userData = $this->application_model->select(array('*'), 'users', array('is_deleted'=>0, 'id'=>$id), 0,1);
        $html = '<table class="table table-hover table-outline mb-0">';
        
        if($userData != FALSE){
            $user = $userData[0];
           // $dob = (strtotime($user['dob'])>0)?date('d-m-Y',strtotime($user['dob'])):'';
            $html .= '<tr><td width="40%">Driver Name :</td><td width="60%">'. $user['name'] .'</td></tr>';
            $html .= '<tr><td>ACompany Name :</td><td>'. $user['company_name'] .'</td></tr>';
            $html .= '<tr><td>Segment :</td><td>'. $user['segment'] .'</td></tr>';
           
            $html .= '<tr><td>Mobile :</td><td>'. $user['mobile'] .'</td></tr>';
            $html .= '<tr><td>Address :</td><td>'. $user['address'] .'</td></tr>';
            $status = ($user['status']==0)?'<span class="tag tag-warning">Inactive</span>':'<span class="tag tag-success">Active</span>';  
            $html .= '<tr><td>Status :</td><td>'. $status.'</td></tr>';
        }
        $html .= '</table>';
        echo $html;exit;   
    }

    public function userDelete(){
        #Grab user input.
        $userId = $this->security->xss_clean($this->input->post('userId'));
        #Delete coaches records
        $formData = array(
            'is_deleted' => 1,
            'updated_by' => $this->administrator['id'],
            'updated_at' => $this->commonfunctions->curDateTime()
        );
        $update = $this->application_model->update($formData, 'users', array('id'=>$userId));
        if($update) {
            $result = 'Record deleted successfully.';
        } else {
            $result = 'The User record cannot be deleted, please contact the administrator.';
        }
        echo $result;exit;
    }

    #Get city
    public function getCity(){
        #Grab user input.
        $state_id = $this->input->post('state_id');
        $cityData = $this->application_model->select(array('id, name'), 'city', array('is_deleted'=>0, 'state_id'=>$state_id), 0,'all');
        $districtArr = array();
        $option = '<option value="">Select </option>';
        if($cityData != FALSE){
            foreach($cityData as $rows){
              $option .= '<option value='.$rows['id'].'>'.$rows['name'].'</option>';  
            }
        }
        echo $option;exit;
    }
	
	
	 #Display list all vendor view
    public function vendorView() {
        #title
		$data['title'] = 'Shop List View';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.users.js?v='. time());
       #Load view template.
		$data['template'] = 'vendor_view';
		$this->load->view('includes/template', $data);
    }
	
	#Coach grid view data from database
    public function vendorGridView() {
        /* DataTables column count */
         $aCount = 8;
        
		/* Array of database columns */
		$aColumns = array('u.id', 'u.name', 'u.company_name', 'u.segment', 'u.mobile','u.paid_amount', 'u.status', 'u.is_deleted');
        
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "id";
		
		/* DB table to use */
		$sTable = "tbl_users as u";
        
        /* Filtering */
        $sWhere = 'WHERE u.is_deleted=0 AND user_type_id =2';
       
		/** Join **/
        $sJoin = '';
        
		/** SQL queries **/
        $result = datatables($aCount, $aColumns, $sIndexColumn, $sTable, $sWhere, $sJoin, 'No');
       # echo '<pre>';print_r($result);die;
        $rResult = $result['rResult'];
        
        /* Page Number */
        $sno = $_GET['iDisplayStart'] + 1;
		
		/** Output **/
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $result['iTotalRecords'],
			"iTotalDisplayRecords" => $result['iTotalDisplayRecords'],
			"aaData" => array()
		);
		#$genderArr = array('M'=>'Male','F'=>'Female');
		foreach($rResult as $aRow){
            $row = array();
			for ( $i=0; $i<$aCount; $i++ ){
				
				$amount = $this->_getVendorBalance($aRow['id']);
				$dueAmount = $amount['vendor_price'] -  $aRow['paid_amount'];
                #Extract join column
				$column = strchr($aColumns[$i], '.');
				$column = substr($column, 1, strlen($column));
                
                if($column=='id') {
                    $row[] = $sno;
                } elseif($column=='first_name') {
                    $row[] = $aRow['first_name'];
                }
				elseif($column=='paid_amount') {
					$amount = $this->_getVendorBalance($aRow['id']);
                    $row[] = $amount['vendor_price'];
					$row[] = $aRow['paid_amount'];
					$row[] = $amount['vendor_price'] -  $aRow['paid_amount'];
					
                }
				elseif($column=='status') {
                    $btnTitle = $class = '';
                  
                    if($dueAmount > 0) {                        
                        $btnTitle = 'Due';
                        $class = 'btn-warning';
						$status = 0;
						
					}else {
						$btnTitle = 'Paid';
                        $class = 'btn-success';
						$status = 1; 
                    } 
                    $row[] = '<button id="userStatusChange1" class="btn btn-xs '.$class.'" data-userid="'.$aRow['id'].'" data-status="'. $status .'">'. $btnTitle .'</button>';
                } elseif($column=='is_deleted') {
                    #View button
                    $action = '<a href="#" id="addPaidAmountVendor" role="button" data-productid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-success" title="Add Paid Amount"><i class="fa fa-plus"></i></a></div>';
					
					$action .= '<div class="action-box"><a href="#" id="viewUser" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-info" title="View"><i class="fa fa-align-justify"></i></a>';
                    #Edit button
                    $action .= '<a href="'. base_url('administrator-driver-section-'. base64_encode($aRow['id'])) .'.html" id="editUser" role="button" class="btn btn-smm btn-outline-warning" title="Edit"><i class="fa fa-pencil-square-o"></i></a>';
                    #Delete button
                    $action .= '<a href="#" id="deleteUser" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></a></div>';
                    
                    $row[] = $action;
                } else {
                    /* General output */
                    $row[] = $aRow[$column];
                }
			}
            $sno++;
			$output['aaData'][] = $row;
		}
		echo json_encode( $output );
    }
	
	private function _getVendorBalance($customerId = 0){
		 $userData = $this->application_model->selectQuery('SELECT * FROM tbl_shipping WHERE is_deleted=0 and  customer_id ='.$customerId.'');
		 $vendor_price  = $paid_amount = 0;
		 if($userData !=false){
			 foreach($userData as $row){
				 $vendor_price += $row['vendor_price']; 
				 $paid_amount += $row['paid_amount']; 
				 
			 }
		 }
		 
		 return array('vendor_price'=>$vendor_price,'paid_amount'=>$paid_amount);
		
	}
	
	public function addPaidAmountVendor($cutomerid = 0){
		
		$shippingData = $this->application_model->selectQuery('SELECT * FROM tbl_shipping WHERE is_deleted=0 and  id ='.$cutomerid.'');
		if($shippingData !=false){
			$data['shippingArr'] = ''; //$shippingData[0];   
		}
		$data['cutomerid'] = $cutomerid;
		
		$this->load->view('addPaidAmountModelVendor',$data);
	}
	
	
	public function updatePaidAmountVendor(){
		
		if ($this->input->is_ajax_request()) {
			
			$amountPaid = $this->input->post('amountPaid');
			$cutomerid = $this->input->post('cutomerid');
			
			if(!empty($amountPaid)){
				$shippingData = $this->application_model->selectQuery('update tbl_users  set paid_amount =paid_amount+'.$amountPaid.' WHERE  id ='.$cutomerid.'');
			}
			$response = array("status"=>1,'message'=>'Amount has been paid Successful.');
			
			echo  json_encode($response);
		}
		
		
	}
	
	
}
/* End of users.php file */
/* location: application/view/administrator/users.php */
/* Omit PHP closing tags to help avoid accidental output */
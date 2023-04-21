<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {
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

    #Display list all customers boy view
    public function customersView() {
        #title
        $data['title'] = 'Customers List View';
        $data['sidebar'] = 'administrator/includes/sidebar';
        $data['administrator'] = $this->administrator;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.customers.js?v='. time());
       #Load view template.
        $data['template'] = 'customers_view';
        $this->load->view('includes/template', $data);
    }

    #customers grid view data from database
    public function customersGridView($subscription=0) {
        /* DataTables column count */
         $aCount = 23;
        
        /* Array of database columns */
        $aColumns = array('u.id','u.name', 'u.mobile_no','u.email', 'u.created_at', 'u.plan_category_id','u.plan_id' ,'u.plan_start_date','u.plan_end_date','u.created_by', 'u.plan_status', 'u.last_login_time', 'u.broker_id', 'u.payment_id', 'u.payment_date', 'u.payment_amount','u.gst_amount', 'u.charting_id','u.charting_password', 'u.charting_ip','u.cust_p_l', 'u.status', 'u.is_deleted','p.name as plan_name','cp.name as category_name','br.name as  brokername');
        
        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = "id";
        
        /* DB table to use */
        $sTable = "tbl_customers as u";
        
        /* Filtering */
        $sWhere = 'WHERE u.is_deleted=0 ';
		if(!empty($subscription)){
			if($subscription == 1){
				$sWhere = 'WHERE u.is_deleted=0 AND u.plan_id=1';
			}else{
				$sWhere = 'WHERE u.is_deleted=0 AND u.plan_id !=1';
			}
		}
		/** Join **/
        $sJoin = ' LEFT JOIN tbl_plans as p ON p.id=u.plan_id';
		 $sJoin .= ' LEFT JOIN tbl_plan_category as cp ON cp.id=u.plan_category_id';
		  $sJoin .= ' LEFT JOIN tbl_brokers as br ON br.id=u.broker_id';
        
        /** SQL queries **/
        $result = datatables($aCount, $aColumns, $sIndexColumn, $sTable, $sWhere, $sJoin, 'NO');
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
                } elseif($column=='name') {
                    $row[] = '<a href="'. base_url('index.php/administrator-customer-payment-history-'.$aRow['id']) .'.html">'.$aRow['name'].'<a>';
                }
                 elseif($column=='plan_id') {
                    $row[] = $aRow['plan_name'];
                }

                 elseif($column=='broker_id') {
                    $row[] = $aRow['brokername'];
                }
                elseif($column=='plan_category_id') {
                    $row[] = $aRow['category_name'];
                }
                elseif($column=='created_by') {
                    
                    $row[] = $this->leftday($aRow);
                }
                elseif($column=='plan_status') {
                    if($aRow['plan_status']==1) {                        
                        $plan_status = 'Subscription Active';
                         $class = 'btn-success';
                    }else if($aRow['plan_status']==2){
                         $class = 'btn-danger';
                        $plan_status = 'Subscription Expired';
                    }
					else
					{
						$isdemo = $this->demoPlanExpire($aRow);
						if($isdemo==1){
							$class = 'btn-warning';
							$plan_status = 'Demo Expired';
						}else{
							$class = 'btn-warning';
							$plan_status = 'Demo plan Running';
						}
                    }
                    $row[] = '<button " class="btn btn-xs '.$class.'" data-title="status">'. $plan_status .'</button>';
                }

				
				elseif($column=='status') {
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
                    $row[] = '<button id="boyStatus" class="btn btn-xs '.$class.'" data-userid="'.$aRow['id'].'" data-status="'. $status .'" data-title="status">'. $btnTitle .'</button>';
                } elseif($column=='is_deleted') {
                    #View button
					//$action = '<a href="#" id="addPaidAmountCustomer" role="button" data-productid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-success" title="Add Paid Amount"><i class="fa fa-plus"></i></a></div>';
					
                   // $action = '<div class="action-box"><a href="#" id="viewcustomers" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-info" title="View"><i class="fa fa-align-justify"></i></a>';
                    #Edit button
                    //$action .= '<a href="'. base_url('administrator-customer-section-'. base64_encode($aRow['id'])) .'.html" id="editDeliveryBoy" role="button" class="btn btn-smm btn-outline-warning" title="Edit"><i class="fa fa-pencil-square-o"></i></a>';
                    #Delete button
                    $action = '<a  id="customersDelete" role="button" data-userid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></a></div>';
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
	
	public function demoPlanExpire($row){
		$status = 0;		
		$no = 0;
		$t= 0;
		$start = new DateTime(date("Y-m-d",strtotime($row['created_at'])));
		$end   = new DateTime(date('Y-m-d'));
		$interval = DateInterval::createFromDateString('1 day');
		$period = new DatePeriod($start, $interval, $end);
		foreach ($period as $dt)
		{   
			if ($dt->format('N') == 7 || $dt->format('N') == 6)
			{
				$no++;
			}
			$t++;
		}
		if(!empty($t)){
			$day =  $t-  $no;
			if($day > 3){
				$status = 1;
			}
		}
		
		return $status; 
	}

    private function leftday($row){

        $plan_start_date = strtotime($row['plan_start_date']); // or your date as well
        $plan_end_date = strtotime($row['plan_end_date']);
        $datediff =  $plan_end_date - $plan_start_date ;

        return round($datediff / (60 * 60 * 24));

    }
	
	private function _getCustomerBalance($customerId = 0){
		 $userData = $this->application_model->selectQuery('SELECT * FROM tbl_shipping WHERE is_deleted=0 and  customer_id ='.$customerId.'');
		 $agreement_price  = $paid_amount = 0;
		 if($userData !=false){
			 foreach($userData as $row){
				 $agreement_price += $row['agreement_price']; 
				 $paid_amount += $row['paid_amount']; 
				 
			 }
		 }
		 
		 return array('agreement_price'=>$agreement_price,'paid_amount'=>$paid_amount);
		
	}
	

    public function customersSection($userId=0){ 
        #title
        $data['title'] = 'Customer Section';
        $data['sidebar'] = 'administrator/includes/sidebar';
        $data['administrator'] = $this->administrator;  
        $data['css'] = array('plugins/bootstrap-datepicker.css');       
        $data['js'] = array('app.delivery.js?v='. time());
        $data['userId'] = $userId; 
        $userId = base64_decode($userId);

        #Define form validation rules
        $this->form_validation->set_rules('name', 'Name', 'trim|required');
        //$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required');
        //$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        //$this->form_validation->set_rules('mobile_no', 'Mobile No', 'trim|required');
        if($this->form_validation->run()==TRUE) {
           $formData = array();
           /* if($_FILES['aadhar']['name'] !=""){
                $folder='./uploads/boy_aadhar/';
                $file_ext1 = explode(".",$_FILES["aadhar"]["name"]);
                $file_ext = end($file_ext1);                
                $filenam = strtotime("now").".".$file_ext;
                if(move_uploaded_file($_FILES["aadhar"]["tmp_name"], $folder.$filenam)){
                    $formData['aadhar']=$filenam;
                }   
            }
            if($_FILES['licence_photo']['name'] !=""){
                $folder='./uploads/boy_license/'; 
                $file_ext1 = explode(".",$_FILES["licence_photo"]["name"]);
                $file_ext = end($file_ext1);                
                $filenam = strtotime("now").".".$file_ext;
                if(move_uploaded_file($_FILES["licence_photo"]["tmp_name"], $folder.$filenam)){
                    $formData['licence_photo']=$filenam;
                }   
            } */
            #echo 'hello';die;
            $formDataSch = array();
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
                    }else {
                        $formData[$key] = $values;
                    }
                }
            }
           
            $formDataSch['updated_by'] = $formData['updated_by'] = $this->administrator['id'];
            $formDataSch['updated_by'] = $formData['updated_at'] = $this->commonfunctions->curDateTime();
            
            if($userId==0){
                $formData['created_by'] = $this->administrator['id'];
                $formData['created_at'] = $this->commonfunctions->curDateTime();
                $this->application_model->insert($formData, 'customers');
                $this->commonfunctions->setFlashMessage('Customer information saved successfully', 'success');
            } else {
                $this->application_model->update($formData, 'customers', array('id'=>$userId));
                $this->commonfunctions->setFlashMessage('Customer information updated successfully', 'success');
            }
            #Redirect to view page.
            redirect('administrator-customer-view', 'refresh');
        }
        
        $userData = $this->application_model->selectQuery('SELECT * FROM tbl_customers WHERE is_deleted=0 and  id ='.$userId.'');
        if($userData !=false){
            $data['userArr'] = $userData[0];   
        }
        #Load view template.
        $data['template'] = 'customers_section';
        $this->load->view('includes/template', $data);   
    }

	public function customerStatusChange(){
        #Update User status
        $result = array('status'=>false, 'message'=>'User status can\'t change, Please try again!');
        #Grab user input
        $userId = $this->security->xss_clean($this->input->post('userId'));
        $status = $this->security->xss_clean($this->input->post('Status'));
        if($status==1){
            $status=0;
        }else{
            $status=1; 
        }
        if(isset($userId) && !empty($userId)){
            
            $formData = array(
                'status' => $status,
                'updated_by' => $this->administrator['id'],
                'updated_at' => $this->commonfunctions->curDateTime()
            );
            $this->application_model->update($formData, 'customers', array('id'=>$userId));
            $result = array('status'=>true, 'message'=>'Customer status updated successfully');
        }
        echo json_encode($result);exit;  
    }
	
	public function customerPaymentHistory($customerId = 0){
		
		$data['title'] = 'Customers Payment History';
        $data['sidebar'] = 'administrator/includes/sidebar';
        $data['administrator'] = $this->administrator;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.customers.js?v='. time());
		$data['customerId'] = $customerId;
		$data['customer'] = $this->customersDetailsById($customerId);
       #Load view template.
        $data['template'] = 'customers_payment_view';
        $this->load->view('includes/template', $data);
	}
	 public function customersDetailsById($id){
   
		return $userData = $this->application_model->select(array('*'), 'customers', array('is_deleted'=>0, 'id'=>$id), 0,1);
	 }
	
	#customers grid view data from database
    public function customersHistoryGridView($customerId=0) {
        /* DataTables column count */
         $aCount = 8;
        
        /* Array of database columns */
			$aColumns = array('u.id', 'u.plan_id', 'u.amount', 'u.gst',  'u.razorpay_payment_id','u.category_id' ,'u.plan_start_date','u.plan_end_date','p.name as  planName','cp.name as category');
        
        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = "id";
        
        /* DB table to use */
        $sTable = "tbl_payments as u ";
        
        /* Filtering */
        $sWhere = 'WHERE u.is_deleted=0 AND u.customer_id='.$customerId.'';
       
        /** Join **/
        $sJoin = ' LEFT JOIN tbl_plans as p ON p.id=u.plan_id';
		 $sJoin .= ' LEFT JOIN tbl_plan_category as cp ON cp.id=u.category_id';
        
        /** SQL queries **/
        $result = datatables($aCount, $aColumns, $sIndexColumn, $sTable, $sWhere, $sJoin, 'NO');
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
                } elseif($column=='plan_id') {
                    $row[] = $aRow['planName'];
                }
                elseif($column=='category_id') {
                    $row[] = $aRow['category'];
                }
                else { 
                    /* General output */
                    $row[] = $aRow[$column];
                }
            }
            $sno++;
            $output['aaData'][] = $row;
        }
        echo json_encode( $output );
    }
	

    public function customersDetails($id){
        $genderArr = array('M'=>'Male', 'F'=>'Female');
        $userData = $this->application_model->select(array('*'), 'customers', array('is_deleted'=>0, 'id'=>$id), 0,1);
        $html = '<table class="table table-hover table-outline mb-0">';
        
        if($userData != FALSE){
            $user = $userData[0];
            $html .= '<tr><td width="40%">Name :</td><td width="60%">'. $user['name'] .'</td></tr>';
            $html .= '<tr><td>Company Name :</td><td>'. $user['company_name'] .'</td></tr>';
            $html .= '<tr><td>Segment :</td><td>'. $user['segment'] .'</td></tr>';
            $html .= '<tr><td>Mobile :</td><td>'. $user['mobile_no'] .'</td></tr>';
			
			 $html .= '<tr><td>Address :</td><td>'. $user['address'] .'</td></tr>';
            $status = ($user['status']==0)?'<span class="tag tag-warning">Inactive</span>':'<span class="tag tag-success">Active</span>';  
            $html .= '<tr><td>Status :</td><td>'. $status.'</td></tr>';
        }
        $html .= '</table>';
        echo $html;exit;   
    }

    public function customersDelete(){
        #Grab user input.
        $userId = $this->security->xss_clean($this->input->post('userId'));
        $formData = array(
            'is_deleted' => 1,
            'updated_by' => $this->administrator['id'],
            'updated_at' => $this->commonfunctions->curDateTime()
        );
        $update = $this->application_model->update($formData, 'customers', array('id'=>$userId));
        if($update) {
            $result = 'Record deleted successfully.';
        } else {
            $result = 'The User record cannot be deleted, please contact the administrator.';
        }
        echo $result;exit;
    }
	
	public function addcustomers(){
		$data['template'] = 'model_customers_add';
        $this->load->view('model_customers_add', $data);   
	}
	
	public function customersSave(){
			
		if ($this->input->is_ajax_request()) {
			
			$fieldsArr = array('btnSubmit');
            foreach($this->input->post() as $key=>$values) {
                if(!in_array($key, $fieldsArr)){
                    if($key == 'dob'){
                        $formData['dob'] =  $this->commonfunctions->changeDate($values);
                    }else {
                        $formData[$key] = $values;
                    }
                }
            }
           
            $formDataSch['updated_by'] = $formData['updated_by'] = $this->administrator['id'];
            $formDataSch['updated_by'] = $formData['updated_at'] = $this->commonfunctions->curDateTime();
         
			$formData['created_by'] = $this->administrator['id'];
			$formData['created_at'] = $this->commonfunctions->curDateTime();
			$customerid = $this->application_model->insert($formData, 'customers');
			$response = array('status'=>1,'message'=>'Customer added','name'=>$formData['name'],'customerid'=>$customerid); 
            
		}
		
		echo json_encode($response);
		
	}
	
	public function addvendor(){
		$data['template'] = 'model_vendor_add';
        $this->load->view('model_vendor_add', $data);   
	}
	
	public function vendorSave(){
			
		if ($this->input->is_ajax_request()) {
			
			$fieldsArr = array('btnSubmit');
            foreach($this->input->post() as $key=>$values) {
                if(!in_array($key, $fieldsArr)){
                    if($key == 'dob'){
                        $formData['dob'] =  $this->commonfunctions->changeDate($values);
                    }else {
                        $formData[$key] = $values;
                    }
                }
            }
           
		    $formData['user_type_id'] = 2;
            $formData['updated_by'] = $formData['updated_by'] = $this->administrator['id'];
            $formData['updated_by'] = $formData['updated_at'] = $this->commonfunctions->curDateTime();
         
			$formData['created_by'] = $this->administrator['id'];
			$formData['created_at'] = $this->commonfunctions->curDateTime();
			$customerid = $this->application_model->insert($formData, 'users');
			$response = array('status'=>1,'message'=>'Customer added','name'=>$formData['name'],'customerid'=>$customerid); 
            
		}
		
		echo json_encode($response);
		
	}
	
	
	public function adddriver(){
		$data['template'] = 'model_driver_add';
        $this->load->view('model_driver_add', $data);   
	}
	
	public function driverSave(){
			
		if ($this->input->is_ajax_request()) {
			
			$fieldsArr = array('btnSubmit');
            foreach($this->input->post() as $key=>$values) {
                if(!in_array($key, $fieldsArr)){
                    if($key == 'dob'){
                        $formData['dob'] =  $this->commonfunctions->changeDate($values);
                    }else {
                        $formData[$key] = $values;
                    }
                }
            }  
           
		    $formData['user_type_id'] = 3;
            $formData['updated_by'] = $formData['updated_by'] = $this->administrator['id'];
            $formData['updated_by'] = $formData['updated_at'] = $this->commonfunctions->curDateTime();
         
			$formData['created_by'] = $this->administrator['id'];
			$formData['created_at'] = $this->commonfunctions->curDateTime();
			$customerid = $this->application_model->insert($formData, 'users');
			$response = array('status'=>1,'message'=>'Customer added','name'=>$formData['name'],'customerid'=>$customerid); 
            
		}
		
		echo json_encode($response);
		
	}
	
	public function addPaidAmountCustomer($cutomerid = 0){
		
		$shippingData = $this->application_model->selectQuery('SELECT * FROM tbl_shipping WHERE is_deleted=0 and  id ='.$cutomerid.'');
		if($shippingData !=false){
			$data['shippingArr'] = ''; //$shippingData[0];   
		}
		$data['cutomerid'] = $cutomerid;
		
		$this->load->view('addPaidAmountModelCustomer',$data);
	}
	
	
	public function updatePaidAmountCustomer(){
		
		if ($this->input->is_ajax_request()) {
			
			$amountPaid = $this->input->post('amountPaid');
			$cutomerid = $this->input->post('cutomerid');
			
			if(!empty($amountPaid)){
				$shippingData = $this->application_model->selectQuery('update tbl_customers  set paid_amount =paid_amount+'.$amountPaid.' WHERE  id ='.$cutomerid.'');
			}
			$response = array("status"=>1,'message'=>'Amount has been paid Successful.');
			
			echo  json_encode($response);
		}
		
		
	}
	
	
	
}
/* End of delivery.php file */
/* location: application/view/administrator/delivery.php */
/* Omit PHP closing tags to help avoid accidental output */
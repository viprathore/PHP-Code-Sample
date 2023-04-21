<?php defined('BASEPATH') OR exit('No direct script access allowed');

class LoginCustomer extends CI_Controller
{
	public function __construct() {
		parent::__construct();
		$this->load->model('application_model');//Load application model
		$this->form_validation->set_error_delimiters('<label class="error">', '</label>');
	}
    /**
    * @DateOfCreation   15-08-2018
    * @ShortDescription Load main page of the controller
    */
	public function index($userId = 0){
		$data['title'] = 'Customer Login';
        $data['response'] = 'login';
		$data['loginUrl'] = 'admin';
		#Set form validation rules.
		
		$formData = array();
		$formData['is_deleted'] = 0;
		
		$this->application_model->update($formData, 'customers', array('id'=>$userId));
       
        $sqlQuery = 'SELECT u.id, u.name, u.email, u.mobile_no, u.address, u.status  FROM tbl_customers as u WHERE u.is_deleted=0 AND u.id="'. $userId .'" LIMIT 1';
		
		$userData = $this->application_model->selectQuery($sqlQuery);
        #echo '<pre>';print_r($userData);die;
		if($userData != FALSE) {
			$userArr = $userData[0];
            if($userArr['status']==1) {
                $userArr['today_login'] = $this->commonfunctions->curDateTime();
                #Set value into a session
                $sessionData['admin'] = $userArr;
                $this->session->set_userdata($sessionData);
                redirect(base_url('index.php/customer/dashboard'));
            }
        }

			
       
	}
	
	
	
	
    #login form India
  
	#503 - Error Page
    public function pageNotFound() {
        #Load view template.
        $this->load->view('admin/503');
    }
	#Authenticate user login.
	public function _loginProcess() {
        $result = array('status'=>false, 'data'=>'');
		#grab user input.
        $username = $this->security->xss_clean($this->input->post('username'));
		$password = $this->security->xss_clean($this->input->post('password'));
		$password = $this->commonfunctions->password($password);
        #echo $password;die;
		#Get account data from database
         $sqlQuery = 'SELECT u.id, u.user_type_id, u.user_id, u.first_name, u.last_name, u.dob, u.email, u.gender, u.mobile, u.address, u.status, u.is_first_login, uh.id AS history_id, uh.last_login, uh.expiry_date FROM tbl_users AS u LEFT JOIN tbl_users_history as uh ON uh.tbl_users_id = u.id WHERE u.is_deleted=0 AND u.email="'. $username .'" AND u.password="'. $password .'" AND u.user_type_id=2  LIMIT 1';
		
		$userData = $this->application_model->selectQuery($sqlQuery);
        #echo '<pre>';print_r($userData);die;
		if($userData != FALSE) {
			$userArr = $userData[0];
            if($userArr['status']==1) {
                $userArr['today_login'] = $this->commonfunctions->curDateTime();
                #Set value into a session
                $sessionData['admin'] = $userArr;
                $this->session->set_userdata($sessionData);
                $result = array('status'=>true, 'data'=>$userArr);
            } else {
                $this->commonfunctions->setFlashMessage('Your account is either Deactivated or Suspended, Please contact The  Admin!', 'danger');
            }
		} else {
			$this->commonfunctions->setFlashMessage('Invalid login details, please try again!', 'danger');
		}
        return $result;
	}
	
	
	
	
}
/* End of Login.php file */
/* location: application/view/Login.php */
/* Omit PHP closing tags to help vaoid accidental output */
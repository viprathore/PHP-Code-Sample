<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
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
	public function index(){
		$data['title'] = 'Administrator Login';
        $data['response'] = 'login';
		$data['loginUrl'] = 'administrator';
		#Set form validation rules.
		$this->form_validation->set_rules('username', 'username', 'trim|required');
		$this->form_validation->set_rules('password', 'password', 'trim|required');		
		if($this->form_validation->run() == TRUE) {
            $result = $this->_loginProcess();
			if($result['status']==true) {
                #Redirect to dashboard
                redirect('administrator-dashboard', 'refresh');
			}
		}
        #Load view template.
        $this->load->view('administrator/login', $data);
	}
    #login form India
  
	#503 - Error Page
    public function pageNotFound() {
        #Load view template.
        $this->load->view('administrator/503');
    }
	#Authenticate user login.
	public function _loginProcess() {
        $result = array('status'=>false, 'data'=>'');
		#grab user input.
        $username = $this->security->xss_clean($this->input->post('username'));
		$password = $this->security->xss_clean($this->input->post('password'));
		$password = $this->commonfunctions->password($password);
		#Get account data from database
         $sqlQuery = 'SELECT u.id, u.user_type_id, u.user_id,u.name ,u.first_name, u.company_name,  u.email,  u.mobile, u.address, u.status, u.is_first_login, uh.id AS history_id, uh.last_login, uh.expiry_date FROM tbl_users AS u LEFT JOIN tbl_users_history as uh ON uh.tbl_users_id = u.id WHERE u.is_deleted=0 AND u.email="'. $username .'" AND u.password="'. $password .'" AND u.user_type_id=1  LIMIT 1';
		
		$userData = $this->application_model->selectQuery($sqlQuery);
		if($userData != FALSE) {
			$userArr = $userData[0];
            if($userArr['status']==1) {
                $userArr['today_login'] = $this->commonfunctions->curDateTime();
                #Set value into a session
                $sessionData['administrator'] = $userArr;
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
	
	
	public function forgotPassword(){
		#title
		$data['title'] = 'Administrator Forgot Password';
        $data['response'] = 'Forgot Password';
		$data['loginUrl'] = 'administrator';
		#Set form validation rules.
		$this->form_validation->set_rules('username', 'username', 'trim|required|valid_email');
		if($this->form_validation->run() == TRUE) {
            $result = $this->_forgotPasswordProcess();
			if($result){
				redirect('administrator-forgotpassword', 'refresh');
			}
		}
        #Load view template.
        $this->load->view('administrator/forgotPassword', $data);
	}
	
	
		#Authenticate user login.
	public function _forgotPasswordProcess() {
        $result = array('status'=>false, 'data'=>'');
		#grab user input.
        $username = $this->security->xss_clean($this->input->post('username'));
		#Get account data from database
         $sqlQuery = 'SELECT u.id, u.type_id, u.user_id, u.first_name, u.last_name, u.dob, u.email, u.gender, u.phone_no, u.address, u.status, u.is_first_login, uh.id AS history_id, uh.last_login, uh.expiry_date FROM tbl_users AS u LEFT JOIN tbl_users_history as uh ON uh.tbl_users_id = u.id WHERE u.is_deleted=0 AND u.email="'. $username .'"  AND u.type_id=1  LIMIT 1';
		
		$userData = $this->application_model->selectQuery($sqlQuery);
		if($userData != FALSE) {
			$userData = $userData[0];
			if($userData['status']==1) {
                $result = array('status'=>true, 'data'=>"");
				// send email 
				$password = random_string('alnum', 10);
				$header = 'Password Reset';
				$email = array($userData['email']);
				$subject = 'Password Reset';
				$body = '<p style="font-family:calibri;">Dear '. ucwords($userData['first_name']) .',</p>';
				$body .= '<p style="font-family:calibri;">As per your request, your password has been reset.</p>';
				$body .= '<p style="font-family:calibri;">Your '. SITE_TITLE .' Email Id <strong>'. $userData['email'] .'</strong> <br> your temporary password is <strong>'. $password .'</strong>. <br> Please change your password upon logging into your Account.</p>';
		
				$body .= '<p style="font-family:calibri;">This is an auto generated password. You are advised to change your password as per your convenience.</p>';
				
				$body .= '<p style="font-family:calibri;">Thanks & Regards<br/>';
				$body .= SITE_NAME .'<br/>';
				$body .= '14, 1st Lane Asaka, Tashkent<br/>';
				$body .= 'Uzbekistan-100035.<br/>';
				$body .= '<a href="'. base_url().'" target="_blank">'. base_url() .'</a></p>';
				$this->commonfunctions->sendMail($header, $email, $subject, $body);
				// update password to db
				 
				$formData = array();
				$formData['modified_date'] = $this->commonfunctions->curDateTime();
				$formData['is_first_login'] = 0;
				$formData['password'] = $this->commonfunctions->password($password);
				$formData['original_password'] = $password;				
				$this->application_model->update($formData, 'users', array('id'=>$userData['id']));
								
				$this->commonfunctions->setFlashMessage('Please Check your Email', 'success');
            } else {
                $this->commonfunctions->setFlashMessage('Your account is either Deactivated or Suspended, Please contact The  Admin!', 'danger');
            }
		} else {
			$this->commonfunctions->setFlashMessage('Invalid Email Id, please try again!', 'danger');
		}
        return $result;
	}
	
	
	
	
	
}
/* End of Login.php file */
/* location: application/view/Login.php */
/* Omit PHP closing tags to help vaoid accidental output */
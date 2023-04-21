<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Setting extends CI_Controller {
    private $administrator;
	public function __construct() {
		parent::__construct();
		#Check administrator login.
		$this->isAdministrator();
        $this->administrator = $this->session->userdata('administrator');
		$this->load->model('application_model');//Load application model
        $this->form_validation->set_error_delimiters('<label class="error">', '</label>');
	}	
	/**
    * @DateOfCration    10-09-2018
    * @ShortDescription Home page of the controller
    * @return void
    */
	public function index(){
		#title
		$data['title'] = 'SLTA-administrator Dashboard';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
		
		#Load view template.
		$data['template'] = '404';
		$this->load->view('includes/template', $data);
	}
    /**
    * @DateOfCration    12-09-2018
    * @ShortDescription Change profile login password
    * @return void 
    */
	public function chnagePassword(){
		#title
		$data['title'] = 'SLTA-Change Password';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
        #Load CSS/JS for this page.
        $data['js'] = array('app.setting.js');
        
        #Define form validation rules.
        $this->form_validation->set_rules('password_old', 'old password', 'trim|required');
        $this->form_validation->set_rules('password_new', 'new password', 'trim|required');
        $this->form_validation->set_rules('password_confirm', 'confirm password', 'trim|required');
        if($this->form_validation->run() == TRUE) {
            #Grab user input
            $oldPass = $this->input->post('password_old');
            $oldPass = $this->commonfunctions->password($oldPass);
            $newPass = $this->input->post('password_new');
            $confirmPass = $this->input->post('password_confirm');
            #Get administrator data from database.
            $courtsData = $this->application_model->select(array('count(id) AS total'), 'users', array('is_deleted'=>0, 'id'=>$this->administrator['id'], 'password'=>$oldPass));
            $result = array('status'=>'danger', 'message'=>'Your old password is wrong, please try again!');
            if($courtsData !== FALSE){
                if($courtsData[0]['total']>0){
                    if($newPass === $confirmPass){
                        $formData = array(
                            'password' => $this->commonfunctions->password($newPass),
                            'updated_by' => $this->administrator['id'],
                            'updated_at' => $this->commonfunctions->curDateTime()
                        );
                        $update = $this->application_model->update($formData, 'users', array('id'=>$this->administrator['id'])); 
                        if($update){  
                            #Update password expiry date
                            $sessionArr = $this->administrator;
                            if(isset($sessionArr['history_id']) && !empty($sessionArr['history_id'])) {
                                #Get +3 month before expiry date
                                $expiryDate = date('Y-m-d H:i:s', strtotime('+3 month', strtotime($sessionArr['today_login'])));
                                #Prepare form data
                                $formData = array();
                                $formData['ip_address'] = $this->input->ip_address();
                                $formData['expiry_date'] = $expiryDate;
                                $formData['modify_date'] = $this->commonfunctions->curDateTime();
                                $this->application_model->update($formData, 'users_history', array('id'=>$sessionArr['history_id']));
                                #Re-set session data
                                $sessionArr['expiry_date'] = $expiryDate;
                                $this->session->unset_userdata('administrator');
                                $this->session->set_userdata(array('administrator'=>$sessionArr));
                            }
                            $result = array('status'=>'success', 'message'=>'Your profile password successfully changed.');
                        } else {
                            $result = array('status'=>'danger', 'message'=>'Your profile password does not changed, please contact to our administrator');
                        }
                    } else {
                        $result = array('status'=>'danger', 'message'=>'Your confirm password does not match, please try again!');
                    }
                }
            }
            #Set display message
            $this->commonfunctions->setFlashMessage($result['message'], $result['status']);
        }
		
		#Load view template.
		$data['template'] = 'setting_change_password';
		$this->load->view('includes/template', $data);
	}
    /**
    * @DateOfCration    22-09-2018
    * @ShortDescription User profile management
    * @return void
    */
	public function profile(){
		#title
		$data['title'] = 'SLTA-Profile Management';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
        $data['genderArr'] = array('M'=>'Male', 'F'=>'Female');
        #Load external JS/CSS files.
        $data['css'] = array('plugins/bootstrap-datepicker.css');
        $data['js'] = array('plugins/bootstrap-datepicker.js', 'app.setting.js');
        
        #Define form validation rules
        $this->form_validation->set_rules('first_name', 'first name', 'trim|required');
        $this->form_validation->set_rules('dob', 'date of birth', 'trim|required');
        $this->form_validation->set_rules('gender', 'gender', 'trim|required');
        if($this->form_validation->run() == TRUE) {
            #Prepare form data.
            $formData = array();
            foreach($this->input->post() as $key=>$value) {
                if($key=='dob') {
                    $formData[ $key ] = $this->commonfunctions->changeDate($value, 'Y-m-d');
                } elseif($key=='email'){
                } else {
                    $formData[ $key ] = $value;
                }
            }
            $formData['updated_by'] = $this->administrator['id'];
            $formData['updated_at'] = $this->commonfunctions->curDateTime();
            #echo '<pre>';print_r($formData);die;
            #Update user profile data.
            $this->application_model->update($formData, 'users', array('id'=>$this->administrator['id']));
            $this->commonfunctions->setFlashMessage('Your profile information has been updated successfully.', 'success');
        }
        #Get provinces(region) data from database
        /* $regionData = $this->application_model->selectQuery('SELECT region_id, region FROM tbl_provinces WHERE is_deleted=0 GROUP by region_id ORDER BY region ASC');
        if($regionData != FALSE){
            $data['regionArr'] = $regionData;
        } */
        #Get personal information logged in user.
        $userData = $this->application_model->select(array('id, user_id, first_name, last_name, dob, gender, email,mobile, address,shop_photo,shop_license,aadhar,status, created_at'), 'users', array('is_deleted'=>0, 'id'=>$this->administrator['id']), 0,1);
        if($userData != FALSE) {
            $userData = $userData[0];
            $userData['dob'] = $this->commonfunctions->changeDate($userData['dob'], 'd-m-Y');
            $userData['created_at'] = $this->commonfunctions->changeDate($userData['created_at'], 'd-m-Y H:i A');
            $data['userArr'] = $userData;  
        }
		
		#Load view template.
		$data['template'] = 'setting_profile';
		$this->load->view('includes/template', $data);
	}
    /**
    * @DateOfCration    22-09-2018
    * @ShortDescription Check user duplicate email
    * @return boolean
    */
    public function check_email($email) {
        #Get data from database
		$dataArr = $this->application_model->selectQuery('SELECT COUNT(*) AS total FROM tbl_users WHERE is_deleted=0 AND email="'. $email .'" AND id != '. $this->administrator['id']);
		if($dataArr[0]['total'] > 0) {
			$this->form_validation->set_message('check_email', 'This email was already taken, please try again!');
			return FALSE;
		} else {
			return TRUE;
		}
	}
    /**
    * @DateOfCration    22-09-2018
    * @ShortDescription Get district by region from database
    * @return html
    */
    public function getDistrict() {
        #Grab user input.
        $regionId = $this->input->post('regionId');
        #Get player district data from database
        $districtData = $this->application_model->select(array('id, district'), 'provinces', array('is_deleted'=>0, 'region_id'=>$regionId), 0,'all');
        $option = '<option value="">Select</option>';
        if($districtData != FALSE) {
            foreach($districtData as $rRows) {
                $option .= '<option value="'. $rRows['id'] .'">'. $rRows['district'] .'</option>';
            }
        }
        #Return output
        echo $option;exit;
    }
    /**
    * @DateOfCration    14-10-2018
    * @ShortDescription Load change password form when ever login first time in their zone.
    * @return boolean
    */
    public function firstLoginChangePassword(){
        #Load view template.
        $this->load->view('settings_changepassword_firstlogin');
    }
    /**
    * @DateOfCration    14-10-2018
    * @ShortDescription Update password form when ever login first time in their zone.
    * @return boolean
    */
    public function firstLoginSavePassword(){
        $result = array('status'=>false, 'message'=>'Your old password is wrong, please try again!');
        #Grab user input
        $oldPass = $this->input->post('old_password');
        $oldPass = $this->commonfunctions->password($oldPass);
        $newPass = $this->input->post('new_password');
        $confirmPass = $this->input->post('confirm_password');
        $isExpiry = $this->input->post('is_expiry');
        #Get player data from database.
        $playerData = $this->application_model->select(array('count(id) AS total'), 'users', array('is_deleted'=>0, 'id'=>$this->administrator['id'], 'password'=>$oldPass));
        if($playerData !== FALSE){
            if($playerData[0]['total']>0){
                if($newPass === $confirmPass){
                    $formData = array(
                        'is_first_login' => 1,
                        'password' => $this->commonfunctions->password($newPass),
                        'original_password' => $newPass,
                        'modified_by' => $this->administrator['id'],
                        'modified_date' => $this->commonfunctions->curDateTime()
                    );
                    $update = $this->application_model->update($formData, 'users', array('id'=>$this->administrator['id']));
                    if($update){
                        #Update Expire password
                        if($isExpiry==1) {
                            $sessionArr = $this->administrator;
                            #Get +3 month before expiry date
                            $expiryDate = date('Y-m-d H:i:s', strtotime('+3 month'));
                            $formData = array(
                                'ip_address' => $this->input->ip_address(),
                                'expiry_date' => $expiryDate,
                                'modify_date' => $this->commonfunctions->curDateTime()
                            );
                            #Let's check old records
                            $historyData = $this->application_model->select(array('COUNT(*) AS total'), 'users_history', array('tbl_users_id'=>$this->administrator['id']),0,'all');
                            if($historyData[0]['total']==0) {
                                #Insert record
                                $formData['tbl_users_id'] = $sessionArr['id'];
                                $formData['user_id'] = $sessionArr['user_id'];
                                $formData['user_type'] = $sessionArr['type_id'];
                                $formData['last_login'] = $sessionArr['today_login'];
                                $formData['created_date'] = $this->commonfunctions->curDateTime();
                                $formData['modify_date'] = $this->commonfunctions->curDateTime();
                                $insertId = $this->application_model->insert($formData, 'users_history');
                                $sessionArr['history_id'] = $insertId;
                            } else {
                                $this->application_model->update($formData, 'users_history', array('tbl_users_id'=>$this->administrator['id']));
                            }
                            #Re-set session data
                            $sessionArr['expiry_date'] = $expiryDate;
                            $this->session->unset_userdata('administrator');
                            $this->session->set_userdata(array('administrator'=>$sessionArr));
                        }
                        $result['status'] = true;
                        $result['message'] = 'Your profile password successfully changed.';
                    } else {
                        $result['message'] = 'Your profile password does not changed, please contact to our administrator';
                    }
                } else {
                    $result['message'] = 'Your confirm password does not match, please try again!';
                }
            }
        }
        #Return output
        echo json_encode($result);exit;
    }    
}
/* End of dashboard.php file */
/* location: application/view/administrator/dashboard.php */
/* Omit PHP closing tags to help vaoid accidental output */
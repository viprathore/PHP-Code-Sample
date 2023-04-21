<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    private $administrator;
	public function __construct() {
		parent::__construct();
		#Check administrator login.
		$this->isAdministrator();
        $this->administrator = $this->session->userdata('administrator');
        $this->load->model('application_model');//Load application model
	}
	/**
    * @DateOfCreation    16-07-2020
    * @ShortDescription Home page of the controller
    * @return void
    */
	public function index(){
		#title
		$data['title'] = 'Administrator Dashboard';
        $data['sidebar'] = 'administrator/includes/sidebar';
		$data['administrator'] = $this->administrator;
        $year = date('Y');
        #Load CSS/JS for this page.
        $data['css'] = array('plugins/jquery-confirm.css');
        $data['js'] = array('chart.min.js', 'app.chart.js', 'plugins/jquery-confirm.js', 'app.setting.js');
        
        #Let's check first login.
        $isFirstLogin = $isExpirePass = 0;//False-Already change password
        $userData = $this->application_model->select(array('COUNT(*) AS total'), 'users', array('is_deleted'=>0, 'is_first_login'=>0, 'user_id'=>$this->administrator['user_id']), 0,1);
        if($userData[0]['total']==1) {
            $isFirstLogin = 1;//True-change password
            //$this->_updateLastLogin();
        } else {
            #Check password expiry
            $expiryDate = $this->administrator['expiry_date'];
            $todayLogin = $this->administrator['today_login'];
            if(strtotime($todayLogin)>=strtotime($expiryDate)) {
                $isExpirePass = 1;//True-change password
            }
        }
        $data['isExpirePass'] = $isExpirePass;
        $data['isFirstLogin'] = $isFirstLogin;
        $data['year'] = $year;
        $data['totalCount'] = $this->_getTotalCount();

		#Load view template.
		$data['template'] = 'dashboard';
		$this->load->view('includes/template', $data);
	}
    #Get total count data from database
    private function _getTotalCount() {
        #Get register player count.
        $totalShopData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0, 'user_type_id'=>2), 0,'all');
        $totalActiveShopData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0,'status'=>1, 'user_type_id'=>2), 0,'all');

        $totalUserData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0), 0,'all');
        $totalActiveUserData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0,'status'=>1), 0,'all');

        $totalDeliveryUserData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0), 0,'all');
        $totalActiveDeliveryUserData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0,'status'=>1), 0,'all');

        $totalCompanyData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0), 0,'all');
        $totalActiveCompanyData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0,'status'=>1), 0,'all');

        $totalProductData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0), 0,'all');
        $totalActiveProductData = $this->application_model->select(array('status'), 'users', array('is_deleted'=>0,'status'=>1), 0,'all');

        $totalCount = array(
            'shop_total' => count($totalShopData),
            'shop_total_active' => count($totalActiveShopData),
            'user_total' => count($totalUserData),
            'user_total_active' => count($totalActiveUserData),
            'delivery_user_total' => count($totalDeliveryUserData),
            'delivery_user_total_active' => count($totalActiveDeliveryUserData),
            'company_total' => count($totalCompanyData),
            'company_total_active' => count($totalActiveCompanyData),
            'product_total' => count($totalProductData),
            'product_total_active' => count($totalActiveProductData)
        );
        return $totalCount;
    }
    
	/**
    * @DateOfCration    10-09-2018
    * @ShortDescription Logout from your zone
    * @return void 
    */
	public function logout() {
		if(!empty($this->administrator)) {
            #Update last login time
            $this->_updateLastLogin();
            #Unset session data and redirect to home page
			$this->session->unset_userdata('administrator');
			redirect('administrator', 'refresh');
		}
	}
    #Update last login information into database
    private function _updateLastLogin() {
        #Get user session data
        $sessionArr = $this->administrator;
        #Prepare form data
        $formData = array();
        $formData['ip_address'] = $this->input->ip_address();
        $formData['last_login'] = $sessionArr['today_login'];
        $formData['modify_date'] = $this->commonfunctions->curDateTime();
        if(isset($sessionArr['history_id']) && !empty($sessionArr['history_id'])) {
            #Update record
            $this->application_model->update($formData, 'users_history', array('id'=>$sessionArr['history_id']));
        } else {
            #Get +3 month before expiry date
            $expiryDate = date('Y-m-d H:i:s', strtotime('+3 month', strtotime($sessionArr['today_login'])));
            #Insert record
            $formData['tbl_users_id'] = $sessionArr['id'];
            $formData['user_id'] = $sessionArr['user_id'];
            $formData['user_type'] = $sessionArr['type_id'];
            $formData['expiry_date'] = $expiryDate;
            $formData['created_date'] = $this->commonfunctions->curDateTime();
            $this->application_model->insert($formData, 'users_history');
        }
    }
}
/* End of dashboard.php file */
/* location: application/view/administrator/dashboard.php */
/* Omit PHP closing tags to help avoid accidental output */
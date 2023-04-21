<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Getdata extends CI_Controller {
    private $admin;
    private $alice_url;
    private $alice_tokan;
	public function __construct() {
		parent::__construct();
		#Check admin login.
		#$this->isAdmin();
        $this->admin = $this->session->userdata('admin');
        $this->load->model('application_model');//Load application model
        $this->alice_url = 'https://ant.aliceblueonline.com';
        $customerData = $this->application_model->selectQuery('SELECT * FROM tbl_customers WHERE id=1');
        if($customerData !=false){
            $customerData = $customerData[0];                
            $this->alice_tokan = $customerData['token'];
        }

		/*AB053908
google@1a
robo*/

	

  
		
	}
	
	public function index(){
		#title
		$data['title'] = 'admin Dashboard';
        $data['sidebar'] = 'customer/includes/sidebar';
		
		
		#Load view template.
		$data['template'] = 'dashboard';
		$this->load->view('includes/template', $data);
	}

   

    public function chartingTradingSignal(){
		
		#echo $sss = base64_encode("asdf"); 
		print_r($_REQUEST); die;
		print_r(base64_decode($_REQUEST['id']));

		//$formData['name'] = json_encode($_REQUEST);
		$getData = $_REQUEST;
		if(!empty($getData)){
			$data = $getData['chatVala1d380dffb1'];
			$formData['name'] = $getData['chatVala1d380dffb1'];
			$form_data= explode("|",$data);
			$formData['symbol_name'] = $form_data[0]; 
			$formData['current_price'] = $form_data[1]; 
			$formData['trend'] = $form_data[2];
			$formData['entry_price'] = $form_data[3];	
			$formData['stop_loss'] = $form_data[4];	
			$formData['target'] = $form_data[5];
			$formData['day_high'] = $form_data[6];
			$formData['day_low'] = $form_data[7];
			$formData['status'] = $form_data[8];
			$formData['date_time'] = $form_data[9];
			$formData['exchange'] = $form_data[10];
			$formData['create_date'] = date('Y-m-d');		
			
		}
		 $checkexist = $this->application_model->selectQuery('SELECT * FROM tbl_signals WHERE symbol_name="'.$formData['symbol_name'].'" AND  trend="'.$formData['trend'].'" AND create_date="'.date('Y-m-d').'" ');
		 if($checkexist ==false)
		 {
			//$this->application_model->insert($formData, 'signals');
		 }
		
       # echo $this->input->get("chatVala1d380dffb1");
		
        die;
    }
	
}
/* End of dashboard.php file */
/* location: application/view/admin/dashboard.php */
/* Omit PHP closing tags to help avoid accidental output */
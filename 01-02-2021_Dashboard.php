<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    private $admin;
    private $alice_url;
    private $alice_tokan;
    private $customer_id;
    
	public function __construct() {
		parent::__construct();
		#Check admin login.
		$this->isAdmin();
        $this->admin = $this->session->userdata('admin');
        $this->load->model('application_model');//Load application model
        $this->alice_url = 'https://ant.aliceblueonline.com';
        $customerData = $this->application_model->selectQuery('SELECT * FROM tbl_customers WHERE id=1');
        if($customerData !=false){
            $customerData = $customerData[0];                
            $this->alice_tokan = $customerData['token'];
            $this->customer_id = $customerData['id'];
        }

		/*AB053908
google@1a
robo*/

	#$res =  $this->insertOrderDb();

	//$this->getTrades();
	
	//$this->getTrades('RELIANCE','NFO');
		
	}
	/**
    * @DateOfCration    10-09-2018
    * @ShortDescription Home page of the controller
    * @return void
    */
	public function index(){
		#title
		$data['title'] = 'admin Dashboard';
        $data['sidebar'] = 'customer/includes/sidebar';
		$data['admin'] = $this->admin;
		$data['alice_tokan'] = $this->alice_tokan;
        $year = date('Y');
        #Load CSS/JS for this page.
        $data['css'] = array('jquery-confirm.css');
        $data['js'] = array('jquery-confirm.js','typeahead.bundle.js','json/alice.symbols.JS','app.alice.js?='.time());
       
        $data['userOAuth2State'] = time();

        #Let's check first login.
        $isFirstLogin = $isExpirePass = 0;//False-Already change password
        /*$userData = $this->application_model->select(array('COUNT(*) AS total'), 'users', array('is_deleted'=>0, 'is_first_login'=>0, 'user_id'=>$this->admin['user_id']), 0,1);
        if($userData[0]['total']==1) {
            $isFirstLogin = 1;//True-change password
            //$this->_updateLastLogin();
        } else {
            #Check password expiry
            $expiryDate = $this->admin['expiry_date'];
            $todayLogin = $this->admin['today_login'];
            if(strtotime($todayLogin)>=strtotime($expiryDate)) {
                $isExpirePass = 1;//True-change password
            }
        }
        $data['isExpirePass'] = $isExpirePass;
        $data['isFirstLogin'] = $isFirstLogin;
        $data['year'] = $year;*/
       // $data['totalCount'] = $this->_getTotalCount();

        $data['isAliceLogin'] = $this->getProfile();
		#print_r($data['isAliceLogin'] );	
		
		#Load view template.
		$data['template'] = 'dashboard';
		$this->load->view('includes/template', $data);
	}

    public function getProfile($type=0){
	
        $url = "https://ant.aliceblueonline.com/api/v2/profile";
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json; charset=UTF-8",
            "Authorization: Bearer ".$this->alice_tokan
          ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($response, true);
		if($type==0){
			if($json['status'] =='success'){
				return 1;
			}else{
				return 0;
			}
		}else{
		  return $json;	
		}

    }


    
  
   
   
	/**
    * @DateOfCration    10-09-2018
    * @ShortDescription Logout from your zone
    * @return void 
    */
	public function logout() {
		if(!empty($this->admin)) {
            #Update last login time
           //$this->_updateLastLogin();
            #Unset session data and redirect to home page
			$this->session->unset_userdata('admin');
			redirect(base_url(), 'refresh');
		}
	}
  
	
	public function aliceLogin(){
		#echo $_GET['code']; die;
		
	$curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/oauth2/token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "grant_type=authorization_code&client_id=DEMATADE&client_secret_basic=UJLVW5IMH6UZGMI54U8F34QYGZ2GCFFQ0HG2LAP8IWB8NVUGIWP5P4A02XP6CCKB&code=" .$_GET['code']. "&redirect_uri=https%3A//dematadesolution.com/demate/index.php/customer/dashboard/aliceLogin",
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/x-www-form-urlencoded",
        "Authorization: Basic ". base64_encode("DEMATADE:UJLVW5IMH6UZGMI54U8F34QYGZ2GCFFQ0HG2LAP8IWB8NVUGIWP5P4A02XP6CCKB")
      ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
	 $json = json_decode($response, true);	
	
     if (!empty($json['access_token'])) {
         $formData['token'] = $json['access_token'];
         $formData['updated_at'] = $this->commonfunctions->curDateTime();
         $formData['expires_in'] = $json['expires_in'];
         #echo  $this->admin['id'];
         $this->application_model->update($formData, 'customers', array('id'=> 1)); 
         $this->commonfunctions->setFlashMessage('Login Alice Successfully', 'success');
         redirect(base_url('index.php/customer/dashboard'));
     }else{

         $this->commonfunctions->setFlashMessage('tokan .', 'danger');
         redirect(base_url('index.php/customer/dashboard'));
     }
		
	}

    public function placeOrder()
    {
        $tokan = $this->alice_tokan;
		
		$exchange = $this->input->post('segment');

		$symbol_name = $this->input->post('symbol_name');
		$qty = $this->input->post('qty');
		$order_type = $this->input->post('order_type');
        $trend_type = $this->input->post('trend_type');
        $stop_loss = $this->input->post('stop_loss');
        $tralling_step = $this->input->post('tralling_step');
        $target = $this->input->post('target');
        $current_price = $this->input->post('current_price');
		$oms_order_id = $this->input->post('oms_order_id');
		$leg_order_indicator = $this->input->post('leg_order_indicator');
		$oder_run_type = $this->input->post('oder_run_type');
		$symbol = explode("/", $symbol_name);
        $isorderExist = $this->checkExistOrder($exchange,$symbol[1]);
		if($isorderExist==0){
		
			if($oder_run_type==0){
				if($order_type=='BO' && !empty($oms_order_id) && !empty($leg_order_indicator)){
					 $this->orderDeleteBO($oms_order_id,$leg_order_indicator);
				}else if($order_type=='CO' && !empty($oms_order_id) && !empty($leg_order_indicator)){
					$this->orderDeleteCO($oms_order_id,$leg_order_indicator);
				}
				else{

					$symbol = explode("/", $symbol_name);
					$order = $this->fireOrder($tokan, '', $exchange, $order_type, '', trim($symbol[1]), $trend_type, $qty, $stop_loss,$tralling_step,$target,$current_price,$symbol_name,$oder_run_type);
					$orderArr = json_decode($order);
					#print_r($orderArr);
					$json_response = array('status'=>0,'history'=>'','message'=>'');   
					if(!empty($orderArr)){
						if($orderArr->status=='success'){
							//$histry = $this->getOrderHistory($tokan,$orderArr->data->oms_order_id);
							$json_response = array('status'=>1,'history'=>'','message'=>$orderArr->message);      
							
						}else{
							
						}
					}
					echo json_encode($json_response);
				}
			}else{
				$symbol = explode("/", $symbol_name);
				$this->insertOrderDb($broker_id=1,$exchange,$symbol_name,$qty,$order_type,$current_price,$trend_type,$entry_price=0,$stop_loss,$tralling_step,$target,$oder_run_type,$order_created_type=0,$symbol[1],$oms_order_id=0);
				
				$json_response = array('status'=>1,'history'=>'','message'=>'Auto Order has been Placed Successfully.');  
				echo json_encode($json_response);
			}
		}else{
			echo json_encode(array('status'=>1,'message'=>'Record Allready Exist.')); 	
		}
        
		
    }

  	public function checkExistOrder($segment,$symbol_tokan){
		
		$customer_id = $this->customer_id;
        $sql ='SELECT id  FROM tbl_customer_trade WHERE customer_id='.$customer_id.' AND segment="'.$segment.'" AND symbol_tokan ='.$symbol_tokan.' AND is_deleted=0 AND auto_trade_status=1 AND trade_type=1';
        $exist = $this->application_model->selectQuery($sql);
        if($exist !=false){
        	return 1;
        }else{
        	return 0;	
        }	
		

	}

	
	function fireOrder($code, $br='', $ex, $pr, $sy='', $sym, $ord, $qu,$stop_loss,$tralling_step,$target,$current_price,$symbol_name,$auto_trade_status,$orderstatus='') {
  
   // price shoud be add 25 buffer 
	$dtFire01 = microtime();
  	if($pr=='BO'){
		if($ord=='BUY'){	
			$stop_loss =  $current_price - $stop_loss; 
			$target =    $target - $current_price; 
			
			$current_price = $current_price+25;
		}else{
			$stop_loss =  $stop_loss - $current_price; 
			$target =   $current_price - $target; 
			$current_price = $current_price-25;
			
		}
		
		if(empty($tralling_step)){
			$tralling_step = 0;
		}
		
		#echo "{\"exchange\": \"" .$ex. "\",\r\n\"order_type\": \"LIMIT\",\r\n\"instrument_token\": " .$sym. ",\r\n\"quantity\": " .$qu. ",\r\n\"disclosed_quantity\": 0,\r\n\"price\": " .$current_price. ",\r\n\"transaction_type\": \"" .$ord. "\",\r\n\"square_off_value\": " .$target.",\r\n\"validity\": \"DAY\",\r\n\"trailing_stop_loss\":  ".$tralling_step. ",\r\n\"stop_loss_value\": ".$stop_loss.",\r\n\"product\": \"" .$pr. "\",\r\n\"source\": \"web\"\r\n,\r\n\"order_tag\": \"order1\"\r\n}";
		
  		$crl = curl_init();
	    curl_setopt_array($crl, array(
	      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/bracketorder",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 0,
	      CURLOPT_FOLLOWLOCATION => true,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS =>"{\"exchange\": \"" .$ex. "\",\r\n\"order_type\": \"LIMIT\",\r\n\"instrument_token\": " .$sym. ",\r\n\"quantity\": " .$qu. ",\r\n\"disclosed_quantity\": 0,\r\n\"price\": " .$current_price. ",\r\n\"transaction_type\": \"" .$ord. "\",\r\n\"square_off_value\": " .$target.",\r\n\"validity\": \"DAY\",\r\n\"trailing_stop_loss\":  ".$tralling_step. ",\r\n\"stop_loss_value\": ".$stop_loss.",\r\n\"product\": \"" .$pr. "\",\r\n\"source\": \"web\"\r\n,\r\n\"order_tag\": \"order1\"\r\n}",
	      CURLOPT_HTTPHEADER => array(
	        "Content-Type: application/json; charset=UTF-8",
	        "Authorization: Bearer ".$code
	      ),
	    ));
		$order_type='LIMIT';
	    $response = curl_exec($crl);
 	 }else if($pr=='CO'){
		
 	 	 $crl = curl_init();
	    curl_setopt_array($crl, array(
	      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/order",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 0,
	      CURLOPT_FOLLOWLOCATION => true,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS =>"{\"exchange\": \"" .$ex. "\",\r\n\"order_type\": \"MARKET\",\r\n\"instrument_token\": " .$sym. ",\r\n\"quantity\": " .$qu. ",\r\n\"disclosed_quantity\": 0,\r\n\"price\": 0,\r\n\"transaction_type\": \"" .$ord. "\",\r\n\"trigger_price\": " .$stop_loss. ",\r\n\"validity\": \"DAY\",\r\n\"product\": \"" .$pr. "\",\r\n\"source\": \"web\"\r\n}",
	      CURLOPT_HTTPHEADER => array(
	        "Content-Type: application/json; charset=UTF-8",
	        "Authorization: Bearer ".$code
	      ),
	    ));
		$order_type='MARKET';
	    $response = curl_exec($crl);

  	 }
  	else{
		
		echo "{\"exchange\": \"" .$ex. "\",\r\n\"order_type\": \"MARKET\",\r\n\"instrument_token\": " .$sym. ",\r\n\"quantity\": " .$qu. ",\r\n\"disclosed_quantity\": 0,\r\n\"price\": 0,\r\n\"transaction_type\": \"" .$ord. "\",\r\n\"trigger_price\": 0,\r\n\"validity\": \"DAY\",\r\n\"product\": \"" .$pr. "\",\r\n\"source\": \"web\"\r\n}";
		
		
	    $crl = curl_init();
	    curl_setopt_array($crl, array(
	      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/order",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 0,
	      CURLOPT_FOLLOWLOCATION => true,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS =>"{\"exchange\": \"" .$ex. "\",\r\n\"order_type\": \"MARKET\",\r\n\"instrument_token\": " .$sym. ",\r\n\"quantity\": " .$qu. ",\r\n\"disclosed_quantity\": 0,\r\n\"price\": 0,\r\n\"transaction_type\": \"" .$ord. "\",\r\n\"trigger_price\": 0,\r\n\"validity\": \"DAY\",\r\n\"product\": \"" .$pr. "\",\r\n\"source\": \"web\"\r\n}",
	      CURLOPT_HTTPHEADER => array(
	        "Content-Type: application/json; charset=UTF-8",
	        "Authorization: Bearer ".$code
	      ),
	    ));
		
	    $response = curl_exec($crl);
		$order_type='MARKET';
	}
    $ors = json_decode($response);
	echo "<pre>"; print_r($ors);
	$oms_order_id = 0;
	if(!empty($ors) && $ors->status=='success'){
		$oms_order_id = $ors->data->oms_order_id;
	}
		
	$this->insertOrderDb($broker_id=1,$ex,$symbol_name,$qu,$pr,$current_price,$ord,$entry_price=0,$stop_loss,$tralling_step,$target,$auto_trade_status,$order_created_type=0,$sym,$oms_order_id,$orderstatus);
	
    $dtFire02 = microtime();
    curl_close($crl);
    return $response;

	}
	
	function orderDeleteBO($oms_order_id,$leg_order_indicator){
		
		$tokan = $this->alice_tokan;
		
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://ant.aliceblueonline.com/api/v2/bracketorder?oms_order_id='.$oms_order_id.'&leg_order_indicator='.$leg_order_indicator.'&order_status=open',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'DELETE',
		  CURLOPT_HTTPHEADER => array(
	        "Content-Type: application/json; charset=UTF-8",
	        "Authorization: Bearer ".$tokan
	      ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;
		
	}

    function orderDeleteCO($oms_order_id,$leg_order_indicator){
		
		$tokan =$this->alice_tokan;
		
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://ant.aliceblueonline.com/api/v2/coverorder?oms_order_id='.$oms_order_id.'&leg_order_indicator='.$leg_order_indicator.'&order_status=open',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'DELETE',
		  CURLOPT_HTTPHEADER => array(
	        "Content-Type: application/json; charset=UTF-8",
	        "Authorization: Bearer ".$tokan
	      ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;
		
	}
	
	public function  insertOrderDb($broker_id=1,$segment='',$symbol_name='',$qty=0,$order_type='',$current_price=0,$trend='',$entry_price =0,$stop_loss=0,$tralling_step=0,$target=0,$auto_trade_status=0,$order_created_type=0,$tokan,$oms_order_id =0,$status='',$trade_type=1,$trade_status=0){
		if($broker_id==1){
			$sLogin = $this->getProfile(1);
			$broker_customer_id = $sLogin['data']['login_id'];
		}
		$formData =array();
		$formData['broker_id'] = $broker_id;
		$formData['broker_customer_id'] = $broker_customer_id;
		$formData['customer_id'] = 1;
		$formData['segment'] = $segment;
		$formData['symbol_tokan'] = $tokan;
		$formData['symbol_name'] = $symbol_name;
		$formData['qty'] = $qty;
		$formData['order_type'] = $order_type;
		$formData['current_price'] = $current_price;
		$formData['trend'] = $trend;
		$formData['entry_price'] = $entry_price;
		$formData['stop_loss'] = $stop_loss;
		$formData['tralling_step'] = $tralling_step;
		$formData['target'] = $target;
		$formData['auto_trade_status'] = $auto_trade_status;
		$formData['order_created_type'] = $order_created_type;
		$formData['oms_order_id'] = $oms_order_id;
		$formData['order_status'] = $status;
		$formData['trade_type'] = $trade_type;
		$formData['trade_status'] = $trade_status;
		
		#echo "<pre>"; print_r($formData);
		$this->application_model->insert($formData, 'customer_trade');
		
	}
	
	
	public function getPendingOrderHistory($code=0,$oms_order_id = 0){
	
    $tokan =$this->alice_tokan;

	
	$crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/order",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$tokan
      ),
    ));

    $response = curl_exec($crl);
    
    curl_close($crl);
    
     $resArr  = json_decode($response);	
	 #echo "<pre>"; print_r($resArr);die;
     $pending_orders_Arr = array(); 
	 
     if(!empty( $resArr->data->pending_orders)){
            foreach($resArr->data->pending_orders as $row){
			  if($row->order_status=='trigger pending'){	
				  $key = $row->exchange.'#'.$row->product.'#'.$row->instrument_token; 
				  $pending_orders_Arr[$key] = 	$row;	
			  }	  
			}
		}
		#echo "<pre>"; print_r($pending_orders_Arr);die;
		return $pending_orders_Arr;
	}
	
	 
	public function getOrderHistory($code=0,$oms_order_id = 0){
	
    $tokan =$this->alice_tokan;
	
	//CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/order/".$oms_order_id,
	
	$crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/order",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$tokan
      ),
    ));

    $response = curl_exec($crl);
    
    curl_close($crl);
    
     $resArr  = json_decode($response);	
	 #echo "<pre>"; print_r($resArr);die;
     $html = ''; 
     if(!empty( $resArr->data->completed_orders)){
            foreach($resArr->data->completed_orders as $row){
                $html .= '<tr>
                            <td>'.$row->trading_symbol.'</td>
                            <td>'.$row->transaction_type.'</td>
                            <td>'.$row->order_type.'</td>
                            <td>'.$row->quantity.'</td>
                            <td>'.$row->average_price.'</td>
                            <td>'.date("d-M-Y, h:i:s A",$row->order_entry_time).'</td>';
                            if($row->order_status=='complete'){
                 $html .= ' <td>
                                <button type="button" class="btn btn-success">'.ucfirst($row->order_status).'</button>
                            </td>';
                            }else{
                    $html .= ' <td>
                                <button type="button" class="btn btn-danger">'.ucfirst($row->order_status).'</button>
                            </td>';
                            }
                     $html .= ' <td>
                                '.$row->rejection_reason.'
                            </td>';
                                    

                            
                      $html .= '</tr>';
            }

     }
     echo  $html;

	}
	
    public function getPostionList(){
	   $code =$this->alice_tokan;

    $crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/positions?type=netwise",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$code
      ),
    ));

    $response = curl_exec($crl);
    curl_close($crl);
	$resArr = json_decode($response); 
	
	return $resArr;
	
    }
	
	public function getPostionHolding(){
	   $code =$this->alice_tokan;

    $crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v2/holdings",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$code
      ),
    ));

    $response = curl_exec($crl);
    curl_close($crl);
	$resArr = json_decode($response); 
	#echo "<pre>"; print_r($resArr); die;
	return $resArr;
	
    }
	
    public function getPostion(){
		
    $resArr = $this->getPostionList();
	$resHoldArr = $this->getPostionHolding();
    #echo "<pre>"; print_r($resHoldArr);
	 #echo "<pre>"; print_r($resArr);

     $html = '';
     $totalpl = 0;
    if(!empty($resArr) && $resArr->status =='success' ){ 
        foreach($resArr->data->positions as $row){
            if($totalpl !=0){
			    $totalpl +=str_replace(",","",$row->m2m);
            }else{
               $totalpl =str_replace(",","",$row->m2m);
            }
			$btncls = 'btn-danger';
			if($row->m2m >=0){
			  $btncls = 'btn-success';	
			}
			
			if($row->cf_sell_quantity !=0){
				$sell_quantity = $row->cf_sell_quantity;
			}else{
				$sell_quantity = $row->sell_quantity;
			}
			
			if($row->cf_buy_quantity !=0){
				$buy_quantity = $row->cf_buy_quantity;
			}else{
				$buy_quantity = $row->buy_quantity;
			}
			
			
            $html .= '<tr>
                            <td>'.$row->exchange.'</td>
                            <td>'.$row->product.'</td>
                            <td>'.$row->trading_symbol.'</td>
                            <td>'.$buy_quantity.'</td>
                            <td>'.$sell_quantity.'</td>
                            <td>'.$row->ltp.'</td>
                            <td> <button type="button" class="btn '.$btncls.'">'.$row->m2m.'</button></td>
                        </tr>'; 


        }
    }
	
	
	
	 if(!empty($resHoldArr) && $resHoldArr->status =='success' ){ 
        foreach($resHoldArr->data->holdings as $row){
			$btncls = 'buttont2red';
			/*if($row->m2m >=0){
			 // $btncls = 'buttont2green';	
			}*/
            $html .= '<tr>
                           <td>'.$row->exchange.'</td>
                            <td>'.$row->product.'</td>
                            <td>'.$row->trading_symbol.'</td>
                            <td>'.$row->t1_quantity.'</td>
                            <td> </td>
                            <td >'.$row->nse_ltp.'</td>
                            <td ></td>
                        </tr>';
		

        }
    }
	
    if($totalpl>0){
      $tcls = 'green';
    }else{
        $tcls = 'danger';
    }
				
	 $totalplhtml ='<a href="#" class="dashboard-green-btn '. $tcls.'">P&L <span>'.$totalpl.'</span></a>';			
				

    echo json_encode(array('position'=> $html,'totalpl'=>$totalplhtml));

    //return json_decode($response);  
    }

   public function orderList(){
    $data['postionArr']= $this->getPostionList();
	$data['pendingOrderHistory']= $this->getPendingOrderHistory();
	$data['object'] =$this;
	$data['cutomerLiveData'] = $this->getLiveTrade();
   # print_r($data['cutomerLiveData']);
       //print_r($data['pendingOrderHistory']);
    echo  $this->load->view('orderList',$data);

   }

   public function add_leg_order_indicator($oms_order_id,$leg_order_indicator){
         $formData['leg_order_indicator'] = $leg_order_indicator;
		$sql ='update tbl_customer_trade set leg_order_indicator='.$leg_order_indicator.' WHERE oms_order_id='.$oms_order_id.''; 
         $this->application_model->selectQuery($sql); 

   }
   
   private function getLiveTrade(){
	    $customer_id =  $this->customer_id;
	    $livedata = $this->application_model->selectQuery('SELECT * FROM tbl_customer_trade WHERE trade_type=1 AND  customer_id='.$customer_id.' AND is_deleted=0 AND auto_trade_status=1');
	    return $livedata;
		
	   
   }

  public function gainerslosers(){
       $code =$this->alice_tokan;

    $crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v1/screeners/gainerslosers?index=nifty_100",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$code
      ),
    ));

    $response = curl_exec($crl);
    curl_close($crl);
    $resArr = json_decode($response);
    $dataArrgainer = array();
    if(!empty($resArr)){    
        foreach($resArr->data->gainers as $row){
            if($row->ltp>=600){
                $netPrice = round($row->netPrice,2);

                $dataArrgainer[] = array(
                        'netPrice' =>$netPrice,
                        'symbol' => $row->symbol,
                        'ltp' => $row->ltp,
                        'close_price' => $row->close_price
                    );
       }
    } 
         rsort($dataArrgainer);  
    }

     $dataArrloser = array();
    if(!empty($resArr)){    
        foreach($resArr->data->losers as $row){
            if($row->ltp>=600){
                $netPrice = round($row->netPrice,2);

                $dataArrloser[] = array(
                        'netPrice' =>$netPrice,
                        'symbol' => $row->symbol,
                        'ltp' => $row->ltp,
                        'close_price' => $row->close_price
                    );
       }
    } 
         asort($dataArrloser);  
    }

	#echo "<pre>"; print_r($resArr);
	$volume_shocker =$this->volume_shockers();
	#echo "<pre>"; print_r($volume_shocker);
	$htmlGainer = '';
	$htmllosers = '';
	$htmlvolume_shocker = '';
	if(!empty($dataArrgainer)){	
		foreach($dataArrgainer as $row){
			
				 $htmlGainer .='<tr>
					<td>'.$row['symbol'].'</td>
					<td>'.$row['ltp'].'</td>
					<td>  <span class="green">'.$row['netPrice'].'</span></td>
					<td>'.$row['close_price'].' </td>
				</tr>';
				
				
			}
		
		
		foreach($dataArrloser as $row){
				
					 $htmllosers .='<tr>
						<td>'.$row['symbol'].'</td>
                        <td>'.$row['ltp'].'</td>
                        <td>  <span class="green">'.$row['netPrice'].'</span></td>
                        <td>'.$row['close_price'].' </td>
					</tr>';
				
			
			
			}
		}
		
		

		if(!empty($volume_shocker)){	
			foreach($volume_shocker as $row){
				
					 $htmlvolume_shocker .='<tr>
						<td>'.$row['trading_symbol'].'</td>
						<td>'.$row['ltp'].'</td>
						<td>  <span class="purple">'.$row['volume_change'].'</span></td>
						<td>'.$row['volume'].'</td>
					</tr>';
				
				}
		}
			
		echo json_encode(array('htmlGainer'=> $htmlGainer,'htmllosers'=>$htmllosers,'htmlvolume_shocker'=>$htmlvolume_shocker));
		
		
    } 

    public function volume_shockers(){
       $code =$this->alice_tokan;

    $crl = curl_init();
    curl_setopt_array($crl, array(
      CURLOPT_URL => "https://ant.aliceblueonline.com/api/v1/screeners/volume_shockers?index=nifty_500&days=3",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json; charset=UTF-8",
        "Authorization: Bearer ".$code
      ),
    ));

    $response = curl_exec($crl);
    curl_close($crl);
    $resArr = json_decode($response); 
	$dataArr =array();
	#echo "<pre>"; print_r($resArr);
    if(!empty($resArr->data->volume_shockers)){
    foreach ($resArr->data->volume_shockers as $row) {
        if($row->ltp >= 300){
         $dataArr[] = array(
                        'volume_change' =>round($row->volume_change,2),
                        'trading_symbol' => $row->trading_symbol,
                        'ltp' => $row->ltp,
                        'volume' => round($row->volume/1000,2)
                    );
        }
    }
       
       rsort($dataArr);
    }
    
    return $dataArr;
    
    } 

    public function getsignal(){
		
		#echo $sss = base64_encode("asdf"); 
		print_r($_REQUEST);
		//print_r(base64_decode($_REQUEST['id']));
		
		
		
		//$formData['name'] = json_encode($_REQUEST);
		$getData = $_REQUEST;
		if(!empty($getData)){
			$data = $getData['chatVala1d380dffb1'];
			$formData['name'] = $getData['chatVala1d380dffb1'];
			$form_data= explode("|",$data);
			$symbol_name = $form_data[0];
			$exchage =  explode("_", $form_data[10]);
		
       		$symbolArr = $this->getSymbol($symbol_name,$exchage[0]);
			
			$tokan  = 0;
			if(!empty($symbolArr)){
				$symbol_name  = $symbolArr['name'];
				$tokan  = $symbolArr['tokan'];
				$formData['symbol_tokan'] = $tokan;
			}

			$formData['symbol_name'] = $symbol_name; 
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
		 //$checkexist = $this->application_model->selectQuery('SELECT * FROM tbl_signals WHERE symbol_tokan='.$tokan.' AND  trend="'.$formData['trend'].'" AND create_date="'.date('Y-m-d').'" ');
		 /*if($checkexist ==false)
		 { */ 
            if(!empty($form_data[10])){ 
                 //$exchage =  explode("_", $form_data[10]);

                // $tokan  = $this->getTokanBySymbolName($exchage[0],$symbol_name);
                  //$this->application_model->insert($formData, 'signals');
				 if($tokan !=0){
			       	$this->application_model->insert($formData, 'signals');
                    $tradsArr = $this->getTrades($formData);
                 }else{
				  $this->application_model->insert($formData, 'signals');  	 
				 }
            }
		/* }else{
           
         }*/

		 //$tradeArr = $this->application_model->selectQuery('SELECT * FROM tbl_signals WHERE symbol_name="'.$formData['symbol_name'].'" AND  trend="'.$formData['trend'].'" AND create_date="'.date('Y-m-d').'" ');
		
       # echo $this->input->get("chatVala1d380dffb1");
		
        die;
    }

    private function getTokanBySymbolName($segmentname = 0,$symName = '',$uture = ''){
        $arraySeg =array('NSE'=>1,'NFO'=>2,'CDS'=>3,'MCX'=>4);
        $segment = $arraySeg[$segmentname];
        $tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name="'.$symName.'" AND  segment="'.$segment.'"');
	
        if($tokan !=false){
				
           $arra = array('tokan'=>$tokan[0]['tokan'],'name'=>$tokan[0]['name']);
        }else{
			
			if($segmentname=='MCX'){ // only fut 
				$tokan = $this->searchSymbolMCX($symName,$segment);
				if($tokan !=false){
					$arra = array('tokan'=>$tokan[0]['tokan'],'name'=>$tokan[0]['name']);
				}else{
				  $arra = array();  	
				}
				
			}else if($segmentname=='NFO'){
				$tokan = $this->searchSymbolNFO($symName,$segment,$uture);
				
				if($tokan !=false){
					$arra = array('tokan'=>$tokan[0]['tokan'],'name'=>$tokan[0]['name']);
				}else{
				  $arra = array();  	
				}
			}
			else{
			   $arra = array(); 
			}
            
        }
		return  $arra;	
    }
	
	private function searchSymbolMCX($symName,$segment){
		$curentmonth =  date('M');
		$symName1 = $symName.' '.strtoupper($curentmonth).' FUT';
		
		$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "%'.$symName1.'%" AND  segment="'.$segment.'"');
		if($tokan ==false){
			$month = date('M', strtotime("first day of +1 months", strtotime($curentmonth)));
			$symName2 = $symName.' '.strtoupper($month).' FUT';
			
			$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "%'.$symName2.'%" AND  segment="'.$segment.'"');
			if($tokan == false){
				$month = date('M', strtotime("first day of +2 months", strtotime($curentmonth)));
				$symName = $symName.' '.strtoupper($month).' FUT';
				 
				$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "%'.$symName.'%" AND  segment="'.$segment.'"');
				if($tokan !=false){
					return $tokan;
				}else{
					return array();
				}
			
			}else{
				return $tokan;
			
			}
		}else{
			return $tokan;	
		}
		
	}
	
	private  function searchSymbolNFO($symName,$segment){
		
		#echo $symName.'==='.$segment.'=='.$uture; 
		$response = array();
		$sym = explode("_",$symName);
		$smy = array('BNCW','BNPW','NFWC','NEWP');
		if(in_array($sym[0],$smy)){
			if(!empty($symName)){
				
				$symNameNew = '';
				if($sym[0]=='BNCW'){
					$symNameNew = 'BANKNIFTY';
					$uture = 'CE';
				}elseif($sym[0]=='BNPW'){
					$symNameNew = 'BANKNIFTY';
					$uture = 'PE';
				}else if($sym[0]=='NFWC'){
					$symNameNew = 'NIFTY';
					$uture = 'CE';
				}
				else if($sym[0]=='NEWP'){
					$symNameNew = 'NIFTY';
					$uture = 'PE';
				}
				$thursdaydate = date("d",strtotime('next thursday')); 
				$month = strtoupper(date("M")); 
				$symNameNewweak = $symNameNew.' '.$thursdaydate.' '.$month.$thursdaydate.' '.$sym[1].'.0 '.$uture;
				
				
				$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "'.$symNameNewweak.'" AND  segment="'.$segment.'"');
				if($tokan !=false){
					$response = $tokan;
				}else{
					$symNameNewMonth = $symNameNew.' '.$month.' '.$sym[1].'.0 '.$uture;
					$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "'.$symNameNewMonth.'" AND  segment="'.$segment.'"');
					$response = $tokan;
				}
				
			}
		}else{
			
			$month = strtoupper(date("M")); 
			$symNameNewMonth = $symName.' '.$month.' FUT';
			$tokan = $this->application_model->selectQuery('SELECT * FROM tbl_tokens WHERE name LIKE "'.$symNameNewMonth.'" AND  segment="'.$segment.'"');
			$response = $tokan;
			
		}
		return $response;
		
	}
	

    private function getTrades($signleArr = array()){
		
	   /*$signleArr['exchange'] = 'NSE_BO_CO_MIS';
	   $signleArr['trend'] = 'Sell';
	   $signleArr['symbol_tokan'] = 317;	
	   $signleArr['status'] = 'SLHIT';*/
	   #echo "<pre>"; print_r($signleArr);
	   $customer_id =  $this->customer_id; 
       $login_tokan =  $this->alice_tokan;    
       $exchangeArr  = explode("_",$signleArr['exchange']);
       $segment = $exchangeArr[0]; //'NSE';
       $orderType = $exchangeArr[1]; //'BO_CO_MIS';//;
       $where ='';
       if($orderType=='BO_CO_MIS'){
       	 	$where = 'AND ct.order_type IN("BO","CO","MIS")';
       }elseif($orderType=='NRML'){
       		$where = 'AND ct.order_type ="NRML"';
       }elseif($orderType=='CNC'){
       		$where = 'AND ct.order_type ="CNC"';
       }


       $trend =  strtoupper($signleArr['trend']); //'BUY';
       $tokan = $signleArr['symbol_tokan']; //'2885';
       $trend_type = strtoupper($signleArr['trend']); //'BUY';
       $stop_loss = $signleArr['stop_loss'];
       $tralling_step = '';
       $target = $signleArr['target'];
       $current_price = $signleArr['entry_price'];
	   $entprice = $signleArr['current_price'];
       $symbol_name = $signleArr['symbol_name'];
       $oder_run_type = 0;
       $status = $signleArr['status'];
       #echo 'SELECT * FROM tbl_customer_trade WHERE segment="'.$segment.'" AND  symbol_tokan='.$tokan.' AND auto_trade_status=1 '.$where.'';
	   if($status=='CallActive'){
		$orders = $this->application_model->selectQuery('SELECT ct.*,c.token as ctoken  FROM tbl_customer_trade as ct JOIN tbl_customers as c on ct.customer_id=c.id   WHERE  ct.customer_id='. $customer_id.' AND  ct.segment="'.$segment.'" AND  ct.symbol_tokan='.$tokan.' AND ct.auto_trade_status=1 AND ct.trade_type=1 AND ct.is_deleted=0 '.$where.''); 
		
		$this->paperTrade($segment,$tokan,$where,$orderType,$trend_type, 0, $stop_loss,$tralling_step,$target,$current_price,$symbol_name,$entprice);
		
	   }else{
		   $chkstatus  = $this->checkAutoStatus($tokan);
		   if($chkstatus==1){
			    $sql = 'SELECT ct.*  FROM tbl_customer_trade ct LEFT JOIN tbl_customer_trade m2  ON (ct.customer_id = m2.customer_id AND ct.id < m2.id)  WHERE m2.id IS NULL AND ct.segment="'.$segment.'" AND  ct.symbol_tokan='.$tokan.' AND ct.auto_trade_status=0  AND  ct.order_status="CallActive" ct.customer_id='. $customer_id.' AND   AND ct.trade_type=1 AND ct.is_deleted=0  '.$where.'';
			   $orders = $this->application_model->selectQuery($sql);
		   }
	   }

		
		#echo "<pre>";  print_r($orders); die;
		if($orders !=false){
        foreach($orders as $rows){
           # echo "<pre>"; print_r($rows);
            $qty = $rows['qty'];
			$orderType = $rows['order_type'];
			#echo $status;
			if($status=='CallActive'){
				$login_tokan = $rows['ctoken'];
            	//$order = $this->fireOrder($login_tokan, '', $segment, $orderType, '', $tokan, $trend_type, $qty, $stop_loss,$tralling_step,$target,$current_price,$symbol_name,$oder_run_type,$status); 
				
				 
            }elseif($status=='SLHIT' || $status=='TPHIT'){
            
            	$oms_order_id = $rows['oms_order_id'];
                $leg_order_indicator = $rows['oms_order_id']; //$rows['leg_order_indicator'];
            	if($orderType=='BO' && !empty($oms_order_id) && !empty($leg_order_indicator)){
				
					$this->orderDeleteBO($oms_order_id,$leg_order_indicator);
				}else if($orderType=='CO' && !empty($oms_order_id) && !empty($leg_order_indicator)){
				
					$this->orderDeleteCO($oms_order_id,$leg_order_indicator);
				}else {
					if($rows['trend']=='SELL'){
						$trend_type = 'BUY'; 
					}else{
						$trend_type = 'SELL';
					}
					
					$login_tokan = $this->getTokanByCustomerId($rows['customer_id']);
					//$order = $this->fireOrder($login_tokan, '', $segment, $orderType, '', $tokan, $trend_type, $qty, $stop_loss,$tralling_step,$target,$current_price,$symbol_name,$oder_run_type,$status); 
					
				} 	
            }  
           //$this->fireOrder($code, $br='', $ex, $pr, $sy='', $sym, $ord, $qu,$stop_loss,$tralling_step,$target,$current_price,$symbol_name,$auto_trade_status) {
        }
		
		
		
       }
    }
	
	
	private  function checkAutoStatus($tokan){
		$trade = $this->application_model->selectQuery('SELECT id FROM tbl_customer_trade    WHERE  symbol_tokan='.$tokan.'  AND auto_trade_status=1 AND is_deleted=0 AND ct.trade_type=1'); 
			$status = 0;
			if($trade !=false){
				$status = 1;
			}
			return $status;
	}  
	
	private function getTokanByCustomerId($CustomerId){
			$customer = $this->application_model->selectQuery('SELECT token   FROM tbl_customers    WHERE  id='.$CustomerId.''); 
			$tokan = 0;
			if($customer !=false){
				$tokan = $customer[0]['token'];
			}
			return $tokan;

	}
	
    public function getSymbol($sym='',$exchage){
		
    	$arra = array('MCRO'=>'CRUDEOIL',
    					'SLMM'=>'SILVERM',
    					'GLMM'=>'GOLDM',
    					'MCOP'=>'COPPER',
						'MGLD'=>'GOLD',
						'MNAG'=>'NATURALGAS',
						'MSIL'=>'SILVER',
						'MZIN'=>'ZINC',
						'SILMIC'=>'SILVERMIC',
						'MLEA'=>'LEAD',
						'MINILEA'=>'LEADMINI',
						'MNIC'=>'NICKEL',
						'MALU'=>'ALUMINIUM',
						'MINIALU'=>'ALUMINI');

    	if(array_key_exists($sym, $arra)){
    		$sym_name = $arra[$sym];  
			$tokan = $this->getTokanBySymbolName($exchage,$sym_name);
    	}else{
    		$tokan = $this->getTokanBySymbolName($exchage,$sym);
    	}
		return 	$tokan;

    }
	public function cancelOrder(){
		$customer_trade_id = $this->input->post('customer_trade_id');
		$formData['is_deleted'] = 1;
         $this->application_model->update($formData, 'customer_trade', array('id'=> $customer_trade_id)); 
		echo json_encode(array('status'=>1,'message'=>'Removed Successfully.')); 

	}
	
	public function add_paper_trade()
    {
        $tokan = $this->alice_tokan;
		
		$exchange = $this->input->post('segment');

		$symbol_name = $this->input->post('symbol_name');
		$qty = $this->input->post('qty');
		$order_type = $this->input->post('order_type');
        $trend_type = $this->input->post('trend_type');
        $stop_loss = $this->input->post('stop_loss');
        $target = $this->input->post('target');
        $current_price = $this->input->post('current_price');
		$oms_order_id = $this->input->post('oms_order_id');
		$leg_order_indicator = $this->input->post('leg_order_indicator');
		$oder_run_type = $this->input->post('oder_run_type');
		$symbol = explode("/", $symbol_name);
        $isorderExist = $this->checkExistOrderPaperTrade($exchange,$symbol[1]);
		if($isorderExist==0){
			$symbol = explode("/", $symbol_name);
			$this->insertOrderDb($broker_id=1,$exchange,$symbol_name,$qty,$order_type,$current_price,$trend_type,$entry_price=0,$stop_loss,0,$target,$oder_run_type,$order_created_type=0,$symbol[1],$oms_order_id=0,0,2,1);
				
			$json_response = array('status'=>1,'history'=>'','message'=>'Paper Trade Order has been Placed Successfully.');  
				echo json_encode($json_response);
			
		}else{
			echo json_encode(array('status'=>1,'message'=>'Record Allready Exist.')); 	
		}
        
		
    }
	
	public function checkExistOrderPaperTrade($segment,$symbol_tokan){
	
		$customer_id = $this->customer_id;
		$sql ='SELECT id  FROM tbl_customer_trade WHERE customer_id='.$customer_id.' AND segment="'.$segment.'" AND symbol_tokan ='.$symbol_tokan.' AND is_deleted=0 AND trade_type=2';
		$exist = $this->application_model->selectQuery($sql);
		if($exist !=false){
			return 1;
		}else{
			return 0;	
		}	
		

	}
	
	
	public function paperTradeorderList(){
   
		$customer_id =  $this->customer_id;
	
	    $livedata = $this->application_model->selectQuery('SELECT * FROM tbl_customer_trade WHERE customer_id='.$customer_id.' AND is_deleted=0 AND trade_type=2');
		$data['cutomerLiveData'] = $livedata;
		echo  $this->load->view('paperTradeorderList',$data);

   }
   
   public function paperTrade($segment,$tokan,$where,$orderType,$trend_type, $qty, $stop_loss,$tralling_step,$target,$current_price,$symbol_name,$entprice){
	   $customer_id =  $this->customer_id;
	   
	   $sql = 'SELECT ct.*,c.token as ctoken  FROM tbl_customer_trade as ct JOIN tbl_customers as c on ct.customer_id=c.id   WHERE ct.segment="'.$segment.'" AND  ct.symbol_tokan='.$tokan.' AND ct.auto_trade_status=0 AND ct.trade_type=2 AND ct.customer_id='. $customer_id.' AND  ct.is_deleted=0 '.$where.'';
	   
	   $orders = $this->application_model->selectQuery($sql); 
		#echo $entprice;
		#echo $current_price;
		
	   if($orders !=false){
		   foreach($orders as $row){
			
			if($trend_type=='SELL'){
				$price = $current_price - $entprice;	
			}else{
				$price = $entprice - $current_price;
			} 
			$pl =  $price * $row['qty'];
			$formData['p_and_l'] = $row['p_and_l']+$pl;
			$formData['stop_loss'] = $stop_loss;
			$formData['target'] = $target;
			$formData['current_price'] =$entprice;
			$formData['entry_price'] = $current_price;
			$formData['trend'] = $trend_type;
			$formData['trade_status'] = 2;
			
			$this->application_model->update($formData, 'customer_trade', array('id'=> $row['id']));    
		   }
	   }
	   #echo "<pre>"; print_r($orders);
	   
	   
   }



    public function getsignalJmd(){
		
		#echo $sss = base64_encode("asdf"); 
		print_r($_REQUEST);
		//print_r(base64_decode($_REQUEST['id']));
		
		
		
		$formData['name'] = json_encode($_REQUEST);
		$this->application_model->insert($formData, 'jmd');

		/*
		$getData = $_REQUEST;
		if(!empty($getData)){
			$data = $getData['chatVala1d380dffb1'];
			$formData['name'] = $getData['chatVala1d380dffb1'];
			$form_data= explode("|",$data);
			$symbol_name = $form_data[0];
			$exchage =  explode("_", $form_data[10]);
		
       		$symbolArr = $this->getSymbol($symbol_name,$exchage[0]);
			
			$tokan  = 0;
			if(!empty($symbolArr)){
				$symbol_name  = $symbolArr['name'];
				$tokan  = $symbolArr['tokan'];
				$formData['symbol_tokan'] = $tokan;
			}

			$formData['symbol_name'] = $symbol_name; 
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
		 //$checkexist = $this->application_model->selectQuery('SELECT * FROM tbl_signals WHERE symbol_tokan='.$tokan.' AND  trend="'.$formData['trend'].'" AND create_date="'.date('Y-m-d').'" ');
		 /*if($checkexist ==false)
		 { */ 
        /*   if(!empty($form_data[10])){ 
                 //$exchage =  explode("_", $form_data[10]);

                // $tokan  = $this->getTokanBySymbolName($exchage[0],$symbol_name);
                  //$this->application_model->insert($formData, 'signals');
				 if($tokan !=0){
			       	$this->application_model->insert($formData, 'signals');
                    $tradsArr = $this->getTrades($formData);
                 }else{
				  $this->application_model->insert($formData, 'signals');  	 
				 }
            }
		/* }else{
           
         }*/

		 //$tradeArr = $this->application_model->selectQuery('SELECT * FROM tbl_signals WHERE symbol_name="'.$formData['symbol_name'].'" AND  trend="'.$formData['trend'].'" AND create_date="'.date('Y-m-d').'" ');
		
       # echo $this->input->get("chatVala1d380dffb1");
		
        die;
    }

	

     

	
}
/* End of dashboard.php file */
/* location: application/view/admin/dashboard.php */
/* Omit PHP closing tags to help avoid accidental output */
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Imports extends CI_Controller
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
		
		$querytr = 'TRUNCATE tbl_tokens'; 
        $this->application_model->selectQuery($querytr);

		$jsonData = json_decode(file_get_contents('https://ant.aliceblueonline.com/api/v2/contracts.json?exchanges=NSE'));
       #echo "<pre>";  print_r($jsonData); die;
       $dataArr = array();
       $dataArrQuery = '';
       foreach($jsonData as $rowk=>$rowv){
       			foreach($rowv as $row){
       			#echo "<pre>"; print_r($row); die;
				if(!empty($row->trading_symbol)){
					$exp = explode("-",$row->trading_symbol);
					if(isset($exp[1]) && $exp[1]=='EQ'){
					if(isset($row->lotSize) &&  !empty($row->lotSize)){
						$dataArr[] = $row->symbol.' / '.$row->code.' / '.$row->lotSize; 
						$dataArrQuery .= "(1,'".$row->symbol."',".$row->code.",".$row->lotSize.'),'; 
					}else{
					 $dataArr[] =$row->symbol.' / '.$row->code.' / 1'; 
					 $lotSize = 0;
					$dataArrQuery .= "(1,'".$row->symbol."',".$row->code.",".$lotSize.'),'; 
					}
					}
				}

	       	}
       		
       }
	   
	   #echo "<pre>"; print_r($dataArr); die;
	    
      $str_NSE = implode('","', $dataArr);
    
   		$query = 'INSERT INTO tbl_tokens (segment,name,tokan,qty) VALUES '.rtrim($dataArrQuery,",").''; 
        $this->application_model->selectQuery($query);
	  
	 
	  $jsonData = json_decode(file_get_contents('https://ant.aliceblueonline.com/api/v2/contracts.json?exchanges=NFO'));
       
       $dataArr = array();
       $dataArrQuery = '';
       foreach($jsonData as $rowk=>$rowv){
       			foreach($rowv as $row){
       			#echo "<pre>"; print_r($row);
       			if(isset($row->lotSize) &&  !empty($row->lotSize)){
       				$dataArr[] = $row->symbol.' / '.$row->code.' / '.$row->lotSize; 
       				$dataArrQuery .= "(2,'".$row->symbol."',".$row->code.",".$row->lotSize.'),'; 
	       		}else{
	       		 $dataArr[] =$row->symbol.' / '.$row->code; 
	       		$lotSize = 0;
	       		$dataArrQuery .= "(2,'".$row->symbol."',".$row->code.",".$lotSize.'),'; 
	       		}
	       	}
       		
       }
      $str_NFO = implode('","', $dataArr);
	  
	  $query = 'INSERT INTO tbl_tokens (segment,name,tokan,qty) VALUES '.rtrim($dataArrQuery,",").''; 
        $this->application_model->selectQuery($query);
	  
	  $jsonData = json_decode(file_get_contents('https://ant.aliceblueonline.com/api/v2/contracts.json?exchanges=MCX'));
       
       $dataArr = array();
        $dataArrQuery = '';
       foreach($jsonData as $rowk=>$rowv){
       			foreach($rowv as $row){
       			#echo "<pre>"; print_r($row);
       			if(isset($row->lotSize) &&  !empty($row->lotSize)){
       				$dataArr[] = $row->symbol.' / '.$row->code.' / '.$row->lotSize; 
       				$dataArrQuery .= "(4,'".$row->symbol."',".$row->code.",".$row->lotSize.'),'; 
	       		}else{
	       		 $dataArr[] =$row->symbol.' / '.$row->code.' / 1'; 
	       		 $lotSize = 0;
	       		$dataArrQuery .= "(4,'".$row->symbol."',".$row->code.",".$lotSize.'),';
	       		}
	       	}
       		
       }
      $str_MCX = implode('","', $dataArr);
        $query = 'INSERT INTO tbl_tokens (segment,name,tokan,qty) VALUES '.rtrim($dataArrQuery,",").''; 
        $this->application_model->selectQuery($query);
	  
	  $jsonData = json_decode(file_get_contents('https://ant.aliceblueonline.com/api/v2/contracts.json?exchanges=CDS'));
       
       $dataArr = array();
         $dataArrQuery = '';
       foreach($jsonData as $rowk=>$rowv){
       			foreach($rowv as $row){
       			#echo "<pre>"; print_r($row);
       			if(isset($row->lotSize) &&  !empty($row->lotSize)){
       				$dataArr[] = $row->symbol.' / '.$row->code.' / '.$row->lotSize; 
       				$dataArrQuery .= "(3,'".$row->symbol."',".$row->code.",".$row->lotSize.'),'; 
	       		}else{
	       		 $dataArr[] =$row->symbol.' / '.$row->code; 
	       		  $lotSize = 0;
	       		$dataArrQuery .= "(3,'".$row->symbol."',".$row->code.",".$lotSize.'),';
	       		}
	       	}
       		
       }
      $str_CDS = implode('","', $dataArr);
	  $query = 'INSERT INTO tbl_tokens (segment,name,tokan,qty) VALUES '.rtrim($dataArrQuery,",").''; 
        $this->application_model->selectQuery($query);
	  
	  $str = 'var aliceSymbols = {"status":false, "dt":"'.date('Y-m-d').'"';
	  $str .=',"NSE":["'.$str_NSE.'"]';
	  $str .=',"NFO":["'.$str_NFO.'"]';
	  $str .=',"CDS":["'.$str_CDS.'"]';
	   $str .=',"MCX":["'.$str_MCX.'"]';
	  $str .='};';
	  
	  $dir_to_save = "assets/customer/json/";
		if (!is_dir($dir_to_save)) {
		  mkdir($dir_to_save);
		}
	  @unlink($dir_to_save.'alice.symbols.JS');
	  
	  file_put_contents($dir_to_save.'alice.symbols.JS',$str);

      echo  "haresh";
     
	
	}
	

	public function testCron(){
	  $formData['name'] = 'testcron';
	  $formData['create_date'] = date("Y-m-d H:i:s");
	  
      $this->application_model->insert($formData, 'signals_charting');
	  echo "testCron";
	}
	
	
	
	
	
   
	
	
}
/* End of Login.php file */
/* location: application/view/Login.php */
/* Omit PHP closing tags to help vaoid accidental output */
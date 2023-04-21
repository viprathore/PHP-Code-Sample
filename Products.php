<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Products extends CI_Controller {
    private $admin;
    public function __construct() {
		parent::__construct();
		#Check account login.
		$this->isAdmin();
        $this->admin = $this->session->userdata('admin');
		$this->load->model('application_model');//Load application model
        $this->load->helper('datatables');//Load DataTables helper
        $this->form_validation->set_error_delimiters('<label class="error">', '</label>');
	}

	public function productView() {
		$data['title'] = 'product List View';
        $data['sidebar'] = 'admin/includes/sidebar';
		$data['admin'] = $this->admin;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.products.js?v='. time());
       	#Load view template.
		$data['template'] = 'prducts_view';
		$this->load->view('includes/template', $data);
    }

    private function getUserAddedProduct(){
		$query = 'SELECT product_id FROM tbl_product_shop WHERE shop_id ='.$this->admin['id'];
		$productData = $this->application_model->selectQuery($query);
		#print_r($productData);
		$res = '';
		$prodArr = array();
		if($productData !=false){
			foreach($productData as $row){
				$prodArr[] = $row['product_id']; 
			}
			$res = implode(",",$prodArr);
		}
		return $res;
	}

    public function productsGridView() {
		$res = $this->getUserAddedProduct();
        /* DataTables column count */
        $aCount = 5;
		/* Array of database columns */
		$aColumns = array('p.id', 'p.name' ,'p.company_id', 'p.unit_id',  'p.is_deleted','u.name as unit','c.name as company','p.name_hindi','c.name_hindi as company_hindi');
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "p.id";
		/* DB table to use */
		$sTable = "tbl_products as p";
        /* Filtering */
		if(empty($res)){
			$sWhere = 'WHERE p.is_deleted=0';
		}else{
			$sWhere = 'WHERE p.is_deleted=0 AND p.id NOT IN('.$res.')';
		}
		/** Join **/
        $sJoin = ' LEFT JOIN tbl_company as c ON c.id=p.company_id';
		$sJoin .= ' LEFT JOIN tbl_units as u ON u.id=p.unit_id';
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
		$lang = $this->session->userdata('activated_lang');
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
					if($lang=='english'){
						$row[] = $aRow['name'];
					}else{
						$row[] = $aRow['name_hindi'];
					}
                }elseif($column=='company_id') {
					if($lang=='english'){
						$row[] = $aRow['company'];
					}else{
						$row[] = $aRow['company_hindi'];
					}
                }elseif($column=='unit_id') {
                    $row[] = $aRow['unit'];
                }elseif($column=='is_deleted') {
                    #Add tp product button
                    $action = '<div class="action-box"><a href="#" id="addToProduct" role="button" data-productid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-info" title="add"><i class="fa fa-plus"></i></a>';
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

    public function addToProduct($productId){
		$this->load->view('addToProductModel');
	}

	public function saveProduct(){
		#Update User status
		$result = array('status'=>false, 'message'=>'User status can\'t change, Please try again!');
		#Grab user input
		$quantity = $this->security->xss_clean($this->input->post('quantity'));
		$price = $this->security->xss_clean($this->input->post('price'));
		$productid = $this->security->xss_clean($this->input->post('productid'));
		$discount_percentage = $this->security->xss_clean($this->input->post('discount_percentage'));
		$discounted_price = $this->security->xss_clean($this->input->post('discounted_price'));

		if(isset($quantity) && !empty($quantity)){
			$formData = array(
				'shop_id' => $this->admin['id'],
				'quantity' => $quantity,
				'product_id'=>$productid,
				'price' => $price,
				'updated_by' => $this->admin['id'],
				'updated_at' => $this->commonfunctions->curDateTime()
			);
			if (!empty($discount_percentage) && !empty($discounted_price)) {
				$formData['discount_percentage'] = $discount_percentage;
				$formData['discounted_price'] = $discounted_price;
			}
			$this->application_model->insert($formData, 'product_shop');
			$result = array('status'=>true, 'message'=>'Product has been successfully');
		}
		echo json_encode($result);exit;
	}
	
}

/* End of product.php file */

/* location: application/view/admin/product.php */

/* Omit PHP closing tags to help avoid accidental output */
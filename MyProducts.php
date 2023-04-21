<?php defined('BASEPATH') OR exit('No direct script access allowed');
class MyProducts extends CI_Controller {
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

	public function myProductView() {
        #title
		$data['title'] = 'product List View';
        $data['sidebar'] = 'admin/includes/sidebar';
		$data['admin'] = $this->admin;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.myProducts.js?v='. time());
       	#Load view template.
		$data['template'] = 'my_prducts_view';
		$this->load->view('includes/template', $data);
    }

	#My Product grid view data from database
    public function myProductsGridView() {
        /* DataTables column count */
        $aCount = 9;
		/* Array of database columns */
		$aColumns = array('mp.id', 'mp.product_id' ,'mp.shop_id', 'mp.updated_by', 'mp.quantity','mp.price', 'mp.discount_percentage', 'mp.discounted_price' ,'mp.is_deleted','u.name as unit','c.name as company','p.name_hindi as name_hindi','p.name','c.name_hindi as company_hindi');
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "mp.id";
		/* DB table to use */
		$sTable = "tbl_product_shop as mp";
        /* Filtering */
        $sWhere = 'WHERE mp.is_deleted=0';
		/** Join **/
		$sJoin = ' LEFT JOIN tbl_products as p ON p.id=mp.product_id';
        $sJoin .= ' LEFT JOIN tbl_company as c ON c.id=p.company_id';
		$sJoin .= ' LEFT JOIN tbl_units as u ON u.id=p.unit_id';
		/** SQL queries **/
        $result = datatables($aCount, $aColumns, $sIndexColumn, $sTable, $sWhere, $sJoin, 'No');
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
                } elseif($column=='product_id') {
					if($lang=='english'){
						$row[] = $aRow['name'];
					}else{
						$row[] = $aRow['name_hindi'];
					}
                }elseif($column=='shop_id') {
					if($lang=='english'){
						$row[] = $aRow['company'];
					}else{
						$row[] = $aRow['company_hindi'];
					}
                } elseif($column=='updated_by') {
                    $row[] = $aRow['unit'];
                }elseif($column=='is_deleted') {
                    #View button
                    $action = '<div class="action-box"><a href="#" id="myProductUpdateQty" role="button" data-productid="'. $aRow['id'] .'" class="btn btn-smm btn-outline-info" title="add product quantity"><i class="fa fa-edit"></i></a>';
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

	public function myProductUpdateQty($productId){
		$this->load->view('myProductUpdateQtyModel');
	}

	public function saveMyProductQty(){
		#Update User status
		$result = array('status'=>false, 'message'=>'User status can\'t change, Please try again!');
		#Grab user input
		$quantity = $this->security->xss_clean($this->input->post('quantity'));
		$price = $this->security->xss_clean($this->input->post('price'));
		$productid = $this->security->xss_clean($this->input->post('productid'));
		$discount_percentage = $this->security->xss_clean($this->input->post('discount_percentage'));
		$discounted_price = $this->security->xss_clean($this->input->post('discounted_price'));

		$formData['updated_by'] = $this->admin['id'];
		$formData['updated_at'] = $this->commonfunctions->curDateTime();
		if(isset($quantity) && !empty($quantity)){
			$productData = $this->application_model->select(array('quantity'), 'product_shop', array('id'=>$productid), 0,1);
			if($productData !=false){
				$quantity = $quantity + $productData[0]['quantity'];  
			}
			$formData['shop_id'] = $this->admin['id'];
			$formData['quantity'] = $quantity;
			if(!empty($price)){
				$formData['price'] = $price;
				if (!empty($discounted_price)) {
					$formData['discount_percentage'] = $discount_percentage;
					$formData['discounted_price'] = $discounted_price;
				}
			}
			$this->application_model->update($formData, 'product_shop',array('id'=>$productid));
			$result = array('status'=>true, 'message'=>'Product has been updated');
		}else if(!empty($price)){
			$formData['price'] = $price;
			if (!empty($discounted_price)) {
				$formData['discount_percentage'] = $discount_percentage;
				$formData['discounted_price'] = $discounted_price;
			}
			$this->application_model->update($formData, 'product_shop',array('id'=>$productid));
			$result = array('status'=>true, 'message'=>'Product Price  has been updated');
		}
		echo json_encode($result);exit;
	}

}

/* End of product.php file */
/* location: application/view/admin/product.php */
/* Omit PHP closing tags to help avoid accidental output */
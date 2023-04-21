<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Orders extends CI_Controller {
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

	public function myOrderView($status =0 ) {
        #title
		$data['title'] = 'Order List View';
        $data['sidebar'] = 'admin/includes/sidebar';
		$data['admin'] = $this->admin;
		$data['status'] = $status;
        #Load external CSS/JS files
        $data['css'] = array('jquery-confirm.css','plugins/dataTables/datatables.min.css');
        $data['js'] = array('jquery-confirm.js','plugins/dataTables/datatables.min.js', 'app.datatables.js', 'app.orders.js?v='. time());
       	$data['allstatus'] = $this->getAllstatus();
       	#Load view template.
		$data['template'] = 'order_view';
		$this->load->view('includes/template', $data);
    }

    private function getAllstatus(){
		return $this->application_model->select(array('id, name'), 'order_status', array('is_deleted'=>0), 0,'all');
	}

    public function myOrdersGridView($status =0 ) {
        /* DataTables column count */
        $aCount = 8;
		/* Array of database columns */
		$aColumns = array('o.id', 'o.user_id','o.updated_by' ,'o.address_id','o.total_price','o.created_at','o.delivery_user_id', 'o.order_status_id','l.first_name','l.last_name','l.mobile_no','a.address','d.first_name as d_first_name','d.last_name as d_last_name','w.name as order_status','w.order_class');
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "o.id";
		/* DB table to use */
		$sTable = "tbl_order as o";
        /* Filtering */
		if($status !=0){
			$sWhere = 'WHERE o.is_deleted=0 and o.order_status_id='.$status;
		}else{
			$sWhere = 'WHERE o.is_deleted=0';
		}
		/** Join **/
		$sJoin = ' LEFT JOIN tbl_user_login as l ON l.id=o.user_id';
		$sJoin .= ' LEFT JOIN tbl_user_address as a ON a.id=o.address_id';
		$sJoin .= ' LEFT JOIN tbl_user_login as d ON d.id=o.delivery_user_id';
		$sJoin .= ' LEFT JOIN tbl_order_status as w ON w.id=o.order_status_id';
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
		foreach($rResult as $aRow){
            $row = array();
			for ( $i=0; $i<$aCount; $i++ ){
                #Extract join column
				$column = strchr($aColumns[$i], '.');
				$column = substr($column, 1, strlen($column));
                if($column=='id') {
                    $row[] = $sno;
                }elseif($column=='user_id') {
                  	$row[] = $aRow['first_name']." ".$aRow['last_name'];
				}elseif($column=='updated_by') {
                  	$row[] = $aRow['mobile_no'];
				}elseif($column=='address_id') {
                  	$row[] = $aRow['address'];
				}elseif($column=='created_at') {
                  	$row[] = date("d/m/y h:i A",strtotime($aRow['created_at']));
				}elseif($column=='delivery_user_id') {
                  	$row[] = $aRow['d_first_name']." ".$aRow['d_last_name'];
				}elseif($column=='order_status_id') {
                    #View button
                    $action = "";
					if($aRow['order_status_id']==2){
                    	$action = '<div class="action-box"><a href="#" id="updateOrderstatus" role="button" data-orderid="'. $aRow['id'] .'" class="btn btn-smm '.$aRow['order_class'].'" title="'.$aRow['order_status'].'"><i class="fa fa-plus"></i> '.$aRow['order_status'].'</a>';
                    }else{
                    	$action = '<div class="action-box"><a href="#" id="" role="button" data-orderid="'. $aRow['id'] .'" class="btn btn-smm '.$aRow['order_class'].'" title="'.$aRow['order_status'].'">'.$aRow['order_status'].'</a>';
                    }
                    $action .= '<div class="action-box"><a href="#" id="viewBill" role="button" data-orderid="'. $aRow['id'] .'" class="btn btn-smm btn-secondary" title="view bill">View Bill</a>';
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

    public function changeOrderStatus(){
		$result = array('status'=>false, 'message'=>'Order status can\'t change, Please try again!');
		#Grab order input
		$orderId = $this->security->xss_clean($this->input->post('orderId'));
		if(isset($orderId) && !empty($orderId)){
			$formData = array(
				'order_status_id' => 3,
				'updated_by' => $this->admin['id'],
				'updated_at' => $this->commonfunctions->curDateTime()
			);
			$this->application_model->update($formData, 'order', array('id'=>$orderId));

			$orderProductData = $this->application_model->select(array('product_id, quantity'), 'order_product', array('is_deleted'=>0,'order_id'=>$orderId), 0,'all');
			foreach ($orderProductData as $key => $value) {
				$shopProData = $this->application_model->selectQuery('UPDATE tbl_product_shop SET quantity=quantity-'.$value['quantity'].', updated_at="'.$this->commonfunctions->curDateTime().'",updated_by='.$this->admin['id'].' WHERE id='.$value['product_id']);
			}
			$result = array('status'=>true, 'message'=>'Order status updated successfully');
		}
		echo json_encode($result);exit;
	}

	public function viewBill($orderId){
		$orderData = $this->application_model->selectQuery('SELECT o.id, o.total_price, o.total_discount, o.discount, o.description, o.created_at, o.updated_at, ul.first_name AS kisan_f_name, ul.last_name AS kisan_l_name, u.shop_name, u.address AS shop_address, u.first_name AS shop_f_name, u.last_name AS shop_l_name, u.mobile AS shop_mobile, u.shop_photo, ua.address, os.name AS order_status FROM tbl_order AS o JOIN tbl_users AS u ON u.id=o.shop_id JOIN tbl_user_login AS ul ON ul.id=o.user_id JOIN tbl_user_address AS ua ON ua.id=o.address_id JOIN tbl_order_status AS os ON os.id=o.order_status_id WHERE o.is_deleted=0 AND o.status=1 AND o.id='.$orderId);
		$orderData=$orderData[0];
		$orderProData = $this->application_model->selectQuery('SELECT op.id, op.quantity, op.price, op.discount_percentage, op.discounted_price, p.name AS product_name, p.name_hindi AS product_name_hindi, p.photo, c.name AS company_name, c.name_hindi AS company_name_hindi, u.name AS unit_name FROM tbl_order_product AS op JOIN tbl_product_shop AS ps ON ps.id=op.product_id JOIN tbl_products AS p ON p.id=ps.product_id JOIN tbl_company AS c ON c.id=p.company_id JOIN tbl_units AS u ON u.id = p.unit_id WHERE op.is_deleted=0 AND op.order_id='.$orderData['id']);
		$orderData["products"] = $orderProData;
		$orderData["invoice"] = "A000".$orderData['id'];
		$this->load->view('viewBillModel',$orderData);
	}

}
/* End of orders.php file */
/* location: application/view/admin/orders.php */
/* Omit PHP closing tags to help avoid accidental output */